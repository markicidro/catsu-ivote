<?php
session_start();
// Check if admin
if ($_SESSION['role'] !== 'admin') die('Access denied');

require 'config_v.php';

function decryptVote($encrypted) {
    $cipher = "aes-256-cbc";
    list($encrypted_data, $iv) = explode('::', base64_decode($encrypted), 2);
    return json_decode(openssl_decrypt($encrypted_data, $cipher, VOTE_ENCRYPTION_KEY, 0, $iv), true);
}

$conn = new mysqli("localhost", "root", "", "ivote_db");

$votes = $conn->query("SELECT id, voter_id, encrypted_vote FROM votes");

echo "<h2>Encrypted Votes (Admin Only)</h2>";
echo "<table border='1'><tr><th>Vote ID</th><th>Voter ID</th><th>Decrypted Choices</th></tr>";

while ($row = $votes->fetch_assoc()) {
    $decrypted = decryptVote($row['encrypted_vote']);
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['voter_id'] . "</td>";
    echo "<td><pre>" . print_r($decrypted, true) . "</pre></td>";
    echo "</tr>";
}

echo "</table>";
?>