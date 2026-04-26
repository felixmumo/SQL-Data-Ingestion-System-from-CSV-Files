<?php
$conn = new mysqli("localhost", "root", "", "gov_finance");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$messageType = "";

if (isset($_FILES['file'])) {
    $file = fopen($_FILES['file']['tmp_name'], "r");

    // skip header
    fgetcsv($file);

    $data = [];

    while (($row = fgetcsv($file)) !== FALSE) {
        $year = (int)$row[0];
        $month = (int)$row[1];
        $revenue = (float)$row[2];
        $expenditure = (float)$row[3];

        $data[] = compact('year','month','revenue','expenditure');
    }

    // SORT data (important for sequence check)
    usort($data, function($a, $b) {
        return ($a['year'] * 100 + $a['month']) - ($b['year'] * 100 + $b['month']);
    });

    // 🔴 CHECK SEQUENCE
    for ($i = 1; $i < count($data); $i++) {
        $prev = $data[$i-1];
        $curr = $data[$i];

        $expectedMonth = $prev['month'] + 1;
        $expectedYear = $prev['year'];

        if ($expectedMonth > 12) {
            $expectedMonth = 1;
            $expectedYear++;
        }

        if ($curr['month'] != $expectedMonth || $curr['year'] != $expectedYear) {
            $message = "⚠️ Missing month detected between {$prev['year']}-{$prev['month']} and {$curr['year']}-{$curr['month']}";
            $messageType = "error";
            break;
        }
    }

    if (empty($message)) {
        // 🔴 CHECK AGAINST DATABASE (NO SKIPPING FROM LAST ENTRY)
        $result = $conn->query("SELECT year, month FROM finances ORDER BY year DESC, month DESC LIMIT 1");

        if ($result->num_rows > 0) {
            $last = $result->fetch_assoc();

            $expectedMonth = $last['month'] + 1;
            $expectedYear = $last['year'];

            if ($expectedMonth > 12) {
                $expectedMonth = 1;
                $expectedYear++;
            }

            if ($data[0]['month'] != $expectedMonth || $data[0]['year'] != $expectedYear) {
                $message = "⚠️ Upload must continue from {$expectedYear}-{$expectedMonth}";
                $messageType = "error";
            }
        }
    }

    if (empty($message)) {
        // 🔴 INSERT DATA
        foreach ($data as $row) {
            $stmt = $conn->prepare("INSERT INTO finances (year, month, revenue, expenditure) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iidd", $row['year'], $row['month'], $row['revenue'], $row['expenditure']);

            if (!$stmt->execute()) {
                if ($conn->errno == 1062) {
                    $message = "⚠️ Duplicate Entries not Allowed for {$row['year']}-{$row['month']}";
                    $messageType = "error";
                    break;
                } else {
                    $message = "⚠️ Database Error: " . $conn->error;
                    $messageType = "error";
                    break;
                }
            }
        }
    }

    if (empty($message)) {
        $message = "✅ Data uploaded successfully!";
        $messageType = "success";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload CSV - Government Finance System</title>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', 'Poppins', 'Inter', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated background bubbles */
        body::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 20% 80%, rgba(255,255,255,0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        body::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 80% 20%, rgba(255,255,255,0.08) 0%, transparent 50%);
            pointer-events: none;
        }

        .container {
            max-width: 550px;
            width: 100%;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 32px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            backdrop-filter: blur(10px);
            animation: slideUp 0.6s ease-out;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .container:hover {
            transform: translateY(-5px);
            box-shadow: 0 35px 60px -15px rgba(0, 0, 0, 0.4);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header {
            text-align: center;
            margin-bottom: 32px;
        }

        .icon {
            font-size: 48px;
            margin-bottom: 12px;
            display: inline-block;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        h1 {
            font-size: 28px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #6b7280;
            font-size: 14px;
        }

        .upload-area {
            border: 2px dashed #d1d5db;
            border-radius: 20px;
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f9fafb;
            margin-bottom: 24px;
            position: relative;
        }

        .upload-area:hover {
            border-color: #667eea;
            background: #f3f4f6;
            transform: scale(1.02);
        }

        .upload-area.drag-over {
            border-color: #0cb413;
            background: rgba(12, 180, 19, 0.05);
        }

        .upload-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }

        .upload-text {
            color: #4b5563;
            font-size: 16px;
            margin-bottom: 8px;
        }

        .upload-hint {
            color: #9ca3af;
            font-size: 12px;
        }

        input[type="file"] {
            display: none;
        }

        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 24px;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(102, 126, 234, 0.4);
        }

        button:active {
            transform: translateY(0);
        }

        /* Message Styles */
        .message {
            padding: 16px 20px;
            border-radius: 16px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: messageSlide 0.5s ease-out;
            font-weight: 500;
        }

        @keyframes messageSlide {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .message-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-left: 4px solid #10b981;
            color: #065f46;
        }

        .message-error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-left: 4px solid #dc2626;
            color: #991b1b;
        }

        .message-icon {
            font-size: 24px;
            flex-shrink: 0;
        }

        .message-content {
            flex: 1;
            font-size: 14px;
            line-height: 1.4;
        }

        .info-box {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-radius: 16px;
            padding: 16px;
            margin-top: 24px;
        }

        .info-title {
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .info-content {
            color: #1e3a8a;
            font-size: 12px;
            line-height: 1.6;
        }

        .info-content pre {
            background: rgba(0, 0, 0, 0.05);
            padding: 10px;
            border-radius: 8px;
            margin-top: 8px;
            font-family: 'Courier New', monospace;
            font-size: 11px;
            overflow-x: auto;
        }

        .back-link {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .back-link a:hover {
            color: #764ba2;
        }

        /* File name display */
        .file-name {
            margin-top: 10px;
            font-size: 12px;
            color: #0cb413;
            font-weight: 500;
            display: none;
        }

        .file-name.show {
            display: block;
        }

        /* Loading spinner */
        .loading {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        button.loading .button-text {
            display: none;
        }

        button.loading .loading {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon">📊</div>
            <h1>Upload Financial Data</h1>
            <div class="subtitle">Import CSV files with revenue and expenditure records</div>
        </div>

        <?php if (!empty($message)): ?>
        <div class="message message-<?= $messageType ?>">
            <div class="message-icon">
                <?= $messageType === 'success' ? '✅' : '⚠️' ?>
            </div>
            <div class="message-content">
                <?= htmlspecialchars($message) ?>
            </div>
        </div>
        <?php endif; ?>

        <form id="uploadForm" action="" method="POST" enctype="multipart/form-data">
            <div class="upload-area" id="uploadArea">
                <div class="upload-icon">📁</div>
                <div class="upload-text">Click or drag CSV file here</div>
                <div class="upload-hint">Supports .csv files up to 10MB</div>
                <input type="file" name="file" id="fileInput" accept=".csv" required>
                <div class="file-name" id="fileName"></div>
            </div>

            <button type="submit" id="submitBtn">
                <span class="button-text">🚀 Upload & Process</span>
                <div class="loading"></div>
            </button>
        </form>

        <div class="info-box">
            <div class="info-title">
                <span>📋</span> CSV Format Requirements
            </div>
            <div class="info-content">
                Your CSV file must have the following columns in order:
                <pre>year,month,revenue,expenditure
2024,1,500000,450000
2024,2,550000,470000
2024,3,600000,490000</pre>
                <strong>⚠️ Important:</strong>
                <ul style="margin-top: 8px; margin-left: 20px;">
                    <li>Months must be sequential (no gaps)</li>
                    <li>Continues from last record in database</li>
                    <li>No duplicate month/year entries</li>
                    <li>Revenue and expenditure must be numbers</li>
                </ul>
            </div>
        </div>

        <div class="back-link">
            <a href="index.php">← Back to Dashboard</a>
        </div>
    </div>

    <script>
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        const fileName = document.getElementById('fileName');
        const submitBtn = document.getElementById('submitBtn');
        const form = document.getElementById('uploadForm');

        // Drag and drop functionality
        uploadArea.addEventListener('click', () => {
            fileInput.click();
        });

        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('drag-over');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('drag-over');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('drag-over');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                updateFileName(files[0]);
            }
        });

        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                updateFileName(e.target.files[0]);
            } else {
                fileName.classList.remove('show');
            }
        });

        function updateFileName(file) {
            fileName.textContent = `📎 Selected: ${file.name}`;
            fileName.classList.add('show');
        }

        // Loading state on form submit
        form.addEventListener('submit', (e) => {
            if (!fileInput.files.length) {
                e.preventDefault();
                return;
            }
            submitBtn.classList.add('loading');
        });
    </script>
</body>
</html>