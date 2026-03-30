<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sensor_db";

// Check if ESP32 is sending data
$temperature = isset($_GET['temperature']) ? floatval($_GET['temperature']) : null;
$humidity = isset($_GET['humidity']) ? floatval($_GET['humidity']) : null;

// If data received from ESP32, insert into database
if ($temperature !== null && $humidity !== null) {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit();
    }
    
    $sql = "INSERT INTO sensor_readings (temperature, humidity, reading_time) VALUES (?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("dd", $temperature, $humidity);
    
    if ($stmt->execute()) {
        // FIXED: Proper string concatenation to avoid undefined variable warning
        echo "SUCCESS: Data saved - Temp: " . $temperature . "°C, Humidity: " . $humidity . "%";
    } else {
        http_response_code(500);
        echo "ERROR: " . $stmt->error;
    }
    
    $stmt->close();
    $conn->close();
    exit(); // Stop execution after saving data
}

// Dashboard continues to load HTML below...
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>ESP32-DHT11 | Climate Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
<style>
/* Reset and base styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}
body {
    font-family: 'Inter', 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #0a0f1f 0%, #0c1222 100%);
    color: #fff;
    min-height: 100vh;
    padding: 16px;
}
/* Dashboard container */
.dashboard {
    max-width: 1600px;
    margin: 0 auto;
    padding: 0 16px;
}
/* Header styles */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}
.logo h1 {
    font-size: 1.4rem;
    font-weight: 700;
    background: linear-gradient(135deg, #60a5fa, #a78bfa);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: -0.5px;
}
.logo p {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 4px;
}
/* Last reading */
.last-reading {
    font-size: 0.75rem;
    color: #6b7280;
    background: rgba(255, 255, 255, 0.03);
    padding: 6px 12px;
    border-radius: 8px;
}
/* Stats grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
/* Stat cards (smaller and modern) */
.stat-card {
    background: rgba(15, 20, 35, 0.6);
    backdrop-filter: blur(8px);
    border-radius: 16px;
    padding: 14px;
    border: 1px solid rgba(255, 255, 255, 0.05);
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
.stat-card:hover {
    border-color: rgba(59, 130, 246, 0.3);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}
.stat-title {
    font-size: 0.75rem;
    font-weight: 500;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin-bottom: 8px;
}
.stat-value {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 6px;
    line-height: 1.2;
}
.stat-unit {
    font-size: 0.75rem;
    font-weight: 400;
    color: #6b7280;
}
.stat-compare {
    font-size: 0.65rem;
    color: #10b981;
    display: flex;
    align-items: center;
    gap: 4px;
}
.stat-compare.down {
    color: #ef4444;
}
.main-content {
    display: flex;
    gap: 16px;
    margin-bottom: 24px;
    flex-wrap: wrap;
    align-items: stretch;
}
.chart-section {
    flex: 2;
    min-width: 350px;
}
.chart-card {
    background: rgba(15, 20, 35, 0.6);
    backdrop-filter: blur(8px);
    border-radius: 16px;
    padding: 14px;
    border: 1px solid rgba(255, 255, 255, 0.05);
    height: 100%;
    display: flex;
    flex-direction: column;
    transition: box-shadow 0.3s ease;
}
.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    flex-wrap: wrap;
    gap: 8px;
}
.chart-header h3 {
    font-size: 0.9rem;
    font-weight: 600;
    color: #e5e7eb;
    margin: 0;
}
.chart-badge {
    font-size: 0.65rem;
    padding: 3px 8px;
    background: rgba(59, 130, 246, 0.2);
    border-radius: 16px;
    color: #60a5fa;
}
.chart-wrapper {
    flex: 1;
    min-height: 280px;
    position: relative;
}
.logs-section {
    flex: 1;
    min-width: 280px;
}
.logs-card {
    background: rgba(15, 20, 35, 0.6);
    backdrop-filter: blur(8px);
    border-radius: 16px;
    padding: 14px;
    border: 1px solid rgba(255, 255, 255, 0.05);
    height: 100%;
    display: flex;
    flex-direction: column;
    transition: box-shadow 0.3s ease;
}
.logs-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    flex-wrap: wrap;
    gap: 8px;
}
.logs-header h3 {
    font-size: 0.9rem;
    font-weight: 600;
    color: #e5e7eb;
    margin: 0;
}
.refresh-btn {
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    color: white;
    border: none;
    padding: 4px 12px;
    border-radius: 8px;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.3s ease;
}
.refresh-btn:hover {
    transform: scale(1.03);
}
.logs-table-container {
    flex: 1;
    overflow-y: auto;
    overflow-x: auto;
    max-height: 280px;
    border-radius: 12px;
}
table {
    width: 100%;
    border-collapse: collapse;
}
th {
    text-align: left;
    padding: 8px;
    color: #9ca3af;
    font-weight: 500;
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    position: sticky;
    top: 0;
    background: rgba(15, 20, 35, 0.95);
    z-index: 10;
}
td {
    padding: 8px;
    color: #e5e7eb;
    font-size: 0.75rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}
tr:hover {
    background: rgba(255, 255, 255, 0.03);
}
#particle-canvas {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 0;
}
/* Responsive adjustments */
@media(max-width: 1024px){
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }
    .main-content {
        flex-direction: column;
    }
    .logs-section {
        min-width: 100%;
    }
    .logs-table-container {
        max-height: 250px;
    }
}
@media(max-width: 768px){
    body {
        padding: 12px;
    }
    .dashboard {
        padding: 0 8px;
    }
}
</style>
</head>
<body>
<canvas id="particle-canvas"></canvas>
<div class="dashboard">
<!-- Header -->
<div class="header">
<div class="logo">
<h1>🌡️ Sander Monitoring Dashboard</h1>
<p>Dashboard ESP32-DHT11</p>
</div>
<div class="last-reading" id="last-updated">
Last reading: ---
</div>
</div>
<!-- Stats Grid -->
<div class="stats-grid">
<div class="stat-card">
<div class="stat-title">🌡️ Temperature</div>
<div class="stat-value" id="temperature">--<span class="stat-unit">°C</span></div>
<div class="stat-compare" id="temp-trend">vs prev --</div>
</div>
<div class="stat-card">
<div class="stat-title">💧 Humidity</div>
<div class="stat-value" id="humidity">--<span class="stat-unit">%</span></div>
<div class="stat-compare" id="humidity-trend">vs prev --</div>
</div>
<div class="stat-card">
<div class="stat-title">🌡️ Heat Index</div>
<div class="stat-value" id="heat-index">--<span class="stat-unit">°C</span></div>
<div class="stat-compare">Perceived temp</div>
</div>
<div class="stat-card">
<div class="stat-title">📊 Total Readings</div>
<div class="stat-value" id="total-readings">0<span class="stat-unit"> pts</span></div>
<div class="stat-compare" id="session-time">Total records</div>
</div>
</div>
<!-- Main Content: Chart + Logs Side by Side -->
<div class="main-content">
<!-- Left: Temperature & Humidity Trend -->
<div class="chart-section">
<div class="chart-card">
<div class="chart-header">
<h3>📈 Temperature & Humidity Trend</h3>
<div class="chart-badge" id="live-badge">Live Data</div>
</div>
<div class="chart-wrapper">
<canvas id="climate-chart" style="max-height: 320px; width: 100%;"></canvas>
</div>
</div>
</div>
<!-- Right: Recent Sensor Logs (Scrollable) -->
<div class="logs-section">
<div class="logs-card">
<div class="logs-header">
<h3>📋 Recent Sensor Logs</h3>
<button class="refresh-btn" onclick="refreshAll()">⟳ Refresh</button>
</div>
<div class="logs-table-container">
<table id="logs-table">
<thead>
<tr>
<th>#</th>
<th>Temp (°C)</th>
<th>Humidity (%)</th>
<th>Heat Index (°C)</th>
<th>Date & Time</th>
</tr>
</thead>
<tbody id="logs-body">
<tr>
<td colspan="5" style="text-align: center;">Loading data...</td>
</tr>
</tbody>
</table>
</div>
</div>
</div>
</div>
</div>
<script>
let mainChart;
let previousTemp = null;
let previousHumidity = null;
let totalReadingsCount = 0;

// Accurate Heat Index Formula (NOAA Standard)
function calculateHeatIndex(temp, humidity) {
    let tempF = (temp * 9/5) + 32;
    let hiF;
    if (tempF < 80) {
        return temp;
    }
    hiF = -42.379 + 2.04901523 * tempF + 10.14333127 * humidity - 0.22475541 * tempF * humidity - 0.00683783 * tempF * tempF - 0.05481717 * humidity * humidity + 0.00122874 * tempF * tempF * humidity + 0.00085282 * tempF * humidity * humidity - 0.00000199 * tempF * tempF * humidity * humidity;
    if (humidity < 13 && tempF >= 80 && tempF <= 112) {
        let adjustment = ((13 - humidity) / 4) * Math.sqrt((17 - Math.abs(tempF - 95)) / 17);
        hiF = hiF - adjustment;
    } else if (humidity > 85 && tempF >= 80 && tempF <= 87) {
        let adjustment = ((humidity - 85) / 10) * ((87 - tempF) / 5);
        hiF = hiF + adjustment;
    }
    let hiC = (hiF - 32) * 5/9;
    if (hiC < temp) {
        return temp;
    }
    return Math.round(hiC * 10) / 10;
}

// Particle System
class Particle {
    constructor(canvas, ctx, isHot) {
        this.canvas = canvas;
        this.ctx = ctx;
        this.isHot = isHot;
        this.x = Math.random() * canvas.width;
        this.y = Math.random() * canvas.height;
        this.size = Math.random() * 3 + 1;
        if (isHot) {
            this.speedX = (Math.random() - 0.5) * 1.5;
            this.speedY = Math.random() * 2 + 1;
            this.color = `hsl(${Math.random() * 30 + 20}, 80%, 60%)`;
        } else {
            this.speedX = (Math.random() - 0.5) * 0.5;
            this.speedY = Math.random() * 1 + 0.5;
            this.color = `hsl(200, 80%, ${Math.random() * 30 + 50}%)`;
        }
    }
    update() {
        this.x += this.speedX;
        this.y += this.speedY;
        if (this.y > this.canvas.height) {
            this.y = 0;
            this.x = Math.random() * this.canvas.width;
        }
        if (this.x > this.canvas.width) this.x = 0;
        if (this.x < 0) this.x = this.canvas.width;
    }
    draw() {
        this.ctx.fillStyle = this.color;
        this.ctx.beginPath();
        this.ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
        this.ctx.fill();
    }
}

let particles = [];
let canvas, ctx;

function initParticles(isHot) {
    canvas = document.getElementById('particle-canvas');
    ctx = canvas.getContext('2d');
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    particles = [];
    for (let i = 0; i < 80; i++) {
        particles.push(new Particle(canvas, ctx, isHot));
    }
    animateParticles();
}

function animateParticles() {
    if (!ctx) return;
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    for (let particle of particles) {
        particle.update();
        particle.draw();
    }
    requestAnimationFrame(animateParticles);
}

function updateParticles(isHot) {
    if (particles.length > 0 && particles[0].isHot !== isHot) {
        particles = [];
        for (let i = 0; i < 80; i++) {
            particles.push(new Particle(canvas, ctx, isHot));
        }
    }
}

// Fetch total readings count from database
async function fetchTotalReadings() {
    try {
        const response = await fetch('get_total_readings.php');
        const data = await response.json();
        if (!data.error) {
            totalReadingsCount = data.total;
            document.getElementById('total-readings').innerHTML = totalReadingsCount.toLocaleString() + '<span class="stat-unit"> pts</span>';
        }
    } catch (error) {
        console.error('Error fetching total readings:', error);
    }
}

// Fetch and update data
async function fetchData() {
    try {
        const response = await fetch('get_data.php');
        const data = await response.json();
        if (!data.error) {
            const temp = parseFloat(data.temperature);
            const hum = parseFloat(data.humidity);
            const heatIndex = calculateHeatIndex(temp, hum);
            document.getElementById('temperature').innerHTML = temp.toFixed(1) + '<span class="stat-unit">°C</span>';
            document.getElementById('humidity').innerHTML = hum.toFixed(1) + '<span class="stat-unit">%</span>';
            document.getElementById('heat-index').innerHTML = heatIndex.toFixed(1) + '<span class="stat-unit">°C</span>';
            document.getElementById('last-updated').innerHTML = 'Last reading: ' + new Date(data.reading_time).toLocaleString();

            // Update trends with arrows
            if (previousTemp !== null) {
                const tempDiff = (temp - previousTemp).toFixed(1);
                const tempTrendElem = document.getElementById('temp-trend');
                if (tempDiff > 0) {
                    tempTrendElem.innerHTML = `&#8593; +${tempDiff}°C vs prev`; // Up arrow
                    tempTrendElem.className = 'stat-compare';
                } else if (tempDiff < 0) {
                    tempTrendElem.innerHTML = `&#8595; ${Math.abs(tempDiff)}°C vs prev`; // Down arrow
                    tempTrendElem.className = 'stat-compare down';
                } else {
                    tempTrendElem.innerHTML = `&#8594; 0°C vs prev`; // Right arrow
                    tempTrendElem.className = 'stat-compare';
                }

                const humDiff = (hum - previousHumidity).toFixed(1);
                const humTrendElem = document.getElementById('humidity-trend');
                if (humDiff > 0) {
                    humTrendElem.innerHTML = `&#8593; +${humDiff}% vs prev`; // Up arrow
                    humTrendElem.className = 'stat-compare';
                } else if (humDiff < 0) {
                    humTrendElem.innerHTML = `&#8595; ${Math.abs(humDiff)}% vs prev`; // Down arrow
                    humTrendElem.className = 'stat-compare down';
                } else {
                    humTrendElem.innerHTML = `&#8594; 0% vs prev`; // Right arrow
                    humTrendElem.className = 'stat-compare';
                }
            }
            previousTemp = temp;
            previousHumidity = hum;

            // Update particles based on temperature
            updateParticles(temp > 28);

            // Fetch history for charts and logs
            await fetchHistory();
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Fetch historical data for charts and logs
async function fetchHistory() {
    try {
        const response = await fetch('get_history.php');
        const data = await response.json();
        if (!data.error && data.length > 0) {
            const labels = data.slice().reverse().map(item => {
                const date = new Date(item.reading_time);
                return date.toLocaleTimeString();
            });
            const temps = data.slice().reverse().map(item => parseFloat(item.temperature));
            const hums = data.slice().reverse().map(item => parseFloat(item.humidity));
            document.getElementById('live-badge').innerHTML = `Live - ${data.length} readings`;

            if (mainChart) {
                mainChart.data.labels = labels;
                mainChart.data.datasets[0].data = temps;
                mainChart.data.datasets[1].data = hums;
                mainChart.update();
            } else {
                const ctx = document.getElementById('climate-chart').getContext('2d');
                mainChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Temperature (°C)',
                                data: temps,
                                borderColor: '#f97316',
                                backgroundColor: 'rgba(249, 115, 22, 0.05)',
                                borderWidth: 2,
                                tension: 0.4,
                                fill: true,
                                pointRadius: 0,
                                pointHoverRadius: 6
                            },
                            {
                                label: 'Humidity (%)',
                                data: hums,
                                borderColor: '#3b82f6',
                                backgroundColor: 'rgba(59, 130, 246, 0.05)',
                                borderWidth: 2,
                                tension: 0.4,
                                fill: true,
                                pointRadius: 0,
                                pointHoverRadius: 6
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                labels: { color: '#9ca3af', usePointStyle: true }
                            },
                            tooltip: { mode: 'index', intersect: false }
                        },
                        scales: {
                            y: { 
                                grid: { color: 'rgba(255, 255, 255, 0.05)' }, 
                                ticks: { color: '#9ca3af' }
                            },
                            x: { 
                                grid: { display: false }, 
                                ticks: { color: '#6b7280', maxRotation: 45, autoSkip: true, maxTicksLimit: 8 }
                            }
                        }
                    }
                });
            }
            updateLogsTable(data.slice(0, 20));
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Update logs table
function updateLogsTable(data) {
    const tbody = document.getElementById('logs-body');
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No data available</td></tr>';
        return;
    }
    let html = '';
    data.forEach((row, index) => {
        const temp = parseFloat(row.temperature);
        const hum = parseFloat(row.humidity);
        const heatIndex = calculateHeatIndex(temp, hum);
        const date = new Date(row.reading_time);
        const formattedDateTime = date.toLocaleString();
        html += `
            <tr>
                <td>${index + 1}</td>
                <td>${temp.toFixed(1)}°C</td>
                <td>${hum.toFixed(1)}%</td>
                <td>${heatIndex.toFixed(1)}°C</td>
                <td><small>${formattedDateTime}</small></td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

// Refresh all data
async function refreshAll() {
    await fetchTotalReadings();
    await fetchData();
}

// Handle window resize
window.addEventListener('resize', () => {
    if (canvas) {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    }
});

// Initialize
initParticles(false);
fetchTotalReadings();
fetchData();
setInterval(() => {
    fetchTotalReadings();
    fetchData();
}, 10000);
</script>
</body>
</html>