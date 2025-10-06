<?php
header('Content-Type: application/json');
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ivote_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Total registered voters
$result = $conn->query("SELECT COUNT(*) AS total FROM users");
$totalVoters = $result ? (int)$result->fetch_assoc()['total'] : 0;

// Students who voted
$result = $conn->query("SELECT COUNT(DISTINCT voter_id) AS voted FROM votes");
$voted = $result ? (int)$result->fetch_assoc()['voted'] : 0;

// Students who have not voted
$notVoted = max(0, $totalVoters - $voted);

// Total candidates (main + sub)
$resultMain = $conn->query("SELECT COUNT(*) AS total FROM main_org_candidates WHERE status='Accepted'");
$totalMainCandidates = $resultMain ? (int)$resultMain->fetch_assoc()['total'] : 0;

$resultSub = $conn->query("SELECT COUNT(*) AS total FROM sub_org_candidates WHERE status='Accepted'");
$totalSubCandidates = $resultSub ? (int)$resultSub->fetch_assoc()['total'] : 0;

$totalCandidates = $totalMainCandidates + $totalSubCandidates;

echo json_encode([
    'totalVoters' => $totalVoters,
    'voted' => $voted,
    'notVoted' => $notVoted,
    'totalCandidates' => $totalCandidates
]);
