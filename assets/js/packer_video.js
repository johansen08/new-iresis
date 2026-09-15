/*
 * Perekam video packing.
 *
 * Halaman Scan Resi Packer dimuat lewat SPA (isi .page-content-wrap diganti tiap
 * scan), jadi elemen apa pun di dalam view ikut hilang setiap kali packer scan.
 * Karena itu modul ini hidup di level window dan menempelkan panel kameranya
 * langsung ke <body>: MediaRecorder-nya selamat melewati pergantian isi halaman,
 * dan rekaman bisa membentang dari scan pertama sampai scan kedua.
 *
 * Alurnya mengikuti aturan double-scan yang sudah ada:
 *   scan ke-1 resi X  -> mulai merekam
 *   scan ke-2 resi X  -> resi tersimpan, rekaman ditutup lalu diunggah
 *
 * Rekaman dikirim per potongan (lihat JEDA_CHUNK_MS) supaya muat di batas
 * upload PHP bawaan XAMPP dan supaya bagian yang sudah naik tetap aman kalau
 * tab ditutup di tengah packing.
 */
(function (window, document) {
    'use strict';

    if (window.PackerVideo) {
        return;
    }

    // --- Setelan yang paling mungkin perlu disesuaikan di lapangan ----------
    // Diminta 720p untuk menekan beban penyimpanan: label resi/SKU masih terbaca
    // dari jarak meja packing. Webcam 1080p akan memberi mode 720p-nya sendiri.
    // Ukuran berkas ditentukan BITRATE_VIDEO, bukan resolusi -- kalau resolusi
    // diubah, bitrate harus ikut disesuaikan.
    var LEBAR_IDEAL      = 1280;
    var TINGGI_IDEAL     = 720;
    var FPS_IDEAL        = 15;
    var BITRATE_VIDEO    = 1200000; // ~9 MB per menit rekaman (~540 MB/jam per PC)
    var JEDA_CHUNK_MS    = 2000;    // potongan dikirim tiap 2 detik
    // Pengaman untuk resi yang ditinggalkan, BUKAN batas kerja normal. Packing
    // resi berisi ratusan sampai seribu barang yang harus dicek satu per satu
    // memang wajar memakan satu jam, jadi batasnya diberi kelonggaran di atas itu
    // -- kalau disamakan dengan satu jam, rekamannya mati tepat saat packing
    // selesai dan scan penutupnya tidak ikut terekam.
    //
    // Kalau diturunkan sementara untuk pengujian, BATAS_LANJUT_REKAM_DETIK di
    // application/controllers/Packer.php harus ikut diturunkan dan tetap lebih
    // besar dari angka ini.
    var MAKS_DURASI_DTK  = 5400;    // 90 menit
    var MAKS_PERCOBAAN   = 4;       // percobaan kirim ulang per potongan
    var JEDA_ULANG_MS    = 1500;    // jeda dasar antar percobaan (naik tiap gagal)
    var MAKS_ANTRIAN     = 60;      // potongan menunggu (~2 menit video, ~18 MB)
    var KUNCI_KAMERA     = 'packer_video_device_id';
    // URI menu Scan Resi Packer (Webcam) di tabel menu; dititipkan di hash
    // #menu=... saat halaman dialihkan dari http ke https (dibaca plugins.js).
    var URI_MENU_WEBCAM  = 'packer/scan_packer_webcam';
    // URI menu Scan Resi Packer biasa (tanpa video): jalur darurat kalau kamera
    // di PC ini bermasalah. Tautannya hanya ditawarkan kalau menu itu memang
    // ada di sidebar pengguna, jadi hak akses per role tetap berlaku.
    var URI_MENU_BIASA   = 'packer/scan_packer';
    var JEDA_ALIH_HTTPS_MS = 2500; // beri waktu membaca pesan sebelum dialihkan
    // -----------------------------------------------------------------------

    var stream = null;
    // Dibaca halaman scan lewat PackerVideo.statusKamera() untuk memutuskan boleh
    // tidaknya sebuah resi baru dibuka: 'belum' | 'membuka' | 'siap' | 'gagal' |
    // 'tidak-didukung'. 'membuka' dipisahkan dari 'gagal' supaya scan yang datang
    // satu detik setelah halaman terbuka tidak dianggap kamera rusak.
    var statusKamera = 'belum';
    var recorder = null;
    var sesi = null;          // { kode, noresi, mulai, seq }
    var antrian = [];         // potongan yang menunggu giliran unggah
    var sedangUnggah = false;
    var sesiRusak = {};       // kode_sesi yang sudah kehilangan potongan

    /**
     * Resi yang rekamannya sudah mentok batas durasi.
     *
     * Tanpa catatan ini, batas durasinya bukan batas sungguhan: begitu rekaman
     * dihentikan, render ulang halaman berikutnya -- cukup satu salah scan yang
     * ditolak -- membuat sinkron() menyalakannya lagi sebagai bagian baru, dan
     * itu bisa berulang terus.
     *
     * Sengaja hilang kalau halaman dimuat ulang penuh, karena itu justru kasus
     * tab mati yang memang perlu melanjutkan rekaman.
     */
    var mentokBatas = {};
    var timerMaks = null;
    var timerDurasi = null;
    var uploadUrl = null;
    var panel = null;
    var el = {};

    /**
     * Satu-satunya jalan modul ini memberi kabar ke halaman.
     *
     * Panel kamera saja tidak cukup: letaknya di pojok, bisa dilipat, dan
     * tertutup popup (z-index 9997 lawan 9998). Kejadian yang paling perlu
     * diketahui packer -- rekaman terputus, durasi hampir habis -- justru yang
     * paling tidak terlihat di sana.
     *
     * Slotnya diisi ulang setiap sinkron(), jadi selalu menunjuk ke DOM halaman
     * yang sekarang dan tidak pernah menumpuk walau isi halaman diganti tiap
     * scan.
     */
    var pemberitahu = null;

    function kabari(info) {
        if (typeof pemberitahu !== 'function') return;

        // Kesalahan di halaman tidak boleh sampai menjatuhkan rekaman.
        try {
            pemberitahu(info);
        } catch (e) { /* diabaikan dengan sengaja */ }
    }

    function acak(panjang) {
        var huruf = 'abcdefghijklmnopqrstuvwxyz0123456789';
        var hasil = '';
        for (var i = 0; i < panjang; i++) {
            hasil += huruf.charAt(Math.floor(Math.random() * huruf.length));
        }
        return hasil;
    }

    function kodeSesiBaru() {
        var t = new Date();
        var pad = function (n) { return (n < 10 ? '0' : '') + n; };
        return '' + t.getFullYear() + pad(t.getMonth() + 1) + pad(t.getDate()) +
            pad(t.getHours()) + pad(t.getMinutes()) + pad(t.getSeconds()) + acak(6);
    }

    function formatDurasi(detik) {
        var m = Math.floor(detik / 60);
        var s = detik % 60;
        return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
    }

    function didukung() {
        return !!(navigator.mediaDevices &&
            navigator.mediaDevices.getUserMedia &&
            window.MediaRecorder);
    }

    /**
     * Alamat https untuk halaman yang sedang dibuka, atau null kalau tidak
     * relevan (sudah https, atau bukan http sama sekali).
     *
     * getUserMedia hanya tersedia di secure context, sedangkan klien LAN
     * terbiasa membuka aplikasi lewat http://IP. Apache di server ini sudah
     * melayani port 443 dengan sertifikat yang memuat IP LAN-nya, jadi cukup
     * pindah skema. Port eksplisit dibuang karena https memakai port bakunya.
     *
     * Navigasi SPA tidak mengubah URL, jadi menu yang sedang dibuka tidak ikut
     * terbawa -- URI-nya dititipkan di hash #menu=... yang dibaca plugins.js
     * saat halaman utama termuat ulang.
     */
    function alamatHttps() {
        if (window.location.protocol !== 'http:') {
            return null;
        }
        return 'https://' + window.location.hostname +
            window.location.pathname + window.location.search +
            '#menu=' + URI_MENU_WEBCAM;
    }

    function alihkanKeHttps() {
        var tujuan = alamatHttps();
        if (tujuan) {
            window.location.replace(tujuan);
        }
    }

    // ---------------------------------------------------------------- panel

    function bangunPanel() {
        if (panel) {
            return;
        }

        panel = document.createElement('div');
        panel.id = 'packer-video-panel';
        panel.innerHTML =
            '<div class="pvp-head">' +
                '<span class="pvp-dot"></span>' +
                '<span class="pvp-status">Kamera siap</span>' +
                '<button type="button" class="pvp-toggle" title="Sembunyikan / tampilkan">&minus;</button>' +
            '</div>' +
            '<div class="pvp-body">' +
                '<video class="pvp-video" muted autoplay playsinline></video>' +
                '<div class="pvp-info">&nbsp;</div>' +
                '<div class="pvp-resolusi"></div>' +
                '<select class="pvp-kamera"></select>' +
            '</div>';

        document.body.appendChild(panel);

        el.status  = panel.querySelector('.pvp-status');
        el.dot     = panel.querySelector('.pvp-dot');
        el.video   = panel.querySelector('.pvp-video');
        el.info    = panel.querySelector('.pvp-info');
        el.resolusi = panel.querySelector('.pvp-resolusi');
        el.kamera  = panel.querySelector('.pvp-kamera');
        el.body    = panel.querySelector('.pvp-body');
        el.toggle  = panel.querySelector('.pvp-toggle');

        el.toggle.addEventListener('click', function () {
            var tersembunyi = el.body.style.display === 'none';
            el.body.style.display = tersembunyi ? '' : 'none';
            el.toggle.innerHTML = tersembunyi ? '&minus;' : '+';
        });

        el.kamera.addEventListener('change', function () {
            try {
                window.localStorage.setItem(KUNCI_KAMERA, el.kamera.value);
            } catch (e) { /* localStorage bisa diblokir; abaikan saja */ }

            // Ganti kamera hanya saat tidak merekam supaya rekaman berjalan
            // tidak terpotong di tengah.
            if (!sedangMerekam()) {
                tutupKamera();
                bukaKamera();
            }
        });
    }

    function setStatus(teks, warna) {
        if (!el.status) return;
        el.status.textContent = teks;
        el.dot.style.background = warna || '#bbb';
    }

    function setInfo(teks) {
        if (el.info) el.info.innerHTML = teks || '&nbsp;';
    }

    /**
     * Resolusi dan fps yang BENAR-BENAR diberikan kamera, bukan yang diminta.
     * LEBAR_IDEAL/TINGGI_IDEAL hanya permintaan; webcam yang tidak sanggup
     * diam-diam memberi resolusi terdekat, dan itu baru ketahuan di sini.
     * Diberi warna peringatan kalau di bawah yang diminta supaya PC yang
     * webcam-nya kurang bisa langsung dikenali dari panel.
     */
    function tampilkanResolusi() {
        if (!el.resolusi) return;
        if (!stream) {
            el.resolusi.textContent = '';
            return;
        }

        var track = stream.getVideoTracks()[0];
        var set   = (track && track.getSettings) ? track.getSettings() : {};
        // Beberapa browser lama tidak mengisi width/height di getSettings();
        // ukuran frame yang sudah dimuat elemen <video> dipakai sebagai cadangan.
        var lebar  = set.width  || (el.video && el.video.videoWidth)  || 0;
        var tinggi = set.height || (el.video && el.video.videoHeight) || 0;
        var fps    = set.frameRate ? Math.round(set.frameRate) : 0;

        if (!lebar || !tinggi) {
            el.resolusi.textContent = 'Resolusi: membaca...';
            el.resolusi.style.color = '';
            return;
        }

        var teks = 'Resolusi: ' + lebar + '×' + tinggi + (fps ? ' @ ' + fps + ' fps' : '');
        var kurang = lebar < LEBAR_IDEAL || tinggi < TINGGI_IDEAL;
        el.resolusi.textContent = kurang
            ? teks + ' (di bawah ' + LEBAR_IDEAL + '×' + TINGGI_IDEAL + ' yang diminta)'
            : teks;
        el.resolusi.style.color = kurang ? '#f0ad4e' : '';
    }

    function tampilkanPanel(tampil) {
        if (panel) panel.style.display = tampil ? '' : 'none';
    }

    // ------------------------------------------------------- jalur darurat

    function tautanSidebarMenuBiasa() {
        var semua = document.querySelectorAll('a.link');
        for (var i = 0; i < semua.length; i++) {
            if (semua[i].getAttribute('href') === URI_MENU_BIASA) return semua[i];
        }
        return null;
    }

    /**
     * Potongan HTML "lanjutkan di menu biasa", atau '' kalau pengguna ini tidak
     * punya menu itu. Dipasang di pesan kamera gagal/tidak didukung supaya
     * packer tidak berhenti bekerja hanya karena kamera PC-nya bermasalah --
     * packing lewat menu biasa tersimpan ke tabel yang sama, cuma tanpa video.
     */
    function htmlJalurDarurat() {
        if (!tautanSidebarMenuBiasa()) return '';

        return '<br><a href="#" class="pvp-menu-biasa" style="font-weight:700">' +
            '&raquo; Lanjutkan packing di menu Scan Resi Packer (tanpa video)</a>';
    }

    function pasangKlikJalurDarurat() {
        var tautan = el.info ? el.info.querySelector('.pvp-menu-biasa') : null;
        if (!tautan) return;

        tautan.addEventListener('click', function (e) {
            e.preventDefault();
            var sidebar = tautanSidebarMenuBiasa();
            // Klik tautan sidebar aslinya supaya jalur navigasi SPA (plugins.js)
            // dan breadcrumb-nya sama persis dengan klik manual.
            if (sidebar) sidebar.click();
        });
    }

    // --------------------------------------------------------------- kamera

    function isiDaftarKamera() {
        if (!navigator.mediaDevices.enumerateDevices) return;

        navigator.mediaDevices.enumerateDevices().then(function (devices) {
            var tersimpan = '';
            try { tersimpan = window.localStorage.getItem(KUNCI_KAMERA) || ''; } catch (e) {}

            var kamera = devices.filter(function (d) { return d.kind === 'videoinput'; });
            el.kamera.innerHTML = '';

            kamera.forEach(function (d, i) {
                var opt = document.createElement('option');
                opt.value = d.deviceId;
                opt.textContent = d.label || ('Kamera ' + (i + 1));
                if (d.deviceId === tersimpan) opt.selected = true;
                el.kamera.appendChild(opt);
            });

            el.kamera.style.display = kamera.length > 1 ? '' : 'none';
        }).catch(function () { /* daftar kamera bukan hal kritis */ });
    }

    function bukaKamera() {
        if (stream) {
            statusKamera = 'siap';
            return Promise.resolve(stream);
        }

        statusKamera = 'membuka';

        var video = {
            width:     { ideal: LEBAR_IDEAL },
            height:    { ideal: TINGGI_IDEAL },
            frameRate: { ideal: FPS_IDEAL }
        };

        var pilihan = '';
        try { pilihan = window.localStorage.getItem(KUNCI_KAMERA) || ''; } catch (e) {}
        if (pilihan) {
            video.deviceId = { ideal: pilihan };
        }

        return navigator.mediaDevices.getUserMedia({ video: video, audio: false })
            .then(function (s) {
                stream = s;
                statusKamera = 'siap';
                el.video.srcObject = s;
                setStatus('Kamera siap', '#5cb85c');
                tampilkanResolusi();
                // Dibaca ulang setelah frame pertama masuk: getSettings() bisa
                // masih kosong tepat setelah getUserMedia selesai.
                el.video.addEventListener('loadedmetadata', tampilkanResolusi, { once: true });
                isiDaftarKamera();
                return s;
            })
            .catch(function (err) {
                statusKamera = 'gagal';
                setStatus('Kamera gagal: ' + (err.name || err.message), '#d9534f');
                setInfo('<span style="color:#d9534f">Scan resi baru DITOLAK sampai kamera hidup. ' +
                    'Izinkan akses kamera di browser, lalu buka ulang menu ini.</span>' +
                    htmlJalurDarurat());
                pasangKlikJalurDarurat();
                throw err;
            });
    }

    function tutupKamera() {
        if (!stream) return;
        stream.getTracks().forEach(function (t) { t.stop(); });
        stream = null;
        statusKamera = 'belum';
        if (el.video) el.video.srcObject = null;
        tampilkanResolusi();
    }

    // -------------------------------------------------------------- unggah

    /**
     * Sesi dioper sebagai argumen, bukan dibaca dari variabel modul: potongan
     * terakhir baru sampai ke sini setelah recorder.stop(), saat sesi yang aktif
     * bisa jadi sudah diganti rekaman resi berikutnya (atau sudah dikosongkan).
     */
    function antreChunk(sesiRef, blob, terakhir) {
        if (!sesiRef) return;

        // Antrean yang terus menumpuk berarti unggahan tidak mengejar laju
        // rekaman -- server lambat, atau halaman sempat membeku lama sehingga
        // event potongan menggunung. Dibiarkan, potongan menumpuk di memori
        // tanpa batas. Lebih baik rekamannya dihentikan di sini: berkas yang
        // sudah naik tetap utuh dan bisa diputar.
        if (antrian.length >= MAKS_ANTRIAN) {
            tandaiRusak(sesiRef.kode, sesiRef.noresi, sesiRef.seq,
                'antrean unggah menumpuk');

            // Ditunda satu putaran supaya stop() tidak dipanggil dari dalam
            // penanganan event ondataavailable milik recorder yang sama.
            window.setTimeout(hentikan, 0);
            return;
        }

        antrian.push({
            blob: blob,
            terakhir: !!terakhir,
            kode: sesiRef.kode,
            noresi: sesiRef.noresi,
            seq: sesiRef.seq++,
            durasi: Math.round((Date.now() - sesiRef.mulai) / 1000)
        });

        prosesAntrian();
    }

    /**
     * Potongan diunggah satu per satu dan berurutan: server menyambung berkas
     * dengan append, jadi urutan tidak boleh tertukar.
     */
    function prosesAntrian() {
        if (sedangUnggah || !antrian.length || !uploadUrl) return;

        sedangUnggah = true;
        var item = antrian.shift();

        // Begitu satu potongan hilang, sisa potongan sesi itu tidak boleh ikut
        // dikirim. Server menyambung berkas apa adanya, jadi menempelkan data
        // setelah lubang justru merusak bagian awal yang masih utuh. Lebih baik
        // videonya berhenti lebih awal tapi tetap bisa diputar, dan barisnya
        // tetap berstatus MEREKAM supaya CS melihat tanda "rekaman terputus".
        if (sesiRusak[item.kode]) {
            lanjutAntrian();
            return;
        }

        kirimPotongan(item, 1);
    }

    function lanjutAntrian() {
        sedangUnggah = false;
        prosesAntrian();
    }

    function tandaiRusak(kode, noresi, seq, alasan) {
        // Satu sesi bisa ditandai rusak berkali-kali: saat antrean meluap,
        // setiap potongan yang datang sesudahnya lewat sini lagi. Menulis ulang
        // teks panel tidak apa-apa, tapi halaman hanya boleh dikabari sekali --
        // kalau tidak, packer dihujani popup dan suara yang sama puluhan kali
        // untuk satu kejadian.
        var pertamaKali = !sesiRusak[kode];

        sesiRusak[kode] = true;
        setStatus('Video terpotong', '#d9534f');
        setInfo('<span style="color:#d9534f">Potongan ke-' + seq +
            ' gagal terkirim (' + alasan + '). Rekaman resi ' + noresi +
            ' tersimpan sebagian.</span>');

        if (pertamaKali) {
            kabari({ jenis: 'rusak', noresi: noresi, seq: seq, alasan: alasan });
        }
    }

    /**
     * Kirim satu potongan, dengan percobaan ulang untuk gangguan sesaat
     * (jaringan putus, server sibuk). Penolakan 4xx tidak diulang karena itu
     * masalah permintaan, bukan gangguan sementara.
     */
    function kirimPotongan(item, percobaan) {
        var fd = new FormData();
        fd.append('kode_sesi', item.kode);
        fd.append('noresi', item.noresi);
        fd.append('seq', item.seq);
        fd.append('terakhir', item.terakhir ? 1 : 0);
        fd.append('durasi', item.durasi);
        fd.append('chunk', item.blob, item.kode + '-' + item.seq + '.webm');

        var ulang = function (alasan) {
            if (percobaan >= MAKS_PERCOBAAN) {
                tandaiRusak(item.kode, item.noresi, item.seq, alasan);
                lanjutAntrian();
                return;
            }

            setInfo('Koneksi bermasalah, mengirim ulang potongan ke-' + item.seq +
                ' (' + percobaan + '/' + (MAKS_PERCOBAAN - 1) + ')...');

            window.setTimeout(function () {
                kirimPotongan(item, percobaan + 1);
            }, JEDA_ULANG_MS * percobaan);
        };

        fetch(uploadUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                var kode = res && res.code ? res.code : 0;

                if (kode >= 500 || !kode) {
                    ulang('server: ' + ((res && res.message) || 'tidak ada jawaban'));
                    return;
                }

                if (kode >= 400) {
                    tandaiRusak(item.kode, item.noresi, item.seq,
                        (res && res.message) || 'ditolak server');
                    lanjutAntrian();
                    return;
                }

                if (item.terakhir) {
                    setStatus('Video tersimpan', '#5cb85c');
                    setInfo('Resi ' + item.noresi + ' &middot; ' + formatDurasi(item.durasi));

                    // Baru di sinilah videonya benar-benar aman di server. Scan
                    // kedua sudah dijawab "tersimpan" beberapa detik sebelumnya,
                    // padahal potongan penutupnya masih di jalan.
                    kabari({ jenis: 'tersimpan', noresi: item.noresi, durasi: item.durasi });
                }

                lanjutAntrian();
            })
            .catch(function (e) {
                // Termasuk jaringan putus dan jawaban yang bukan JSON.
                ulang(e && e.message ? e.message : 'koneksi gagal');
            });
    }

    // -------------------------------------------------------------- rekaman

    function sedangMerekam() {
        return !!(recorder && recorder.state === 'recording');
    }

    function pilihMimeType() {
        var kandidat = [
            'video/webm;codecs=vp8',
            'video/webm;codecs=vp9',
            'video/webm'
        ];

        for (var i = 0; i < kandidat.length; i++) {
            if (MediaRecorder.isTypeSupported(kandidat[i])) return kandidat[i];
        }
        return '';
    }

    function jalankanRekaman(noresi) {
        var opsi = { videoBitsPerSecond: BITRATE_VIDEO };
        var mime = pilihMimeType();
        if (mime) opsi.mimeType = mime;

        var rec;
        try {
            rec = new MediaRecorder(stream, opsi);
        } catch (e) {
            setStatus('Rekam gagal: ' + e.message, '#d9534f');
            return;
        }

        var sesiIni = { kode: kodeSesiBaru(), noresi: noresi, mulai: Date.now(), seq: 0 };
        recorder = rec;
        sesi = sesiIni;

        rec.ondataavailable = function (e) {
            if (!e.data || !e.data.size) return;
            // stop() memindahkan state ke 'inactive' sebelum melepas potongan
            // sisanya, jadi state di sini yang menandai potongan penutup sesi.
            antreChunk(sesiIni, e.data, rec.state === 'inactive');
        };

        // Pembersihan ditunda sampai onstop, bukan langsung di hentikan():
        // potongan terakhir baru dilepas setelah stop() selesai diproses.
        // Penjaga identitas dipakai supaya recorder lama tidak menghapus
        // rekaman baru yang sudah keburu jalan (mis. packer ganti resi).
        rec.onstop = function () {
            if (recorder === rec) recorder = null;
            if (sesi === sesiIni) sesi = null;
        };

        rec.start(JEDA_CHUNK_MS);

        setStatus('MEREKAM', '#d9534f');
        setInfo('Resi ' + noresi);

        // Tidak ada peringatan mendekati batas: batasnya sekarang jauh di atas
        // durasi packing yang wajar, jadi peringatan itu hanya akan muncul pada
        // resi yang memang sudah ditinggalkan -- dan popup di tengah menghitung
        // ratusan barang justru memotong konsentrasi packer.
        timerDurasi = window.setInterval(function () {
            if (!sesi) return;
            var detik = Math.round((Date.now() - sesi.mulai) / 1000);
            setInfo('Resi ' + sesi.noresi + ' &middot; ' + formatDurasi(detik));
        }, 1000);

        // Pengaman kalau scan kedua tidak pernah datang (packer pindah kerjaan,
        // resi batal, dsb.) supaya kamera tidak merekam tanpa batas.
        timerMaks = window.setTimeout(function () {
            var noresiIni = sesi ? sesi.noresi : noresi;
            var durasiIni = sesi ? Math.round((Date.now() - sesi.mulai) / 1000) : MAKS_DURASI_DTK;

            mentokBatas[noresiIni] = true;
            hentikan();

            kabari({
                jenis:  'batas-habis',
                noresi: noresiIni,
                durasi: durasiIni,
                batas:  MAKS_DURASI_DTK
            });
        }, MAKS_DURASI_DTK * 1000);
    }

    function bersihkanTimer() {
        if (timerMaks) { window.clearTimeout(timerMaks); timerMaks = null; }
        if (timerDurasi) { window.clearInterval(timerDurasi); timerDurasi = null; }
    }

    function mulai(noresi) {
        if (!didukung() || !noresi) return;

        // Resi yang sudah mentok batas tidak direkam lagi di halaman ini.
        if (mentokBatas[noresi]) return;

        bangunPanel();
        tampilkanPanel(true);

        if (sedangMerekam()) {
            if (sesi && sesi.noresi === noresi) {
                return; // sudah merekam resi yang sama, biarkan jalan
            }
            hentikan(); // resi berganti: tutup rekaman lama dulu
        }

        bukaKamera().then(function () {
            jalankanRekaman(noresi);
        }).catch(function () { /* pesan error sudah ditampilkan di panel */ });
    }

    function hentikan() {
        bersihkanTimer();

        if (!sedangMerekam()) {
            return;
        }

        setStatus('Menyimpan video...', '#f0ad4e');

        // Sisa buffer keluar sendiri sebagai potongan penutup lewat
        // ondataavailable; variabel recorder/sesi dibereskan di onstop.
        try { recorder.stop(); } catch (e) {}
    }

    // Halaman Scan Resi Packer (Webcam) ditandai #scan-packer-webcam-root --
    // sengaja beda dari halaman scan packer biasa, supaya pindah ke menu itu
    // ikut mematikan kamera. Kalau penanda itu
    // hilang, packer sudah pindah menu: rekaman ditutup (bagian yang sudah
    // terekam tetap tersimpan) dan kamera dilepas supaya lampunya mati.
    //
    // Penanda juga hilang sesaat setiap kali SPA mengganti isi halaman -- saat
    // spinner "loading" tampil. Karena itu baru dianggap benar-benar pindah
    // setelah beberapa kali pemeriksaan berturut-turut, supaya rekaman yang
    // sedang jalan tidak ikut terpotong oleh jeda muat halaman.
    function pantauHalaman() {
        var hilang = 0;

        window.setInterval(function () {
            if (document.getElementById('scan-packer-webcam-root')) {
                hilang = 0;
                return;
            }

            if (!panel || panel.style.display === 'none') return;

            if (++hilang < 3) return;

            hentikan();
            tutupKamera();
            tampilkanPanel(false);
        }, 2000);
    }

    window.PackerVideo = {
        /**
         * Dipanggil view scan packer setiap kali isinya dimuat ulang.
         * status = nilai scan_feedback.status dari server.
         */
        sinkron: function (opsi) {
            uploadUrl = opsi.uploadUrl || uploadUrl;

            if (typeof opsi.pemberitahu === 'function') {
                pemberitahu = opsi.pemberitahu;
            }

            if (!didukung()) {
                statusKamera = 'tidak-didukung';
                bangunPanel();
                tampilkanPanel(true);
                setStatus('Rekam video tidak tersedia', '#d9534f');

                var tujuanHttps = window.isSecureContext ? null : alamatHttps();
                if (tujuanHttps) {
                    // Dibuka lewat http://IP. Alihkan otomatis ke https; tautan
                    // disediakan kalau pengguna tidak mau menunggu jedanya.
                    setInfo('Perekaman butuh HTTPS. Mengalihkan ke alamat https&hellip; ' +
                        '<a href="' + tujuanHttps + '" class="pvp-alih-https">Buka sekarang</a>');
                    var tautan = el.info.querySelector('.pvp-alih-https');
                    if (tautan) {
                        tautan.addEventListener('click', function (e) {
                            e.preventDefault();
                            alihkanKeHttps();
                        });
                    }
                    window.setTimeout(alihkanKeHttps, JEDA_ALIH_HTTPS_MS);
                    return;
                }

                setInfo((window.isSecureContext
                    ? 'Browser ini tidak mendukung perekaman kamera.'
                    : 'Perekaman butuh HTTPS. Buka aplikasi lewat https://' +
                      window.location.host + ' atau localhost.') +
                    htmlJalurDarurat());
                pasangKlikJalurDarurat();
                return;
            }

            bangunPanel();
            tampilkanPanel(true);

            // Scan kedua mengakhiri rekaman apa pun hasil simpannya. Server
            // sudah mengosongkan hitungan double-scan begitu percobaan kedua
            // diproses -- termasuk saat ditolak (resi sudah di-packing, pesanan
            // batal, dsb.) -- jadi rekaman harus ikut ditutup supaya tidak
            // menyatu dengan percobaan berikutnya dan menggantung sampai batas
            // durasi.
            if (opsi.status === 'auto_save_success' || opsi.status === 'auto_save_failed') {
                hentikan();
                return;
            }

            if (opsi.noresi) {
                mulai(opsi.noresi);
                return;
            }

            // Belum ada resi aktif: nyalakan kamera saja supaya scan pertama
            // langsung merekam tanpa menunggu kamera hidup.
            bukaKamera().catch(function () {});
        },

        selesai: function () { hentikan(); },

        /**
         * Buang rekaman yang sedang berjalan, bukan sekadar menutupnya.
         *
         * Dipakai tombol Batal Scan: packing-nya tidak jadi dikerjakan sekarang,
         * jadi rekaman setengah jalan itu tidak menunjukkan apa pun dan tidak
         * boleh ikut terhitung sebagai video resi tersebut.
         *
         * Sesi ditandai rusak lebih dulu supaya potongan yang masih mengantre --
         * termasuk potongan penutup dari stop() -- tidak jadi dikirim. Potongan
         * yang sudah telanjur naik dibereskan server lewat urlBatal.
         */
        batalkan: function (urlBatal) {
            if (!sesi) {
                hentikan();
                return;
            }

            var kode = sesi.kode;
            var noresi = sesi.noresi;

            sesiRusak[kode] = true;
            hentikan();

            setStatus('Rekaman dibatalkan', '#777');
            setInfo('Resi ' + noresi + ' &middot; rekaman dibuang');

            if (!urlBatal) return;

            var fd = new FormData();
            fd.append('kode_sesi', kode);
            fd.append('noresi', noresi);

            fetch(urlBatal, { method: 'POST', body: fd, credentials: 'same-origin' })
                .catch(function () {
                    setInfo('<span style="color:#d9534f">Gagal menghapus rekaman di server.</span>');
                });
        },

        aktif: function () { return sedangMerekam(); },

        /**
         * Lama rekaman berjalan dalam detik, atau null kalau tidak ada rekaman.
         * Dibaca bilah "menunggu scan kedua" untuk menampilkan penanda MEREKAM.
         */
        durasi: function () {
            return sesi ? Math.round((Date.now() - sesi.mulai) / 1000) : null;
        },

        /**
         * Kesiapan kamera, dikirim halaman bersama setiap scan. Server yang
         * memutuskan menolak atau tidak -- di sini cuma dilaporkan apa adanya.
         */
        statusKamera: function () { return statusKamera; }
    };

    pantauHalaman();

    // Tutup rapi saat tab ditinggalkan; potongan yang sudah terkirim tetap aman
    // walau potongan terakhir belum sempat naik.
    window.addEventListener('beforeunload', function () {
        if (sedangMerekam()) hentikan();
    });
})(window, document);
