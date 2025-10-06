<?php
// test_db_structure.php - Run this to check your database structure
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ivote_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h1>Database Structure Check</h1>";
echo "<style>
    body { font-family: Arial; padding: 20px; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
    th { background-color: #4f46e5; color: white; }
    .success { color: green; }
    .error { color: red; }
    .section { margin: 30px 0; padding: 20px; background: #f9fafb; border-radius: 8px; }
</style>";

// Check votes table structure
echo "<div class='section'>";
echo "<h2>Votes Table Structure</h2>";
$result = $conn->query("DESCRIBE votes");
if ($result) {
    echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>Error: " . $conn->error . "</p>";
}
echo "</div>";

// Check vote_tally table structure
echo "<div class='section'>";
echo "<h2>Vote_Tally Table Structure</h2>";
$result = $conn->query("DESCRIBE vote_tally");
if ($result) {
    echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>Error: " . $conn->error . "</p>";
}
echo "</div>";

// Check main_org_candidates table structure
echo "<div class='section'>";
echo "<h2>Main_Org_Candidates Table Structure</h2>";
$result = $conn->query("DESCRIBE main_org_candidates");
if ($result) {
    echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>Error: " . $conn->error . "</p>";
}
echo "</div>";

// Check sub_org_candidates table structure
echo "<div class='section'>";
echo "<h2>Sub_Org_Candidates Table Structure</h2>";
$result = $conn->query("DESCRIBE sub_org_candidates");
if ($result) {
    echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>Error: " . $conn->error . "</p>";
}
echo "</div>";

// Test query for USC
echo "<div class='section'>";
echo "<h2>Test Query - USC Candidates with Vote Count</h2>";
$testQuery = "SELECT 
                m.id,
                m.first_name,
                m.middle_name,
                m.last_name,
                m.position,
                m.organization,
                COUNT(vt.id) as total_votes
              FROM main_org_candidates m
              LEFT JOIN vote_tally vt ON m.id = vt.candidate_id 
                  AND vt.candidate_type = 'main'
                  AND vt.organization = m.organization
              WHERE m.status = 'Accepted' AND m.organization = 'USC'
              GROUP BY m.id, m.first_name, m.middle_name, m.last_name, m.position, m.organization
              ORDER BY m.position, m.last_name";

$result = $conn->query($testQuery);
if ($result) {
    echo "<p class='success'>Query successful! Found {$result->num_rows} candidates</p>";
    if ($result->num_rows > 0) {
        echo "<table><tr><th>ID</th><th>Name</th><th>Position</th><th>Votes</th></tr>";
        while ($row = $result->fetch_assoc()) {
            $name = trim(($row['first_name'] ?? '') . ' ' . ($row['middle_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
            echo "<tr><td>{$row['id']}</td><td>{$name}</td><td>{$row['position']}</td><td>{$row['total_votes']}</td></tr>";
        }
        echo "</table>";
    }
} else {
    echo "<p class='error'>Query failed: " . $conn->error . "</p>";
}
echo "</div>";

$conn->close();
?>