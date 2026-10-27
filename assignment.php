<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireLogin();

$assignment_id = $_GET['id'] ?? 0;
$message = '';
$db = DB::getInstance();
$assignment = $db->fetch("SELECT * FROM assignments WHERE assignment_id = ?", [$assignment_id]);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $submission_type = $_POST['submission_type']; // voice, video, text, file
    $file_path = '';
    if ($submission_type === 'file' && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {
        $file_path = SUBMISSION_DIR . generateUniqueFilename($_FILES['submission_file']['name']);
        move_uploaded_file($_FILES['submission_file']['tmp_name'], $file_path);
    } elseif ($submission_type === 'voice' && isset($_POST['voice_data'])) {
        // base64 audio data from Web Speech API
        $file_path = SUBMISSION_DIR . 'voice_' . time() . '.wav';
        file_put_contents($file_path, base64_decode($_POST['voice_data']));
    } elseif ($submission_type === 'video' && isset($_POST['video_data'])) {
        // base64 video
        $file_path = SUBMISSION_DIR . 'video_' . time() . '.webm';
        file_put_contents($file_path, base64_decode($_POST['video_data']));
    }

    $db->insert('submissions', [
        'assignment_id' => $assignment_id,
        'student_id' => $_SESSION['user_id'],
        'submission_type' => $submission_type,
        'file_path' => $file_path,
        'transcript' => $_POST['transcript'] ?? ''
    ]);
    $message = 'Assignment submitted successfully!';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Submit Assignment</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <h2><?= htmlspecialchars($assignment['title'] ?? 'Assignment') ?></h2>
    <?php if ($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label>Submission Type</label>
            <select name="submission_type" class="form-select" onchange="toggleFields(this.value)">
                <option value="text">Text</option>
                <option value="file">File Upload</option>
                <option value="voice">Voice Recording</option>
                <option value="video">Video Recording</option>
            </select>
        </div>
        <div id="textField" class="mb-3">
            <label>Your Answer</label>
            <textarea name="transcript" class="form-control" rows="5"></textarea>
        </div>
        <div id="fileField" class="mb-3" style="display:none;">
            <label>Upload File</label>
            <input type="file" name="submission_file" class="form-control">
        </div>
        <div id="voiceField" class="mb-3" style="display:none;">
            <button type="button" class="btn btn-primary" onclick="startRecording()">Start Recording</button>
            <button type="button" class="btn btn-danger" onclick="stopRecording()">Stop</button>
            <input type="hidden" name="voice_data" id="voiceData">
        </div>
        <div id="videoField" class="mb-3" style="display:none;">
            <button type="button" class="btn btn-primary" onclick="startVideoRecording()">Start Video</button>
            <button type="button" class="btn btn-danger" onclick="stopVideoRecording()">Stop</button>
            <input type="hidden" name="video_data" id="videoData">
        </div>
        <button type="submit" name="submit" class="btn btn-success">Submit</button>
    </form>
</div>
<script>
function toggleFields(type) {
    document.getElementById('textField').style.display = type === 'text' ? 'block' : 'none';
    document.getElementById('fileField').style.display = type === 'file' ? 'block' : 'none';
    document.getElementById('voiceField').style.display = type === 'voice' ? 'block' : 'none';
    document.getElementById('videoField').style.display = type === 'video' ? 'block' : 'none';
}
// Placeholder recording functions (use MediaRecorder API)
let mediaRecorder;
function startRecording() {
    navigator.mediaDevices.getUserMedia({ audio: true }).then(stream => {
        mediaRecorder = new MediaRecorder(stream);
        mediaRecorder.ondataavailable = e => {
            const reader = new FileReader();
            reader.onload = () => document.getElementById('voiceData').value = reader.result.split(',')[1];
            reader.readAsDataURL(e.data);
        };
        mediaRecorder.start();
    });
}
function stopRecording() { if (mediaRecorder) mediaRecorder.stop(); }
function startVideoRecording() { /* similar */ }
function stopVideoRecording() { /* similar */ }
</script>
</body>
</html>