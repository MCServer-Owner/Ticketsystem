<?php
session_start();
require_once 'config.php';
require_once 'admin_functions.php';
require_once 'navigation.php';

// Nur eingeloggte Benutzer erlauben
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Alle Tickets abrufen
$sql = "SELECT  t.id, t.title, t.status, t.created_at, u.username AS creator
        FROM tickets t
        JOIN users u ON t.user_id = u.id
        ORDER BY t.created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>All Tickets</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 1rem;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 10px;
        }

        th {
            background-color: #f5f5f5;
        }

        .mobile-table {
            overflow-x: auto;
        }

        @media screen and (max-width: 600px) {
            table, thead, tbody, th, td, tr {
                display: block;
            }

            th {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }

            td {
                border: none;
                border-bottom: 1px solid #ccc;
                position: relative;
                padding-left: 50%;
                white-space: pre-wrap;
            }

            td:before {
                position: absolute;
                left: 10px;
                top: 10px;
                font-weight: bold;
                white-space: nowrap;
            }

            td:nth-of-type(1):before { content: "Title"; }
            td:nth-of-type(2):before { content: "Status"; }
            td:nth-of-type(3):before { content: "Created at"; }
            td:nth-of-type(4):before { content: "Creator"; }
        }
    </style>
</head>
<body>
    <h2>All Tickets</h2>
    <div class="mobile-table">
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Created at</th>
                    <th>Creator</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($ticket = $result->fetch_assoc()): ?>
                    <tr onclick="window.location.href='edit_ticket.php?id=<?= $ticket['id'] ?>'" style="cursor:pointer;">
                        <td><?= htmlspecialchars($ticket['title']) ?></td>
                        <td><?= htmlspecialchars($ticket['status']) ?></td>
                        <td><?= htmlspecialchars($ticket['created_at']) ?></td>
                        <td><?= htmlspecialchars($ticket['creator']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

