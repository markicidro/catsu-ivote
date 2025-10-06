<!-- Filing Section -->
<div class="details" id="filing-section" style="padding: 20px;">
    <div class="recentOrders">
        <div class="cardHeader">
            <h2>Filing of Candidacy</h2>
        </div>

        <div style="display: flex; gap: 40px; margin-top: 20px;">
            <!-- Left side: Main Organization -->
            <div style="flex: 1;" id="mainOrgSelectWrapper">
                <label for="mainOrg"><strong>Main Organization</strong></label>
                <select id="mainOrg" name="main_org" onchange="handleMainOrgChange()">
                    <option value="">Select Main Org</option>
                    <option value="USC">USC (University Student Council)</option>
                    <option value="CSC">CSC (College Student Council)</option>
                </select>
            </div>
             <!-- Right side: Sub Organization -->
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

        <!-- Form for Main Organization -->
        <div id="mainOrgForm" style="display:none; margin-top: 30px;">
            <form method="POST" enctype="multipart/form-data" action="">
                <input type="hidden" name="organization" id="hiddenMainOrg" value="" />
                <h3>Main Organization Candidate Details</h3>
                <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                    <div style="flex: 1 1 200px;">
                        <label>Upload 1x1 Picture:</label>
                        <input type="file" name="profile_pic" accept="image/*" required />
                    </div>
                    <div style="flex: 1 1 200px;">
                        <label>Last Name:</label>
                        <input type="text" name="last_name" required />
                    </div>
                    <div style="flex: 1 1 200px;">
                        <label>First Name:</label>
                        <input type="text" name="first_name" required />
                    </div>
                    <div style="flex: 1 1 200px;">
                        <label>Middle Name:</label>
                        <input type="text" name="middle_name" />
                    </div>
                    <div style="flex: 1 1 200px;">
                        <label>Nickname:</label>
                        <input type="text" name="nickname" />
                    </div>
                    <div style="flex: 1 1 200px;">
                        <label>Age:</label>
                        <input type="text" name="age" id="age" readonly />
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
                        <input type="date" name="dob" id="dob" required onchange="calculateAge()" />
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
                        <input type="number" name="year" min="1" max="5" required />
                    </div>
                   <div style="flex: 1 1 200px;">
                        <label>Program:</label>
                        <select name="program" id="programSelect" required>
                            <option value="">Select Program</option>
                        </select>
                    </div>
                    <div style="flex: 1 1 200px;">
                        <label>Phone:</label>
                        <input type="tel" name="phone" required />
                    </div>
                    <div style="flex: 1 1 300px;">
                        <label>Email Address:</label>
                        <input type="email" name="email" required />
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
                        <input type="text" name="partylist" />
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
                       <input type="number" name="residency_years" min="0" max="10" placeholder="Years" required style="flex:1;" />
                       <input type="number" name="residency_semesters" min="0" max="20" placeholder="Semesters" required style="flex:1;" />
                    </div>
                       <small style="color: #6b7280;">Enter how many years and semesters you have studied in the university</small>
                    </div>
                    <div style="flex: 1 1 200px;">
                        <label>Semester and Year in Current College and University:</label>
                        <input type="text" name="semester_year" required />
                    </div>
                    <div style="flex: 1 1 300px;">
                        <label>Upload Certificate of Candidacy:</label><br>
                        <a href="files/COMELEC-FORM-NO.-1.docx" target="_blank" style="color: blue; text-decoration: underline;">
                            Download Certificate of Candidacy (PDF)
                        </a><br><br>
                        <input type="file" name="CertificateofCandidacy" accept=".jpg, .jpeg" required />
                    </div>
                    <div style="flex: 1 1 300px;">
                        <label>Upload COMELEC Form 1:</label><br>
                        <a href="files/COMELEC-FORM-NO.-1.docx" target="_blank" style="color: blue; text-decoration: underline;">
                            Download COMELEC Form 1 (PDF)
                        </a><br><br>
                        <input type="file" name="comelec_form_1" accept=".jpg, .jpeg" required />
                    </div>
                    <div style="flex: 1 1 300px;">
                        <label>Upload Certificate of Recommendation:</label><br>
                        <a href="files/Certificate-of-Recommendation.docx" target="_blank" style="color: blue; text-decoration: underline;">
                            Download Certificate of Recommendation (PDF)
                        </a><br><br>
                        <input type="file" name="CertificateOfRecommendation" accept=".jpg, .jpeg" required />
                    </div>
                    <div style="flex: 1 1 300px;">
                        <label>Upload Photocopy of Prospectus:</label>
                        <input type="file" name="prospectus" accept="image/*,application/pdf" required />
                    </div>
                    <div style="flex: 1 1 300px;">
                        <label>Upload Clearance:</label>
                        <input type="file" name="clearance" accept="image/*,application/pdf" required />
                    </div>
                    <div style="flex: 1 1 300px;">
                        <label>Upload Photocopy of COE:</label>
                        <input type="file" name="coe" accept="image/*,application/pdf" required />
                    </div>
                </div>
                <button type="submit" name="submitFiling" class="btn" style="margin-top: 20px;">Submit Filing</button>
            </form>
        </div>

        <!-- Form for Sub Organization -->
        <div id="subOrgForm" style="display:none; margin-top: 30px;">
            <form method="POST" enctype="multipart/form-data" action="">
                <input type="hidden" name="organization" id="hiddenSubOrg" value="" />
                <h3>Sub Organization Candidate Details</h3>
                <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                    <div style="flex: 1 1 200px;">
                        <label>Last Name:</label>
                        <input type="text" name="last_name_sub" required />
                    </div>
                    <div style="flex: 1 1 200px;">
                        <label>First Name:</label>
                        <input type="text" name="first_name_sub" required />
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
                        <input type="text" name="middle_name_sub" />
                    </div>
                    <div style="flex: 1 1 150px;">
                        <label>Year:</label>
                        <input type="number" name="year_sub" min="1" max="4" required />
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
</div><!DOCTYPE html>
<?php
session_start(); 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// For testing, assign user_id if not set (replace with real auth in production)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; 
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ivote_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
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

// AES Encryption Functions - CHANGE THE KEY!
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

// ------------------- MAIN -------------------

$votingStatus = canVote($conn);

$message = "";
$messageType = "";

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['messageType'] ?? '';
    unset($_SESSION['message'], $_SESSION['messageType']);
}

$userId = $_SESSION['user_id'] ?? null;

// Handle Vote Submission - FIXED VERSION
if (isset($_POST['submitVote']) && $votingStatus['can_vote']) {
    $voter_id = $userId;
    
    if (!$voter_id) {
        $_SESSION['message'] = "User not authenticated. Please login again.";
        $_SESSION['messageType'] = "error";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Check if user already voted
    $checkVote = $conn->prepare("SELECT id FROM votes WHERE voter_id = ?");
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
        
        // Insert into votes table
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
                // Get organization and position from database
                $orgQuery = $conn->prepare("SELECT organization, position FROM main_org_candidates WHERE id = ? AND status = 'Accepted'");
                if (!$orgQuery) {
                    throw new Exception("Failed to prepare org query: " . $conn->error);
                }
                $orgQuery->bind_param("i", $candidate_id);
                $orgQuery->execute();
                $orgResult = $orgQuery->get_result();
                
                if ($orgResult->num_rows === 0) {
                    $orgQuery->close();
                    throw new Exception("Invalid candidate ID: $candidate_id");
                }
                
                $orgData = $orgResult->fetch_assoc();
                $organization = $orgData['organization'];
                $position = $orgData['position'];
                $orgQuery->close();
                
                // Insert into vote_tally
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
                // Extract organization from key
                $organization = str_replace('sub_', '', $key);
                $organization = str_replace('_', ' ', $organization);
                
                // Verify candidate and get position
                $verifyQuery = $conn->prepare("SELECT position FROM sub_org_candidates WHERE id = ? AND status = 'Accepted' AND organization = ?");
                if (!$verifyQuery) {
                    throw new Exception("Failed to prepare sub verify query: " . $conn->error);
                }
                $verifyQuery->bind_param("is", $candidate_id, $organization);
                $verifyQuery->execute();
                $verifyResult = $verifyQuery->get_result();
                
                if ($verifyResult->num_rows === 0) {
                    $verifyQuery->close();
                    throw new Exception("Invalid sub org candidate ID: $candidate_id");
                }
                
                $subData = $verifyResult->fetch_assoc();
                $position = $subData['position'] ?? 'Representative';
                $verifyQuery->close();
                
                // Insert into vote_tally
                $insertTally = $conn->prepare("INSERT INTO vote_tally (vote_id, candidate_id, candidate_type, position, organization) VALUES (?, ?, 'sub', ?, ?)");
                if (!$insertTally) {
                    throw new Exception("Failed to prepare sub tally insert: " . $conn->error);
                }
                $insertTally->bind_param("iiss", $vote_id, $candidate_id, $position, $organization);
                if (!$insertTally->execute()) {
                    throw new Exception("Failed to insert sub vote tally for candidate $candidate_id: " . $insertTally->error);
                }
                $insertTally->close();
            }
        }
        
        $conn->commit();
        
        $_SESSION['message'] = "Your vote has been successfully submitted and encrypted!";
        $_SESSION['messageType'] = "success";
        error_log("VOTE SUCCESS: User $voter_id voted. Vote ID: $vote_id");
        
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

// Total registered voters
$totalVoters = 0;
$votedCount = 0;
$notVotedCount = 0;
$totalCandidates = 0;

$result = $conn->query("SELECT COUNT(*) as total FROM users");
if ($result) {
    $row = $result->fetch_assoc();
    $totalVoters = intval($row['total']);
    $result->free();
}

// Count voters who voted
$result = $conn->query("SELECT COUNT(DISTINCT voter_id) as voted FROM votes");
if ($result) {
    $row = $result->fetch_assoc();
    $votedCount = intval($row['voted']);
    $result->free();
}

$notVotedCount = $totalVoters - $votedCount;

// Count total accepted candidates (main + sub)
$result = $conn->query("SELECT COUNT(*) as total FROM main_org_candidates WHERE status='Accepted'");
$totalCandidatesMain = 0;
if ($result) {
    $row = $result->fetch_assoc();
    $totalCandidatesMain = intval($row['total']);
    $result->free();
}

$result = $conn->query("SELECT COUNT(*) as total FROM sub_org_candidates WHERE status='Accepted'");
$totalCandidatesSub = 0;
if ($result) {
    $row = $result->fetch_assoc();
    $totalCandidatesSub = intval($row['total']);
    $result->free();
}

$totalCandidates = $totalCandidatesMain + $totalCandidatesSub;

// ------------------- FETCH NOTIFICATIONS -------------------

$notificationCount = 0;
$notifications = [];

if ($userId) {
    $stmt = $conn->prepare("SELECT id FROM main_org_candidates WHERE user_id = ? AND status = 'Accepted'");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $countMain = $result->num_rows;
        $notificationCount += $countMain;
        for ($i=0; $i < $countMain; $i++) {
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
        for ($i=0; $i < $countSub; $i++) {
            $notifications[] = "Your filing for Sub Organization has been approved.";
        }
        $stmt2->close();
    }
}

// Handle Main Organization Filing
if (isset($_POST['submitFiling'])) {
    $organization = $_POST['organization'] ?? "";
    $first_name = $_POST['first_name'] ?? "";
    $middle_name = $_POST['middle_name'] ?? "";
    $last_name = $_POST['last_name'] ?? "";
    $nickname = $_POST['nickname'] ?? "";
    $age = $_POST['age'] ?? "";
    $gender = $_POST['gender'] ?? "";
    $dob = $_POST['dob'] ?? "";
    $college = $_POST['college'] ?? "";
    $year = $_POST['year'] ?? "";
    $program = $_POST['program'] ?? "";
    $phone = $_POST['phone'] ?? "";
    $email = $_POST['email'] ?? "";
    $partylist = $_POST['partylist'] ?? "";
    $position = $_POST['position'] ?? "";
    $permanent_address = $_POST['permanent_address'] ?? "";
    $temporary_address = $_POST['temporary_address'] ?? "";
    $residency_years = intval($_POST['residency_years'] ?? 0);
    $residency_semesters = intval($_POST['residency_semesters'] ?? 0);
    $semester_year = $_POST['semester_year'] ?? "";

    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $profile_pic = uploadFile('profile_pic', $uploadDir) ?? "";
    $comelec_form_1 = uploadFile('comelec_form_1', $uploadDir) ?? "";
    $recommendation_letter = uploadFile('CertificateOfRecommendation', $uploadDir) ?? "";
    $prospectus = uploadFile('prospectus', $uploadDir) ?? "";
    $clearance = uploadFile('clearance', $uploadDir) ?? "";
    $coe = uploadFile('coe', $uploadDir) ?? "";
    $certificate_of_candidacy = uploadFile('CertificateofCandidacy', $uploadDir) ?? "";

    $stmt = $conn->prepare("INSERT INTO main_org_candidates
        (user_id, organization, first_name, middle_name, last_name, nickname, age, gender, dob, college, year, program, phone, email, position, partylist, permanent_address, temporary_address, residency_years, residency_semesters, semester_year, profile_pic, comelec_form_1, recommendation_letter, prospectus, clearance, coe, certificate_of_candidacy, status, filing_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");

    if (!$stmt) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }

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
        $stmt->close();
        $_SESSION['message'] = "Main Organization filing submitted successfully!";
        $_SESSION['messageType'] = "success";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $message = "Error submitting filing: " . $stmt->error;
        $messageType = "error";
        $stmt->close();
    }
}

// Handle Sub Organization Filing
if (isset($_POST['submitFilingSub'])) {
    $organization = $_POST['organization'] ?? '';
    $first_name_sub = $_POST['first_name_sub'] ?? '';
    $middle_name_sub = $_POST['middle_name_sub'] ?? '';
    $position_sub = $_POST['position_sub'] ?? 'Representative';
    $last_name_sub = $_POST['last_name_sub'] ?? '';
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
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }
    
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
        $stmt->close();
        $_SESSION['message'] = "Sub Organization filing submitted successfully!";
        $_SESSION['messageType'] = "success";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $message = "Error submitting filing: " . $stmt->error;
        $messageType = "error";
        $stmt->close();
        error_log("Sub Org Filing Error: " . $stmt->error);
    }
}

?>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CATSU iVote</title>
    <link rel="stylesheet" href="assets/css/style.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
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

.dont-show {
    margin-top: 15px;
    display: flex;
    align-items: center;
    font-size: 0.9rem;
}

.dont-show input {
    margin-right: 5px;
}

/* Filing Section */
#filing-section {
    display: none;
    padding: 20px;
}

#filing-section label {
    display: block;
    margin-top: 10px;
    font-weight: bold;
}

#filing-section select,
#filing-section input[type="text"],
#filing-section textarea,
#filing-section input[type="file"] {
    width: 100%;
    padding: 8px;
    margin-top: 5px;
    box-sizing: border-box;
}

#filing-form {
    margin-top: 20px;
}

#filing-form button.btn {
    margin-top: 10px;
    padding: 10px 20px;
    background-color: #4f46e5;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

#filing-form button.btn:hover {
    background-color: #4338ca;
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

/* Voting Disabled State */
.voting-disabled .voting-section {
    opacity: 0.6;
    pointer-events: none;
    filter: grayscale(50%);
}

/* Filing Section Container */
.details#filing-section {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    max-width: 1000px;
    margin: 30px auto;
    padding: 30px 40px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.details#filing-section .cardHeader h2 {
    font-size: 2rem;
    font-weight: 700;
    color: #4f46e5;
    margin-bottom: 25px;
    text-align: center;
}

.details#filing-section > .recentOrders > div:first-child {
    display: flex;
    gap: 40px;
    justify-content: center;
    margin-bottom: 30px;
}

.details#filing-section label {
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
    display: block;
    font-size: 1rem;
}

.details#filing-section select {
    width: 100%;
    padding: 10px 12px;
    border: 1.8px solid #cbd5e1;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
}

.details#filing-section select:focus {
    border-color: #4f46e5;
    outline: none;
}

#mainOrgForm form,
#subOrgForm form {
    background: #f9fafb;
    padding: 25px 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(79, 70, 229, 0.15);
}

#mainOrgForm h3,
#subOrgForm h3 {
    color: #4f46e5;
    font-weight: 700;
    margin-bottom: 20px;
    font-size: 1.5rem;
    text-align: center;
}

#mainOrgForm form > div,
#subOrgForm form > div {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: space-between;
}

#mainOrgForm form > div > div,
#subOrgForm form > div > div {
    flex: 1 1 45%;
    min-width: 220px;
    display: flex;
    flex-direction: column;
}

#mainOrgForm label,
#subOrgForm label {
    font-weight: 600;
    margin-bottom: 6px;
    color: #374151;
}

#mainOrgForm input[type="text"],
#mainOrgForm input[type="email"],
#mainOrgForm input[type="tel"],
#mainOrgForm input[type="number"],
#mainOrgForm input[type="date"],
#mainOrgForm select,
#mainOrgForm textarea,
#mainOrgForm input[type="file"],
#subOrgForm input[type="text"],
#subOrgForm input[type="number"],
#subOrgForm textarea {
    padding: 10px 12px;
    border: 1.8px solid #cbd5e1;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
    resize: vertical;
}

#mainOrgForm input[type="text"]:focus,
#mainOrgForm input[type="email"]:focus,
#mainOrgForm input[type="tel"]:focus,
#mainOrgForm input[type="number"]:focus,
#mainOrgForm input[type="date"]:focus,
#mainOrgForm select:focus,
#mainOrgForm textarea:focus,
#mainOrgForm input[type="file"]:focus,
#subOrgForm input[type="text"]:focus,
#subOrgForm input[type="number"]:focus,
#subOrgForm textarea:focus {
    border-color: #4f46e5;
    outline: none;
}

#mainOrgForm input[type="file"],
#subOrgForm input[type="file"] {
    padding: 6px 10px;
}

#mainOrgForm button.btn,
#subOrgForm button.btn {
    display: block;
    width: 100%;
    max-width: 300px;
    margin: 30px auto 0;
    padding: 14px 0;
    background-color: #4f46e5;
    color: white;
    font-weight: 700;
    font-size: 1.1rem;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

#mainOrgForm button.btn:hover,
#subOrgForm button.btn:hover {
    background-color: #4338ca;
}

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

/* Candidates Profile Section Styles */
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
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.candidate-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(79, 70, 229, 0.15);
    border-color: #4f46e5;
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
    margin-bottom: 15px;
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

.org-badge.sub-org {
    background: linear-gradient(135deg, #059669, #10b981);
}

.college-info {
    color: #6b7280;
    font-size: 0.9rem;
    font-weight: 500;
    text-align: center;
    line-height: 1.3;
}

.year-info {
    color: #374151;
    font-size: 0.9rem;
    font-weight: 500;
    background: #e5e7eb;
    padding: 4px 10px;
    border-radius: 12px;
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

.candidate-subtitle {
    color: #6b7280;
    font-size: 0.9rem;
    font-weight: 500;
    margin-top: 5px;
}

/* Notification Bell Styles */
#notificationBell {
    position: relative;
    display: flex;
    align-items: center;
    cursor: pointer;
    color: #4f46e5;
    font-weight: 600;
}

#notificationBell .icon {
    font-size: 24px;
    margin-right: 6px;
}

#notificationCount {
    position: absolute;
    top: 2px;
    right: 0px;
    background: red;
    color: white;
    font-size: 0.75rem;
    font-weight: bold;
    padding: 2px 6px;
    border-radius: 50%;
    user-select: none;
}

#notificationDropdown {
    display: none;
    position: fixed;
    top: 60px;
    right: 20px;
    width: 320px;
    max-height: 400px;
    overflow-y: auto;
    background: white;
    border: 1px solid #ccc;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 2000;
    font-family: Arial, sans-serif;
}

#notificationDropdown > div {
    padding: 10px;
    font-weight: bold;
    border-bottom: 1px solid #eee;
    color: #4f46e5;
}

#notificationDropdown ul {
    list-style: none;
    margin: 0;
    padding: 10px;
}

#notificationDropdown ul li {
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
    font-size: 0.9rem;
    color: #333;
}

#notificationDropdown ul li:last-child {
    border-bottom: none;
}

#orgCandidatesContainer table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

#orgCandidatesContainer th,
#orgCandidatesContainer td {
    border: 1px solid #e5e7eb;
    padding: 12px;
    text-align: left;
}

#orgCandidatesContainer th {
    background-color: #4f46e5;
    color: white;
    font-weight: 600;
    text-align: left;
}

#orgCandidatesContainer tbody tr:nth-child(even) {
    background-color: #f9fafb;
}

#orgCandidatesContainer tbody tr:hover {
    background-color: #e0e7ff;
    transition: background-color 0.2s ease;
}

#orgCandidatesContainer td:last-child {
    text-align: center;
    font-weight: bold;
    color: #4f46e5;
    font-size: 1.1rem;
}

.tally-position {
    color: #6b7280;
    font-size: 0.9rem;
    font-style: italic;
}

/* Voting Section Styles */
.voting-section {
    background: #fff;
    border-radius: 16px;
    padding: 40px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    position: relative;
}

.voting-section > h2 {
    text-align: center;
    color: #1e293b;
    font-size: 2.5rem;
    margin-bottom: 40px;
    font-weight: 800;
}

.voting-section-title {
    font-size: 1.8rem;
    color: #4f46e5;
    margin: 40px 0 25px;
    padding-bottom: 15px;
    border-bottom: 3px solid #e0e7ff;
    font-weight: 700;
}

.org-voting-group {
    background: #f8fafc;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
    border: 2px solid #e2e8f0;
}

.org-voting-title {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 25px;
    flex-wrap: wrap;
}

.org-badge-vote {
    padding: 10px 20px;
    border-radius: 25px;
    font-size: 1.2rem;
    font-weight: 700;
    color: white;
}

.org-badge-vote.main {
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
}

.org-badge-vote.sub {
    background: linear-gradient(135deg, #059669, #10b981);
}

.position-voting-block {
    background: white;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #e5e7eb;
}

.position-voting-header {
    color: #1e293b;
    font-size: 1.3rem;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.position-icon {
    font-size: 1.5rem;
}

.vote-instruction {
    font-size: 0.9rem;
    color: #6b7280;
    font-weight: 500;
    margin-left: auto;
}

.candidates-voting-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.candidate-vote-card {
    position: relative;
}

.vote-radio {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.vote-card-label-simple {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 25px;
    background: white;
    border: 3px solid #e5e7eb;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    min-height: 80px;
}

.vote-card-label-simple:hover {
    border-color: #4f46e5;
    box-shadow: 0 8px 25px rgba(79, 70, 229, 0.15);
    transform: translateY(-2px);
}

.vote-radio:checked + .vote-card-label-simple {
    border-color: #4f46e5;
    background: linear-gradient(135deg, #eef2ff, #e0e7ff);
    box-shadow: 0 8px 30px rgba(79, 70, 229, 0.25);
}

.vote-candidate-info {
    text-align: left;
    flex: 1;
}

.vote-candidate-name {
    font-weight: 700;
    font-size: 1.15rem;
    color: #1e293b;
    margin-bottom: 5px;
}

.vote-partylist, .vote-college, .vote-year {
    font-size: 0.85rem;
    color: #6b7280;
    margin-top: 3px;
}

.vote-checkmark {
    width: 30px;
    height: 30px;
    background: #4f46e5;
    color: white;
    border-radius: 50%;
    display: none;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    font-weight: bold;
    flex-shrink: 0;
}

.vote-radio:checked + .vote-card-label-simple .vote-checkmark {
    display: flex;
}

.vote-submit-container {
    text-align: center;
    margin-top: 50px;
    padding-top: 30px;
    border-top: 2px solid #e5e7eb;
}

.vote-submit-btn {
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    color: white;
    padding: 18px 50px;
    font-size: 1.3rem;
    font-weight: 800;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 12px;
    transition: all 0.3s ease;
    box-shadow: 0 10px 30px rgba(79, 70, 229, 0.3);
}

.vote-submit-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 40px rgba(79, 70, 229, 0.4);
}

.vote-submit-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.btn-icon {
    font-size: 1.5rem;
}

.vote-disclaimer {
    margin-top: 20px;
    color: #6b7280;
    font-size: 0.95rem;
}

/* Already Voted Message */
.already-voted-message {
    text-align: center;
    padding: 60px 40px;
    background: linear-gradient(135deg, #d1fae5, #a7f3d0);
    border-radius: 16px;
    border: 3px solid #10b981;
}

.voted-icon {
    width: 100px;
    height: 100px;
    background: #10b981;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 4rem;
    margin: 0 auto 25px;
    font-weight: bold;
}

.already-voted-message h3 {
    color: #047857;
    font-size: 2rem;
    margin-bottom: 15px;
}

.already-voted-message p {
    color: #065f46;
    font-size: 1.1rem;
    margin: 10px 0;
}

/* Responsive Design */
@media (max-width: 768px) {
    .candidates-voting-grid {
        grid-template-columns: 1fr;
    }
    
    .voting-section {
        padding: 20px;
    }
    
    .voting-section > h2 {
        font-size: 2rem;
    }
    
    .voting-section-title {
        font-size: 1.5rem;
    }
    
    .vote-submit-btn {
        padding: 15px 30px;
        font-size: 1.1rem;
    }
    
    .vote-card-label-simple {
        padding: 15px 20px;
    }
}