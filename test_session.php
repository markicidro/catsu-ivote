<?php
session_start();

// Set a test value
if (!isset($_SESSION['test'])) {
    $_SESSION['test'] = 'Session is working!';
    echo "Session created. Refresh this page.";
} else {
    echo "Session value: " . $_SESSION['test'];
}

echo "<br><br>Session ID: " . session_id();
echo "<br>All session data: <pre>";
print_r($_SESSION);
echo "</pre>";
?>