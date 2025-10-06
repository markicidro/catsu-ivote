<?php
// debug_votes.php - Create this file to check if votes are being recorded
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ivote_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h1>Voting System Debug Information</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
    th { background-color: #4f46e5; color: white; }
    tr:nth-child(even) { background-color: #f2f2f2; }
    .section { margin: 30px 0; padding: 20px; background: #f9fafb; border-radius: 8px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
</style>";

// Check votes table
echo "<div class='section'>";
echo "<h2>📊 Votes Table</h2>";
$votesQuery = "SELECT id, voter_id, vote_date, 
                SUBSTRING(encrypted_vote, 1, 50) as encrypted_preview 
                FROM votes 
                ORDER BY vote_date DESC 
                LIMIT 10";
$votesResult = $conn->query($votesQuery);

if ($votesResult && $votesResult->num_rows > 0) {
    echo "<p class='success'>✓ Found " . $votesResult->num_rows . " votes (showing last 10)</p>";
    echo "<table>";
    echo "<tr><th>Vote ID</th><th>Voter ID</th><th>Vote Date</th><th>Encrypted Data (Preview)</th></tr>";
    while ($row = $votesResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['voter_id'] . "</td>";
        echo "<td>" . $row['vote_date'] . "</td>";
        echo "<td>" . htmlspecialchars($row['encrypted_preview']) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>✗ No votes found in database</p>";
}
echo "</div>";

// Check vote_tally table
echo "<div class='section'>";
echo "<h2>🗳️ Vote Tally Table</h2>";
$tallyQuery = "SELECT vt.*, 
                CASE 
                    WHEN vt.candidate_type = 'main' THEN CONCAT(mc.first_name, ' ', mc.last_name)
                    WHEN vt.candidate_type = 'sub' THEN CONCAT(sc.first_name, ' ', sc.last_name)
                END as candidate_name
                FROM vote_tally vt
                LEFT JOIN main_org_candidates mc ON vt.candidate_id = mc.id AND vt.candidate_type = 'main'
                LEFT JOIN sub_org_candidates sc ON vt.candidate_id = sc.id AND vt.candidate_type = 'sub'
                ORDER BY vt.id DESC
                LIMIT 20";
$tallyResult = $conn->query($tallyQuery);

if ($tallyResult && $tallyResult->num_rows > 0) {
    echo "<p class='success'>✓ Found " . $tallyResult->num_rows . " vote tallies (showing last 20)</p>";
    echo "<table>";
    echo "<tr><th>Tally ID</th><th>Vote ID</th><th>Candidate Name</th><th>Type</th><th>Position</th><th>Organization</th></tr>";
    while ($row = $tallyResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['vote_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['candidate_name'] ?? 'Unknown') . "</td>";
        echo "<td>" . $row['candidate_type'] . "</td>";
        echo "<td>" . htmlspecialchars($row['position'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['organization'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>✗ No vote tallies found in database</p>";
}
echo "</div>";

// Vote count by organization
echo "<div class='section'>";
echo "<h2>📈 Vote Count by Organization</h2>";
$countQuery = "SELECT 
                organization, 
                candidate_type,
                COUNT(*) as vote_count 
                FROM vote_tally 
                WHERE organization IS NOT NULL
                GROUP BY organization, candidate_type
                ORDER BY organization, candidate_type";
$countResult = $conn->query($countQuery);

if ($countResult && $countResult->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>Organization</th><th>Type</th><th>Vote Count</th></tr>";
    while ($row = $countResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['organization']) . "</td>";
        echo "<td>" . $row['candidate_type'] . "</td>";
        echo "<td><strong>" . $row['vote_count'] . "</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>✗ No vote counts available</p>";
}
echo "</div>";

// Accepted candidates count
echo "<div class='section'>";
echo "<h2>👥 Accepted Candidates</h2>";
$mainCount = $conn->query("SELECT COUNT(*) as count FROM main_org_candidates WHERE status='Accepted'")->fetch_assoc()['count'];
$subCount = $conn->query("SELECT COUNT(*) as count FROM sub_org_candidates WHERE status='Accepted'")->fetch_assoc()['count'];

echo "<p><strong>Main Organization Candidates:</strong> $mainCount</p>";
echo "<p><strong>Sub Organization Candidates:</strong> $subCount</p>";
echo "<p><strong>Total Candidates:</strong> " . ($mainCount + $subCount) . "</p>";
echo "</div>";

// Check for orphaned vote_tally records
echo "<div class='section'>";
echo "<h2>⚠️ Data Integrity Check</h2>";

// Check if all vote_tally records have corresponding votes
$orphanedQuery = "SELECT vt.* FROM vote_tally vt 
                  LEFT JOIN votes v ON vt.vote_id = v.id 
                  WHERE v.id IS NULL";
$orphanedResult = $conn->query($orphanedQuery);

if ($orphanedResult && $orphanedResult->num_rows > 0) {
    echo "<p class='error'>✗ Found " . $orphanedResult->num_rows . " orphaned vote_tally records (no matching vote)</p>";
} else {
    echo "<p class='success'>✓ All vote_tally records have corresponding votes</p>";
}

// Check if all votes have tally records
$missingTallyQuery = "SELECT v.id, v.voter_id FROM votes v 
                      LEFT JOIN vote_tally vt ON v.id = vt.vote_id 
                      WHERE vt.id IS NULL";
$missingTallyResult = $conn->query($missingTallyQuery);

if ($missingTallyResult && $missingTallyResult->num_rows > 0) {
    echo "<p class='error'>✗ Found " . $missingTallyResult->num_rows . " votes without tally records:</p>";
    echo "<ul>";
    while ($row = $missingTallyResult->fetch_assoc()) {
        echo "<li>Vote ID: " . $row['id'] . ", Voter ID: " . $row['voter_id'] . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p class='success'>✓ All votes have corresponding tally records</p>";
}
echo "</div>";

$conn->close();

echo "<p style='margin-top: 40px;'><a href='home.php' style='padding: 10px 20px; background: #4f46e5; color: white; text-decoration: none; border-radius: 5px;'>← Back to Main Page</a></p>";
?>