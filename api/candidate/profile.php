<?php
require_once __DIR__ . '/../_bootstrap.php';

$user = requireApiAuth('candidate');
$candidate = getCandidateByUserId((int) $user['id']);
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    apiJson(['ok' => true, 'profile' => userPayload($user)['profile'], 'email' => $user['email']]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiError('Method not allowed', 405);
}

$fullName = clean($_POST['full_name'] ?? '');
$phone = clean($_POST['phone'] ?? '');
$location = clean($_POST['location'] ?? '');
$skills = clean($_POST['skills'] ?? '');
$education = trim($_POST['education'] ?? '');
$experience = trim($_POST['experience'] ?? '');
$bio = trim($_POST['bio'] ?? '');

if ($fullName === '') {
    apiError('Full name is required.');
}

$photoName = $candidate['photo'];
$resumeName = $candidate['resume'];

if (!empty($_FILES['photo']['name'])) {
    $v = validateUpload($_FILES['photo'], ALLOWED_IMAGE_TYPES, MAX_IMAGE_SIZE, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    if (!$v['ok']) {
        apiError('Photo: ' . $v['error']);
    }
    $stored = storeUpload($_FILES['photo'], PHOTO_PATH, 'photo_' . $candidate['id'], $v['ext']);
    if (!$stored) {
        apiError('Failed to upload photo.');
    }
    deleteUpload(PHOTO_PATH, $candidate['photo']);
    $photoName = $stored;
}

if (!empty($_FILES['resume']['name'])) {
    $v = validateUpload($_FILES['resume'], ALLOWED_RESUME_TYPES, MAX_RESUME_SIZE, ['pdf']);
    if (!$v['ok']) {
        apiError('Resume: ' . $v['error']);
    }
    $stored = storeUpload($_FILES['resume'], RESUME_PATH, 'resume_' . $candidate['id'], $v['ext']);
    if (!$stored) {
        apiError('Failed to upload resume.');
    }
    deleteUpload(RESUME_PATH, $candidate['resume']);
    $resumeName = $stored;
}

$pdo->prepare(
    'UPDATE candidates SET full_name=?, phone=?, location=?, skills=?, education=?, experience=?, bio=?, photo=?, resume=? WHERE id=?'
)->execute([
    $fullName, $phone ?: null, $location ?: null, $skills ?: null,
    $education ?: null, $experience ?: null, $bio ?: null,
    $photoName, $resumeName, $candidate['id'],
]);

$fresh = getCandidateByUserId((int) $user['id']);
apiJson(['ok' => true, 'message' => 'Profile updated.', 'profile' => [
    'id' => (int) $fresh['id'],
    'full_name' => $fresh['full_name'],
    'phone' => $fresh['phone'],
    'photo' => $fresh['photo'],
    'skills' => $fresh['skills'],
    'education' => $fresh['education'],
    'experience' => $fresh['experience'],
    'resume' => $fresh['resume'],
    'bio' => $fresh['bio'],
    'location' => $fresh['location'],
]]);
