<?php
ini_set('session.cookie_lifetime', 3600); // Set session cookie lifetime to 1 hour
ini_set('session.gc_maxlifetime', 3600); // Set session garbage collection lifetime
session_set_cookie_params(3600); // Ensure cookie persists for 1 hour
session_start();

date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'voter') {
    header('Location: home.php');
    exit;
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ivote_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Connection failed: " . $conn->connect_error);
}

// ------------------- FUNCTIONS -------------------

// Voting Schedule Check Function
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

// AES Encryption Functions
function encryptVote($data, $key = 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2') {
    $cipher = "aes-256-cbc";
    $ivlen = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($ivlen);
    $encrypted = openssl_encrypt(json_encode($data), $cipher, $key, 0, $iv);
    return base64_encode($encrypted . '::' . $iv);
}

function decryptVote($encrypted, $key = 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2') {
    $cipher = "aes-256-cbc";
    list($encrypted_data, $iv) = explode('::', base64_decode($encrypted), 2);
    return json_decode(openssl_decrypt($encrypted_data, $cipher, $key, 0, $iv), true);
}

$votingStatus = canVote($conn);

// Helper function to upload file safely
function uploadFile($fileInputName, $uploadDir) {
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $fileTmpPath = $_FILES[$fileInputName]['tmp_name'];
    $fileName = basename($_FILES[$fileInputName]['name']);
    $fileName = preg_replace("/[^a-zA-Z0-9\.\-_]/", "_", $fileName);
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $destPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

    if (move_uploaded_file($fileTmpPath, $destPath)) {
        return $destPath;
    }
    return null;
}

// Handle Vote Submission
if (isset($_POST['submitVote']) && $votingStatus['can_vote']) {
    $voter_id = $_SESSION['user_id'];
    
    if (!$voter_id) {
        $_SESSION['message'] = "User not authenticated. Please login again.";
        $_SESSION['messageType'] = "error";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Check if user already voted
    $checkVote = $conn->prepare("SELECT id FROM votes WHERE voter_id = ?");
    if (!$checkVote) {
        error_log("Prepare failed for vote check: " . $conn->error);
        $_SESSION['message'] = "Database error. Please try again.";
        $_SESSION['messageType'] = "error";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    $checkVote->bind_param("i", $voter_id);
    $checkVote->execute();
    $existingVote = $checkVote->get_result();
    
    if ($existingVote->num_rows > 0) {
        $_SESSION['message'] = "You have already voted! Multiple voting is not allowed.";
        $_SESSION['messageType'] = "error";
        $checkVote->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    $checkVote->close();
    
    // Collect votes from POST
    $votes = [];
    $hasVoted = false;
    
    foreach ($_POST as $key => $value) {
        if ($key === 'submitVote') continue;
        if ((strpos($key, 'main_') === 0 || strpos($key, 'sub_') === 0) && !empty($value)) {
            $votes[$key] = intval($value);
            $hasVoted = true;
        }
    }
    
    if (!$hasVoted) {
        $_SESSION['message'] = "Please select at least one candidate before submitting.";
        $_SESSION['messageType'] = "error";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    $conn->begin_transaction();
    
    try {
        $encryptedVotes = encryptVote($votes);
        
        $stmt = $conn->prepare("INSERT INTO votes (voter_id, encrypted_vote, vote_date) VALUES (?, ?, NOW())");
        if (!$stmt) {
            throw new Exception("Failed to prepare votes insert: " . $conn->error);
        }
        $stmt->bind_param("is", $voter_id, $encryptedVotes);
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert vote: " . $stmt->error);
        }
        $vote_id = $stmt->insert_id;
        $stmt->close();
        
        // Insert vote tally records
        foreach ($votes as $key => $candidate_id) {
            if (strpos($key, 'main_') === 0) {
                $position = str_replace('main_', '', $key);
                $position = str_replace('_', ' ', $position);
                
                // Get organization from main_org_candidates
                $orgQuery = $conn->prepare("SELECT organization FROM main_org_candidates WHERE id = ? AND status = 'Accepted'");
                if (!$orgQuery) {
                    throw new Exception("Failed to prepare org query: " . $conn->error);
                }
                $orgQuery->bind_param("i", $candidate_id);
                $orgQuery->execute();
                $orgResult = $orgQuery->get_result();
                $organization = $orgResult->num_rows === 0 ? 'Unknown' : $orgResult->fetch_assoc()['organization'];
                $orgQuery->close();
                
                $insertTally = $conn->prepare("INSERT INTO vote_tally (vote_id, candidate_id, candidate_type, position, organization) VALUES (?, ?, 'main', ?, ?)");
                if (!$insertTally) {
                    throw new Exception("Failed to prepare tally insert: " . $conn->error);
                }
                $insertTally->bind_param("iiss", $vote_id, $candidate_id, $position, $organization);
                if (!$insertTally->execute()) {
                    throw new Exception("Failed to insert vote tally for candidate $candidate_id: " . $insertTally->error);
                }
                $insertTally->close();
                
            } elseif (strpos($key, 'sub_') === 0) {
                $organization = str_replace('sub_', '', $key);
                $organization = str_replace('_', ' ', $organization);
                
                // Verify candidate exists in sub_org_candidates
                $verifyQuery = $conn->prepare("SELECT id FROM sub_org_candidates WHERE id = ? AND status = 'Accepted'");
                if ($verifyQuery) {
                    $verifyQuery->bind_param("i", $candidate_id);
                    $verifyQuery->execute();
                    $verifyResult = $verifyQuery->get_result();
                    $verifyQuery->close();
                }
                
                $insertTally = $conn->prepare("INSERT INTO vote_tally (vote_id, candidate_id, candidate_type, organization) VALUES (?, ?, 'sub', ?)");
                if (!$insertTally) {
                    throw new Exception("Failed to prepare sub tally insert: " . $conn->error);
                }
                $insertTally->bind_param("iis", $vote_id, $candidate_id, $organization);
                if (!$insertTally->execute()) {
                    throw new Exception("Failed to insert sub vote tally for candidate $candidate_id: " . $insertTally->error);
                }
                $insertTally->close();
            }
        }
        
        $conn->commit();
        
        $_SESSION['message'] = "Your vote has been successfully submitted and encrypted!";
        $_SESSION['messageType'] = "success";
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['message'] = "Error submitting vote: " . $e->getMessage();
        $_SESSION['messageType'] = "error";
        error_log("VOTING ERROR: " . $e->getMessage());
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ------------------- FETCH DASHBOARD DATA -------------------

$totalVoters = 0;
$votedCount = 0;
$notVotedCount = 0;
$totalCandidates = 0;

$result = $conn->query("SELECT COUNT(*) as total FROM users");
if ($result) {
    $totalVoters = intval($result->fetch_assoc()['total']);
    $result->free();
} else {
    error_log("Error fetching total voters: " . $conn->error);
}

$result = $conn->query("SELECT COUNT(DISTINCT voter_id) as voted FROM votes");
if ($result) {
    $votedCount = intval($result->fetch_assoc()['voted']);
    $result->free();
} else {
    error_log("Error fetching voted count: " . $conn->error);
}

$notVotedCount = $totalVoters - $votedCount;

$result = $conn->query("SELECT COUNT(*) as total FROM main_org_candidates WHERE status='Accepted'");
$totalCandidatesMain = $result ? intval($result->fetch_assoc()['total']) : 0;
$result->free();

$result = $conn->query("SELECT COUNT(*) as total FROM sub_org_candidates WHERE status='Accepted'");
$totalCandidatesSub = $result ? intval($result->fetch_assoc()['total']) : 0;
$result->free();

$totalCandidates = $totalCandidatesMain + $totalCandidatesSub;

// ------------------- FETCH NOTIFICATIONS -------------------

$notificationCount = 0;
$notifications = [];
$userId = $_SESSION['user_id'] ?? null;

if ($userId) {
    $stmt = $conn->prepare("SELECT id FROM main_org_candidates WHERE user_id = ? AND status = 'Accepted'");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $countMain = $result->num_rows;
        $notificationCount += $countMain;
        for ($i = 0; $i < $countMain; $i++) {
            $notifications[] = "Your filing for Main Organization has been approved.";
        }
        $stmt->close();
    }

    $stmt2 = $conn->prepare("SELECT id FROM sub_org_candidates WHERE user_id = ? AND status = 'Accepted'");
    if ($stmt2) {
        $stmt2->bind_param("i", $userId);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        $countSub = $result2->num_rows;
        $notificationCount += $countSub;
        for ($i = 0; $i < $countSub; $i++) {
            $notifications[] = "Your filing for Sub Organization has been approved.";
        }
        $stmt2->close();
    }
}

// Handle Main Organization Filing
if (isset($_POST['submitFiling'])) {
    $organization = $_POST['organization'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $middle_name = $_POST['middle_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $nickname = $_POST['nickname'] ?? '';
    $age = $_POST['age'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $college = $_POST['college'] ?? '';
    $year = $_POST['year'] ?? '';
    $program = $_POST['program'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $partylist = $_POST['partylist'] ?? '';
    $position = $_POST['position'] ?? '';
    $permanent_address = $_POST['permanent_address'] ?? '';
    $temporary_address = $_POST['temporary_address'] ?? '';
    $residency_years = intval($_POST['residency_years'] ?? 0);
    $residency_semesters = intval($_POST['residency_semesters'] ?? 0);
    $semester_year = $_POST['semester_year'] ?? '';

    $uploadDir = "Uploads/";
    $profile_pic = uploadFile('profile_pic', $uploadDir) ?? '';
    $comelec_form_1 = uploadFile('comelec_form_1', $uploadDir) ?? '';
    $recommendation_letter = uploadFile('CertificateOfRecommendation', $uploadDir) ?? '';
    $prospectus = uploadFile('prospectus', $uploadDir) ?? '';
    $clearance = uploadFile('clearance', $uploadDir) ?? '';
    $coe = uploadFile('coe', $uploadDir) ?? '';
    $certificate_of_candidacy = uploadFile('CertificateofCandidacy', $uploadDir) ?? '';

    $stmt = $conn->prepare("INSERT INTO main_org_candidates
        (user_id, organization, first_name, middle_name, last_name, nickname, age, gender, dob, college, year, program, phone, email, position, partylist, permanent_address, temporary_address, residency_years, residency_semesters, semester_year, profile_pic, comelec_form_1, recommendation_letter, prospectus, clearance, coe, certificate_of_candidacy, status, filing_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");

    if (!$stmt) {
        error_log("Prepare failed for main org filing: " . $conn->error);
        $_SESSION['message'] = "Database error: " . $conn->error;
        $_SESSION['messageType'] = "error";
    } else {
        $stmt->bind_param(
            "isssssssssssssssssiissssssss",
            $userId,
            $organization,
            $first_name,
            $middle_name,
            $last_name,
            $nickname,
            $age,
            $gender,
            $dob,
            $college,
            $year,
            $program,
            $phone,
            $email,
            $position,
            $partylist,
            $permanent_address,
            $temporary_address,
            $residency_years,
            $residency_semesters,
            $semester_year,
            $profile_pic,
            $comelec_form_1,
            $recommendation_letter,
            $prospectus,
            $clearance,
            $coe,
            $certificate_of_candidacy
        );

        if ($stmt->execute()) {
            $_SESSION['message'] = "Main Organization filing submitted successfully!";
            $_SESSION['messageType'] = "success";
        } else {
            $_SESSION['message'] = "Error submitting filing: " . $stmt->error;
            $_SESSION['messageType'] = "error";
            error_log("Main Org Filing Error: " . $stmt->error);
        }
        $stmt->close();
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle Sub Organization Filing
if (isset($_POST['submitFilingSub'])) {
    $organization = $_POST['organization'] ?? '';
    $first_name_sub = $_POST['first_name_sub'] ?? '';
    $middle_name_sub = $_POST['middle_name_sub'] ?? '';
    $last_name_sub = $_POST['last_name_sub'] ?? '';
    $position_sub = $_POST['position_sub'] ?? 'Representative';
    $year_sub = intval($_POST['year_sub'] ?? 0);
    $block_address_sub = $_POST['block_address_sub'] ?? '';

    if (empty($organization) || empty($first_name_sub) || empty($last_name_sub) || empty($position_sub) || $year_sub < 1) {
        $_SESSION['message'] = "Please fill in all required fields correctly.";
        $_SESSION['messageType'] = "error";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO sub_org_candidates
        (user_id, organization, last_name, first_name, middle_name, year, block_address, position, status, filing_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");
    
    if (!$stmt) {
        error_log("Prepare failed for sub org filing: " . $conn->error);
        $_SESSION['message'] = "Database error: " . $conn->error;
        $_SESSION['messageType'] = "error";
    } else {
        $stmt->bind_param(
            "issssiss",
            $userId,
            $organization,
            $last_name_sub,
            $first_name_sub,
            $middle_name_sub,
            $year_sub,
            $block_address_sub,
            $position_sub
        );

        if ($stmt->execute()) {
            $_SESSION['message'] = "Sub Organization filing submitted successfully!";
            $_SESSION['messageType'] = "success";
        } else {
            $_SESSION['message'] = "Error submitting filing: " . $stmt->error;
            $_SESSION['messageType'] = "error";
            error_log("Sub Org Filing Error: " . $stmt->error);
        }
        $stmt->close();
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['messageType'] ?? 'info';
    unset($_SESSION['message'], $_SESSION['messageType']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CATSU iVote</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/voters.css">
    <style>
        .message { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .message.success { background: #d4edda; color: #155724; }
        .message.error { background: #f8d7da; color: #721c24; }
        .voting-alert { padding: 15px; margin: 10px 0; border-radius: 5px; }
        .voting-alert.active { background: #e6f3ff; border-left: 4px solid #0ea5e9; }
        #notificationDropdown { display: none; position: absolute; background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.2); padding: 10px; width: 200px; z-index: 1000; }
        #notificationDropdown ul { list-style: none; padding: 0; margin: 0; }
        #notificationDropdown li { padding: 5px 0; border-bottom: 1px solid #eee; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 5% auto; padding: 20px; width: 80%; max-width: 600px; border-radius: 5px; }
        .close { float: right; font-size: 20px; cursor: pointer; }
        .candidates-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
        .candidate-card { border: 1px solid #ddd; padding: 10px; border-radius: 5px; }
        .candidate-image img { width: 100px; height: 100px; object-fit: cover; border-radius: 50%; }
        .org-badge { padding: 5px; border-radius: 3px; font-size: 0.9em; }
        .org-badge.main-org { background: #e6f3ff; color: #0ea5e9; }
        .org-badge.sub-org { background: #f0f9ff; color: #1f2937; }
        .vote-submit-btn { padding: 10px 20px; background: #0ea5e9; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .vote-submit-btn:disabled { background: #ccc; cursor: not-allowed; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #f0f9ff; }
    </style>
</head>
<body>
    <div class="container">
        <div class="navigation">
            <ul>
                <li>
                    <a href="#">
                        <span class="icon">
                            <img src="assets/imgs/catsu.png" alt="CATSU-iVote Logo" style="width:24px; height:24px;">
                        </span>
                        <span class="title">CATSU-iVote Voters</span>
                    </a>
                </li>
                <li style="position: relative;">
                    <a href="#" id="notificationBell" title="Notifications">
                        <span class="icon">
                            <ion-icon name="notifications-outline"></ion-icon>
                        </span>
                        <span class="title">Notifications</span>
                        <?php if ($notificationCount > 0): ?>
                            <span id="notificationCount"><?= $notificationCount ?></span>
                        <?php endif; ?>
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
                    <a href="#" id="rulesBtn">
                        <span class="icon">
                            <ion-icon name="book-outline"></ion-icon>
                        </span>
                        <span class="title">Voting Rules</span>
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
                    <a href="#" id="filingBtn">
                        <span class="icon">
                            <ion-icon name="chatbubble-sharp"></ion-icon>
                        </span>
                        <span class="title">Filing</span>
                    </a>
                </li>
                <li>
                    <a href="#" id="votesBtn">
                        <span class="icon">
                            <ion-icon name="people-outline"></ion-icon>
                        </span>
                        <span class="title">Votes</span>
                    </a>
                </li>
                <li>
                    <a href="home.php?logout=true">
                        <span class="icon">
                            <ion-icon name="log-out-outline"></ion-icon>
                        </span>
                        <span class="title">Log Out</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="main">
            <div class="topbar">
                <div class="toggle">
                    <ion-icon name="menu-outline"></ion-icon>
                </div>
                <div class="search">
                    <label>
                        <input type="text" placeholder="Search here">
                        <ion-icon name="search-outline"></ion-icon>
                    </label>
                </div>
                <div class="user">
                    <img src="catsu.png" alt="User Profile">
                </div>
            </div>

            <div class="voting-alert <?= $votingStatus['can_vote'] ? 'active' : '' ?>" id="votingAlert">
                <h3><?= $votingStatus['can_vote'] ? '🗳️ Voting is Active' : '⚠️ Voting is Closed' ?></h3>
                <p><?= htmlspecialchars($votingStatus['message']) ?></p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="message <?= htmlspecialchars($messageType) ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div id="dashboardSection">
                <div class="cardBox">
                    <div class="card">
                        <div>
                            <div class="numbers" id="totalVotersCount"><?= $totalVoters ?></div>
                            <div class="cardName">Total Registered Voters</div>
                        </div>
                        <div class="iconBx">
                            <ion-icon name="people-outline"></ion-icon>
                        </div>
                    </div>
                    <div class="card">
                        <div>
                            <div class="numbers" id="votedCount"><?= $votedCount ?></div>
                            <div class="cardName">Students Who Voted</div>
                        </div>
                        <div class="iconBx">
                            <ion-icon name="people-outline"></ion-icon>
                        </div>
                    </div>
                    <div class="card">
                        <div>
                            <div class="numbers" id="notVotedCount"><?= $notVotedCount ?></div>
                            <div class="cardName">Students Who Have Not Voted</div>
                        </div>
                        <div class="iconBx">
                            <ion-icon name="people-outline"></ion-icon>
                        </div>
                    </div>
                    <div class="card">
                        <div>
                            <div class="numbers" id="totalCandidatesCount"><?= $totalCandidates ?></div>
                            <div class="cardName">Total Candidates</div>
                        </div>
                        <div class="iconBx">
                            <ion-icon name="people-outline"></ion-icon>
                        </div>
                    </div>
                </div>

                <div style="max-width: 600px; margin: 40px auto; text-align: center;">
                    <label for="orgSelect" style="font-weight: bold; font-size: 1.1rem;">Select Organization:</label>
                    <select id="orgSelect" style="width: 100%; max-width: 300px; padding: 8px; margin-top: 10px;">
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

                <div id="orgCandidatesContainer" style="max-width: 800px; margin: 20px auto; font-family: Arial, sans-serif;">
                    <p style="text-align: center; color: #6b7280; font-style: italic;">Select an organization to view vote tally</p>
                </div>
            </div>

            <div class="details" id="filing-section" style="display:none; padding:20px;">
                <div class="recentOrders">
                    <div class="cardHeader">
                        <h2>Filing of Candidacy</h2>
                    </div>
                    <div style="display: flex; gap: 40px; margin-top: 20px;">
                        <div style="flex: 1;" id="mainOrgSelectWrapper">
                            <label for="mainOrg"><strong>Main Organization</strong></label>
                            <select id="mainOrg" name="main_org" onchange="handleMainOrgChange()">
                                <option value="">Select Main Org</option>
                                <option value="USC">USC (University Student Council)</option>
                                <option value="CSC">CSC (College Student Council)</option>
                            </select>
                        </div>
                        <div style="flex: 1;" id="subOrgSelectWrapper">
                            <label for="subOrg"><strong>Sub Organization</strong></label>
                            <select id="subOrg" name="sub_org" onchange="handleSubOrgChange()">
                                <option value="">Select Sub Org</option>
                                <option value="ACCESS">ACCESS</option>
                                <option value="ASITS">ASITS</option>
                                <option value="BSEMC PromtPT">BSEMC PromtPT</option>
                                <option value="ISSO">ISSO</option>
                                <option value="LISAUX">LISAUX</option>
                                <option value="CICT-womens club">CICT-womens club</option>
                            </select>
                        </div>
                    </div>

                    <div id="mainOrgForm" style="display:none; margin-top: 30px;">
                        <form method="POST" enctype="multipart/form-data" action="">
                            <input type="hidden" name="organization" id="hiddenMainOrg" value="">
                            <h3>Main Organization Candidate Details</h3>
                            <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                                <div style="flex: 1 1 200px;">
                                    <label>Upload 1x1 Picture:</label>
                                    <input type="file" name="profile_pic" accept="image/*" required>
                                </div>
                                <div style="flex: 1 1 200px;">
                                    <label>Last Name:</label>
                                    <input type="text" name="last_name" required>
                                </div>
                                <div style="flex: 1 1 200px;">
                                    <label>First Name:</label>
                                    <input type="text" name="first_name" required>
                                </div>
                                <div style="flex: 1 1 200px;">
                                    <label>Middle Name:</label>
                                    <input type="text" name="middle_name">
                                </div>
                                <div style="flex: 1 1 200px;">
                                    <label>Nickname:</label>
                                    <input type="text" name="nickname">
                                </div>
                                <div style="flex: 1 1 200px;">
                                    <label>Age:</label>
                                    <input type="text" name="age" id="age" readonly>
                                </div>
                                <div style="flex: 1 1 150px;">
                                    <label>Gender:</label>
                                    <select name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div style="flex: 1 1 200px;">
                                    <label>Date of Birth:</label>
                                    <input type="date" name="dob" id="dob" required onchange="calculateAge()">
                                </div>
                                <div style="flex: 1 1 200px;">
                                    <label>College:</label>
                                    <select name="college" id="collegeSelect" required>
                                        <option value="">Select College</option>
                                        <option value="CICT">College of Information and Communications Technology (CICT)</option>
                                        <option value="CBA">College of Business and Accountancy (CBA)</option>
                                        <option value="CIT">College of Industrial Technology (CIT)</option>
                                        <option value="CHS">College of Health Sciences (CHS)</option>
                                        <option value="CEA">College of Engineering and Architecture (CEA)</option>
                                        <option value="CHUMMS">College of Humanities and Social Sciences (CHUMMS)</option>
                                        <option value="COS">College of Sciences (COS)</option>
                                        <option value="CAF">College of Agriculture and Fisheries (CAF)</option>
                                        <option value="COED">College of Education (COED)</option>
                                    </select>
                                </div>
                                <div style="flex: 1 1 150px;">
                                    <label>Year:</label>
                                    <input type="number" name="year" min="1" max="5" required>
                                </div>
                                <div style="flex: 1 1 200px;">
                                    <label>Program:</label>
                                    <select name="program" id="programSelect" required>
                                        <option value="">Select Program</option>
                                    </select>
                                </div>
                                <div style="flex: 1 1 200px;">
                                    <label>Phone:</label>
                                    <input type="tel" name="phone" required>
                                </div>
                                <div style="flex: 1 1 300px;">
                                    <label>Email Address:</label>
                                    <input type="email" name="email" required>
                                </div>
                                <div style="flex: 1 1 200px;">
                                    <label>Position Candidating For:</label>
                                    <select name="position" required>
                                        <option value="">Select position</option>
                                        <option value="President">President</option>
                                        <option value="Vice President">Vice President</option>
                                        <option value="Executive Secretary">Executive Secretary</option>
                                        <option value="Finance Secretary">Finance Secretary</option>
                                        <option value="Budget Secretary">Budget Secretary</option>
                                        <option value="Auditor">Auditor</option>
                                        <option value="Public Information Secretary">Public Information Secretary</option>
                                        <option value="Property Custodian">Property Custodian</option>
                                        <option value="Senators">Senators</option>
                                        <option value="Legislators">Legislators</option>
                                        <option value="Year Representative">Year Representative</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div style="flex: 1 1 200px;">
                                    <label>Partylist:</label>
                                    <input type="text" name="partylist">
                                </div>
                                <div style="flex: 1 1 300px;">
                                    <label>Permanent Address:</label>
                                    <textarea name="permanent_address" rows="2" required></textarea>
                                </div>
                                <div style="flex: 1 1 300px;">
                                    <label>Temporary Address:</label>
                                    <textarea name="temporary_address" rows="2" required></textarea>
                                </div>
                                <div style="flex: 1 1 200px;">
                                    <label>Period of Residency:</label>
                                    <div style="display: flex; gap: 10px;">
                                        <input type="number" name="residency_years" min="0" max="10" placeholder="Years" required style="flex:1;">
                                        <input type="number" name="residency_semesters" min="0" max="20" placeholder="Semesters" required style="flex:1;">
                                    </div>
                                    <small style="color: #6b7280;">Enter how many years and semesters you have studied in the university</small>
                                </div>
                                <div style="flex: 1 1 200px;">
                                    <label>Semester and Year in Current College and University:</label>
                                    <input type="text" name="semester_year" required>
                                </div>
                                <div style="flex: 1 1 300px;">
                                    <label>Upload Certificate of Candidacy:</label><br>
                                    <a href="files/COMELEC-FORM-NO.-1.docx" target="_blank" style="color: blue; text-decoration: underline;">
                                        📄 Download Certificate of Candidacy (PDF)
                                    </a><br><br>
                                    <input type="file" name="CertificateofCandidacy" accept=".jpg, .jpeg" required>
                                </div>
                                <div style="flex: 1 1 300px;">
                                    <label>Upload COMELEC Form 1:</label><br>
                                    <a href="files/COMELEC-FORM-NO.-1.docx" target="_blank" style="color: blue; text-decoration: underline;">
                                        📄 Download COMELEC Form 1 (PDF)
                                    </a><br><br>
                                    <input type="file" name="comelec_form_1" accept=".jpg, .jpeg" required>
                                </div>
                                <div style="flex: 1 1 300px;">
                                    <label>Upload Certificate of Recommendation:</label><br>
                                    <a href="files/Certificate-of-Recommendation.docx" target="_blank" style="color: blue; text-decoration: underline;">
                                        📄 Download Certificate of Recommendation (PDF)
                                    </a><br><br>
                                    <input type="file" name="CertificateOfRecommendation" accept=".jpg, .jpeg" required>
                                </div>
                                <div style="flex: 1 1 300px;">
                                    <label>Upload Photocopy of Prospectus:</label>
                                    <input type="file" name="prospectus" accept="image/*,application/pdf" required>
                                </div>
                                <div style="flex: 1 1 300px;">
                                    <label>Upload Clearance:</label>
                                    <input type="file" name="clearance" accept="image/*,application/pdf" required>
                                </div>
                                <div style="flex: 1 1 300px;">
                                    <label>Upload Photocopy of COE:</label>
                                    <input type="file" name="coe" accept="image/*,application/pdf" required>
                                </div>
                            </div>
                            <button type="submit" name="submitFiling" class="btn" style="margin-top: 20px;">Submit Filing</button>
                        </form>
                    </div>

                    <div id="subOrgForm" style="display:none; margin-top: 30px;">
                        <form method="POST" enctype="multipart/form-data" action="">
                            <input type="hidden" name="organization" id="hiddenSubOrg" value="">
                            <h3>Sub Organization Candidate Details</h3>
                            <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                                <div style="flex: 1 1 200px;">
                                    <label>Last Name:</label>
                                    <input type="text" name="last_name_sub" required>
                                </div>
                                <div style="flex: 1 1 200px;">
                                    <label>First Name:</label>
                                    <input type="text" name="first_name_sub" required>
                                </div>
                                <div style="flex: 1 1 200px;">
                                    <label>Position Candidating For:</label>
                                    <select name="position_sub" required>
                                        <option value="">Select position</option>
                                        <option value="Representative">Representative</option>
                                        <option value="President">President</option>
                                        <option value="Vice President">Vice President</option>
                                        <option value="Secretary">Secretary</option>
                                        <option value="Treasurer">Treasurer</option>
                                        <option value="Auditor">Auditor</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div style="flex: 1 1 200px;">
                                    <label>Middle Name:</label>
                                    <input type="text" name="middle_name_sub">
                                </div>
                                <div style="flex: 1 1 150px;">
                                    <label>Year:</label>
                                    <input type="number" name="year_sub" min="1" max="4" required>
                                </div>
                                <div style="flex: 1 1 300px;">
                                    <label>Address:</label>
                                    <textarea name="block_address_sub" rows="2" required></textarea>
                                </div>
                            </div>
                            <button type="submit" name="submitFilingSub" class="btn" style="margin-top: 20px;">Submit Filing</button>
                        </form>
                    </div>
                </div>
            </div>

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
                                $profilePic = !empty($row['profile_pic']) ? htmlspecialchars($row['profile_pic']) : '';
                                $collegeDisplay = !empty($row['college']) ? "<div class='college-subtitle'>College: " . htmlspecialchars($row['college']) . "</div>" : "<div class='college-subtitle'>College: Not specified</div>";

                                echo "<div class='candidate-card'>";
                                echo "<div class='candidate-image'>";
                                echo $profilePic ? "<img src='$profilePic' alt='Profile Picture' onerror=\"this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iI2YzZjRmNiIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LXNpemU9IjEyIiBmaWxsPSIjOWNhM2FmIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+Tm8gSW1hZ2U8L3RleHQ+PC9zdmc+';\" />" : "<img src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iI2YzZjRmNiIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LXNpemU9IjEyIiBmaWxsPSIjOWNhM2FmIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+Tm8gSW1hZ2U8L3RleHQ+PC9zdmc+' alt='No Image'>";
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
                            echo "<div class='candidate-position'>" . htmlspecialchars($row['position'] ?? 'Representative') . "</div>";
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

            <div id="votesSection" style="display:none; padding:20px;" class="<?= !$votingStatus['can_vote'] ? 'voting-disabled' : '' ?>">
                <div class="voting-section">
                    <h2>Cast Your Vote</h2>
                    <?php if ($votingStatus['can_vote']): ?>
                        <?php
                        $voter_id = $_SESSION['user_id'];
                        $checkVoted = $conn->prepare("SELECT id FROM votes WHERE voter_id = ?");
                        $checkVoted->bind_param("i", $voter_id);
                        $checkVoted->execute();
                        $hasVoted = $checkVoted->get_result()->num_rows > 0;
                        $checkVoted->close();
                        if ($hasVoted): ?>
                            <div class="already-voted-message">
                                <div class="voted-icon">✓</div>
                                <h3>You Have Already Voted!</h3>
                                <p>Thank you for participating in the election. Your vote has been securely recorded.</p>
                                <p>Multiple voting is not allowed to ensure fair elections.</p>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="" id="votingForm">
                                <?php
                                $mainCandidatesVote = $conn->query("SELECT * FROM main_org_candidates WHERE status='Accepted'");
                                if ($mainCandidatesVote && $mainCandidatesVote->num_rows > 0) {
                                    echo "<div class='candidate-group'>";
                                    echo "<h3>Main Organization</h3>";
                                    while ($row = $mainCandidatesVote->fetch_assoc()) {
                                        $org = htmlspecialchars($row['organization'] ?? 'Main Organization');
                                        $position = htmlspecialchars($row['position'] ?? 'Position not specified');
                                        echo "<div class='candidate-option'>";
                                        echo "<input type='radio' name='main_{$position}' value='{$row['id']}' id='main{$row['id']}'>";
                                        echo "<label for='main{$row['id']}'>";
                                        echo htmlspecialchars(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) . " - {$position}";
                                        echo "<br><small>Organization: {$org}</small>";
                                        echo "</label>";
                                        echo "</div>";
                                    }
                                    echo "</div>";
                                } else {
                                    echo "<div class='candidate-group'><p>No candidates filed for Main Organization.</p></div>";
                                }
                                $subCandidatesVote = $conn->query("SELECT * FROM sub_org_candidates WHERE status='Accepted'");
                                if ($subCandidatesVote && $subCandidatesVote->num_rows > 0) {
                                    echo "<div class='candidate-group'>";
                                    echo "<h3>Sub Organization</h3>";
                                    while ($row = $subCandidatesVote->fetch_assoc()) {
                                        $org = htmlspecialchars($row['organization'] ?? 'Sub Organization');
                                        echo "<div class='candidate-option'>";
                                        echo "<input type='radio' name='sub_{$org}' value='{$row['id']}' id='sub{$row['id']}'>";
                                        echo "<label for='sub{$row['id']}'>";
                                        echo htmlspecialchars(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) . " - " . htmlspecialchars($row['position'] ?? 'Representative');
                                        echo "<br><small>Organization: {$org} - Year " . ($row['year'] ?? 'N/A') . "</small>";
                                        echo "</label>";
                                        echo "</div>";
                                    }
                                    echo "</div>";
                                } else {
                                    echo "<div class='candidate-group'><p>No candidates filed for Sub Organization.</p></div>";
                                }
                                if (($mainCandidatesVote && $mainCandidatesVote->num_rows > 0) || ($subCandidatesVote && $subCandidatesVote->num_rows > 0)) {
                                    echo "<button type='submit' name='submitVote' class='vote-submit-btn'>Submit Vote</button>";
                                }
                                ?>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 50px;">
                            <h-marquee3>Voting is Currently Closed</h3>
                            <p><?= htmlspecialchars($votingStatus['message']) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div id="notificationDropdown">
                <div>Notifications</div>
                <ul>
                    <?php if ($notificationCount > 0): ?>
                        <?php foreach ($notifications as $note): ?>
                            <li><?= htmlspecialchars($note) ?></li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>No new notifications.</li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="modal" id="rulesModal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal('rulesModal')">&times;</span>
                    <main id="voters-section">
                        <h1 class="title">How to Cast Your Vote</h1>
                        <div class="steps-grid">
                            <div class="step-box">
                                <div class="step-icon"><i class="fas fa-user-check"></i></div>
                                <div class="step-number">Step 1</div>
                                <div class="step-title">Register and Login</div>
                                <div class="step-description">
                                    Ensure you have registered and securely logged into the CATSU-iVote system using your credentials.
                                </div>
                            </div>
                            <div class="step-box">
                                <div class="step-icon"><i class="fas fa-vote-yea"></i></div>
                                <div class="step-number">Step 2</div>
                                <div class="step-title">Select Your Candidate</div>
                                <div class="step-description">
                                    Browse through the list of candidates and choose your preferred representatives for each position.
                                </div>
                            </div>
                            <div class="step-box">
                                <div class="step-icon"><i class="fas fa-paper-plane"></i></div>
                                <div class="step-number">Step 3</div>
                                <div class="step-title">Submit Your Vote</div>
                                <div class="step-description">
                                    Review your selections carefully and confirm submission to cast your official vote.
                                </div>
                            </div>
                            <div class="step-box">
                                <div class="step-icon"><i class="fas fa-shield-alt"></i></div>
                                <div class="step-number">Step 4</div>
                                <div class="step-title">Secure Confirmation</div>
                                <div class="step-description">
                                    Receive confirmation that your vote has been securely recorded in the system.
                                </div>
                            </div>
                            <div class="step-box">
                                <div class="step-icon"><i class="fas fa-chart-line"></i></div>
                                <div class="step-number">Step 5</div>
                                <div class="step-title">Track Progress</div>
                                <div class="step-description">
                                    Monitor voting turnout updates and ensure transparency of the election.
                                </div>
                            </div>
                            <div class="step-box">
                                <div class="step-icon"><i class="fas fa-check-circle"></i></div>
                                <div class="step-number">Step 6</div>
                                <div class="step-title">Finalize Process</div>
                                <div class="step-description">
                                    Ensure all procedures are followed and the voting process is officially completed.
                                </div>
                            </div>
                        </div>
                        <div class="dont-show">
                            <input type="checkbox" id="dontShowAgain">
                            <label for="dontShowAgain">Don't show again</label>
                        </div>
                    </main>
                </div>
            </div>
        </div>
    </div>

    <script>
        const mainOrgSelect = document.getElementById('mainOrg');
        const subOrgSelect = document.getElementById('subOrg');
        const mainOrgForm = document.getElementById('mainOrgForm');
        const subOrgForm = document.getElementById('subOrgForm');
        const mainOrgSelectWrapper = document.getElementById('mainOrgSelectWrapper');
        const subOrgSelectWrapper = document.getElementById('subOrgSelectWrapper');
        const hiddenMainOrg = document.getElementById('hiddenMainOrg');
        const hiddenSubOrg = document.getElementById('hiddenSubOrg');
        const rulesModal = document.getElementById('rulesModal');
        const dontShowCheckbox = document.getElementById('dontShowAgain');
        const rulesBtn = document.getElementById('rulesBtn');
        const filingBtn = document.getElementById('filingBtn');
        const dashboardBtn = document.getElementById('dashboardBtn');
        const candidatesBtn = document.getElementById('candidatesBtn');
        const votesBtn = document.getElementById('votesBtn');
        const filingSection = document.getElementById('filing-section');
        const dashboardSection = document.getElementById('dashboardSection');
        const candidatesSection = document.getElementById('candidatesSection');
        const votesSection = document.getElementById('votesSection');

        function handleMainOrgChange() {
            if (mainOrgSelect.value) {
                hiddenMainOrg.value = mainOrgSelect.value;
                mainOrgForm.style.display = 'block';
                subOrgForm.style.display = 'none';
                subOrgSelectWrapper.style.display = 'none';
                subOrgSelect.value = '';
                hiddenSubOrg.value = '';
            } else {
                mainOrgForm.style.display = 'none';
                subOrgSelectWrapper.style.display = 'block';
            }
        }

        function handleSubOrgChange() {
            if (subOrgSelect.value) {
                hiddenSubOrg.value = subOrgSelect.value;
                subOrgForm.style.display = 'block';
                mainOrgForm.style.display = 'none';
                mainOrgSelectWrapper.style.display = 'none';
                mainOrgSelect.value = '';
                hiddenMainOrg.value = '';
            } else {
                subOrgForm.style.display = 'none';
                mainOrgSelectWrapper.style.display = 'block';
            }
        }

        function showSection(sectionToShow) {
            console.log('Showing section:', sectionToShow ? sectionToShow.id : 'none');
            const sections = [dashboardSection, filingSection, candidatesSection, votesSection];
            sections.forEach(section => {
                if (section) {
                    section.style.display = (section === sectionToShow) ? 'block' : 'none';
                    console.log(`${section.id} display: ${section.style.display}`);
                }
            });
            if (rulesModal) rulesModal.style.display = 'none';
        }

        function closeModal(id) {
            if (dontShowCheckbox.checked) {
                localStorage.setItem('hideRules', 'true');
            }
            document.getElementById(id).style.display = 'none';
        }

        function calculateAge() {
            let dob = document.getElementById('dob').value;
            if (dob) {
                let today = new Date();
                let birthDate = new Date(dob);
                let age = today.getFullYear() - birthDate.getFullYear();
                let monthDiff = today.getMonth() - birthDate.getMonth();
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                document.getElementById('age').value = age;
            } else {
                document.getElementById('age').value = '';
            }
        }

        document.getElementById('notificationBell').addEventListener('click', function(e) {
            e.preventDefault();
            const dropdown = document.getElementById('notificationDropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        });

        window.addEventListener('click', function(e) {
            const dropdown = document.getElementById('notificationDropdown');
            const bell = document.getElementById('notificationBell');
            if (!dropdown.contains(e.target) && !bell.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });

        window.onload = function() {
            if (!localStorage.getItem('hideRules')) {
                rulesModal.style.display = 'block';
            }
            showSection(dashboardSection);
            updateDashboardData();
            setInterval(updateDashboardData, 30000);
        };

        rulesBtn.addEventListener('click', function(e) {
            e.preventDefault();
            rulesModal.style.display = 'block';
        });

        filingBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showSection(filingSection);
        });

        dashboardBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showSection(dashboardSection);
        });

        candidatesBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showSection(candidatesSection);
        });

        votesBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showSection(votesSection);
        });

        document.addEventListener('DOMContentLoaded', function() {
            const votingForm = document.getElementById('votingForm');
            if (votingForm) {
                votingForm.addEventListener('submit', function(e) {
                    const radios = votingForm.querySelectorAll('input[type="radio"]:checked');
                    if (radios.length === 0) {
                        e.preventDefault();
                        alert('Please select at least one candidate before submitting your vote.');
                        return false;
                    }
                    if (!confirm('Are you sure you want to submit your vote?\n\nThis action is FINAL and cannot be undone.')) {
                        e.preventDefault();
                        return false;
                    }
                    const btn = votingForm.querySelector('button[name="submitVote"]');
                    if (btn) {
                        btn.disabled = true;
                        btn.innerHTML = 'Submitting...';
                    }
                });
            }
        });

        function updateDashboardData() {
            fetch('dashboard_data.php')
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        console.error('Dashboard error:', data.error);
                        return;
                    }
                    document.getElementById('totalVotersCount').textContent = data.totalVoters || 0;
                    document.getElementById('votedCount').textContent = data.voted || 0;
                    document.getElementById('notVotedCount').textContent = data.notVoted || 0;
                    document.getElementById('totalCandidatesCount').textContent = data.totalCandidates || 0;
                })
                .catch(err => {
                    console.error('Error fetching dashboard data:', err);
                    document.getElementById('dashboardSection').innerHTML += '<p style="color: red; text-align: center;">Failed to load dashboard data.</p>';
                });
        }

        document.getElementById('orgSelect').addEventListener('change', function() {
            const org = this.value;
            const container = document.getElementById('orgCandidatesContainer');
            if (!org) {
                container.innerHTML = '<p style="text-align: center; color: #6b7280; font-style: italic;">Select an organization to view vote tally</p>';
                return;
            }

            container.innerHTML = '<p style="text-align: center;">Loading vote tally...</p>';

            fetch('fetch_org_candidates.php?organization=' + encodeURIComponent(org))
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        container.innerHTML = '<p style="color:red; text-align: center;">Error: ' + data.error + '</p>';
                        return;
                    }
                    if (!data.candidates || data.candidates.length === 0) {
                        container.innerHTML = '<p style="text-align: center; color: #6b7280;">No accepted candidates found for ' + org + '.</p>';
                        return;
                    }

                    const positionOrder = [
                        'President', 'Vice President', 'Executive Secretary', 'Finance Secretary', 
                        'Budget Secretary', 'Auditor', 'Public Information Secretary', 
                        'Property Custodian', 'Senators', 'Legislators', 'Year Representative', 
                        'Representative', 'Other'
                    ];

                    const sortedCandidates = data.candidates.sort((a, b) => {
                        const posA = positionOrder.indexOf(a.position) !== -1 ? positionOrder.indexOf(a.position) : positionOrder.length;
                        const posB = positionOrder.indexOf(b.position) !== -1 ? positionOrder.indexOf(b.position) : positionOrder.length;
                        return posA - posB;
                    });

                    let html = `<h3 style="text-align: center; margin-bottom: 20px; color: #1f2937;">Vote Tally for ${org}</h3>`;
                    html += `<table>`;
                    html += `<thead><tr><th>Candidate Name</th><th>Position</th><th style="text-align: center;">Total Votes</th></tr></thead><tbody>`;

                    sortedCandidates.forEach(c => {
                        const fullName = [c.first_name, c.middle_name, c.last_name].filter(Boolean).join(' ');
                        const position = c.position || 'Representative';
                        html += `<tr><td><strong>${fullName}</strong></td><td><span class="tally-position">${position}</span></td><td style="text-align: center;">${c.total_votes || 0}</td></tr>`;
                    });

                    html += `</tbody></table>`;
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

        const collegePrograms = {
            'CICT': [
                'Bachelor of Science in Information Systems',
                'Bachelor of Science in Information Technology',
                'Bachelor of Science in Computer Science',
                'Bachelor of Science in Entertainment and Multimedia Computing - Game Development',
                'Bachelor of Science in Entertainment and Multimedia Computing - Digital Animation',
                'Bachelor of Library and Information Science'
            ],
            'CBA': [
                'Bachelor of Science in Accountancy',
                'Bachelor of Science in Accounting Information System',
                'Bachelor of Science in Business Administration - Financial Management',
                'Bachelor of Science in Business Administration - Human Resource Development Management',
                'Bachelor of Science in Business Administration - Management',
                'Bachelor of Science in Business Administration - Marketing Management',
                'Bachelor of Science in Entrepreneurship',
                'Bachelor of Science in Internal Auditing',
                'Bachelor of Science in Office Administration'
            ],
            'CIT': [
                'Bachelor of Industrial Technology - Culinary',
                'Bachelor of Industrial Technology - Mechatronics',
                'Bachelor of Industrial Technology - Architectural Drafting',
                'Bachelor of Industrial Technology - Welding and Fabrication',
                'BS in Industrial Technology - Automotive',
                'BS in Industrial Technology - Drafting',
                'BS in Industrial Technology - Electrical',
                'BS in Industrial Technology - Electronics',
                'BS in Industrial Technology - Food and Service Management'
            ],
            'CHS': [
                'Bachelor of Science in Nursing',
                'Bachelor of Science in Nutrition and Dietetics'
            ],
            'CEA': [
                'Bachelor of Science in Civil Engineering',
                'Bachelor of Science in Computer Engineering',
                'Bachelor of Science in Electronics & Communication Engineering',
                'Bachelor of Science in Architecture (no graduates yet)'
            ],
            'CHUMMS': [
                'Bachelor of Arts in Political Science',
                'Bachelor of Arts in Economics',
                'Bachelor of Public Administration',
                'Bachelor of Arts in English Language'
            ],
            'COS': [
                'Bachelor of Science in Biology',
                'Bachelor of Science in Environmental Science',
                'Bachelor of Science in Mathematics'
            ],
            'CAF': [
                'Bachelor of Science in Agriculture - Animal Husbandry',
                'Bachelor of Science in Agriculture - Crop Science',
                'Bachelor of Science in AgriBusiness',
                'Bachelor of Science in Fisheries',
                'Certificate in Agricultural Science'
            ],
            'COED': [
                'Bachelor of Elementary Education',
                'Bachelor of Secondary Education - English',
                'Bachelor of Secondary Education - Filipino',
                'Bachelor of Secondary Education - Mathematics',
                'Bachelor of Secondary Education - Biological Science',
                'Bachelor of Secondary Education - Social Studies',
                'Bachelor of Secondary Education - Music, Arts, PE',
                'Bachelor of Technical-Vocational Teacher Education - Electronics Technology',
                'Bachelor of Technical-Vocational Teacher Education - Food and Service Management',
                'Bachelor of Culture and Arts Education',
                'Bachelor of Physical Education',
                'Bachelor of Arts in English Language'
            ]
        };

        const collegeSelect = document.getElementById('collegeSelect');
        const programSelect = document.getElementById('programSelect');

        collegeSelect.addEventListener('change', function() {
            const selectedCollege = this.value;
            programSelect.options.length = 1;
            if (selectedCollege && collegePrograms[selectedCollege]) {
                collegePrograms[selectedCollege].forEach(program => {
                    const option = document.createElement('option');
                    option.value = program;
                    option.textContent = program;
                    programSelect.appendChild(option);
                });
            }
        });
    </script>
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
