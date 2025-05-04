<?php
session_start();
require_once 'config.php';
require_once 'admin_functions.php';
require_once 'navigation.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$filter_status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// SQL Query mit optionalem Filter
$sql = "SELECT t.*, u.username AS author_name, a.username AS assigned_to_name
        FROM tickets t
        JOIN users u ON t.user_id = u.id
        LEFT JOIN users a ON t.assigned_to = a.id
        WHERE (t.user_id = ? OR t.assigned_to = ?)";
$params = [$user_id, $user_id];
$types = "ii";

if ($filter_status) {
    $sql .= " AND t.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if ($search) {
    $sql .= " AND (t.title LIKE ? OR t.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

$sql .= " ORDER BY t.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>My Tickets</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .ticket-container {
            background: #f9f9f9;
            border: 1px solid #ccc;
            margin: 1em 0;
            padding: 1em;
            border-radius: 8px;
        }
        .ticket-actions {
            margin-top: 0.5em;
        }
        form.filter-form {
            margin: 1em 0;
        }
        @media screen and (max-width: 600px) {
            .ticket-container {
                font-size: 0.95em;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h2>My Tickets</h2>

    <form method="get" class="filter-form">
        <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
        <select name="status">
            <option value="">Status: All</option>
            <option value="open" <?= $filter_status == 'open' ? 'selected' : '' ?>>Open</option>
            <option value="in_progress" <?= $filter_status == 'in_progress' ? 'selected' : '' ?>>In Progress</option>
            <option value="closed" <?= $filter_status == 'closed' ? 'selected' : '' ?>>Closed</option>
        </select>
        <button type="submit">Filter</button>
    </form>

    <?php while ($row = $result->fetch_assoc()): ?>
        <div class="ticket-container">
            <strong>Title:</strong> <?= htmlspecialchars($row['title']) ?><br>
            <strong>Status:</strong> <?= htmlspecialchars($row['status']) ?><br>
            <strong>Created by:</strong> <?= htmlspecialchars($row['author_name']) ?><br>
            <strong>Assigned to:</strong> <?= $row['assigned_to_name'] ?? 'Not assigned' ?><br>
            <strong>Created at:</strong> <?= $row['created_at'] ?><br>
            <div class="ticket-actions">
                <a href="edit_ticket.php?id=<?= $row['id'] ?>">Show/Edit</a>
            </div>
        </div>
    <?php endwhile; ?>
</div>
</body>
</html>

