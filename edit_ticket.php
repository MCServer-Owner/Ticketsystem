<?php
session_start();
require_once 'config.php';
require_once 'admin_functions.php';
require_once 'navigation.php';

if (!isset($_GET['id'])) {
    echo "No ticket specified.";
    exit;
}

$ticket_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
$is_admin = isAdmin($user_id, $conn);

// Load ticket
$stmt = $conn->prepare("SELECT t.*, u.username AS creator_name FROM tickets t LEFT JOIN users u ON t.user_id = u.id WHERE t.id = ?");
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$result = $stmt->get_result();
$ticket = $result->fetch_assoc();
$stmt->close();

if (!$ticket) {
    echo "Ticket not found.";
    exit;
}

// Handle ticket actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Take unassigned ticket
    if (isset($_POST['take_ticket'])) {
        if (empty($ticket['assigned_to'])) {
            $stmt = $conn->prepare("UPDATE tickets SET assigned_to = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ii", $user_id, $ticket_id);
            $stmt->execute();
            $stmt->close();
            
            // Add to status history
            $stmt = $conn->prepare("INSERT INTO ticket_status_history (ticket_id, old_status, new_status, assigned_user_id, changed_by) 
                                   VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issii", $ticket_id, $ticket['status'], $ticket['status'], $user_id, $user_id);
            $stmt->execute();
            $stmt->close();
            
            $ticket['assigned_to'] = $user_id;
        }
    } 
    // Admin updates (status/assignment)
    elseif (isset($_POST['update_ticket']) && $is_admin) {
        $new_status = $_POST['status'] ?? $ticket['status'];
        $assigned_to = $_POST['assigned_to'] ?? null;

        if ($new_status !== $ticket['status'] || $assigned_to != $ticket['assigned_to']) {
            $stmt = $conn->prepare("UPDATE tickets SET status = ?, assigned_to = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("sii", $new_status, $assigned_to, $ticket_id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO ticket_status_history (ticket_id, old_status, new_status, assigned_user_id, changed_by)
                                    VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issii", $ticket_id, $ticket['status'], $new_status, $assigned_to, $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    header("Location: edit_ticket.php?id=" . $ticket_id);
    exit;
}

// Add comment
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_comment'])) {
    $comment = trim($_POST['comment']);
    if (!empty($comment)) {
        $stmt = $conn->prepare("INSERT INTO ticket_comments (ticket_id, comment, user_id, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("isi", $ticket_id, $comment, $user_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: edit_ticket.php?id=" . $ticket_id);
    exit;
}

// Edit comment
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_comment'])) {
    $comment_id = intval($_POST['comment_id']);
    $comment = trim($_POST['comment']);
    $stmt = $conn->prepare("SELECT user_id FROM ticket_comments WHERE id = ?");
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($is_admin || $result['user_id'] == $user_id) {
        $stmt = $conn->prepare("UPDATE ticket_comments SET comment = ? WHERE id = ?");
        $stmt->bind_param("si", $comment, $comment_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: edit_ticket.php?id=" . $ticket_id);
    exit;
}

// Delete comment
if (isset($_GET['delete_comment'])) {
    $comment_id = intval($_GET['delete_comment']);
    $stmt = $conn->prepare("SELECT user_id FROM ticket_comments WHERE id = ?");
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $comment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($is_admin || $comment['user_id'] == $user_id) {
        $stmt = $conn->prepare("DELETE FROM ticket_comments WHERE id = ?");
        $stmt->bind_param("i", $comment_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: edit_ticket.php?id=" . $ticket_id);
    exit;
}
?>

<div class="container mt-4">
    <h2>Ticket #<?= $ticket_id ?></h2>
    
    <!-- Ticket Information -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title"><?= htmlspecialchars($ticket['title']) ?></h5>
            <p class="card-text"><strong>Status:</strong> <?= ucfirst($ticket['status']) ?></p>
            <p class="card-text"><strong>Created by:</strong> <?= htmlspecialchars($ticket['creator_name']) ?></p>
            <p class="card-text"><strong>Description:</strong><br><?= nl2br(htmlspecialchars($ticket['description'])) ?></p>
            
            <!-- Assignment Information -->
            <p class="card-text">
                <strong>Assigned to:</strong> 
                <?php 
                if (empty($ticket['assigned_to'])) {
                    echo '<span class="text-danger">Unassigned</span>';
                } else {
                    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
                    $stmt->bind_param("i", $ticket['assigned_to']);
                    $stmt->execute();
                    $stmt->bind_result($assigned_username);
                    $stmt->fetch();
                    $stmt->close();
                    echo htmlspecialchars($assigned_username);
                }
                ?>
            </p>
            
            <!-- Ticket Action Buttons -->
            <?php if (empty($ticket['assigned_to'])): ?>
                <form method="post">
                    <input type="hidden" name="take_ticket" value="1">
                    <button type="submit" class="btn btn-primary">Take this ticket</button>
                </form>
            <?php elseif ($is_admin): ?>
                <form method="post">
                    <input type="hidden" name="update_ticket" value="1">
                    <div class="mb-3">
                        <label for="status" class="form-label"><strong>Status:</strong></label>
                        <select class="form-select" name="status">
                            <option value="open" <?= $ticket['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                            <option value="in_progress" <?= $ticket['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="closed" <?= $ticket['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="assigned_to" class="form-label"><strong>Reassign to:</strong></label>
                        <select class="form-select" name="assigned_to">
                            <option value="">Unassigned</option>
                            <?php
                            $users = $conn->query("SELECT id, username FROM users");
                            while ($u = $users->fetch_assoc()):
                            ?>
                                <option value="<?= $u['id'] ?>" <?= $ticket['assigned_to'] == $u['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['username']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            <?php elseif ($ticket['assigned_to'] == $user_id): ?>
                <div class="alert alert-info">You are assigned to this ticket</div>
            <?php else: ?>
                <div class="alert alert-warning">This ticket is already assigned to another user</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Comments Section -->
    <h4>Comments</h4>
    <?php
    $stmt = $conn->prepare("SELECT c.id, c.comment, c.created_at, c.user_id, u.username FROM ticket_comments c LEFT JOIN users u ON c.user_id = u.id WHERE c.ticket_id = ? ORDER BY c.created_at DESC");
    $stmt->bind_param("i", $ticket_id);
    $stmt->execute();
    $comments = $stmt->get_result();
    while ($row = $comments->fetch_assoc()):
        $can_edit = $is_admin || $row['user_id'] == $user_id;
    ?>
        <div class="border p-2 mb-2 bg-light">
            <?php if (isset($_GET['edit_comment']) && $_GET['edit_comment'] == $row['id'] && $can_edit): ?>
                <form method="post">
                    <input type="hidden" name="edit_comment" value="1">
                    <input type="hidden" name="comment_id" value="<?= $row['id'] ?>">
                    <textarea class="form-control mb-2" name="comment" rows="3" required><?= htmlspecialchars($row['comment']) ?></textarea>
                    <button type="submit" class="btn btn-sm btn-success">Save</button>
                    <a href="edit_ticket.php?id=<?= $ticket_id ?>" class="btn btn-sm btn-secondary">Cancel</a>
                </form>
            <?php else: ?>
                <p class="mb-1"><?= nl2br(htmlspecialchars($row['comment'])) ?></p>
                <small>By <?= htmlspecialchars($row['username']) ?> on <?= $row['created_at'] ?></small>
                <?php if ($can_edit): ?>
                    <div class="mt-1">
                        <a href="edit_ticket.php?id=<?= $ticket_id ?>&edit_comment=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                        <a href="edit_ticket.php?id=<?= $ticket_id ?>&delete_comment=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this comment?')">Delete</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endwhile; $stmt->close(); ?>

    <!-- Add Comment Form -->
    <form method="post" class="mt-3">
        <input type="hidden" name="add_comment" value="1">
        <div class="mb-3">
            <label for="comment" class="form-label"><strong>Add Comment:</strong></label>
            <textarea class="form-control" name="comment" rows="3" required></textarea>
        </div>
        <button type="submit" class="btn btn-secondary">Submit Comment</button>
    </form>

    <!-- Status History -->
    <hr>
    <h4>Status History</h4>
    <ul class="list-group">
        <?php
        $stmt = $conn->prepare("SELECT tsh.*, u.username AS changer, a.username AS assigned_user FROM ticket_status_history tsh LEFT JOIN users u ON tsh.changed_by = u.id LEFT JOIN users a ON tsh.assigned_user_id = a.id WHERE tsh.ticket_id = ? ORDER BY tsh.changed_at DESC");
        $stmt->bind_param("i", $ticket_id);
        $stmt->execute();
        $history = $stmt->get_result();
        while ($row = $history->fetch_assoc()):
        ?>
            <li class="list-group-item">
                <strong><?= htmlspecialchars($row['new_status']) ?></strong>
                <?php if (!empty($row['old_status'])): ?>
                    <span class="text-muted">(changed from <?= htmlspecialchars($row['old_status']) ?>)</span>
                <?php endif; ?>
                <?= !empty($row['assigned_user']) ? ' – assigned to ' . htmlspecialchars($row['assigned_user']) : '' ?>
                – changed on <?= htmlspecialchars($row['changed_at']) ?> by <?= htmlspecialchars($row['changer']) ?>
            </li>
        <?php endwhile; $stmt->close(); ?>
    </ul>
</div>
