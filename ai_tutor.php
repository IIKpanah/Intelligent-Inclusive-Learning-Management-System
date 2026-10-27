<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireLogin();
require_once 'includes/ai_helper.php';

$disability = getUserDisability();
$question = '';
$response = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['question'])) {
    $question = sanitizeInput($_POST['question']);
    $response = callOpenAI($question, $disability);
    // Log to database
    $db = DB::getInstance();
    $db->insert('ai_logs', [
        'user_id' => $_SESSION['user_id'],
        'query' => $question,
        'response' => $response,
        'disability_type' => $disability
    ]);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>AI Tutor</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <h2><i class="fas fa-robot text-primary"></i> AI Tutor</h2>
    <form method="post">
        <div class="input-group">
            <input type="text" name="question" class="form-control form-control-lg" placeholder="Ask anything..." required>
            <button type="submit" class="btn btn-primary btn-lg">Ask</button>
        </div>
    </form>
    <?php if ($response): ?>
        <div class="card mt-4">
            <div class="card-body">
                <h5>Answer:</h5>
                <p class="lead" id="aiResponse"><?= nl2br(htmlspecialchars($response)) ?></p>
                <?php if ($disability === 'blind'): ?>
                    <button class="btn btn-secondary" onclick="speak(document.getElementById('aiResponse').innerText);">
                        <i class="fas fa-volume-up"></i> Listen
                    </button>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<script>
function speak(text) {
    if ('speechSynthesis' in window) {
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'en-US';
        window.speechSynthesis.speak(utterance);
    }
}
</script>
</body>
</html>