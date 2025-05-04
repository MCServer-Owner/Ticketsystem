<?php
session_start();
require_once 'config.php';
require_once 'send_email.php';
require_once 'admin_functions.php';
include 'navigation.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$is_admin = isAdmin($user_id, $conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $assigned_to = $_POST['assigned_to'] ?? null;

    $stmt = $conn->prepare("INSERT INTO tickets (title, description, status, user_id, assigned_to) VALUES (?, ?, 'open', ?, ?)");
    $stmt->bind_param("ssii", $title, $description, $user_id, $assigned_to);
    $stmt->execute();
    $ticket_id = $stmt->insert_id;
    $stmt->close();

    // Email to creator
    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($email);
    $stmt->fetch();
    $stmt->close();

    $ticketLink = "http://support.myts3server.at/support/edit_ticket.php?id=" . $ticket_id;
    $subject = "Ticket #$ticket_id has been created";
    $message = "Your ticket has been successfully created.<br><br><a href='$ticketLink'>View Ticket</a>";
    send_email($email, $subject, $message);

    // Email to assigned user
    if (!empty($assigned_to) && $assigned_to != $user_id) {
        $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->bind_param("i", $assigned_to);
        $stmt->execute();
        $stmt->bind_result($assigned_email);
        $stmt->fetch();
        $stmt->close();

        $subject = "New Ticket #$ticket_id has been assigned to you";
        $message = "A new ticket has been assigned to you.<br><br><a href='$ticketLink'>View Ticket</a>";
        send_email($assigned_email, $subject, $message);
    }

    header("Location: list_tickets.php");
    exit;
}
?>

<div class="container mt-4">
    <h2>Create New Ticket</h2>
    <form method="POST">
        <div class="mb-3">
            <label for="title" class="form-label">Title*</label>
            <input type="text" class="form-control" name="title" required>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description*</label>
            <textarea class="form-control" name="description" rows="4" required></textarea>
        </div>

        <!-- Assignment dropdown -->
        <div class="mb-3">
            <label for="assigned_to" class="form-label"><strong>Assign to:</strong></label>
            <select class="form-select" name="assigned_to">
                <option value="">Unassigned</option>
                
                <!-- Special technical users -->
                <?php
                $special_users = $conn->query("
                    SELECT id, username 
                    FROM users 
                    WHERE username IN ('emergency', 'maintenance', 'root')
                    ORDER BY FIELD(username, 'emergency', 'maintenance', 'root')
                ");
                
                while ($u = $special_users->fetch_assoc()):
                    $style = '';
                    if ($u['username'] == 'emergency') {
                        $style = 'style="font-weight: bold; color: #dc3545;"';
                    } elseif ($u['username'] == 'maintenance') {
                        $style = 'style="font-weight: bold; color: #fd7e14;"';
                    } elseif ($u['username'] == 'root') {
                        $style = 'style="font-weight: bold; color: #0d6efd;"';
                    }
                ?>
                    <option value="<?= $u['id'] ?>" <?= $style ?>>
                        <?= htmlspecialchars($u['username']) ?> (<?= $u['id'] ?>)
                    </option>
                <?php endwhile; ?>
                
                <option disabled>───────────────</option>
                
                <!-- Regular users -->
                <?php
                $regular_users = $conn->query("
                    SELECT id, username 
                    FROM users 
                    WHERE username NOT IN ('emergency', 'maintenance', 'root')
                    AND id != $user_id
                    ORDER BY username
                ");
                
                while ($u = $regular_users->fetch_assoc()):
                ?>
                    <option value="<?= $u['id'] ?>">
                        <?= htmlspecialchars($u['username']) ?> (<?= $u['id'] ?>)
                    </option>
                <?php endwhile; ?>
            </select>
            <small class="text-muted">
                Select "emergency" for critical outages, "maintenance" for maintenance tickets
            </small>
        </div>

        <button type="submit" class="btn btn-primary">Create Ticket</button>
    </form>
</div>
