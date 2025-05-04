<?php
session_start();
require_once 'config.php';
require_once 'admin_functions.php';
require_once 'navigation.php';

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'], $conn)) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Access denied.</div></div>";
    exit;
}

// Handle file saving
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['file_name']) && isset($_POST['file_content'])) {
    $file = basename($_POST['file_name']);
    if (in_array($file, ['config.php', 'send_email.php'])) {
        file_put_contents($file, $_POST['file_content']);
        $message = "File '$file' saved successfully.";
    }
}
?>

<div class="container mt-5">
    <h2>Admin Dashboard</h2>

    <?php if (isset($message)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="mb-3">
        <a href="manage_users.php" class="btn btn-primary">Manage Users</a>
    </div>

    <h4>Edit Configuration Files</h4>
    <form method="post">
        <div class="mb-3">
            <label for="file_name" class="form-label">Select file:</label>
            <select class="form-select" name="file_name" id="file_name" onchange="this.form.submit()">
                <option value="">-- Choose file --</option>
                <option value="config.php" <?= ($_POST['file_name'] ?? '') === 'config.php' ? 'selected' : '' ?>>config.php</option>
                <option value="send_email.php" <?= ($_POST['file_name'] ?? '') === 'send_email.php' ? 'selected' : '' ?>>send_email.php</option>
            </select>
        </div>
    </form>

    <?php
    if (!empty($_POST['file_name']) && in_array($_POST['file_name'], ['config.php', 'send_email.php'])):
        $content = htmlspecialchars(file_get_contents($_POST['file_name']));
    ?>
        <form method="post">
            <input type="hidden" name="file_name" value="<?= htmlspecialchars($_POST['file_name']) ?>">
            <div class="mb-3">
                <label for="file_content" class="form-label"><?= htmlspecialchars($_POST['file_name']) ?> content:</label>
                <textarea name="file_content" id="file_content" class="form-control" rows="20"><?= $content ?></textarea>
            </div>
            <button type="submit" class="btn btn-success">Save File</button>
        </form>
    <?php endif; ?>
</div>

