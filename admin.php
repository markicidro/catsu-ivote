<?php
//admin side

session_start();

date_default_timezone_set('Asia/Manila'); // Set your timezone
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: home.php');
    exit;
}
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ivote_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// User Management
if (isset($_POST['addUser'])) {
    $email = $_POST['email'];
    $student_id = $_POST['student_id'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash password
    $role = $_POST['role'];

    $stmt = $conn->prepare("INSERT INTO users (email, student_id, password, role) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("ssss", $email, $student_id, $password, $role);
    if (!$stmt->execute()) {
        $message = "Error adding user: " . $stmt->error;
        $messageType = "error";
    } else {
        $message = "User added successfully!";
        $messageType = "success";
    }
    $stmt->close();
}

if (isset($_POST['editUser'])) {
    $id = $_POST['id'];
    $email = $_POST['email'];
    $student_id = $_POST['student_id'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash password
    $role = $_POST['role'];

    $stmt = $conn->prepare("UPDATE users SET email=?, student_id=?, password=?, role=? WHERE id=?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("ssssi", $email, $student_id, $password, $role, $id);
    if (!$stmt->execute()) {
        $message = "Error updating user: " . $stmt->error;
        $messageType = "error";
    } else {
        $message = "User updated successfully!";
        $messageType = "success";
    }
    $stmt->close();
}

if (isset($_POST['deleteUser'])) {
    $id = $_POST['id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        $message = "Error deleting user: " . $stmt->error;
        $messageType = "error";
    } else {
        $message = "User deleted successfully!";
        $messageType = "success";
    }
    $stmt->close();
}

// ------------------- FILING MANAGEMENT -------------------

// Filing Management
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['filing_id'], $_POST['type'])) {
    $action = $_POST['action'];
    $filing_id = intval($_POST['filing_id']);
    $type = $_POST['type'];
    $comment = $_POST['comment'] ?? '';

    if (in_array($action, ['accept', 'reject']) && in_array($type, ['main', 'sub'])) {
        $status = $action === 'accept' ? 'Accepted' : 'Rejected';
        $table = $type === 'main' ? 'main_org_candidates' : 'sub_org_candidates';
        $stmt = $conn->prepare("UPDATE $table SET status=?, comment=? WHERE id=?");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("ssi", $status, $comment, $filing_id);
        if (!$stmt->execute()) {
            $message = "Error updating filing: " . $stmt->error;
            $messageType = "error";
        } else {
            $message = "Filing #$filing_id has been $status.";
            $messageType = "success";
        }
        $stmt->close();
    }
}

// ------------------- FETCH FILINGS -------------------

$mainFilings = [];
$result = $conn->query("SELECT * FROM main_org_candidates ORDER BY filing_date DESC");
if (!$result) {
    die("Main filings query failed: " . $conn->error);
}
$mainFilings = $result->fetch_all(MYSQLI_ASSOC);
$result->free();

$subFilings = [];
$result = $conn->query("SELECT * FROM sub_org_candidates ORDER BY filing_date DESC");
if (!$result) {
    die("Sub filings query failed: " . $conn->error);
}
$subFilings = $result->fetch_all(MYSQLI_ASSOC);
$result->free();

// ------------------- VOTING SCHEDULE MANAGEMENT -------------------

// Voting Schedule Management
if (isset($_POST['updateVotingSchedule'])) {
    $voting_status = $_POST['voting_status'];
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $description = $_POST['description'] ?? '';
    
    if ($start_date && $end_date) {
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        if ($end <= $start) {
            $message = "Error: End date must be after start date.";
            $messageType = "error";
        } else {
            $checkStmt = $conn->query("SELECT id FROM voting_schedule LIMIT 1");
            if ($checkStmt->num_rows > 0) {
                $stmt = $conn->prepare("UPDATE voting_schedule SET status=?, start_date=?, end_date=?, description=?, updated_at=NOW() WHERE id=(SELECT id FROM (SELECT id FROM voting_schedule LIMIT 1) as temp)");
                $stmt->bind_param("ssss", $voting_status, $start_date, $end_date, $description);
            } else {
                $stmt = $conn->prepare("INSERT INTO voting_schedule (status, start_date, end_date, description, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
                $stmt->bind_param("ssss", $voting_status, $start_date, $end_date, $description);
            }
            if ($stmt->execute()) {
                $message = "Voting schedule updated successfully!";
                $messageType = "success";
            } else {
                $message = "Error updating schedule: " . $stmt->error;
                $messageType = "error";
            }
            $stmt->close();
        }
    } else {
        $message = "Error: Start and end dates are required.";
        $messageType = "error";
    }
}

$votingSchedule = null;
$result = $conn->query("SELECT * FROM voting_schedule ORDER BY updated_at DESC LIMIT 1");
if ($result && $result->num_rows > 0) {
    $votingSchedule = $result->fetch_assoc();
}

function isVotingActive($schedule) {
    if (!$schedule || $schedule['status'] !== 'open') {
        return false;
    }
    $now = new DateTime();
    $startDate = new DateTime($schedule['start_date']);
    $endDate = new DateTime($schedule['end_date']);
    return ($now >= $startDate && $now <= $endDate);
}

// File Upload Helper
function uploadFile($fileInputName, $targetDir) {
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK || $_FILES[$fileInputName]['size'] > 2097152) {
        return '';
    }
    $filename = time() . "_" . uniqid() . "_" . basename($_FILES[$fileInputName]['name']);
    $targetPath = $targetDir . $filename;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    if (!move_uploaded_file($_FILES[$fileInputName]['tmp_name'], $targetPath)) {
        die("Failed to upload file: $filename");
    }
    return $filename;
}

// ------------------- FETCH VOTERS WITH VOTE STATUS -------------------
$voters = [];
$sql = "SELECT u.id, u.email, u.student_id, u.role, v.vote_date as voted_at 
        FROM users u 
        LEFT JOIN votes v ON u.id = v.users_id 
        WHERE u.role = 'voter' 
        ORDER BY u.student_id ASC";
$result = $conn->query($sql);
if ($result) {
    $voters = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
} else {
    die("Voters query failed: " . $conn->error);
}

// ------------------- DASHBOARD DATA -------------------
$totalVoters = 0;
$votersResult = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'voter'");
if ($votersResult) {
    $totalVoters = $votersResult->fetch_assoc()['count'];
    $votersResult->free();
}

$totalCandidates = 0;
$candidatesMainResult = $conn->query("SELECT COUNT(*) as count FROM main_org_candidates");
$candidatesSubResult = $conn->query("SELECT COUNT(*) as count FROM sub_org_candidates");
if ($candidatesMainResult && $candidatesSubResult) {
    $totalCandidates = $candidatesMainResult->fetch_assoc()['count'] + $candidatesSubResult->fetch_assoc()['count'];
    $candidatesMainResult->free();
    $candidatesSubResult->free();
}

$studentsVoted = 0;
$votesResult = $conn->query("SELECT COUNT(*) as count FROM votes");
if ($votesResult) {
    $studentsVoted = $votesResult->fetch_assoc()['count'];
    $votesResult->free();
}

// ------------------- REPORTING MODULE -------------------

// Handle report generation
$reportData = [];
$reportType = '';
$reportTitle = '';

if (isset($_POST['generateReport'])) {
    $reportType = $_POST['report_type'];
    
    switch($reportType) {
        case 'voters_summary':
            $reportTitle = 'Voters Summary Report';
            $sql = "SELECT 
                         u.id, 
                         u.student_id, 
                         u.email, 
                         CASE WHEN v.vote_date IS NOT NULL THEN 'Voted' ELSE 'Not Voted' END as vote_status,
                         v.vote_date as voted_at
                         FROM users u 
                        LEFT JOIN votes v ON u.id = v.voter_id 
                        WHERE u.role = 'voter' 
                        ORDER BY u.student_id ASC";
            break;
            
        case 'candidates_summary':
            $reportTitle = 'Candidates Summary Report';
            // Updated to use 'organization' field for both tables
            $sql = "SELECT 'Main Organization' as org_type, id, 
                           CONCAT(last_name, ', ', first_name, ' ', middle_name) as full_name,
                           organization as organization, position, status, filing_date
                    FROM main_org_candidates
                    UNION ALL
                    SELECT 'Sub Organization' as org_type, id,
                           CONCAT(last_name, ', ', first_name, ' ', middle_name) as full_name,
                           organization as organization, year as position, status, filing_date
                    FROM sub_org_candidates
                    ORDER BY filing_date DESC";
            break;
            
        case 'voting_activity':
            $reportTitle = 'Voting Activity Report';
            $sql = "SELECT 
            DATE(v.vote_date) as vote_date,
            COUNT(*) as votes_count,
            HOUR(v.vote_date) as vote_hour
            FROM votes v 
            GROUP BY DATE(v.vote_date), HOUR(v.vote_date)
            ORDER BY vote_date DESC, vote_hour ASC";
            break;
            
        case 'filing_status':
            $reportTitle = 'Filing Status Report';
            $sql = "SELECT 'Main Organization' as org_type, status, COUNT(*) as count
                    FROM main_org_candidates
                    GROUP BY status
                    UNION ALL
                    SELECT 'Sub Organization' as org_type, status, COUNT(*) as count
                    FROM sub_org_candidates
                    GROUP BY status
                    ORDER BY org_type, status";
            break;
            
        case 'complete_election':
            $reportTitle = 'Complete Election Report';
            // This will be a comprehensive report combining multiple data sources
            $reportData = generateCompleteElectionReport($conn);
            break;
    }
    
    if ($reportType !== 'complete_election') {
        $result = $conn->query($sql);
        if ($result) {
            $reportData = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();
        }
    }
}

function generateCompleteElectionReport($conn) {
    $data = [];
    
    // Voters statistics
    $votersResult = $conn->query("SELECT 
    COUNT(*) as total_voters,
    COUNT(CASE WHEN v.vote_date IS NOT NULL THEN 1 END) as voted_count
    FROM users u 
    LEFT JOIN votes v ON u.id = v.voter_id 
    WHERE u.role = 'voter'");
    $data['voters_stats'] = $votersResult->fetch_assoc();
    
    // Candidates statistics
    $mainCandidatesResult = $conn->query("SELECT status, COUNT(*) as count FROM main_org_candidates GROUP BY status");
    $subCandidatesResult = $conn->query("SELECT status, COUNT(*) as count FROM sub_org_candidates GROUP BY status");
    $data['main_candidates'] = $mainCandidatesResult->fetch_all(MYSQLI_ASSOC);
    $data['sub_candidates'] = $subCandidatesResult->fetch_all(MYSQLI_ASSOC);
    
    // Voting schedule
    $scheduleResult = $conn->query("SELECT * FROM voting_schedule ORDER BY updated_at DESC LIMIT 1");
    $data['schedule'] = $scheduleResult ? $scheduleResult->fetch_assoc() : null;
    
    // Daily voting activity
   $activityResult = $conn->query("SELECT 
    DATE(vote_date) as vote_date, 
    COUNT(*) as votes_count 
    FROM votes 
    GROUP BY DATE(vote_date) 
    ORDER BY vote_date DESC");
    $data['daily_activity'] = $activityResult->fetch_all(MYSQLI_ASSOC);
    
    return $data;
}

// Export functionality
if (isset($_POST['exportReport']) && isset($_SESSION['report_data'])) {
    $exportType = $_POST['export_type'];
    $reportData = unserialize($_SESSION['report_data']);
    $reportType = $_SESSION['report_type'];
    $reportTitle = $_SESSION['report_title'];
    
    if ($exportType === 'csv') {
        exportToCSV($reportData, $reportType, $reportTitle);
    } elseif ($exportType === 'excel') {
        exportToExcel($reportData, $reportType, $reportTitle);
    }
}

function exportToCSV($data, $type, $title) {
    $filename = sanitizeFilename($title) . '_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    if ($type === 'complete_election') {
        // Handle complex report structure
        fputcsv($output, ['COMPLETE ELECTION REPORT - ' . date('Y-m-d H:i:s')]);
        fputcsv($output, []);
        
        // Voters statistics
        fputcsv($output, ['VOTERS STATISTICS']);
        fputcsv($output, ['Total Registered Voters', $data['voters_stats']['total_voters']]);
        fputcsv($output, ['Voters Who Voted', $data['voters_stats']['voted_count']]);
        if ($data['voters_stats']['total_voters'] > 0) {
            fputcsv($output, ['Voting Percentage', round(($data['voters_stats']['voted_count'] / $data['voters_stats']['total_voters']) * 100, 2) . '%']);
        }
        fputcsv($output, []);
    } else {
        // Handle regular tabular data
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }
    }
    
    fclose($output);
    exit;
}

function exportToExcel($data, $type, $title) {
    // For simplicity, we'll export as CSV with .xlsx extension
    $filename = sanitizeFilename($title) . '_' . date('Y-m-d_H-i-s') . '.xlsx';
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    exportToCSV($data, $type, $title);
}

function sanitizeFilename($filename) {
    return preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
}

// Store report data in session for export
if (!empty($reportData)) {
    $_SESSION['report_data'] = serialize($reportData);
    $_SESSION['report_type'] = $reportType;
    $_SESSION['report_title'] = $reportTitle;
}

// Initialize message variable if not set
if (!isset($message)) {
    $message = '';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> CATSU iVote</title>
    <link rel="stylesheet" href="assets/css/style.css">
     <link rel="stylesheet" href="assets/css/admin.css">
</head>

<body>
    <div class="container">
        <div class="navigation">
            <ul>
                <li><a href="#"><span class="icon"><img src="catsu.png" alt="CATSU-ivote Logo" style="width:24px; height:24px;"></span><span class="title">CATSU-ivote Admin</span></a></li>
                <li><a href="#" id="dashboardBtn"><span class="icon"><ion-icon name="home-outline"></ion-icon></span><span class="title">Dashboard</span></a></li>
                <li><a href="#" id="userMgmtBtn"><span class="icon"><ion-icon name="people-outline"></ion-icon></span><span class="title">Users Management</span></a></li>
                <li><a href="#" id="filingBtn"><span class="icon"><ion-icon name="file-tray-full-outline"></ion-icon></span><span class="title">Filing Management</span></a></li>
                <li><a href="#" id="votersBtn"><span class="icon"><ion-icon name="person-outline"></ion-icon></span><span class="title">Voters</span></a></li>
                <li><a href="#" id="votingScheduleBtn"><span class="icon"><ion-icon name="calendar-outline"></ion-icon></span><span class="title">Schedule Voting Time</span></a></li>
                <li><a href="#" id="reportingBtn"><span class="icon"><ion-icon name="document-text-outline"></ion-icon></span><span class="title">Reporting Module</span></a></li>
                <li><a href="home.php"><span class="icon"><ion-icon name="log-out-outline"></ion-icon></span><span class="title">Log Out</span></a></li>
            </ul>
        </div>

        <div class="main">
            <div class="topbar">
                <div class="toggle"><ion-icon name="menu-outline"></ion-icon></div>
                <div class="search"><label><input type="text" placeholder="Search here"><ion-icon name="search-outline"></ion-icon></label></div>
                <div class="user"><img src="catsu.png" alt="User  Profile"></div>
            </div>

           <div id="dashboardSection">
    <div class="cardBox">
        <div class="card">
            <div>
                <div class="numbers"><?= number_format($totalVoters) ?></div>
                <div class="cardName">Total registered voters</div>
            </div>
            <div class="iconBx"><ion-icon name="people-outline"></ion-icon></div>
        </div>
        <div class="card">
            <div>
                <div class="numbers"><?= number_format($totalCandidates) ?></div>
                <div class="cardName">Total of candidates</div>
            </div>
            <div class="iconBx"><ion-icon name="people-outline"></ion-icon></div>
        </div>
        <div class="card">
            <div>
                <div class="numbers"><?= number_format($studentsVoted) ?></div>
                <div class="cardName">Students who voted</div>
            </div>
            <div class="iconBx"><ion-icon name="people-outline"></ion-icon></div>
        </div>
        <div class="card">
            <div>
                <div class="numbers"><?= $totalVoters > 0 ? round(($studentsVoted / $totalVoters) * 100, 1) . '%' : '0%' ?></div>
                <div class="cardName">Voting Percentage</div>
            </div>
            <div class="iconBx"><ion-icon name="analytics-outline"></ion-icon></div>
        </div>
    </div>

    <!-- Vote Tally Section -->
    <div style="max-width: 800px; margin: 40px auto; background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
        <h2 style="text-align: center; color: #4f46e5; margin-bottom: 30px;">Vote Tally by Organization</h2>
        
        <!-- Organization Selector -->
        <div style="max-width: 400px; margin: 0 auto 30px;">
            <label for="adminOrgSelect" style="font-weight: bold; font-size: 1.1rem; display: block; margin-bottom: 10px;">Select Organization:</label>
            <select id="adminOrgSelect" style="width: 100%; padding: 12px; border: 2px solid #cbd5e1; border-radius: 8px; font-size: 1rem;">
                <option value="">-- Select Organization --</option>
                <optgroup label="Main Organizations">
                    <option value="USC">USC (University Student Council)</option>
                    <option value="CSC">CSC (College Student Council)</option>
                </optgroup>
                <optgroup label="Sub Organizations">
                    <option value="ACCESS">ACCESS</option>
                    <option value="ASITS">ASITS</option>
                    <option value="BSEMC PromtPT">BSEMC PromtPT</option>
                    <option value="ISSO">ISSO</option>
                    <option value="LISAUX">LISAUX</option>
                    <option value="CICT-womens club">CICT-womens club</option>
                </optgroup>
            </select>
        </div>

        <!-- Candidates Tally Container -->
        <div id="adminTallyContainer">
            <p style="text-align: center; color: #6b7280; font-style: italic;">Select an organization to view vote tally</p>
        </div>
    </div>
</div>
            <!-- Users Management Section -->
            <div class="details" id="userManagementSection" style="display:none;">
                <div class="recentOrders">
                    <div class="cardHeader">
                        <h2>Users Management</h2>
                        <button class="btn" onclick="openModal('addModal')">Add User</button>
                    </div>
                    
                    <?php if ($message && (isset($_POST['addUser ']) || isset($_POST['editUser ']) || isset($_POST['deleteUser ']))): ?>
                        <div class="message"><?= htmlspecialchars($message) ?></div>
                    <?php endif; ?>
                    
                    <table>
                        <thead><tr><td>ID</td><td>Email</td><td>Student ID</td><td>Password</td><td>Role</td><td>Action</td></tr></thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * FROM users";
                            $result = $conn->query($sql);
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>
                                            <td>{$row['id']}</td>
                                            <td>{$row['email']}</td>
                                            <td>{$row['student_id']}</td>
                                            <td>{$row['password']}</td>
                                            <td>{$row['role']}</td>
                                            <td>
                                                <button class='action-btn edit' onclick=\"editUser ({$row['id']}, '{$row['email']}', '{$row['student_id']}', '{$row['password']}', '{$row['role']}')\">Edit</button>
                                                <form method='POST' style='display:inline;' onsubmit=\"return confirm('Are you sure you want to delete this user?');\">
                                                    <input type='hidden' name='id' value='{$row['id']}'/>
                                                    <button class='action-btn delete' type='submit' name='deleteUser '>Delete</button>
                                                </form>
                                            </td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6'>No users found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Filing Management Section -->
            <div class="details" id="filingSection" style="display:none;">
                <div class="recentOrders">
                    <div class="cardHeader"><h2>Filing Management</h2></div>

                    <?php if ($message && (isset($_POST['action']) && isset($_POST['filing_id']))): ?>
                        <div class="message"><?= htmlspecialchars($message) ?></div>
                    <?php endif; ?>

                    <!-- Main Organization Filings -->
                    <h3>Main Organization Filings</h3>
                    <?php if (count($mainFilings) === 0): ?>
                        <p>No filings found.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th><th>Name</th><th>Organization</th>
                                    <th>Position</th><th>Status</th><th>Preview</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mainFilings as $filing): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($filing['id'] ?? '') ?></td>
                                        <td><?= htmlspecialchars(($filing['last_name'] ?? '') . ', ' . ($filing['first_name'] ?? '') . ' ' . ($filing['middle_name'] ?? '')) ?></td>
                                        <!-- Updated: Use 'organization' field instead of 'main_org' -->
                                        <td><?= htmlspecialchars($filing['organization'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($filing['position'] ?? '') ?></td>
                                        <td><span class="status <?= htmlspecialchars($filing['status'] ?? '') ?>"><?= htmlspecialchars($filing['status'] ?? '') ?></span></td>
                                        <td>
                                            <button class="btn" 
                                                onclick="openPreviewModal(
                                                    '<?= $filing['id'] ?? '' ?>',
                                                    '<?= htmlspecialchars(($filing['last_name'] ?? '') . ', ' . ($filing['first_name'] ?? '') . ' ' . ($filing['middle_name'] ?? '')) ?>',
                                                    '<?= htmlspecialchars($filing['organization'] ?? '') ?>',
                                                    '<?= htmlspecialchars($filing['position'] ?? '') ?>',
                                                    '<?= htmlspecialchars($filing['status'] ?? '') ?>',
                                                    '<?= addslashes($filing['profile_pic'] ?? '') ?>',
                                                    '<?= addslashes($filing['comelec_form_1'] ?? '') ?>',
                                                    '<?= addslashes($filing['recommendation_letter'] ?? '') ?>',
                                                    '<?= addslashes($filing['prospectus'] ?? '') ?>',
                                                    '<?= addslashes($filing['clearance'] ?? '') ?>',
                                                    '<?= addslashes($filing['coe'] ?? '') ?>',
                                                    'main',
                                                    '<?= addslashes($filing['comment'] ?? '') ?>'
                                                )">Preview</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>

                    <!-- Sub Organization Filings -->
                    <h3>Sub Organization Filings</h3>
                    <?php if (count($subFilings) === 0): ?>
                        <p>No filings found.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th><th>Name</th><th>Organization</th>
                                    <th>Year</th><th>Status</th><th>Preview</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subFilings as $filing): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($filing['id'] ?? '') ?></td>
                                        <td><?= htmlspecialchars(($filing['last_name'] ?? '') . ', ' . ($filing['first_name'] ?? '') . ' ' . ($filing['middle_name'] ?? '')) ?></td>
                                        <!-- Updated: Use 'organization' field instead of 'sub_org', and header to "Organization" for consistency -->
                                        <td><?= htmlspecialchars($filing['organization'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($filing['year'] ?? '') ?></td>
                                        <td><span class="status <?= htmlspecialchars($filing['status'] ?? '') ?>"><?= htmlspecialchars($filing['status'] ?? '') ?></span></td>
                                        <td>
                                           <button class="btn" 
                                                onclick="openPreviewModal(
                                                    '<?= $filing['id'] ?? '' ?>',
                                                    '<?= htmlspecialchars(($filing['last_name'] ?? '') . ', ' . ($filing['first_name'] ?? '') . ' ' . ($filing['middle_name'] ?? '')) ?>',
                                                    '<?= htmlspecialchars($filing['organization'] ?? '') ?>',
                                                    '<?= htmlspecialchars($filing['year'] ?? '') ?>',
                                                    '<?= htmlspecialchars($filing['status'] ?? '') ?>',
                                                    '', '', '', '', '', '',
                                                    'sub',
                                                    '<?= htmlspecialchars($filing['block_address'] ?? '') ?>',
                                                    '<?= addslashes($filing['comment'] ?? '') ?>'
                                                )">Preview</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Voters Section -->
            <div class="details" id="votersSection" style="display:none;">
                <div class="recentOrders">
                    <div class="cardHeader">
                        <h2>Voters Management</h2>
                        <button class="btn" onclick="location.reload()">Refresh</button>
                    </div>
                    
                    <?php if (count($voters) === 0): ?>
                        <p style="text-align: center; color: #6b7280; font-style: italic; padding: 20px;">No voters found.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Student ID</th>
                                    <th>Email</th>
                                    <th>Vote Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($voters as $voter): ?>
                                    <?php 
                                        $voteStatus = !empty($voter['voted_at']) ? 'Voted' : 'Not Voted';
                                        $statusClass = !empty($voter['voted_at']) ? 'voted' : 'not-voted';
                                        $votedAt = !empty($voter['voted_at']) ? date('M j, Y g:i A', strtotime($voter['voted_at'])) : 'N/A';
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($voter['id']) ?></td>
                                        <td><?= htmlspecialchars($voter['student_id']) ?></td>
                                        <td><?= htmlspecialchars($voter['email']) ?></td>
                                        <td>
                                            <span class="vote-status <?= $statusClass ?>">
                                                <?= $voteStatus ?>
                                            </span>
                                            <?php if ($voteStatus === 'Voted'): ?>
                                                <br><small style="color: #6b7280;">Voted on: <?= $votedAt ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($voteStatus === 'Voted'): ?>
                                                <button class="action-btn" style="background-color: #f59e0b; color: white; padding: 4px 8px; font-size: 0.8rem;" 
                                                        onclick="alert('Reset vote functionality can be implemented here.')">
                                                    Reset Vote
                                                </button>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div style="margin-top: 20px; padding: 15px; background: #f8fafc; border-radius: 8px; border-left: 4px solid #4f46e5;">
                            <strong>Total Voters:</strong> <?= count($voters) ?><br>
                            <strong>Voted:</strong> <?= count(array_filter($voters, function($v) { return !empty($v['voted_at']); })) ?><br>
                            <strong>Not Voted:</strong> <?= count($voters) - count(array_filter($voters, function($v) { return !empty($v['voted_at']); })) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Voting Schedule Management Section -->
            <div class="details" id="votingScheduleSection" style="display:none;">
                <div class="recentOrders">
                    <div class="cardHeader"><h2>Voting Schedule Management</h2></div>

                    <?php if ($message && isset($_POST['updateVotingSchedule'])): ?>
                        <div class="message"><?= htmlspecialchars($message) ?></div>
                    <?php endif; ?>

                    <!-- Current Voting Status -->
                    <div class="voting-status-card">
                        <h3>Current Voting Status</h3>
                        <?php if ($votingSchedule): ?>
                            <div class="status-info">
                                <p><strong>Status:</strong> 
                                    <span class="voting-status <?= $votingSchedule['status'] ?>">
                                        <?= strtoupper($votingSchedule['status']) ?>
                                    </span>
                                </p>
                                <?php if ($votingSchedule['status'] === 'open'): ?>
                                    <p><strong>Active Period:</strong> 
                                        <?php 
                                            $isActive = isVotingActive($votingSchedule);
                                            echo $isActive ? '<span class="active-indicator">CURRENTLY ACTIVE</span>' : '<span class="inactive-indicator">NOT IN ACTIVE PERIOD</span>';
                                        ?>
                                    </p>
                                <?php endif; ?>
                                <p><strong>Start Date:</strong> <?= $votingSchedule['start_date'] ? date('M j, Y g:i A', strtotime($votingSchedule['start_date'])) : 'Not set' ?></p>
                                <p><strong>End Date:</strong> <?= $votingSchedule['end_date'] ? date('M j, Y g:i A', strtotime($votingSchedule['end_date'])) : 'Not set' ?></p>
                                <p><strong>Description:</strong> <?= htmlspecialchars($votingSchedule['description']) ?: 'No description' ?></p>
                                <p><strong>Last Updated:</strong> <?= date('M j, Y g:i A', strtotime($votingSchedule['updated_at'])) ?></p>
                            </div>
                        <?php else: ?>
                            <p class="no-schedule">No voting schedule has been set yet.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Update Voting Schedule Form -->
                    <div class="schedule-form-container">
                        <h3>Update Voting Schedule</h3>
                        <form method="POST" class="schedule-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="voting_status"><strong>Voting Status:</strong></label>
                                    <select name="voting_status" id="voting_status" required>
                                        <option value="closed" <?= (!$votingSchedule || $votingSchedule['status'] === 'closed') ? 'selected' : '' ?>>Closed</option>
                                        <option value="open" <?= ($votingSchedule && $votingSchedule['status'] === 'open') ? 'selected' : '' ?>>Open</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="start_date"><strong>Start Date & Time:</strong></label>
                                    <input type="datetime-local" name="start_date" id="start_date" 
                                           value="<?= $votingSchedule ? date('Y-m-d\TH:i', strtotime($votingSchedule['start_date'])) : '' ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="end_date"><strong>End Date & Time:</strong></label>
                                    <input type="datetime-local" name="end_date" id="end_date" 
                                           value="<?= $votingSchedule ? date('Y-m-d\TH:i', strtotime($votingSchedule['end_date'])) : '' ?>" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group full-width">
                                    <label for="description"><strong>Description/Notes:</strong></label>
                                    <textarea name="description" id="description" rows="3" placeholder="Optional description or notes about this voting period"><?= $votingSchedule ? htmlspecialchars($votingSchedule['description']) : '' ?></textarea>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" name="updateVotingSchedule" class="btn update-btn">Update Voting Schedule</button>
                                <?php if ($votingSchedule && $votingSchedule['status'] === 'open'): ?>
                                    <button type="button" class="btn emergency-close" onclick="emergencyClose()">Emergency Close</button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>

                    <!-- Quick Actions -->
                    <div class="quick-actions" style="background: #fef3c7; border: 1px solid #fbbf24; border-radius: 12px; padding: 20px;">
                        <h3 style="color: #92400e; margin-bottom: 15px; font-size: 1.1rem;">Quick Actions</h3>
                        <div class="action-buttons" style="display: flex; gap: 15px; flex-wrap: wrap;">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="voting_status" value="open">
                                <input type="hidden" name="start_date" value="<?= date('Y-m-d\TH:i') ?>">
                                <input type="hidden" name="end_date" value="<?= date('Y-m-d\TH:i', strtotime('+8 hours')) ?>">
                                <input type="hidden" name="description" value="Quick open for 8 hours">
                                <button type="submit" name="updateVotingSchedule" class="btn" style="background-color: #059669;">Open Voting Now (4 hours)</button>
                            </form>
                            
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="voting_status" value="closed">
                                <input type="hidden" name="start_date" value="<?= date('Y-m-d\TH:i') ?>">
                                <input type="hidden" name="end_date" value="<?= date('Y-m-d\TH:i') ?>">
                                <input type="hidden" name="description" value="Voting closed by admin">
                                <button type="submit" name="updateVotingSchedule" class="btn" style="background-color: #dc2626;">Close Voting Now</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reporting Module Section -->
            <div class="details" id="reportingSection" style="display:none;">
                <div class="recentOrders">
                    <div class="cardHeader">
                        <h2>Reporting Module</h2>
                        <div style="display: flex; gap: 10px;" id="reportActions">
                            <?php if (!empty($reportData)): ?>
                            <button class="btn" onclick="window.print()">Print Report</button>
                            <button class="btn" onclick="openModal('exportModal')">Export Report</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="report-generator">
                        <h3>Generate Reports</h3>
                        <form method="POST" class="report-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="report_type"><strong>Select Report Type:</strong></label>
                                    <select name="report_type" id="report_type" required>
                                        <option value="">-- Select Report Type --</option>
                                        <option value="voters_summary" <?= $reportType == 'voters_summary' ? 'selected' : '' ?>>Voters Summary Report</option>
                                        <option value="candidates_summary" <?= $reportType == 'candidates_summary' ? 'selected' : '' ?>>Candidates Summary Report</option>
                                        <option value="voting_activity" <?= $reportType == 'voting_activity' ? 'selected' : '' ?>>Voting Activity Report</option>
                                        <option value="filing_status" <?= $reportType == 'filing_status' ? 'selected' : '' ?>>Filing Status Report</option>
                                        <option value="complete_election" <?= $reportType == 'complete_election' ? 'selected' : '' ?>>Complete Election Report</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <button type="submit" name="generateReport" class="btn generate-btn">Generate Report</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Report Display -->
                    <?php if (!empty($reportData) && $reportType !== 'complete_election'): ?>
                    <div class="report-content printable-content">
                        <div class="report-header">
                            <h2><?= htmlspecialchars($reportTitle) ?></h2>
                            <p>Generated on: <?= date('F j, Y g:i A') ?></p>
                            <p>CATSU iVote System</p>
                        </div>
                        
                        <div class="report-data">
                            <?php if ($reportType === 'voters_summary'): ?>
                                <table class="report-table">
                                    <thead>
                                        <tr>
                                            <th>Student ID</th>
                                            <th>Email</th>
                                            <th>Vote Status</th>
                                            <th>Voted At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reportData as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['student_id']) ?></td>
                                            <td><?= htmlspecialchars($row['email']) ?></td>
                                            <td>
                                                <span class="vote-status <?= $row['vote_status'] === 'Voted' ? 'voted' : 'not-voted' ?>">
                                                    <?= $row['vote_status'] ?>
                                                </span>
                                            </td>
                                            <td><?= $row['voted_at'] ? date('M j, Y g:i A', strtotime($row['voted_at'])) : 'N/A' ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                
                                <div class="report-summary">
                                    <?php
                                    $totalVotersReport = count($reportData);
                                    $votedCount = count(array_filter($reportData, function($row) { return $row['vote_status'] === 'Voted'; }));
                                    $votingPercentage = $totalVotersReport > 0 ? round(($votedCount / $totalVotersReport) * 100, 2) : 0;
                                    ?>
                                    <h4>Summary Statistics:</h4>
                                    <p>Total Registered Voters: <strong><?= $totalVotersReport ?></strong></p>
                                    <p>Voters Who Voted: <strong><?= $votedCount ?></strong></p>
                                    <p>Voters Who Haven't Voted: <strong><?= $totalVotersReport - $votedCount ?></strong></p>
                                    <p>Voting Percentage: <strong><?= $votingPercentage ?>%</strong></p>
                                </div>
                            
                            <?php elseif ($reportType === 'candidates_summary'): ?>
                                <table class="report-table">
                                    <thead>
                                        <tr>
                                            <th>Organization Type</th>
                                            <th>Candidate Name</th>
                                            <th>Organization</th>
                                            <th>Position/Year</th>
                                            <th>Status</th>
                                            <th>Filing Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reportData as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['org_type']) ?></td>
                                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                                            <td><?= htmlspecialchars($row['organization']) ?></td>
                                            <td><?= htmlspecialchars($row['position']) ?></td>
                                            <td><span class="status <?= htmlspecialchars($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                                            <td><?= date('M j, Y', strtotime($row['filing_date'])) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            
                            <?php elseif ($reportType === 'voting_activity'): ?>
                                <table class="report-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Hour</th>
                                            <th>Votes Count</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reportData as $row): ?>
                                        <tr>
                                            <td><?= date('M j, Y', strtotime($row['vote_date'])) ?></td>
                                            <td><?= str_pad($row['vote_hour'], 2, '0', STR_PAD_LEFT) ?>:00</td>
                                            <td><?= $row['votes_count'] ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            
                            <?php elseif ($reportType === 'filing_status'): ?>
                                <table class="report-table">
                                    <thead>
                                        <tr>
                                            <th>Organization Type</th>
                                            <th>Status</th>
                                            <th>Count</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reportData as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['org_type']) ?></td>
                                            <td><span class="status <?= htmlspecialchars($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                                            <td><?= $row['count'] ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php elseif (!empty($reportData) && $reportType === 'complete_election'): ?>
                    <!-- Complete Election Report -->
                    <div class="report-content printable-content">
                        <div class="report-header">
                            <h2>Complete Election Report</h2>
                            <p>Generated on: <?= date('F j, Y g:i A') ?></p>
                            <p>CATSU iVote System</p>
                        </div>
                        
                        <div class="report-sections">
                            <!-- Voters Statistics Section -->
                            <div class="report-section">
                                <h3>1. Voters Statistics</h3>
                                <div class="stats-grid">
                                    <div class="stat-card">
                                        <div class="stat-number"><?= $reportData['voters_stats']['total_voters'] ?></div>
                                        <div class="stat-label">Total Registered Voters</div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="stat-number"><?= $reportData['voters_stats']['voted_count'] ?></div>
                                        <div class="stat-label">Voters Who Voted</div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="stat-number">
                                            <?= round(($reportData['voters_stats']['voted_count'] / $reportData['voters_stats']['total_voters']) * 100, 1) ?>%
                                        </div>
                                        <div class="stat-label">Voting Turnout</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Candidates Statistics Section -->
                            <div class="report-section">
                                <h3>2. Candidates Statistics</h3>
                                <div class="candidates-stats">
                                    <h4>Main Organization Candidates:</h4>
                                    <table class="mini-table">
                                        <thead><tr><th>Status</th><th>Count</th></tr></thead>
                                        <tbody>
                                            <?php foreach ($reportData['main_candidates'] as $stat): ?>
                                            <tr>
                                                <td><span class="status <?= $stat['status'] ?>"><?= $stat['status'] ?></span></td>
                                                <td><?= $stat['count'] ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    
                                    <h4>Sub Organization Candidates:</h4>
                                    <table class="mini-table">
                                        <thead><tr><th>Status</th><th>Count</th></tr></thead>
                                        <tbody>
                                            <?php foreach ($reportData['sub_candidates'] as $stat): ?>
                                            <tr>
                                                <td><span class="status <?= $stat['status'] ?>"><?= $stat['status'] ?></span></td>
                                                <td><?= $stat['count'] ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Voting Schedule Section -->
                            <?php if ($reportData['schedule']): ?>
                            <div class="report-section">
                                <h3>3. Voting Schedule</h3>
                                <table class="info-table">
                                    <tr><td><strong>Status:</strong></td><td><span class="voting-status <?= $reportData['schedule']['status'] ?>"><?= strtoupper($reportData['schedule']['status']) ?></span></td></tr>
                                    <tr><td><strong>Start Date:</strong></td><td><?= date('M j, Y g:i A', strtotime($reportData['schedule']['start_date'])) ?></td></tr>
                                    <tr><td><strong>End Date:</strong></td><td><?= date('M j, Y g:i A', strtotime($reportData['schedule']['end_date'])) ?></td></tr>
                                    <tr><td><strong>Description:</strong></td><td><?= htmlspecialchars($reportData['schedule']['description']) ?: 'No description' ?></td></tr>
                                </table>
                            </div>
                            <?php endif; ?>

                            <!-- Daily Voting Activity Section -->
                            <?php if (!empty($reportData['daily_activity'])): ?>
                            <div class="report-section">
                                <h3>4. Daily Voting Activity</h3>
                                <table class="report-table">
                                    <thead>
                                        <tr><th>Date</th><th>Votes Count</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reportData['daily_activity'] as $activity): ?>
                                        <tr>
                                            <td><?= date('M j, Y', strtotime($activity['vote_date'])) ?></td>
                                            <td><?= $activity['votes_count'] ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (empty($reportData) && !isset($_POST['generateReport'])): ?>
                    <div class="no-report">
                        <p>Select a report type and click "Generate Report" to view the report.</p>
                    </div>
                    <?php elseif (empty($reportData) && isset($_POST['generateReport'])): ?>
                    <div class="no-data">
                        <p>No data found for the selected report type.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Add User Modal -->
        <div id="addModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('addModal')">&times;</span>
                <h3>Add User</h3>
                <form method="POST">
                    <input type="email" name="email" placeholder="Email" required/>
                    <input type="text" name="student_id" placeholder="Student ID" required/>
                    <input type="text" name="password" placeholder="Password" required/>
                    <select name="role" required>
                        <option value="voter">Voter</option>
                        <option value="admin">Admin</option>
                        <option value="candidate">Candidate</option>
                    </select>
                    <button class="btn" type="submit" name="addUser  ">Save</button>
                </form>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('editModal')">&times;</span>
                <h3>Edit User</h3>
                <form method="POST">
                    <input type="hidden" name="id" id="edit_id"/>
                    <input type="email" name="email" id="edit_email" required/>
                    <input type="text" name="student_id" id="edit_student_id" required/>
                    <input type="text" name="password" id="edit_password" required/>
                    <select name="role" id="edit_role" required>
                        <option value="voter">Voter</option>
                        <option value="admin">Admin</option>
                        <option value="candidate">Candidate</option>
                    </select>
                    <button class="btn" type="submit" name="editUser  ">Update</button>
                </form>
            </div>
        </div>

        <!-- Filing Preview Modal -->
<div id="previewModal" class="modal">
    <div class="modal-content" style="max-width: 800px; width: 90%; background: #fff; border-radius: 16px; padding: 0; box-shadow: 0 20px 60px rgba(0,0,0,0.3); overflow: hidden;">
        <div style="background: linear-gradient(135deg, #4f46e5, #7c3aed); padding: 20px; position: relative;">
            <span class="close" onclick="closeModal('previewModal')" style="position: absolute; top: 15px; right: 20px; color: white; font-size: 32px; font-weight: bold; cursor: pointer; transition: transform 0.2s;">&times;</span>
            <h3 style="color: white; margin: 0; font-size: 1.5rem; font-weight: 600;">Candidate Filing Preview</h3>
        </div>
        
        <div style="padding: 30px; max-height: 70vh; overflow-y: auto;">
            <div id="previewContent"></div>
            
            <form method="post" id="previewForm" style="margin-top: 30px;">
                <input type="hidden" name="filing_id" id="preview_filing_id">
                <input type="hidden" name="type" id="preview_type">
                <input type="hidden" name="action" id="preview_action">
                
                <div style="background: #f8fafc; border-radius: 12px; padding: 20px; border: 2px solid #e2e8f0;">
                    <label for="comment" style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 10px; font-size: 1rem;">
                        <ion-icon name="chatbox-outline" style="vertical-align: middle; font-size: 1.2rem;"></ion-icon>
                        Admin Comment:
                    </label>
                    <textarea 
                        name="comment" 
                        id="comment" 
                        rows="5" 
                        placeholder="Enter your comment, feedback, or reason for decision here..."
                        style="width: 100%; padding: 15px; border: 2px solid #cbd5e1; border-radius: 10px; font-size: 0.95rem; font-family: inherit; resize: vertical; transition: all 0.3s ease; background: white;"
                        onfocus="this.style.borderColor='#4f46e5'; this.style.boxShadow='0 0 0 3px rgba(79,70,229,0.1)';"
                        onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none';"
                    ></textarea>
                    <p style="margin: 10px 0 0 0; color: #64748b; font-size: 0.85rem;">
                        <ion-icon name="information-circle-outline" style="vertical-align: middle;"></ion-icon>
                        This comment will be visible to the candidate.
                    </p>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 15px; margin-top: 25px; padding-top: 20px; border-top: 2px solid #e2e8f0;">
                    <button 
                        type="submit" 
                        class="accept" 
                        onclick="setAction('accept')"
                        style="background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 12px 30px; border: none; border-radius: 10px; font-weight: 600; font-size: 1rem; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(16,185,129,0.3);"
                        onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(16,185,129,0.4)';"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(16,185,129,0.3)';"
                    >
                        <ion-icon name="checkmark-circle" style="font-size: 1.3rem;"></ion-icon>
                        Accept Application
                    </button>
                    <button 
                        type="submit" 
                        class="reject" 
                        onclick="setAction('reject')"
                        style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white; padding: 12px 30px; border: none; border-radius: 10px; font-weight: 600; font-size: 1rem; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(239,68,68,0.3);"
                        onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(239,68,68,0.4)';"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(239,68,68,0.3)';"
                    >
                        <ion-icon name="close-circle" style="font-size: 1.3rem;"></ion-icon>
                        Reject Application
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Image Preview Modal -->
<div id="imagePreviewModal" class="modal" onclick="closeImagePreview(event)">
    <div style="position: relative; max-width: 90%; max-height: 90vh; margin: 2% auto;">
        <span onclick="closeModal('imagePreviewModal')" style="position: absolute; top: -40px; right: 0; color: white; font-size: 40px; font-weight: bold; cursor: pointer; z-index: 1001; text-shadow: 0 2px 4px rgba(0,0,0,0.5);">&times;</span>
        <img id="previewImage" src="" style="max-width: 100%; max-height: 85vh; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.5); display: block;">
        <p id="imageFileName" style="color: white; text-align: center; margin-top: 15px; font-size: 1.1rem; text-shadow: 0 2px 4px rgba(0,0,0,0.5);"></p>
    </div>
</div>

        <!-- Export Modal -->
        <div id="exportModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('exportModal')">&times;</span>
                <h3>Export Report</h3>
                <form method="POST">
                    <div class="export-options">
                        <p>Choose export format:</p>
                        <div class="radio-group">
                            <label><input type="radio" name="export_type" value="csv" checked> CSV (.csv)</label>
                            <label><input type="radio" name="export_type" value="excel"> Excel (.xlsx)</label>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="exportReport" class="btn">Export</button>
                        <button type="button" class="btn" onclick="closeModal('exportModal')">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

    <script>
        const dashboardSection = document.getElementById("dashboardSection");
        const userManagementSection = document.getElementById("userManagementSection");
        const filingSection = document.getElementById("filingSection");
        const votingScheduleSection = document.getElementById("votingScheduleSection");
        const votersSection = document.getElementById("votersSection");
        const reportingSection = document.getElementById("reportingSection");

        document.getElementById("dashboardBtn").addEventListener("click", function(e){
            e.preventDefault();
            showSection("dashboard");
        });

        document.getElementById("userMgmtBtn").addEventListener("click", function(e){
            e.preventDefault();
            showSection("userManagement");
        });

        document.getElementById("filingBtn").addEventListener("click", function(e){
            e.preventDefault();
            showSection("filing");
        });

        document.getElementById("votersBtn").addEventListener("click", function(e) {
            e.preventDefault();
            showSection("voters");
        });

        document.getElementById("votingScheduleBtn").addEventListener("click", function(e){
            e.preventDefault();
            showSection("votingSchedule");
        });

        document.getElementById("reportingBtn").addEventListener("click", function(e){
            e.preventDefault();
            showSection("reporting");
        });

        function showSection(section) {
            // Hide all sections
            dashboardSection.style.display = "none";
            userManagementSection.style.display = "none";
            filingSection.style.display = "none";
            votingScheduleSection.style.display = "none";
            votersSection.style.display = "none";
            reportingSection.style.display = "none";
            
            // Show selected section
            switch(section) {
                case "dashboard":
                    dashboardSection.style.display = "block";
                    break;
                case "userManagement":
                    userManagementSection.style.display = "block";
                    break;
                case "filing":
                    filingSection.style.display = "block";
                    break;
                case "votingSchedule":
                    votingScheduleSection.style.display = "block";
                    break;
                case "voters":
                    votersSection.style.display = "block";
                    break;
                case "reporting":
                    reportingSection.style.display = "block";
                    break;
            }
        }

        function emergencyClose() {
            if (confirm("Are you sure you want to immediately close voting? This action will stop all ongoing voting.")) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="voting_status" value="closed">
                    <input type="hidden" name="start_date" value="<?= date('Y-m-d\TH:i') ?>">
                    <input type="hidden" name="end_date" value="<?= date('Y-m-d\TH:i') ?>">
                    <input type="hidden" name="description" value="Emergency closure by admin">
                    <input type="hidden" name="updateVotingSchedule" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Validate date inputs
        document.getElementById('start_date').addEventListener('change', function() {
            const startDate = new Date(this.value);
            const endDateInput = document.getElementById('end_date');
            const endDate = new Date(endDateInput.value);
            
            if (endDate <= startDate) {
                const newEndDate = new Date(startDate.getTime() + (60 * 60 * 1000));
                endDateInput.value = newEndDate.toISOString().slice(0, 16);
            }
        });

        document.getElementById('end_date').addEventListener('change', function() {
            const endDate = new Date(this.value);
            const startDate = new Date(document.getElementById('start_date').value);
            
            if (endDate <= startDate) {
                alert('End date must be after start date');
                const newEndDate = new Date(startDate.getTime() + (60 * 60 * 1000));
                this.value = newEndDate.toISOString().slice(0, 16);
            }
        });

        function openModal(id) { 
            document.getElementById(id).style.display = "block"; 
        }
        
        function closeModal(id) { 
            document.getElementById(id).style.display = "none"; 
        }

        function editUser (id, email, student_id, password, role){
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_student_id').value = student_id;
            document.getElementById('edit_password').value = password;
            document.getElementById('edit_role').value = role;
            openModal('editModal');
        }

       function openPreviewModal(id, name, org, position, status, profile = '', form1 = '', rec = '', prospectus = '', clearance = '', coe = '', type = 'main', block = '', comment = '') {
    let content = `
        <div style="background: linear-gradient(135deg, #f0f9ff, #e0f2fe); padding: 20px; border-radius: 12px; margin-bottom: 20px; border-left: 4px solid #4f46e5;">
            <p><strong>Name:</strong> ${name}</p>
            <p><strong>Organization:</strong> ${org}</p>
            <p><strong>Position/Year:</strong> ${position}</p>
            ${block ? `<p><strong>Block Address:</strong> ${block}</p>` : ""}
            <p><strong>Current Status:</strong> <span class="status ${status}">${status}</span></p>
        </div>
    `;

    if (type === "main") {
        content += `<p style="font-weight: 600; color: #1e293b; font-size: 1.1rem; margin: 20px 0 15px 0;">📁 Submitted Documents:</p><ul>`;
        
        if (profile) content += `<li><a href="#" onclick="showImage('${profile}', 'Profile Picture'); return false;">View Profile Picture</a></li>`;
        if (form1) content += `<li><a href="#" onclick="showImage('${form1}', 'COMELEC Form 1'); return false;">View COMELEC Form 1</a></li>`;
        if (rec) content += `<li><a href="#" onclick="showImage('${rec}', 'Recommendation Letter'); return false;">View Recommendation Letter</a></li>`;
        if (prospectus) content += `<li><a href="#" onclick="showImage('${prospectus}', 'Prospectus'); return false;">View Prospectus</a></li>`;
        if (clearance) content += `<li><a href="#" onclick="showImage('${clearance}', 'Clearance'); return false;">View Clearance</a></li>`;
        if (coe) content += `<li><a href="#" onclick="showImage('${coe}', 'Certificate of Enrollment'); return false;">View Certificate of Enrollment</a></li>`;
        
        content += `</ul>`;
    }

    if(comment) {
        content += `
            <div style="background: #fef3c7; border: 2px solid #f59e0b; border-radius: 12px; padding: 15px; margin-top: 20px;">
                <p style="font-weight: 600; color: #92400e; margin-bottom: 10px;">
                    <ion-icon name="warning" style="vertical-align: middle; font-size: 1.2rem;"></ion-icon>
                    Previous Admin Comment:
                </p>
                <p style="white-space: pre-wrap; background: white; padding: 15px; border-radius: 8px; color: #1e293b; margin: 0;">${comment}</p>
            </div>
        `;
    }

    document.getElementById("previewContent").innerHTML = content;
    document.getElementById("preview_filing_id").value = id;
    document.getElementById("preview_type").value = type;
    document.getElementById("comment").value = comment || '';
    openModal("previewModal");
}

function showImage(imagePath, fileName) {
    // Remove any leading/trailing whitespace
    imagePath = imagePath.trim();
    
    // Show the image in the preview modal
    document.getElementById('previewImage').src = imagePath;
    document.getElementById('imageFileName').textContent = fileName;
    openModal('imagePreviewModal');
}

function closeImagePreview(event) {
    // Close only if clicking outside the image
    if (event.target.id === 'imagePreviewModal') {
        closeModal('imagePreviewModal');
    }
}
        function setAction(act) {
            document.getElementById("preview_action").value = act;
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const modals = document.getElementsByClassName('modal');
            for (let i = 0; i < modals.length; i++) {
                if (event.target == modals[i]) {
                    modals[i].style.display = "none";
                }
            }
        }
        // Admin Vote Tally Functionality
document.getElementById('adminOrgSelect').addEventListener('change', function() {
    const org = this.value;
    const container = document.getElementById('adminTallyContainer');
    
    if (!org) {
        container.innerHTML = '<p style="text-align: center; color: #6b7280; font-style: italic;">Select an organization to view vote tally</p>';
        return;
    }

    // Show loading
    container.innerHTML = '<p style="text-align: center;">Loading vote tally...</p>';

    // Position hierarchy for sorting
    const positionOrder = [
        "President", "Vice President", "Executive Secretary", "Finance Secretary",
        "Budget Secretary", "Auditor", "Public Information Secretary", "Property Custodian",
        "Senators", "Legislators", "Year Representative", "Representative", "Other"
    ];

    // Fetch vote tally data
    fetch('fetch_org_candidates.php?organization=' + encodeURIComponent(org))
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                container.innerHTML = '<p style="color: red; text-align: center;">Error: ' + data.error + '</p>';
                return;
            }

            if (!data.candidates || data.candidates.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: #6b7280;">No accepted candidates found for ' + org + '.</p>';
                return;
            }

            // Sort candidates by position
            const sortedCandidates = data.candidates.sort((a, b) => {
                const posA = positionOrder.indexOf(a.position) !== -1 ? positionOrder.indexOf(a.position) : positionOrder.length;
                const posB = positionOrder.indexOf(b.position) !== -1 ? positionOrder.indexOf(b.position) : positionOrder.length;
                return posA - posB;
            });

            // Build HTML table
            let html = `<h3 style="text-align: center; margin-bottom: 20px; color: #1f2937;">Vote Tally for ${org}</h3>`;
            html += `<table>`;
            html += `<thead><tr>
                        <th>Candidate Name</th>
                        <th>Position</th>
                        <th style="text-align: center;">Total Votes</th>
                    </tr></thead><tbody>`;

            sortedCandidates.forEach(c => {
                const fullName = [c.first_name, c.middle_name, c.last_name].filter(Boolean).join(' ');
                const position = c.position || 'Representative';
                html += `<tr>
                            <td><strong>${fullName}</strong></td>
                            <td><span class="tally-position">${position}</span></td>
                            <td style="text-align: center;">${c.total_votes || 0}</td>
                        </tr>`;
            });

            html += `</tbody></table>`;
            
            // Add summary
            const totalVotes = sortedCandidates.reduce((sum, c) => sum + (parseInt(c.total_votes) || 0), 0);
            html += `<div style="margin-top: 20px; padding: 15px; background: #f0f9ff; border-left: 4px solid #0ea5e9; border-radius: 6px;">`;
            html += `<p style="margin: 0; color: #0c4a6e;"><strong>Total Votes Cast for ${org}:</strong> ${totalVotes}</p>`;
            html += `</div>`;

            container.innerHTML = html;
        })
        .catch(err => {
            container.innerHTML = '<p style="color: red; text-align: center;">Error loading vote tally.</p>';
            console.error(err);
        });
});

        // Initialize with dashboard view
        showSection('dashboard');
    </script>
</body>
</html>
            