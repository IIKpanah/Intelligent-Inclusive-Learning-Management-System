<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireLogin();

$action = $_GET['action'] ?? 'view';
$course_id = $_GET['course_id'] ?? 0;
$message = '';

if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireRole('lecturer');
    $course_id = $_POST['course_id'];
    $file = $_FILES['material'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $target = COURSE_UPLOAD_DIR . generateUniqueFilename($file['name']);
        move_uploaded_file($file['tmp_name'], $target);

        // Convert using AI helper
        require_once 'includes/ai_helper.php';
        $text = extractTextFromPDF($target);
        $audio = textToSpeech($text);
        $simplified = simplifyText($text);
        $summaryFlash = summarizeAndFlashcard($text);

        $db = DB::getInstance();
        $db->insert('course_materials', [
            'course_id' => $course_id,
            'original_file' => $target,
            'audio_file' => $audio,
            'simplified_text' => $simplified,
            'summary_text' => $summaryFlash['summary'],
            'flashcards' => json_encode($summaryFlash['flashcards'])
        ]);
        $message = 'Material uploaded and converted successfully!';
    }
}

// Fetch course details and materials
$db = DB::getInstance();
$course = $db->fetch("SELECT * FROM courses WHERE course_id = ?", [$course_id]);
$materials = $db->fetchAll("SELECT * FROM course_materials WHERE course_id = ?", [$course_id]);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Course Materials</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <h2><?= htmlspecialchars($course['course_title'] ?? 'Course') ?></h2>
    <?php if ($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>
    <?php if ($_SESSION['role'] === 'lecturer'): ?>
        <form method="post" enctype="multipart/form-data" action="?action=upload">
            <input type="hidden" name="course_id" value="<?= $course_id ?>">
            <div class="mb-3">
                <label>Upload Material (PDF, DOC, PPT, Video)</label>
                <input type="file" name="material" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Upload & Convert</button>
        </form>
        <hr>
    <?php endif; ?>
    <h4>Available Materials</h4>
    <ul class="list-group">
        <?php foreach ($materials as $mat): ?>
            <li class="list-group-item">
                <a href="<?= BASE_URL . '/' . $mat['original_file'] ?>">Original</a>
                <?php if ($mat['audio_file']): ?><a href="<?= BASE_URL . '/' . $mat['audio_file'] ?>" class="ms-2">Audio</a><?php endif; ?>
                <?php if ($mat['simplified_text']): ?><a href="#" class="ms-2" onclick="alert('<?= addslashes($mat['simplified_text']) ?>')">Simplified</a><?php endif; ?>
                <?php if ($mat['summary_text']): ?><a href="#" class="ms-2" onclick="alert('<?= addslashes($mat['summary_text']) ?>')">Summary</a><?php endif; ?>
                <?php if ($mat['flashcards']): ?><a href="#" class="ms-2" onclick="alert('<?= addslashes($mat['flashcards']) ?>')">Flashcards</a><?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
</body>
</html>