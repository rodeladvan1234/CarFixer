<?php
session_start();

if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h1>Welcome to the Admin Dashboard</h1>
    <p>Hello, <?php echo $_SESSION['username']['username']; ?>!</p>
</header>

<main>
    <h2>Dashboard</h2>
    <p>This is the admin dashboard where you can manage the website.</p>
</main>

<footer>
    <p>&copy; 2025 Car Fixer</p>
</footer>

</body>
</html>