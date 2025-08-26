<?php
// admin.php
// Admin dashboard to view and manage appointments. The page lists all existing
// appointments along with client details and assigned mechanics. For each
// appointment there is an edit link that allows the admin to reschedule the
// appointment or assign it to a different mechanic via update_appointment.php.
// Access to this page is restricted to logged‑in admins.

declare(strict_types=1);

session_start();
require_once __DIR__ . '/DBconnect.php';

// Ensure the user is logged in as admin. If not, redirect to login page.
if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true) {
    header('Location: login.html');
    exit;
}

// Update session activity to enforce timeouts
$_SESSION['LAST_ACTIVITY'] = time();

// Get database connection
$mysqli = get_mysqli();

// Fetch appointments with associated mechanic names. Join appointments and mechanics.
$sql = "
    SELECT a.id, a.client_name, a.phone, a.car_license, a.appointment_date, m.name AS mechanic_name
    FROM appointments a
    JOIN mechanics m ON a.mechanic_id = m.id
    ORDER BY a.appointment_date ASC, a.id ASC
";

$result = $mysqli->query($sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CarFixer</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2rem;
        }
        .admin-table th, .admin-table td {
            border: 1px solid #888;
            padding: 0.75rem;
            text-align: left;
        }
        .admin-table th {
            background-color: #004080;
            color: #fff;
        }
        .admin-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .edit-link {
            color: #004080;
            text-decoration: underline;
        }
    </style>
</head>
<body>
<header id="header">
    <h1 id="topName">CarFixer</h1>
    <h4 id="topProfession">Admin Dashboard</h4>
    <button id="darkModeToggle" onclick="toggleMode()">🌙</button>
</header>
<nav>
    <ul id="navList">
        <li><a href="index.html">Home</a></li>
        <li><a href="index.html#mechanics">Mechanics</a></li>
        <li><a href="contact.html">Contact</a></li>
        <li><a href="login.html">Logout</a></li>
    </ul>
</nav>
<main style="max-width:1200px;margin:auto;">
    <h2>Appointments</h2>
    <?php if ($result && $result->num_rows > 0): ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Client Name</th>
                    <th>Phone</th>
                    <th>Car License</th>
                    <th>Appointment Date</th>
                    <th>Mechanic</th>
                    <th>Edit</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                    <td><?php echo htmlspecialchars($row['car_license']); ?></td>
                    <td><?php echo htmlspecialchars($row['appointment_date']); ?></td>
                    <td><?php echo htmlspecialchars($row['mechanic_name']); ?></td>
                    <td><a class="edit-link" href="update_appointment.php?id=<?php echo (int)$row['id']; ?>">Edit</a></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No appointments found.</p>
    <?php endif; ?>
</main>
<footer>
    <p>&copy; 2025 Car Fixer</p>
</footer>
<script src="script.js"></script>
</body>
</html>