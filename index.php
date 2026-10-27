<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['register'])) {
        // Registration with disability profile
        $full_name = sanitizeInput($_POST['full_name']);
        $email = sanitizeInput($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $disability_type = $_POST['disability_type'];
        $preferences = json_encode([
            'voice_nav' => isset($_POST['voice_nav']),
            'large_fonts' => isset($_POST['large_fonts']),
            'sign_language' => isset($_POST['sign_language']),
        ]);

        $db = DB::getInstance();

        // Check if email exists
        $existing = $db->fetch("SELECT user_id FROM users WHERE email = ?", [$email]);
        if ($existing) {
            $error = 'Email already registered.';
        } else {
            // Insert disability
            $db->insert('disabilities', [
                'type' => $disability_type,
                'accessibility_preferences' => $preferences,
                'learning_preferences' => json_encode([])
            ]);
            $disability_id = $db->lastInsertId();

            // Insert user
            $db->insert('users', [
                'full_name' => $full_name,
                'email' => $email,
                'password_hash' => $password,
                'role' => 'student',
                'disability_id' => $disability_id,
                'status' => 'active'
            ]);
            $error = 'Account created! Please login.';
        }
    } else {
        // Login
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'];
        if (loginUser($email, $password)) {
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IILMS - Inclusive Learning</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/fontawesome.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="text-center mb-3"><i class="fas fa-universal-access text-primary"></i> IILMS</h1>
                    <p class="text-center text-muted">Intelligent Inclusive Learning Management System</p>
                    <?php if ($error): ?>
                        <div class="alert alert-<?= strpos($error, 'created') !== false ? 'success' : 'danger' ?>">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    <ul class="nav nav-tabs" id="authTab" role="tablist">
                        <li class="nav-item"><button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login">Login</button></li>
                        <li class="nav-item"><button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register">Register</button></li>
                    </ul>
                    <div class="tab-content mt-4">
                        <!-- Login -->
                        <div class="tab-pane fade show active" id="login">
                            <form method="post">
                                <div class="mb-3">
                                    <label for="loginEmail" class="form-label">Email</label>
                                    <input type="email" name="email" id="loginEmail" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="loginPassword" class="form-label">Password</label>
                                    <input type="password" name="password" id="loginPassword" class="form-control" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Sign In</button>
                            </form>
                        </div>
                        <!-- Register -->
                        <div class="tab-pane fade" id="register">
                            <form method="post">
                                <div class="mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="full_name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Disability Type</label>
                                    <select name="disability_type" class="form-select" required>
                                        <option value="none">None</option>
                                        <option value="blind">Blind / Visual Impairment</option>
                                        <option value="deaf">Deaf / Hard of Hearing</option>
                                        <option value="speech_impaired">Speech Impaired</option>
                                        <option value="physical">Physical Disability</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label>Accessibility Preferences</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="voice_nav" id="voiceNav">
                                        <label class="form-check-label" for="voiceNav">Voice Navigation</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="large_fonts" id="largeFonts">
                                        <label class="form-check-label" for="largeFonts">Large Fonts</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="sign_language" id="signLang">
                                        <label class="form-check-label" for="signLang">Sign Language Videos</label>
                                    </div>
                                </div>
                                <button type="submit" name="register" class="btn btn-success w-100">Register</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/voice.js"></script>
</body>
</html>