<?php
// fetch_org_candidates.php
session_start();

// Ensure clean JSON output - suppress display errors, log them instead
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

$organization = $_GET['organization'] ?? '';
$organization = trim($organization); // Trim whitespace

if (empty($organization)) {
    echo json_encode(['error' => 'Organization parameter is required']);
    exit;
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ivote_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    error_log("DB Connection failed: " . $conn->connect_error);
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$candidates = [];

try {
    if (in_array($organization, ['USC', 'CSC'])) {
        // Main org candidates with vote tally
        $sql = "
            SELECT c.id, c.first_name, c.middle_name, c.last_name, c.position, c.partylist, c.college,
                   COUNT(vt.id) AS total_votes
            FROM main_org_candidates c
            LEFT JOIN vote_tally vt ON vt.candidate_id = c.id AND vt.candidate_type = 'main' AND vt.organization = ?
            WHERE c.status = 'Accepted' AND c.organization = ?
            GROUP BY c.id
            ORDER BY 
                FIELD(c.position, 'President', 'Vice President', 'Executive Secretary', 'Finance Secretary', 'Budget Secretary', 'Auditor', 'Public Information Secretary', 'Property Custodian', 'Senators', 'Legislators', 'Year Representative', 'Other'), 
                c.last_name
        ";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("ss", $organization, $organization); // First ? for JOIN, second for WHERE
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $candidates[] = $row;
        }
        $stmt->close();

    } else {
        // Sub org candidates with vote tally
        $sql = "
            SELECT c.id, c.first_name, c.middle_name, c.last_name, 
                   COALESCE(c.position, 'Representative') AS position, 
                   c.year, c.block_address,
                   COUNT(vt.id) AS total_votes
            FROM sub_org_candidates c
            LEFT JOIN vote_tally vt ON vt.candidate_id = c.id AND vt.candidate_type = 'sub' AND vt.organization = ?
            WHERE c.status = 'Accepted' AND c.organization = ?
            GROUP BY c.id
            ORDER BY c.last_name
        ";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("ss", $organization, $organization); // First ? for JOIN, second for WHERE
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $candidates[] = $row;
        }
        $stmt->close();
    }

    // Log for debugging (remove in production)
    error_log("Fetched " . count($candidates) . " candidates for organization: " . $organization);

} catch (Exception $e) {
    error_log("Query error for org '$organization': " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]);
    exit;
}

$conn->close();
echo json_encode(['candidates' => $candidates]);
exit;
?>
