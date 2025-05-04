<?php
session_start();
require_once 'config.php';
require_once 'admin_functions.php';
require_once 'navigation.php';

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'], $conn)) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Access denied.</div></div>";
    exit;
}

$current_user = $_SESSION['user_id'];

// Benutzer sperren / entsperren
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    $new_status = ($_GET['toggle'] === 'lock') ? 'locked' : 'active';

    if ($user_id !== $current_user) {
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $user_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: manage_users.php");
    exit;
}

// Benutzer befördern / degradieren
if (isset($_GET['admin']) && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    $new_admin = ($_GET['admin'] === 'promote') ? 1 : 0;

    if ($user_id !== $current_user) {
        $stmt = $conn->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_admin, $user_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: manage_users.php");
    exit;
}
?>

<div class="container mt-5">
    <h2>Manage Users</h2>
    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Registered</th>
                    <th>Status</th>
                    <th>Admin</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $users = $conn->query("SELECT id, username, email, created_at, status, is_admin FROM users ORDER BY id ASC");
                while ($user = $users->fetch_assoc()):
                ?>
                <tr>
                    <td><?= htmlspecialchars($user['id']) ?></td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= $user['created_at'] ?></td>
                    <td>
                        <?php if ($user['status'] === 'locked'): ?>
                            <span class="text-danger">Locked</span>
                        <?php else: ?>
                            <span class="text-success">Active</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= $user['is_admin'] ? '<span class="text-primary">Yes</span>' : 'No' ?>
                    </td>
                    <td>
                        <?php if ($user['id'] != $current_user): ?>
                            <?php if ($user['status'] === 'locked'): ?>
                                <a href="?toggle=unlock&id=<?= $user['id'] ?>" class="btn btn-sm btn-success mb-1">Unlock</a>
                            <?php else: ?>
                                <a href="?toggle=lock&id=<?= $user['id'] ?>" class="btn btn-sm btn-warning mb-1">Lock</a>
                            <?php endif; ?>

                            <?php if ($user['is_admin']): ?>
                                <a href="?admin=demote&id=<?= $user['id'] ?>" class="btn btn-sm btn-danger mb-1">Demote</a>
                            <?php else: ?>
                                <a href="?admin=promote&id=<?= $user['id'] ?>" class="btn btn-sm btn-primary mb-1">Promote</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted">Current User</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

