const express = require('express');
const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');
const puppeteer = require('puppeteer');

const app = express();
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

const PORT = 3000;
// Token dibaca dari secrets.json (tidak ikut di-commit; template: secrets.example.json)
// Harus sama dengan wa_api_token di application/config/secrets.php
const SECRET_TOKEN = require('./secrets.json').secret_token;

let clientReady = false;

// ── WhatsApp Client ──────────────────────────────────────────
const client = new Client({
    authStrategy: new LocalAuth(),
    puppeteer: {
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    }
});

client.on('qr', (qr) => {
    console.log('[WA Gateway] Scan QR Code berikut dengan WhatsApp di HP Anda:');
    qrcode.generate(qr, { small: true });
});

client.on('ready', () => {
    clientReady = true;
    console.log('[WA Gateway] WhatsApp Client siap! Server berjalan di http://localhost:' + PORT);
});

client.on('disconnected', (reason) => {
    clientReady = false;
    console.log('[WA Gateway] Terputus:', reason);
});

client.on('auth_failure', (msg) => {
    console.error('[WA Gateway] Autentikasi gagal:', msg);
});

// ── Middleware: Token Auth ────────────────────────────────────
function authMiddleware(req, res, next) {
    const authHeader = req.headers['authorization'] || '';
    const token = req.body.token || req.query.token || '';

    if (authHeader === `Bearer ${SECRET_TOKEN}` || token === SECRET_TOKEN) {
        return next();
    }
    return res.status(401).json({ error: 'Unauthorized', message: 'Token tidak valid.' });
}

// ── Routes ───────────────────────────────────────────────────

app.get('/status', (req, res) => {
    res.json({
        status: clientReady ? 'online' : 'offline',
        message: clientReady
            ? 'IRESIS WA Gateway berjalan.'
            : 'WhatsApp belum terhubung. Silakan scan QR code.'
    });
});

app.get('/groups', authMiddleware, async (req, res) => {
    if (!clientReady) {
        return res.status(503).json({ error: 'WhatsApp belum terhubung.' });
    }
    try {
        const chats = await client.getChats();
        const groups = chats
            .filter(c => c.isGroup)
            .map(c => ({ id: c.id._serialized, name: c.name }));
        res.json({ groups });
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
});

app.post('/send', authMiddleware, async (req, res) => {
    if (!clientReady) {
        return res.status(503).json({ error: 'WhatsApp belum terhubung.' });
    }

    const { to, message } = req.body;
    if (!to || !message) {
        return res.status(400).json({ error: 'Parameter "to" dan "message" wajib diisi.' });
    }

    try {
        let chatId = to;
        // Jika bukan format group (@g.us), konversi ke format WA
        if (!to.includes('@')) {
            chatId = to + '@c.us';
        }

        await client.sendMessage(chatId, message);
        console.log(`[WA Gateway] Pesan terkirim ke ${chatId}`);
        res.json({ success: true, message: 'Pesan berhasil dikirim.' });
    } catch (err) {
        console.error('[WA Gateway] Gagal kirim:', err.message);
        res.status(500).json({ error: 'Gagal mengirim pesan.', detail: err.message });
    }
});

app.post('/send-image', authMiddleware, async (req, res) => {
    if (!clientReady) {
        return res.status(503).json({ error: 'WhatsApp belum terhubung.' });
    }

    const { to, image, caption, filename } = req.body;
    if (!to || !image) {
        return res.status(400).json({ error: 'Parameter "to" dan "image" (base64) wajib diisi.' });
    }

    try {
        let chatId = to.includes('@') ? to : to + '@c.us';
        const media = new MessageMedia('image/png', image, filename || 'image.png');
        await client.sendMessage(chatId, media, { caption: caption || '' });
        console.log(`[WA Gateway] Gambar terkirim ke ${chatId}`);
        res.json({ success: true, message: 'Gambar berhasil dikirim.' });
    } catch (err) {
        console.error('[WA Gateway] Gagal kirim gambar:', err.message);
        res.status(500).json({ error: 'Gagal mengirim gambar.', detail: err.message });
    }
});

app.post('/send-screenshot', authMiddleware, async (req, res) => {
    if (!clientReady) {
        return res.status(503).json({ error: 'WhatsApp belum terhubung.' });
    }

    const { to, url, caption } = req.body;
    if (!to || !url) {
        return res.status(400).json({ error: 'Parameter "to" dan "url" wajib diisi.' });
    }

    let browser;
    try {
        browser = await puppeteer.launch({
            headless: 'new',
            args: ['--no-sandbox', '--disable-setuid-sandbox']
        });
        const page = await browser.newPage();
        await page.setViewport({ width: 900, height: 600 });
        await page.goto(url, { waitUntil: 'networkidle2', timeout: 60000 });
        await new Promise(r => setTimeout(r, 1000));

        const screenshotBuffer = await page.screenshot({ fullPage: true, type: 'png' });
        const base64 = screenshotBuffer.toString('base64');

        let chatId = to.includes('@') ? to : to + '@c.us';
        const media = new MessageMedia('image/png', base64, 'screenshot.png');
        await client.sendMessage(chatId, media, { caption: caption || '' });

        console.log(`[WA Gateway] Screenshot ${url} terkirim ke ${chatId}`);
        res.json({ success: true, message: 'Screenshot berhasil dikirim.' });
    } catch (err) {
        console.error('[WA Gateway] Gagal screenshot:', err.message);
        res.status(500).json({ error: 'Gagal mengirim screenshot.', detail: err.message });
    } finally {
        if (browser) await browser.close();
    }
});

// ── Start ────────────────────────────────────────────────────
client.initialize();

app.listen(PORT, () => {
    console.log(`[WA Gateway] HTTP Server berjalan di port ${PORT}`);
    console.log('[WA Gateway] Menunggu koneksi WhatsApp...');
});
