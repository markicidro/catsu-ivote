<!DOCTYPE html>
<?php
session_start();

date_default_timezone_set('Asia/Manila'); // Set your timezone
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'commissioner') {
    header('Location: home.php');
    exit;
}

// Database connection (update with your credentials)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ivote_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ------------------- VOTING SCHEDULE CHECK -------------------

// Function to check if voting is currently active
function canVote($conn) {
    $result = $conn->query("SELECT * FROM voting_schedule WHERE status='open' ORDER BY updated_at DESC LIMIT 1");
    
    if (!$result || $result->num_rows === 0) {
        return ['can_vote' => false, 'message' => 'Voting is currently closed by administrator.'];
    }
    
    $schedule = $result->fetch_assoc();
    $now = new DateTime();
    $startDate = new DateTime($schedule['start_date']);
    $endDate = new DateTime($schedule['end_date']);
    
    if ($now < $startDate) {
        return [
            'can_vote' => false, 
            'message' => 'Voting will start on ' . $startDate->format('F j, Y g:i A'),
            'schedule' => $schedule
        ];
    }
    
    if ($now > $endDate) {
        return [
            'can_vote' => false, 
            'message' => 'Voting period ended on ' . $endDate->format('F j, Y g:i A'),
            'schedule' => $schedule
        ];
    }
    
    return [
        'can_vote' => true, 
        'message' => 'Voting is currently active until ' . $endDate->format('F j, Y g:i A'),
        'schedule' => $schedule
    ];
}

// Check voting status
$votingStatus = canVote($conn);

$message = "";
$messageType = "";

// Handle Candidate Approval/Rejection (for commissioners) - Kept for potential use, but not triggered in this view
if (isset($_POST['approveCandidate'])) {
    $candidate_id = intval($_POST['candidate_id'] ?? 0);
    $table = $_POST['table'] ?? ''; // 'main_org_candidates' or 'sub_org_candidates'
    
    if ($candidate_id > 0 && in_array($table, ['main_org_candidates', 'sub_org_candidates'])) {
        $updateStmt = $conn->prepare("UPDATE $table SET status = 'Accepted' WHERE id = ?");
        $updateStmt->bind_param("i", $candidate_id);
        
        if ($updateStmt->execute()) {
            $message = "Candidate approved successfully!";
            $messageType = "success";
        } else {
            $message = "Error approving candidate: " . $updateStmt->error;
            $messageType = "error";
        }
        $updateStmt->close();
    }
}

if (isset($_POST['rejectCandidate'])) {
    $candidate_id = intval($_POST['candidate_id'] ?? 0);
    $table = $_POST['table'] ?? '';
    $rejection_reason = $_POST['rejection_reason'] ?? '';
    
    if ($candidate_id > 0 && in_array($table, ['main_org_candidates', 'sub_org_candidates']) && !empty($rejection_reason)) {
        $updateStmt = $conn->prepare("UPDATE $table SET status = 'Rejected', rejection_reason = ? WHERE id = ?");
        $updateStmt->bind_param("si", $rejection_reason, $candidate_id);
        
        if ($updateStmt->execute()) {
            $message = "Candidate rejected successfully!";
            $messageType = "success";
        } else {
            $message = "Error rejecting candidate: " . $updateStmt->error;
            $messageType = "error";
        }
        $updateStmt->close();
    } else {
        $message = "Invalid rejection. Please provide a reason.";
        $messageType = "error";
    }
}

// Fetch stats for dashboard
$totalCandidates = 0;
$pendingCandidates = 0;
$totalVotes = 0;
$acceptedCandidates = 0;

if ($mainQuery = $conn->query("SELECT COUNT(*) as count FROM main_org_candidates")) {
    $totalCandidates += $mainQuery->fetch_assoc()['count'];
    $pendingMain = $conn->query("SELECT COUNT(*) as count FROM main_org_candidates WHERE status='Pending'");
    $pendingCandidates += $pendingMain->fetch_assoc()['count'];
    $acceptedMain = $conn->query("SELECT COUNT(*) as count FROM main_org_candidates WHERE status='Accepted'");
    $acceptedCandidates += $acceptedMain->fetch_assoc()['count'];
}

if ($subQuery = $conn->query("SELECT COUNT(*) as count FROM sub_org_candidates")) {
    $totalCandidates += $subQuery->fetch_assoc()['count'];
    $pendingSub = $conn->query("SELECT COUNT(*) as count FROM sub_org_candidates WHERE status='Pending'");
    $pendingCandidates += $pendingSub->fetch_assoc()['count'];
    $acceptedSub = $conn->query("SELECT COUNT(*) as count FROM sub_org_candidates WHERE status='Accepted'");
    $acceptedCandidates += $acceptedSub->fetch_assoc()['count'];
}

if ($votesQuery = $conn->query("SELECT COUNT(*) as count FROM votes")) {
    $totalVotes = $votesQuery->fetch_assoc()['count'];
}
?>

<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CATSU iVote - Commissioner Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css" />
    <style>
        /* Reuse all styles from the original code */
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow-y: auto;
            background: rgba(0, 0, 0, 0.6);
        }

        .modal-content {
            background: #fff;
            margin: 5% auto;
            padding: 20px;
            width: 80%;
            max-width: 900px;
            border-radius: 10px;
            position: relative;
        }

        .modal-content h1.title {
            text-align: center;
            margin-bottom: 20px;
        }

        .close {
            position: absolute;
            top: 10px;
            right: 20px;
            cursor: pointer;
            font-size: 28px;
            font-weight: bold;
        }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .step-box {
            background: #f3f4f6;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .step-icon {
            font-size: 28px;
            margin-bottom: 10px;
            color: #4f46e5;
        }

        .step-number {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .step-title {
            font-size: 1.1rem;
            margin-bottom: 5px;
        }

        .step-description {
            font-size: 0.9rem;
            color: #374151;
        }

        /* Checkbox container */
        .dont-show {
            margin-top: 15px;
            display: flex;
            align-items: center;
            font-size: 0.9rem;
        }

        .dont-show input {
            margin-right: 5px;
        }

        /* Voting Status Alert */
        .voting-alert {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            border: 2px solid #f87171;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
            box-shadow: 0 4px 12px rgba(248, 113, 113, 0.3);
        }

        .voting-alert.active {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            border-color: #10b981;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .voting-alert h3 {
            margin: 0 0 10px 0;
            font-size: 1.3rem;
            color: #dc2626;
        }

        .voting-alert.active h3 {
            color: #059669;
        }

        .voting-alert p {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 500;
            color: #7f1d1d;
        }

        .voting-alert.active p {
            color: #047857;
        }

        /* Message styles */
        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin: 20px 0;
            font-weight: 600;
            text-align: center;
        }

        .message.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }

        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        /* Candidates Profile Section Styles for Commissioner (Only Accepted) */
        .candidates-profile-section {
            background: #fff;
            border-radius: 12px;
            padding: 30px;
            margin: 20px 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .candidates-profile-section h2 {
            text-align: center;
            color: #4f46e5;
            margin-bottom: 10px;
            font-size: 2.2rem;
            font-weight: 700;
        }

        .organization-section {
            margin-bottom: 40px;
        }

        .org-title {
            color: #1e293b;
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 3px solid #4f46e5;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .candidates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .candidate-card {
            background: #ecfdf5; /* Green tint for accepted */
            border: 2px solid #10b981;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .candidate-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.15);
            border-color: #059669;
        }

        .candidate-image {
            width: 100px;
            height: 100px;
            margin: 0 auto 15px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid #4f46e5;
            background: #f3f4f6;
        }

        .candidate-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .candidate-info {
            text-align: center;
        }

        .candidate-name {
            color: #1e293b;
            font-size: 1.3rem;
            font-weight: 700;
            margin: 0 0 8px 0;
        }

        .candidate-position {
            color: #4f46e5;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .candidate-subtitle {
            color: #6b7280;
            font-size: 0.95rem;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .candidate-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: center;
        }

        .org-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            color: white;
            margin-bottom: 5px;
        }

        .org-badge.main-org {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
        }

        .org-badge.sub-org {
            background: linear-gradient(135deg, #059669, #10b981);
        }

        .college-subtitle {
            color: #374151;
            font-size: 0.9rem;
            background: #e5e7eb;
            padding: 4px 10px;
            border-radius: 12px;
            margin-top: 5px;
        }

        .no-candidates {
            text-align: center;
            padding: 40px 20px;
            color: #6b7280;
            font-style: italic;
        }

        .no-candidates p {
            margin: 0;
            font-size: 1.1rem;
        }

        /* Results Summary Styles */
        .results-section {
            background: #fff;
            border-radius: 12px;
            padding: 30px;
            margin: 20px 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .results-section h2 {
            text-align: center;
            color: #4f46e5;
            margin-bottom: 30px;
            font-size: 2rem;
        }

        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .result-card {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
        }

        .result-card h3 {
            color: #1e293b;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }

        .vote-count {
            font-size: 2.5rem;
            font-weight: bold;
            color: #4f46e5;
            margin: 10px 0;
        }

        .percentage {
            font-size: 1.1rem;
            color: #6b7280;
        }

        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .results-table th,
        .results-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .results-table th {
            background: #f3f4f6;
            font-weight: 600;
            color: #374151;
        }

        .results-table tr:hover {
            background: #f9fafb;
        }
   /* Responsive Design */
        @media (max-width: 768px) {
            .candidates-grid,
            .results-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .org-title {
                font-size: 1.4rem;
                flex-direction: column;
                gap: 5px;
                text-align: center;
            }
            
            .candidate-card {
                padding: 15px;
            }
            
            .candidate-image {
                width: 80px;
                height: 80px;
            }
            
            .candidate-name {
                font-size: 1.1rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <div class="container">
        <div class="navigation">
            <ul>
                <li>
                    <a href="#">
                        <span class="icon">
                            <img src="catsu.png" alt="CATSU-iVote Logo" style="width:24px; height:24px;" />
                        </span>
                        <span class="title">CATSU-iVote Commissioner</span>
                    </a>
                </li>
                <li>
                    <a href="#" id="dashboardBtn">
                        <span class="icon">
                            <ion-icon name="home-outline"></ion-icon>
                        </span>
                        <span class="title">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="#" id="candidatesBtn">
                        <span class="icon">
                            <ion-icon name="person-outline"></ion-icon>
                        </span>
                        <span class="title">Candidates Profile</span>
                    </a>
                </li>
                <li>
                    <a href="#" id="resultsBtn">
                        <span class="icon">
                            <ion-icon name="bar-chart-outline"></ion-icon>
                        </span>
                        <span class="title">Results Summary</span>
                    </a>
                </li>
                <li>
                    <a href="home.php">
                        <span class="icon">
                            <ion-icon name="log-out-outline"></ion-icon>
                        </span>
                        <span class="title">Log Out</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main -->
        <div class="main">
            <div class="topbar">
                <div class="toggle">
                    <ion-icon name="menu-outline"></ion-icon>
                </div>
                <div class="search">
                    <label>
                        <input type="text" placeholder="Search here" />
                        <ion-icon name="search-outline"></ion-icon>
                    </label>
                </div>
                <div class="user">
                    <img src="catsu.png" alt="User  Profile" />
                    <span>Commissioner Dashboard</span>
                </div>
            </div>

            <!-- Voting Status Alert -->
            <div class="voting-alert <?= $votingStatus['can_vote'] ? 'active' : '' ?>" id="votingAlert">
                <h3><?= $votingStatus['can_vote'] ? '🗳️ Voting is Active' : '⚠️ Voting is Closed' ?></h3>
                <p><?= htmlspecialchars($votingStatus['message']) ?></p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="message <?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Dashboard Section -->
            <div id="dashboardSection">
                <div class="cardBox">
                    <div class="card">
                        <div>
                            <div class="numbers"><?= $totalCandidates ?></div>
                            <div class="cardName">Total Candidates</div>
                        </div>
                        <div class="iconBx">
                            <ion-icon name="people-outline"></ion-icon>
                        </div>
                    </div>

                    <div class="card">
                        <div>
                            <div class="numbers"><?= $pendingCandidates ?></div>
                            <div class="cardName">Pending Approvals</div>
                        </div>
                        <div class="iconBx">
                            <ion-icon name="time-outline"></ion-icon>
                        </div>
                    </div>

                    <div class="card">
                        <div>
                            <div class="numbers"><?= $acceptedCandidates ?></div>
                            <div class="cardName">Accepted Candidates</div>
                        </div>
                        <div class="iconBx">
                            <ion-icon name="checkmark-circle-outline"></ion-icon>
                        </div>
                    </div>

                    <div class="card">
                        <div>
                            <div class="numbers"><?= $totalVotes ?></div>
                            <div class="cardName">Total Votes Cast</div>
                        </div>
                        <div class="iconBx">
                            <ion-icon name="bar-chart-outline"></ion-icon>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Accepted Candidates Section for Commissioner -->
            <div id="candidatesSection" style="display:none; padding:20px;">
                <div class="candidates-profile-section">
    <h2>Candidates Profile</h2>
    <p style="text-align: center; margin-bottom: 30px; color: #6b7280;">View the accepted candidates running for various positions</p>

    <?php
    // Fetch accepted main org candidates grouped by organization (USC and CSC)
    $mainOrgs = ['USC' => 'University Student Council (USC)', 'CSC' => 'College Student Council (CSC)'];

    foreach ($mainOrgs as $orgCode => $orgName) {
        $stmt = $conn->prepare("SELECT * FROM main_org_candidates WHERE status='Accepted' AND organization = ? ORDER BY filing_date DESC");
        $stmt->bind_param("s", $orgCode);
        $stmt->execute();
        $result = $stmt->get_result();

        echo "<div class='organization-section'>";
        echo "<h3 class='org-title'>🏛️ {$orgName} Candidates</h3>";

        if ($result && $result->num_rows > 0) {
            echo "<div class='candidates-grid'>";
            while ($row = $result->fetch_assoc()) {
                $profilePic = '';
                if (!empty($row['profile_pic'])) {
                    $picPath = $row['profile_pic'];
                    if (strpos($picPath, 'uploads/') === 0) {
                        $profilePic = htmlspecialchars($picPath);
                    } else {
                        $profilePic = 'uploads/profile_pics/' . htmlspecialchars($picPath);
                    }
                }

                $collegeValue = $row['college'] ?? '';
                $collegeDisplay = !empty($collegeValue) ? "<div class='college-subtitle'>College: " . htmlspecialchars($collegeValue) . "</div>" : "<div class='college-subtitle'>College: Not specified</div>";

                echo "<div class='candidate-card'>";
                echo "<div class='candidate-image'>";
                if ($profilePic) {
                    echo "<img src='" . $profilePic . "' alt='Profile Picture' onerror=\"this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iI2YzZjRmNiIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LXNpemU9IjEyIiBmaWxsPSIjOWNhM2FmIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+Tm8gSW1hZ2U8L3RleHQ+PC9zdmc+';\">";
                } else {
                    echo "<img src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iI2YzZjRmNiIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LXNpemU9IjEyIiBmaWxsPSIjOWNhM2FmIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+Tm8gSW1hZ2U8L3RleHQ+PC9zdmc+' alt='No Image'>";
                }
                echo "</div>";
                echo "<div class='candidate-info'>";
                echo "<h4 class='candidate-name'>" . htmlspecialchars(trim(($row['first_name'] ?? '') . ' ' . ($row['middle_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))) . "</h4>";
                echo "<div class='candidate-position'>" . htmlspecialchars($row['position'] ?? 'Position not specified') . "</div>";
                echo "<div class='candidate-details'>";
                echo "<span class='org-badge main-org'>{$orgName}</span>";
                echo $collegeDisplay;
                echo "</div>";
                echo "</div>";
                echo "</div>";
            }
            echo "</div>";
        } else {
            echo "<div class='no-candidates'><p>No {$orgName} candidates have been accepted yet.</p></div>";
        }
        echo "</div>";

        $stmt->close();
    }

    // Fetch only accepted sub org candidates
    $subCandidates = $conn->query("SELECT * FROM sub_org_candidates WHERE status='Accepted' ORDER BY filing_date DESC");
    echo "<div class='organization-section'>";
    echo "<h3 class='org-title'>🎯 Sub Organization Candidates</h3>";
    if ($subCandidates && $subCandidates->num_rows > 0) {
        echo "<div class='candidates-grid'>";
        while ($row = $subCandidates->fetch_assoc()) {
            $subOrgValue = $row['organization'] ?? '';
            $subOrgFullName = htmlspecialchars($subOrgValue ?: 'Unknown Sub Organization');
            $subOrgSubtitle = htmlspecialchars($subOrgValue ?: 'Unknown Sub Org');

            echo "<div class='candidate-card'>";
            echo "<div class='candidate-image'>";
            echo "<img src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iI2YzZjRmNiIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LXNpemU9IjEyIiBmaWxsPSIjOWNhM2FmIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+Tm8gSW1hZ2U8L3RleHQ+PC9zdmc+' alt='Profile Picture'>";
            echo "</div>";
            echo "<div class='candidate-info'>";
            echo "<h4 class='candidate-name'>" . htmlspecialchars(trim(($row['first_name'] ?? '') . ' ' . ($row['middle_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))) . "</h4>";
            echo "<div class='candidate-position'>Representative</div>";
            echo "<div class='candidate-subtitle'>{$subOrgSubtitle}</div>";
            echo "<div class='candidate-details'>";
            echo "<span class='org-badge sub-org'>{$subOrgFullName}</span>";
            if (!empty($row['year'])) {
                echo "<div class='year-info'>Year " . htmlspecialchars($row['year']) . "</div>";
            }
            echo "</div>";
            echo "</div>";
            echo "</div>";
        }
        echo "</div>";
    } else {
        echo "<div class='no-candidates'><p>No sub organization candidates have been accepted yet.</p></div>";
    }
    echo "</div>";
    ?>
</div>

            </div>

            <!-- Results Summary Section -->
            <div id="resultsSection" style="display:none; padding:20px;">
                <div class="results-section">
                    <h2>Results Summary</h2>
                    <p style="text-align: center; margin-bottom: 30px; color: #6b7280;">Election results and vote tallies</p>
                    
                    <?php
                    // Total votes
                    $totalVotesQuery = $conn->query("SELECT COUNT(*) as total FROM votes");
                    $totalVotes = $totalVotesQuery->fetch_assoc()['total'];
                    
                    if ($totalVotes > 0) {
                        // Main Org Results
                        echo "<div class='results-grid'>";
                        echo "<div class='result-card'>";
                        echo "<h3>Main Organization Votes</h3>";
                        echo "<div class='vote-count'>". $totalVotes ."</div>";
                        echo "<div class='percentage'>Total Votes Cast</div>";
                        echo "</div>";
                        
                        // Detailed table for Main Org (only accepted candidates)
                        $mainResults = $conn->query("
                            SELECT moc.id, moc.first_name, moc.last_name, moc.position, moc.main_org, moc.college, 
                                   COUNT(v.id) as vote_count
                            FROM main_org_candidates moc
                            LEFT JOIN votes v ON v.main_candidate_id = moc.id
                            WHERE moc.status = 'Accepted'
                            GROUP BY moc.id
                            ORDER BY vote_count DESC
                        ");
                        
                        if ($mainResults && $mainResults->num_rows > 0) {
                            echo "<div class='result-card'>";
                            echo "<h3>Main Organization Results</h3>";
                            echo "<table class='results-table'>";
                            echo "<thead><tr><th>Candidate</th><th>Position</th><th>Organization</th><th>College</th><th>Votes</th><th>%</th></tr></thead>";
                            echo "<tbody>";
                            while($row = $mainResults->fetch_assoc()) {
                                $percentage = $totalVotes > 0 ? round(($row['vote_count'] / $totalVotes) * 100, 1) : 0;
                                $mainOrgValue = $row['main_org'] ?? '';
                                $orgDisplay = ($mainOrgValue == 'USC') ? 'USC' : (($mainOrgValue == 'CSC') ? 'CSC' : htmlspecialchars($mainOrgValue ?: 'Unknown'));
                                $collegeValue = $row['college'] ?? 'N/A';
                                echo "<tr>";
                                echo "<td>".htmlspecialchars(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))."</td>";
                                echo "<td>".htmlspecialchars($row['position'] ?? 'N/A')."</td>";
                                echo "<td>". $orgDisplay ."</td>";
                                echo "<td>".htmlspecialchars($collegeValue)."</td>";
                                echo "<td><strong>". $row['vote_count'] ."</strong></td>";
                                echo "<td>". $percentage ."%</td>";
                                echo "</tr>";
                            }
                            echo "</tbody></table>";
                            echo "</div>";
                        }
                        
                        // Sub Org Results (only accepted candidates)
                        $subResults = $conn->query("
                            SELECT soc.id, soc.first_name, soc.last_name, soc.sub_org, soc.year, 
                                   COUNT(v.id) as vote_count
                            FROM sub_org_candidates soc
                            LEFT JOIN votes v ON v.sub_candidate_id = soc.id
                            WHERE soc.status = 'Accepted'
                            GROUP BY soc.id
                            ORDER BY vote_count DESC
                        ");
                        
                        if ($subResults && $subResults->num_rows > 0) {
                            echo "<div class='result-card'>";
                            echo "<h3>Sub Organization Results</h3>";
                            echo "<table class='results-table'>";
                            echo "<thead><tr><th>Candidate</th><th>Organization</th><th>Year</th><th>Votes</th><th>%</th></tr></thead>";
                            echo "<tbody>";
                            while($row = $subResults->fetch_assoc()) {
                                $percentage = $totalVotes > 0 ? round(($row['vote_count'] / $totalVotes) * 100, 1) : 0;
                                $subOrgValue = $row['sub_org'] ?? '';
                                echo "<tr>";
                                echo "<td>".htmlspecialchars(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))."</td>";
                                echo "<td>".htmlspecialchars($subOrgValue ?: 'Unknown')."</td>";
                                echo "<td>".htmlspecialchars($row['year'] ?? 'N/A')."</td>";
                                echo "<td><strong>". $row['vote_count'] ."</strong></td>";
                                echo "<td>". $percentage ."%</td>";
                                echo "</tr>";
                            }
                            echo "</tbody></table>";
                            echo "</div>";
                        }
                        echo "</div>";
                    } else {
                        echo "<div style='text-align: center; padding: 50px;'>";
                        echo "<h3>No Votes Cast Yet</h3>";
                        echo "<p>Voting results will appear here once votes are submitted.</p>";
                        echo "</div>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Navigation elements
        const dashboardBtn = document.getElementById("dashboardBtn");
        const candidatesBtn = document.getElementById("candidatesBtn");
        const resultsBtn = document.getElementById("resultsBtn");

        const dashboardSection = document.getElementById("dashboardSection");
        const candidatesSection = document.getElementById("candidatesSection");
        const resultsSection = document.getElementById("resultsSection");

        // Show dashboard by default
        window.onload = function () {
            showSection(dashboardSection);
        };

        // Function to show a section
        function showSection(sectionToShow) {
            const sections = [dashboardSection, candidatesSection, resultsSection];
            sections.forEach(section => {
                section.style.display = (section === sectionToShow) ? "block" : "none";
            });
        }

        // Button click handlers
        dashboardBtn.addEventListener("click", function(e) {
            e.preventDefault();
            showSection(dashboardSection);
        });

        candidatesBtn.addEventListener("click", function(e) {
            e.preventDefault();
            showSection(candidatesSection);
        });

        resultsBtn.addEventListener("click", function(e) {
            e.preventDefault();
            showSection(resultsSection);
        });

        // Auto-refresh voting status every 30 seconds (optional)
        setInterval(function() {
            // You can implement AJAX to refresh voting status if needed
            // For now, a simple reload (replace with AJAX for better UX)
            // location.reload();
        }, 30000);
    </script>

    <!-- Scripts -->
    <script src="assets/js/main.js"></script>
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>