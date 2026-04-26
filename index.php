<?php
$conn = new mysqli("localhost", "root", "", "gov_finance");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// FETCH DATA
$result = $conn->query("SELECT * FROM finances ORDER BY year, month");

$months = [];
$revenue = [];
$expenditure = [];

while ($row = $result->fetch_assoc()) {
    $months[] = $row['year'] . "-" . str_pad($row['month'], 2, "0", STR_PAD_LEFT);
    $revenue[] = floatval($row['revenue']);
    $expenditure[] = floatval($row['expenditure']);
}

// Create ultra-smooth interpolated data
$smoothFactor = 30;
$interpolatedRevenue = [];
$interpolatedExpenditure = [];

if (count($revenue) > 1) {
    for ($i = 0; $i < count($revenue) - 1; $i++) {
        for ($j = 0; $j <= $smoothFactor; $j++) {
            $t = $j / $smoothFactor;
            $t2 = $t * $t;
            $t3 = $t2 * $t;
            
            $h00 = 2 * $t3 - 3 * $t2 + 1;
            $h10 = $t3 - 2 * $t2 + $t;
            $h01 = -2 * $t3 + 3 * $t2;
            $h11 = $t3 - $t2;
            
            $revenueValue = $h00 * $revenue[$i] + $h10 * 0 + $h01 * $revenue[$i + 1] + $h11 * 0;
            $expenditureValue = $h00 * $expenditure[$i] + $h10 * 0 + $h01 * $expenditure[$i + 1] + $h11 * 0;
            
            $interpolatedRevenue[] = $revenueValue;
            $interpolatedExpenditure[] = $expenditureValue;
        }
    }
    $interpolatedRevenue[] = $revenue[count($revenue) - 1];
    $interpolatedExpenditure[] = $expenditure[count($expenditure) - 1];
} else {
    $interpolatedRevenue = $revenue;
    $interpolatedExpenditure = $expenditure;
}

$totalSmoothPoints = count($interpolatedRevenue);
$maxValue = max(array_merge($revenue, $expenditure)) * 1.1;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Gov Finance System - Compact Smooth Chart</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body { 
            font-family: 'Segoe UI', 'Poppins', Arial, sans-serif; 
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            min-height: 100vh;
        }

        .nav {
            background: linear-gradient(135deg, #0cb413 0%, #0a8a0f 100%);
            padding: 12px 25px;
            color: white;
            display: flex;
            gap: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            padding: 6px 16px;
            border-radius: 20px;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .nav a:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }

        .section { 
            display: none; 
            padding: 20px 30px; 
            animation: fadeInUp 0.6s ease-out;
        }

        .active { display: block; }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        table { 
            border-collapse: collapse; 
            width: 100%; 
            background: white; 
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            font-size: 13px;
        }

        th, td { 
            padding: 10px; 
            border: 1px solid #e0e0e0; 
            text-align: center; 
        }

        th { 
            background: linear-gradient(135deg, #6077eb 0%, #4a5fc4 100%); 
            color: white; 
            font-size: 13px;
        }

        input { 
            padding: 6px 10px; 
            width: 100px; 
            border: 2px solid #e0e0e0; 
            border-radius: 6px;
            transition: all 0.3s ease;
            font-size: 13px;
        }

        input:focus {
            outline: none;
            border-color: #0cb413;
        }

        button {
            padding: 6px 16px;
            background: linear-gradient(135deg, #0cb413 0%, #0a8a0f 100%);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 12px;
        }

        button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(12,180,19,0.3);
        }

        .chart-container {
            position: relative;
            max-width: 900px;
            margin: 0 auto;
            background: rgba(255,255,255,0.98);
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }

        .chart-container:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.25);
        }

        .cinema-badge {
            position: absolute;
            top: 15px;
            left: 20px;
            background: linear-gradient(135deg, #ff3366, #ff6b3d);
            color: white;
            padding: 5px 14px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: bold;
            z-index: 20;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 10px rgba(255,51,102,0.3);
        }

        .smooth-badge {
            position: absolute;
            top: 15px;
            right: 20px;
            background: rgba(0,0,0,0.75);
            color: #ffd700;
            padding: 5px 14px;
            border-radius: 30px;
            font-size: 10px;
            font-weight: bold;
            z-index: 20;
            font-family: monospace;
        }

        .progress-container {
            margin-top: 18px;
            text-align: center;
        }

        .progress-bar {
            width: 100%;
            height: 3px;
            background: rgba(0,0,0,0.1);
            border-radius: 3px;
            overflow: hidden;
        }

        .progress-fill {
            width: 0%;
            height: 100%;
            background: linear-gradient(90deg, #0cb413, #ffd700, #ff3366);
            transition: width 0.008s linear;
        }

        .status-text {
            text-align: center;
            margin-top: 12px;
            font-size: 11px;
            font-weight: 600;
            color: #555;
            background: linear-gradient(135deg, #667eea15, #764ba215);
            padding: 6px 15px;
            border-radius: 30px;
            display: inline-block;
            width: auto;
            margin-left: auto;
            margin-right: auto;
        }

        .stats {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 18px;
            border-radius: 14px;
            text-align: center;
            flex: 1;
            min-width: 130px;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-card h3 {
            margin: 0 0 5px 0;
            font-size: 11px;
            opacity: 0.9;
        }

        .stat-card p {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }

        canvas {
            cursor: pointer;
            width: 100% !important;
            height: 280px !important;
            max-height: 280px;
        }

        .film-dot {
            width: 6px;
            height: 6px;
            background: #ffd700;
            border-radius: 50%;
            animation: pulse 1s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        .cinema-note {
            text-align: center;
            margin-top: 18px;
            padding: 10px;
            background: linear-gradient(135deg, rgba(102,126,234,0.1), rgba(118,75,162,0.1));
            border-radius: 12px;
            font-size: 11px;
        }

        .fps-counter {
            position: absolute;
            bottom: 10px;
            right: 15px;
            background: rgba(0,0,0,0.6);
            color: #0cb413;
            padding: 2px 8px;
            border-radius: 15px;
            font-size: 9px;
            font-family: monospace;
            z-index: 20;
        }

        h2 {
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            font-size: 22px;
            margin-bottom: 20px;
        }

        .upload-area {
            background: rgba(255,255,255,0.95);
            border-radius: 20px;
            padding: 25px;
        }
    </style>

    <script>
        let chartInstance = null;
        let animationId = null;
        let animationProgress = 0;
        let animationStartTime = 0;
        let cycleCount = 0;
        let frameCount = 0;
        let lastFpsUpdate = 0;
        const ANIMATION_DURATION = 15000;
        
        function showSection(id) {
            document.querySelectorAll('.section').forEach(sec => sec.classList.remove('active'));
            document.getElementById(id).classList.add('active');
        }
        
        function easeInOutSine(x) {
            return -(Math.cos(Math.PI * x) - 1) / 2;
        }
        
        function easeInOutCubic(x) {
            return x < 0.5 ? 4 * x * x * x : 1 - Math.pow(-2 * x + 2, 3) / 2;
        }
        
        function superSmoothEasing(x) {
            return (easeInOutSine(x) + easeInOutCubic(x)) / 2;
        }
        
        function updateChart() {
            if (!chartInstance) return;
            
            const totalPoints = smoothRevenueData.length;
            const easedProgress = superSmoothEasing(animationProgress);
            const pointsToShow = Math.max(1, Math.floor(totalPoints * easedProgress));
            
            const newRevenueData = [];
            const newExpenditureData = [];
            
            for (let i = 0; i < totalPoints; i++) {
                if (i < pointsToShow) {
                    newRevenueData.push(smoothRevenueData[i]);
                    newExpenditureData.push(smoothExpenditureData[i]);
                } else {
                    newRevenueData.push(null);
                    newExpenditureData.push(null);
                }
            }
            
            chartInstance.data.datasets[0].data = newRevenueData;
            chartInstance.data.datasets[1].data = newExpenditureData;
            chartInstance.update('none');
            
            const progressFill = document.getElementById('progressFill');
            if (progressFill) {
                progressFill.style.width = (animationProgress * 100) + '%';
            }
            
            const statusText = document.getElementById('statusText');
            if (statusText) {
                const percent = Math.floor(animationProgress * 100);
                if (animationProgress >= 1) {
                    statusText.innerHTML = `🎬 CYCLE ${cycleCount + 1} COMPLETE`;
                } else {
                    statusText.innerHTML = `🎥 DRAWING: ${percent}% • CYCLE ${cycleCount + 1}`;
                }
            }
            
            frameCount++;
            const now = performance.now();
            if (now - lastFpsUpdate >= 1000) {
                const fpsCounter = document.getElementById('fpsCounter');
                if (fpsCounter) {
                    fpsCounter.innerHTML = `${frameCount} FPS`;
                }
                frameCount = 0;
                lastFpsUpdate = now;
            }
        }
        
        function animate() {
            const now = Date.now();
            const elapsed = now - animationStartTime;
            let rawProgress = Math.min(1, elapsed / ANIMATION_DURATION);
            
            animationProgress = rawProgress;
            updateChart();
            
            if (animationProgress >= 1) {
                cycleCount++;
                animationProgress = 0;
                animationStartTime = Date.now();
                
                const cycleDisplay = document.getElementById('cycleDisplay');
                if (cycleDisplay) {
                    cycleDisplay.innerHTML = `🎬 LOOP #${cycleCount + 1} • 15-SECOND CYCLE`;
                }
            }
            
            animationId = requestAnimationFrame(animate);
        }
        
        function startCinematicMotion() {
            if (animationId) {
                cancelAnimationFrame(animationId);
            }
            
            animationProgress = 0;
            cycleCount = 0;
            frameCount = 0;
            lastFpsUpdate = performance.now();
            animationStartTime = Date.now();
            updateChart();
            animationId = requestAnimationFrame(animate);
        }
    </script>
</head>

<body>

<div class="nav">
    <a onclick="showSection('upload')">📤 UPLOAD</a>
    <a onclick="showSection('edit')">✏️ EDIT</a>
    <a onclick="showSection('dashboard')">📊 DASHBOARD</a>
</div>

<!-- UPLOAD SECTION -->
<div id="upload" class="section active">
    <h2>📤 Upload Financial Data</h2>
    <div class="upload-area">
        <form action="upload.php" method="POST" enctype="multipart/form-data">
            <input type="file" name="file" accept=".csv" required style="padding: 10px; width: 280px; border-radius: 8px; border: 2px solid #0cb413;">
            <button type="submit">🚀 Upload</button>
        </form>
        <div style="margin-top: 20px;">
            <h3>📋 CSV Format:</h3>
            <pre style="margin-top: 8px; padding: 12px; background: #1a1a2e; color: #0cb413; border-radius: 10px; font-size: 12px;">
year,month,revenue,expenditure
2024,1,500000,450000
2024,2,550000,470000
2024,3,600000,490000</pre>
        </div>
    </div>
</div>

<!-- EDIT SECTION -->
<div id="edit" class="section">
    <h2>✏️ Edit Records</h2>
    <div style="background: rgba(255,255,255,0.95); border-radius: 16px; padding: 15px; overflow-x: auto;">
        <table style="font-size: 12px;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Year</th>
                    <th>Month</th>
                    <th>Revenue</th>
                    <th>Expenditure</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $editData = $conn->query("SELECT * FROM finances ORDER BY year, month");
            while($row = $editData->fetch_assoc()):
            ?>
            <tr>
                <form action="update.php" method="POST">
                    <td><?= $row['id'] ?></td>
                    <td><input type="number" name="year" value="<?= $row['year'] ?>" required style="width: 70px;"></td>
                    <td><input type="number" name="month" value="<?= $row['month'] ?>" min="1" max="12" required style="width: 60px;"></td>
                    <td><input type="number" name="revenue" value="<?= $row['revenue'] ?>" required step="0.01" style="width: 100px;"></td>
                    <td><input type="number" name="expenditure" value="<?= $row['expenditure'] ?>" required step="0.01" style="width: 100px;"></td>
                    <td>
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <button type="submit" style="padding: 4px 12px;">Update</button>
                    </td>
                </form>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- DASHBOARD SECTION - SMALLER CHART -->
<div id="dashboard" class="section">
    <h2>🎬 Smooth Financial Dashboard</h2>
    <div class="chart-container">
        <div class="cinema-badge">
            <div class="film-dot"></div>
            <span>60FPS SMOOTH</span>
        </div>
        <div class="smooth-badge">
            🎬 15s CYCLE
        </div>
        <div class="fps-counter" id="fpsCounter">
            60 FPS
        </div>
        
        <canvas id="financeChart"></canvas>
        
        <div class="progress-container">
            <div class="progress-bar">
                <div id="progressFill" class="progress-fill"></div>
            </div>
            <div class="status-text" id="statusText">
                🎬 Loading smooth animation...
            </div>
        </div>
        
        <div class="cinema-note" id="cycleDisplay">
            🎥 60FPS cinematic motion
        </div>
        
        <?php if (!empty($revenue) && !empty($expenditure)): ?>
        <div class="stats">
            <div class="stat-card" style="background: linear-gradient(135deg, #0cb413, #0a8a0f);">
                <h3>💰 REVENUE</h3>
                <p>KES <?= number_format(array_sum($revenue)/1000000, 1) ?>M</p>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #dc3545, #c82333);">
                <h3>📉 EXPENSE</h3>
                <p>KES <?= number_format(array_sum($expenditure)/1000000, 1) ?>M</p>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #ff9800, #f57c00);">
                <h3>📊 POINTS</h3>
                <p><?= count($revenue) ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Ultra-smooth interpolated data
const smoothRevenueData = <?= json_encode($interpolatedRevenue) ?>;
const smoothExpenditureData = <?= json_encode($interpolatedExpenditure) ?>;
const originalMonthLabels = <?= json_encode($months) ?>;

// Create labels
const smoothLabels = [];
for (let i = 0; i < smoothRevenueData.length; i++) {
    const originalIndex = Math.floor(i / <?= $smoothFactor ?>);
    if (originalIndex < originalMonthLabels.length) {
        smoothLabels.push(originalMonthLabels[originalIndex]);
    } else {
        smoothLabels.push(originalMonthLabels[originalMonthLabels.length - 1]);
    }
}

// Create smaller chart
const ctx = document.getElementById('financeChart').getContext('2d');

chartInstance = new Chart(ctx, {
    type: 'line',
    data: {
        labels: smoothLabels,
        datasets: [
            {
                label: 'REVENUE',
                data: new Array(smoothRevenueData.length).fill(null),
                borderColor: '#0cb413',
                backgroundColor: 'rgba(12,180,19,0.03)',
                borderWidth: 2.5,
                borderDash: [],
                tension: 0.5,
                fill: true,
                pointRadius: 0,
                pointHoverRadius: 5,
                segment: { borderDash: () => undefined }
            },
            {
                label: 'EXPENDITURE',
                data: new Array(smoothExpenditureData.length).fill(null),
                borderColor: '#ff3366',
                backgroundColor: 'rgba(255,51,102,0.03)',
                borderWidth: 2.5,
                borderDash: [],
                tension: 0.5,
                fill: true,
                pointRadius: 0,
                pointHoverRadius: 5,
                segment: { borderDash: () => undefined }
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        aspectRatio: 2.2,
        animation: false,
        interaction: {
            mode: 'index',
            intersect: false
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (context.parsed.y !== null) {
                            label += ': KES ' + new Intl.NumberFormat().format(context.parsed.y);
                        }
                        return label;
                    }
                },
                backgroundColor: 'rgba(0,0,0,0.85)',
                titleColor: '#ffd700',
                bodyColor: '#fff',
                borderColor: '#0cb413',
                borderWidth: 1,
                titleFont: { size: 11 },
                bodyFont: { size: 10 }
            },
            legend: {
                position: 'top',
                labels: {
                    font: { size: 10 },
                    usePointStyle: false,
                    padding: 12,
                    boxWidth: 30
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'KES',
                    font: { size: 10 }
                },
                ticks: {
                    callback: function(value) {
                        if (value >= 1000000) return (value/1000000).toFixed(1) + 'M';
                        if (value >= 1000) return (value/1000).toFixed(0) + 'K';
                        return value;
                    },
                    font: { size: 9 }
                },
                grid: { color: 'rgba(0,0,0,0.05)' }
            },
            x: {
                title: {
                    display: true,
                    text: 'Time',
                    font: { size: 10 }
                },
                ticks: {
                    maxRotation: 45,
                    minRotation: 45,
                    autoSkip: true,
                    maxTicksLimit: 8,
                    font: { size: 8 }
                },
                grid: { display: false }
            }
        },
        elements: {
            line: { borderJoin: 'round', borderCap: 'round' }
        },
        layout: {
            padding: { top: 5, bottom: 5, left: 5, right: 5 }
        }
    }
});

// Start animation
if (smoothRevenueData.length > 0) {
    setTimeout(() => {
        startCinematicMotion();
    }, 300);
}
</script>

</body>
</html>