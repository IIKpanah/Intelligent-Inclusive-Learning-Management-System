<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireLogin();

$db = DB::getInstance();
$user = $db->fetch("SELECT * FROM users WHERE user_id = ?", [$_SESSION['user_id']]);
$disability = $db->fetch("SELECT * FROM disabilities WHERE disability_id = ?", [$_SESSION['disability_id']]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $preferences = json_encode([
        'voice_nav' => isset($_POST['voice_nav']),
        'large_fonts' => isset($_POST['large_fonts']),
        'sign_language' => isset($_POST['sign_language']),
    ]);
    $db->update('disabilities', ['accessibility_preferences' => $preferences], ['disability_id' => $_SESSION['disability_id']]);
    $message = 'Preferences updated.';
}
$prefs = json_decode($disability['accessibility_preferences'] ?? '{}', true);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Profile</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <h2>Your Accessibility Profile</h2>
    <?php if (isset($message)): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>
    <form method="post">
        <div class="mb-3">
            <label>Disability Type: <?= ucfirst($disability['type'] ?? 'None') ?></label>
        </div>
        <div class="mb-3">
            <label>Accessibility Preferences</label>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="voice_nav" <?= ($prefs['voice_nav'] ?? false) ? 'checked' : '' ?>>
                <label class="form-check-label">Voice Navigation</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="large_fonts" <?= ($prefs['large_fonts'] ?? false) ? 'checked' : '' ?>>
                <label class="form-check-label">Large Fonts</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="sign_language" <?= ($prefs['sign_language'] ?? false) ? 'checked' : '' ?>>
                <label class="form-check-label">Sign Language Videos</label>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Update</button>
    </form>
</div>
</body>
</html>