<?php
// authenticate.php - processes admin login and redirects to the admin dashboard.
// This file should only handle form submissions from login.html. It performs basic
// credential validation (hard‑coded for this assignment) and sets session
// variables to mark the user as logged in. Upon success it redirects to
// admin.php; on failure it outputs an error and provides a link back to the
// login page.

declare(strict_types=1);

session_start();

// Only handle POST requests. If accessed via GET, redirect to the login page.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.html');
    exit;
}

// Read username and password from the submitted form
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

// Basic validation to ensure both fields are provided
if ($username === '' || $password === '') {
    echo 'Username and password cannot be empty.<br><a href="login.html">Back to login</a>';
    exit;
}

// In this sample application the admin credentials are hard‑coded. A real system
// would verify credentials against a users table and hash passwords.
if ($username === 'admin' && $password === 'admin123') {
    $_SESSION['username'] = [ 'username' => $username ];
    $_SESSION['loggedIn'] = true;
    $_SESSION['LAST_ACTIVITY'] = time();
    // Redirect to the admin dashboard on successful login
    header('Location: admin.php');
    exit;
}

// Invalid credentials: show a simple error and link back
echo 'Invalid username or password.<br><a href="login.html">Back to login</a>';
exit;
