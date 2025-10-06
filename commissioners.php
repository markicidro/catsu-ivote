<!DOCTYPE html>
<?php
// Ensure session configuration is set before starting the session
ini_set('session.cookie_lifetime', 3600); // Set session cookie lifetime to 1 hour
ini_set('session.gc_maxlifetime', 3600); // Set session garbage collection lifetime
session_set_cookie_params(3600); // Ensure cookie persists for 1 hour
session_start();

date_default_timezone_set('Asia/Manila'); // Set your timezone

// Check if session is valid
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

// Handle Candidate Approval/Rejection (for commissioners)
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

// Fetch data for vote tally tables
// Main org data
$mainDataQuery = $conn->query("
    SELECT moc.organization, moc.first_name, moc.middle_name, moc.last_name, moc.position, COUNT(v.id) as vote_count
    FROM main_org_candidates moc
    LEFT JOIN votes v ON v.main_candidate_id = moc.id
    WHERE moc.status = 'Accepted'
    GROUP BY moc.id
    ORDER BY moc.organization, vote_count DESC
");
$mainData = ['USC' => [], 'CSC' => []];
while ($row = $mainDataQuery->fetch_assoc()) {
    $org = $row['organization'] ?? 'Unknown';
    if (in_array($org, ['USC', 'CSC'])) {
        $name = trim(($row['first_name'] ?? '') . ' ' . ($row['middle_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
        $mainData[$org][] = [
            'name' => $name,
            'position' => $row['position'] ?? 'N/A',
            'votes' => $row['vote_count']
        ];
    }
}

// Sub org data
$subDataQuery = $conn->query("
    SELECT soc.organization, soc.first_name, soc.middle_name, soc.last_name, COUNT(v.id) as vote_count
    FROM sub_org_candidates soc
    LEFT JOIN votes v ON v.sub_candidate_id = soc.id
    WHERE soc.status = 'Accepted'
    GROUP BY soc.id
    ORDER BY soc.organization, vote_count DESC
");
$subData = [];
$subOrgs = [];
while ($row = $subDataQuery->fetch_assoc()) {
    $org = $row['organization'] ?? 'Unknown';
    if (!in_array($org, $subOrgs)) {
        $subOrgs[] = $org;
    }
    $name = trim(($row['first_name'] ?? '') . ' ' . ($row['middle_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
    $subData[$org][] = [
        'name' => $name,
        'position' => 'Representative',
        'votes' => $row['vote_count']
    ];
}

// JSON encode data for JavaScript
$mainDataJson = json_encode($mainData);
$subDataJson = json_encode($subData);
?>

<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CATSU iVote - Commissioner Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css" />
    <link rel="stylesheet" href="assets/css/commissioner.css" />
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
                    <a href="#" id="dashboardBtn" data-section="dashboard">
                        <span class="icon">
                            <ion-icon name="home-outline"></ion-icon>
                        </span>
                        <span class="title">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="#" id="candidatesBtn" data-section="candidates">
                        <span class="icon">
                            <ion-icon name="person-outline"></ion-icon>
                        </span>
                        <span class="title">Candidates Profile</span>
                    </a>
                </li>
                <li>
                    <a href="#" id="resultsBtn" data-section="results">
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
                    <img src="catsu.png" alt="User Profile" />
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

                <!-- Vote Tally Section -->
                <div class="vote-tally-section">
                    <h3>Main Organization Vote Tally</h3>
                    <select id="mainOrgSelect">
                        <option value="">Select Main Organization</option>
                        <option value="USC">USC</option>
                        <option value="CSC">CSC</option>
                    </select>
                    <table class="results-table">
                        <thead>
                            <tr>
                                <th>Candidate Name</th>
                                <th>Position</th>
                                <th>Total Votes</th>
                            </tr>
                        </thead>
                        <tbody id="mainVoteTbody">
                            <tr><td colspan="3" class="no-data">Select a main organization to view tally</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="vote-tally-section">
                    <h3>Sub Organization Vote Tally</h3>
                    <select id="subOrgSelect">
                        <option value="">Select Sub Organization</option>
                        <?php foreach ($subOrgs as $sub): ?>
                            <option value="<?= htmlspecialchars($sub) ?>"><?= htmlspecialchars($sub) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <table class="results-table">
                        <thead>
                            <tr>
                                <th>Candidate Name</th>
                                <th>Position</th>
                                <th>Total Votes</th>
                            </tr>
                        </thead>
                        <tbody id="subVoteTbody">
                            <tr><td colspan="3" class="no-data">Select a sub organization to view tally</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Accepted Candidates Section for Commissioner -->
            <div id="candidatesSection" style="display:none; padding:20px;">
                <div class="candidates-profile-section">
                    <h2>Candidates Profile</h2>
                    <p style="text-align: center; margin-bottom: 30px; color: #6b7280;">View the accepted candidates running for various positions</p>

                    <?php
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
                                    if (strpos($picPath, 'Uploads/') === 0) {
                                        $profilePic = htmlspecialchars($picPath);
                                    } else {
                                        $profilePic = 'Uploads/profile_pics/' . htmlspecialchars($picPath);
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
                    $totalVotesQuery = $conn->query("SELECT COUNT(*) as total FROM votes");
                    $totalVotes = $totalVotesQuery->fetch_assoc()['total'];
                    
                    if ($totalVotes > 0) {
                        echo "<div class='results-grid'>";
                        echo "<div class='result-card'>";
                        echo "<h3>Main Organization Votes</h3>";
                        echo "<div class='vote-count'>". $totalVotes ."</div>";
                        echo "<div class='percentage'>Total Votes Cast</div>";
                        echo "</div>";
                        
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

        // Function to show a section
        function showSection(sectionToShow) {
            const sections = [dashboardSection, candidatesSection, resultsSection];
            sections.forEach(section => {
                section.style.display = (section === sectionToShow) ? "block" : "none";
            });
            const sectionId = sectionToShow.id.replace("Section", "");
            sessionStorage.setItem("activeSection", sectionId);
        }

        // Restore active section on page load
        window.onload = function () {
            const activeSection = sessionStorage.getItem("activeSection") || "dashboard";
            const sectionMap = {
                "dashboard": dashboardSection,
                "candidates": candidatesSection,
                "results": resultsSection
            };
            showSection(sectionMap[activeSection] || dashboardSection);
        };

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

        // Auto-refresh voting status every 30 seconds using AJAX
        setInterval(function() {
            fetch('check_voting_status.php')
                .then(response => response.json())
                .then(data => {
                    const votingAlert = document.getElementById("votingAlert");
                    votingAlert.className = "voting-alert" + (data.can_vote ? " active" : "");
                    votingAlert.innerHTML = `
                        <h3>${data.can_vote ? '🗳️ Voting is Active' : '⚠️ Voting is Closed'}</h3>
                        <p>${data.message}</p>
                    `;
                })
                .catch(error => console.error('Error fetching voting status:', error));
        }, 30000);

        // Vote tally table JavaScript
        const mainData = <?= $mainDataJson ?>;
        const subData = <?= $subDataJson ?>;
        const mainOrgSelect = document.getElementById('mainOrgSelect');
        const subOrgSelect = document.getElementById('subOrgSelect');
        const mainVoteTbody = document.getElementById('mainVoteTbody');
        const subVoteTbody = document.getElementById('subVoteTbody');

        function updateTable(tbody, data, selected) {
            tbody.innerHTML = '';
            if (selected && data[selected] && data[selected].length > 0) {
                data[selected].forEach(cand => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${cand.name}</td>
                        <td>${cand.position}</td>
                        <td>${cand.votes}</td>
                    `;
                    tbody.appendChild(row);
                });
            } else {
                const row = document.createElement('tr');
                row.innerHTML = '<td colspan="3" class="no-data">No data available for selected organization</td>';
                tbody.appendChild(row);
            }
        }

        if (mainOrgSelect && mainVoteTbody) {
            mainOrgSelect.addEventListener('change', function() {
                updateTable(mainVoteTbody, mainData, this.value);
            });
            mainOrgSelect.dispatchEvent(new Event('change'));
        }

        if (subOrgSelect && subVoteTbody) {
            subOrgSelect.addEventListener('change', function() {
                updateTable(subVoteTbody, subData, this.value);
            });
            subOrgSelect.dispatchEvent(new Event('change'));
        }
    </script>

    <!-- Scripts -->
    <script src="assets/js/main.js"></script>
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>