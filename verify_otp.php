<?php
// IMPORTANT: No spaces or output before this line!
session_start();

// Set headers FIRST before any output
header('Content-Type: application/json');

// Turn off error display to prevent breaking JSON
ini_set('display_errors', 0);
error_reporting(0);

// Get input
$inputOtp = trim($_POST['otp'] ?? '');

// Validate input
if (empty($inputOtp)) {
    echo json_encode(["status" => "error", "message" => "OTP code is required"]);
    exit;
}

// Check if OTP exists in session
if (!isset($_SESSION['otp'])) {
    echo json_encode(["status" => "error", "message" => "No OTP found. Please login again."]);
    exit;
}

// Check if OTP has expired
if (isset($_SESSION['otp_expiry']) && time() > $_SESSION['otp_expiry']) {
    // Clear expired OTP
    unset($_SESSION['otp']);
    unset($_SESSION['otp_expiry']);
    echo json_encode(["status" => "error", "message" => "OTP has expired. Please login again."]);
    exit;
}

// Verify OTP
if ($inputOtp === (string)$_SESSION['otp']) {
    // OTP is correct - clear it and keep user session
    unset($_SESSION['otp']);
    unset($_SESSION['otp_expiry']);
    
    // User is now authenticated
    $_SESSION['authenticated'] = true;
    $_SESSION['login_time'] = time();
    
    echo json_encode([
        "status" => "success",
        "message" => "OTP verified successfully",
        "role" => $_SESSION['role'] ?? 'voter',
        "user_id" => $_SESSION['user_id'] ?? null
    ]);
} else {
    // Wrong OTP
    echo json_encode(["status" => "error", "message" => "Invalid OTP code. Please try again."]);
}
exit;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification Debug</title>
    <style>
        body {
            font-family: monospace;
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
            background: #1e293b;
            color: #e2e8f0;
        }
        h1 { color: #22d3ee; }
        .section {
            background: #334155;
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
            border-left: 4px solid #22d3ee;
        }
        .success { border-left-color: #22c55e; }
        .error { border-left-color: #ef4444; }
        .warning { border-left-color: #f59e0b; }
        pre {
            background: #1e293b;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        button {
            background: #22d3ee;
            color: #000;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            margin: 5px;
        }
        button:hover { background: #06b6d4; }
        #testResult {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            display: none;
        }
    </style>
</head>
<body>
    <h1>🔍 OTP Verification Debug Tool</h1>

    <div class="section">
        <h3>📋 Current Session Data</h3>
        <pre><?php print_r($_SESSION); ?></pre>
        
        <?php if (isset($_SESSION['otp'])): ?>
            <p style="color: #22c55e;">✅ OTP exists in session: <strong><?php echo $_SESSION['otp']; ?></strong></p>
        <?php else: ?>
            <p style="color: #ef4444;">❌ No OTP in session. Please login first to generate OTP.</p>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['otp_expiry'])): ?>
            <?php 
            $remaining = $_SESSION['otp_expiry'] - time();
            if ($remaining > 0): ?>
                <p style="color: #22c55e;">✅ OTP expires in: <strong><?php echo $remaining; ?> seconds</strong></p>
            <?php else: ?>
                <p style="color: #ef4444;">❌ OTP has expired</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="section">
        <h3>🧪 Test verify_otp.php Response</h3>
        <p>This will send a test OTP to verify_otp.php and show the raw response.</p>
        
        <?php if (isset($_SESSION['otp'])): ?>
            <button onclick="testCorrectOtp()">Test with Correct OTP (<?php echo $_SESSION['otp']; ?>)</button>
            <button onclick="testWrongOtp()">Test with Wrong OTP (999999)</button>
        <?php else: ?>
            <button onclick="testNoSession()">Test without Session</button>
        <?php endif; ?>
        
        <div id="testResult"></div>
    </div>

    <div class="section">
        <h3>📝 File Checks</h3>
        <?php
        $files = ['verify_otp.php', 'login.php', 'config.php'];
        foreach ($files as $file) {
            if (file_exists($file)) {
                echo "<p style='color: #22c55e;'>✅ $file exists</p>";
                
                // Check for BOM or whitespace
                $content = file_get_contents($file);
                if (substr($content, 0, 5) === '<?php') {
                    echo "<p style='color: #22c55e;'>✅ $file starts correctly with &lt;?php</p>";
                } else {
                    $first = bin2hex(substr($content, 0, 10));
                    echo "<p style='color: #ef4444;'>❌ $file has whitespace/BOM before &lt;?php<br>First bytes: $first</p>";
                }
                
                // Check for output after closing tag
                if (preg_match('/\?>\s+\S/', $content)) {
                    echo "<p style='color: #f59e0b;'>⚠️ $file has content after closing ?&gt; tag</p>";
                }
            } else {
                echo "<p style='color: #ef4444;'>❌ $file not found</p>";
            }
        }
        ?>
    </div>

    <div class="section">
        <h3>🔧 Quick Actions</h3>
        <button onclick="clearSession()">Clear Session Data</button>
        <button onclick="location.reload()">Refresh Page</button>
        <a href="index.html"><button>Go to Login Page</button></a>
    </div>

    <script>
        function testCorrectOtp() {
            const otp = '<?php echo $_SESSION['otp'] ?? ''; ?>';
            testOtp(otp, 'Correct OTP');
        }

        function testWrongOtp() {
            testOtp('999999', 'Wrong OTP');
        }

        function testNoSession() {
            testOtp('123456', 'No Session Test');
        }

        function testOtp(otpCode, testName) {
            const resultDiv = document.getElementById('testResult');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '<p>Testing...</p>';
            
            fetch('verify_otp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `otp=${otpCode}`
            })
            .then(res => {
                console.log('Status:', res.status);
                return res.text();
            })
            .then(text => {
                console.log('Raw response:', text);
                
                resultDiv.innerHTML = `
                    <h4>${testName} - Response:</h4>
                    <p><strong>Raw Response:</strong></p>
                    <pre>${text.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</pre>
                `;
                
                try {
                    const json = JSON.parse(text);
                    resultDiv.innerHTML += `
                        <p style="color: #22c55e;"><strong>✅ Valid JSON</strong></p>
                        <pre>${JSON.stringify(json, null, 2)}</pre>
                    `;
                } catch (e) {
                    resultDiv.innerHTML += `
                        <p style="color: #ef4444;"><strong>❌ Invalid JSON</strong></p>
                        <p>Error: ${e.message}</p>
                        <p>First 100 characters:</p>
                        <pre>${text.substring(0, 100).replace(/</g, '&lt;').replace(/>/g, '&gt;')}</pre>
                    `;
                }
            })
            .catch(err => {
                resultDiv.innerHTML = `
                    <p style="color: #ef4444;"><strong>❌ Fetch Error</strong></p>
                    <pre>${err.message}</pre>
                `;
                console.error(err);
            });
        }

        function clearSession() {
            if (confirm('Clear all session data?')) {
                fetch('clear_session.php')
                    .then(() => location.reload());
            }
        }
    </script>
</body>
</html>