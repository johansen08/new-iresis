<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operational Dashboard</title>
    <style>
        :root {
            --bg-color: #0f172a;
            --card-bg: #1e293b;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --accent-green: #22c55e;
            --accent-red: #ef4444;
            --accent-yellow: #eab308;
            --border-color: #334155;
            --slide-duration: 8s;
        }

        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap');

        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-primary);
            overflow: hidden;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            background: rgba(15, 23, 42, 0.9);
            border-bottom: 2px solid var(--border-color);
            z-index: 100;
        }

        h1 {
            margin: 0;
            font-weight: 900;
            letter-spacing: -1px;
            font-size: 1.5rem;
            background: linear-gradient(90deg, #60a5fa, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .clock {
            font-family: 'Courier New', monospace;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            background: var(--card-bg);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        /* Main Content */
        main {
            flex: 1;
            position: relative;
            padding: 2rem;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1s ease-in-out;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            box-sizing: border-box;
            pointer-events: none; /* Prevent clicking hidden slides */
        }

        .slide.active {
            opacity: 1;
            pointer-events: all;
            z-index: 10;
        }

        /* Slide 1: Summary */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            width: 100%;
            max-width: 1400px;
        }

        .card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--border-color);
        }

        .card.green::before { background: var(--accent-green); }
        .card.red::before { background: var(--accent-red); }
        .card.yellow::before { background: var(--accent-yellow); }

        .card-title {
            font-size: 1.1rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .card-value {
            font-size: 4rem;
            font-weight: 800;
            line-height: 1;
        }

        .card-sub {
            margin-top: 1rem;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .status-badge {
            margin-top: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .bg-red { background: rgba(239, 68, 68, 0.2); color: var(--accent-red); }
        .bg-green { background: rgba(34, 197, 94, 0.2); color: var(--accent-green); }
        .bg-yellow { background: rgba(234, 179, 8, 0.2); color: var(--accent-yellow); }

        /* Slide 2-6: Deadline */
        .deadline-container {
            width: 100%;
            max-width: 1200px;
            text-align: center;
        }

        .deadline-date {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 2rem;
            color: var(--text-primary);
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 1rem;
            display: inline-block;
        }

        .courier-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }

        .courier-card {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid var(--border-color);
        }
        
        .courier-name {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .courier-count {
            font-size: 2rem;
            font-weight: 700;
        }

        /* Footer */
        footer {
            padding: 1rem;
            background: var(--card-bg);
            border-top: 1px solid var(--border-color);
            text-align: center;
            font-weight: 700;
            letter-spacing: 2px;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>

    <header>
        <h1>OPERATIONAL DASHBOARD</h1>
        <div class="clock" id="clock">00:00:00</div>
    </header>

    <main id="main-container">
        <!-- Slide 1: Summary -->
        <div class="slide active" id="slide-summary">
            <div class="summary-grid">
                <div class="card">
                    <div class="card-title">Resi Hari Ini</div>
                    <div class="card-value" id="val-resi-today">0</div>
                    <div class="card-sub">Global Scan</div>
                </div>
                <div class="card">
                    <div class="card-title">Target Resi</div>
                    <div class="card-value" id="val-target">0</div>
                    <div class="card-sub">Daily Target</div>
                </div>
                <div class="card" id="card-pending">
                    <div class="card-title">Pending Kemarin</div>
                    <div class="card-value" id="val-pending">0</div>
                    <div class="card-sub">Scan - HO (Yesterday)</div>
                </div>
                <!-- Performance Cards -->
                <div class="card" id="card-picker">
                    <div class="card-title">Picker Gap</div>
                    <div class="card-value" id="val-gap-picker">0</div>
                    <div class="status-badge" id="badge-picker">Checking...</div>
                </div>
                <div class="card" id="card-packer">
                    <div class="card-title">Packer Gap</div>
                    <div class="card-value" id="val-gap-packer">0</div>
                    <div class="status-badge" id="badge-packer">Checking...</div>
                </div>
                <div class="card" id="card-ho">
                    <div class="card-title">HO Gap</div>
                    <div class="card-value" id="val-gap-ho">0</div>
                    <div class="status-badge" id="badge-ho">Checking...</div>
                </div>
            </div>
        </div>

        <!-- Slides 2-6 will be injected here -->
        <div id="deadline-slides-container"></div>

    </main>

    <footer>
        KERJA KERAS • DISIPLIN • HASIL
    </footer>

    <script>
        // Configuration
        const SLIDE_DURATION = 8000;
        const REFRESH_INTERVAL = 60000;
        
        // State
        let currentSlideIndex = 0;
        let slides = [];
        let deadlineData = {};

        function updateClock() {
            const now = new Date();
            document.getElementById('clock').innerText = now.toLocaleTimeString('en-US', { hour12: false });
        }
        setInterval(updateClock, 1000);
        updateClock();

        function showSlide(index) {
            const allSlides = document.querySelectorAll('.slide');
            allSlides.forEach(s => s.classList.remove('active'));
            
            if (allSlides[index]) {
                allSlides[index].classList.add('active');
            }
        }

        function nextSlide() {
            const allSlides = document.querySelectorAll('.slide');
            if (allSlides.length === 0) return;
            currentSlideIndex = (currentSlideIndex + 1) % allSlides.length;
            showSlide(currentSlideIndex);
        }

        let slideInterval = setInterval(nextSlide, SLIDE_DURATION);

        // Fetch Data
        async function fetchData() {
            try {
                // Fetch Summary
                const resSummary = await fetch('<?php echo base_url("operational_dashboard/get_summary_data"); ?>');
                const summary = await resSummary.json();
                updateSummaryOnly(summary);

                // Fetch Deadlines
                const resDeadline = await fetch('<?php echo base_url("operational_dashboard/get_deadline_data"); ?>');
                const deadlines = await resDeadline.json();
                updateDeadlineSlides(deadlines);

            } catch (e) {
                console.error("Failed to fetch data", e);
            }
        }

        function updateSummaryOnly(data) {
            document.getElementById('val-resi-today').innerText = data.total_resi_today;
            document.getElementById('val-target').innerText = data.target_resi;
            document.getElementById('val-pending').innerText = data.pending_yesterday;
            
            // Color Logic for Pending (If > 0 Red, else Green)
            updateCardColor('card-pending', data.pending_yesterday > 0 ? 'red' : 'green');

            // Gap Update
            updateGapCard('val-gap-picker', 'badge-picker', 'card-picker', data.diff_picker);
            updateGapCard('val-gap-packer', 'badge-packer', 'card-packer', data.diff_packer);
            updateGapCard('val-gap-ho', 'badge-ho', 'card-ho', data.diff_ho);
        }

        function updateGapCard(valId, badgeId, cardId, diff) {
            const elVal = document.getElementById(valId);
            const elBadge = document.getElementById(badgeId);
            const elCard = document.getElementById(cardId);
            
            elVal.innerText = diff > 0 ? '+' + diff : diff;
            
            if (diff < 0) {
                // Minus -> Merah (Target not met)
                elCard.classList.remove('green', 'yellow');
                elCard.classList.add('red');
                elBadge.className = 'status-badge bg-red';
                elBadge.innerText = 'BEHIND';
            } else if (diff === 0) {
                // Exact -> Green or Yellow? "Mendekati deadline -> kuning"?
                // For simplified logic: Positive/Zero = Green
                elCard.classList.remove('red', 'yellow');
                elCard.classList.add('green');
                elBadge.className = 'status-badge bg-green';
                elBadge.innerText = 'ON TRACK';
            } else {
                // Positive -> Green
                elCard.classList.remove('red', 'yellow');
                elCard.classList.add('green');
                elBadge.className = 'status-badge bg-green';
                elBadge.innerText = 'AHEAD';
            }
        }

        function updateCardColor(id, color) {
            const el = document.getElementById(id);
            el.classList.remove('red', 'green', 'yellow');
            el.classList.add(color);
        }

        function updateDeadlineSlides(data) {
            const container = document.getElementById('deadline-slides-container');
            container.innerHTML = ''; // Clear existing deadline slides

            // data is Object { "YYYY-MM-DD": [ {courier, count}, ... ] }
            Object.keys(data).forEach((dateStr, index) => {
                const dayData = data[dateStr];
                
                const slideDiv = document.createElement('div');
                slideDiv.className = 'slide';
                // Note: Slide 1 is summary, so deadline slides start index 1, effectively.
                // Actually they are just appended siblings.
                
                const contentDiv = document.createElement('div');
                contentDiv.className = 'deadline-container';
                
                // Format Date
                const dateObj = new Date(dateStr);
                const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                const formattedDate = dateObj.toLocaleDateString('id-ID', options).toUpperCase();
                
                contentDiv.innerHTML = `<div class="deadline-date">BATAS KIRIM: ${formattedDate}</div>`;
                
                const gridDiv = document.createElement('div');
                gridDiv.className = 'courier-grid';
                
                // List of required couriers to ensure order/presence
                const requiredCouriers = ['SHOPEE', 'TOKOPEDIA', 'LAZADA', 'J&T', 'NINJA', 'SICEPAT'];
                
                requiredCouriers.forEach(cName => {
                    // Find matching data loosely
                    const found = dayData.find(d => d.courier.toUpperCase().includes(cName));
                    const count = found ? found.count : 0;
                    
                    // Color Logic based on "deadline".
                    // "Mendekati deadline -> kuning"
                    // Assume: Today = Red/Yellow? 
                    // Let's use simple logic: If count > 0 -> Check date.
                    // If Date == Today -> Red.
                    // If Date == Tomorrow -> Yellow.
                    // Else -> Green.
                    
                    const todayStr = new Date().toISOString().split('T')[0];
                    const tomorrow = new Date();
                    tomorrow.setDate(tomorrow.getDate() + 1);
                    const tomorrowStr = tomorrow.toISOString().split('T')[0];
                    
                    let colorClass = 'green';
                    if (count > 0) {
                         if (dateStr === todayStr) colorClass = 'red';
                         else if (dateStr === tomorrowStr) colorClass = 'yellow';
                    } else {
                        colorClass = 'green'; // Aman
                    }
                    
                    // HTML Construction
                    gridDiv.innerHTML += `
                        <div class="courier-card" style="border-left: 5px solid var(--accent-${colorClass})">
                            <div class="courier-name">${cName}</div>
                            <div class="courier-count" style="color: var(--accent-${colorClass})">${count}</div>
                        </div>
                    `;
                });
                
                contentDiv.appendChild(gridDiv);
                slideDiv.appendChild(contentDiv);
                container.appendChild(slideDiv);
            });
        }

        // Init
        fetchData();
        setInterval(fetchData, REFRESH_INTERVAL);

    </script>
</body>
</html>
