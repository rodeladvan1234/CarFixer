<?php
// authenticate.php
session_start();
include 'DBConnect.php';

$loggedIn = False;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if ($username == '' || $password == '') {
        echo "Username and password cannot be empty.";
        exit();
    }

    if ($username == 'admin' and $password == 'admin123') {
        $_SESSION['username'] = [
            'username'=> $username,
        ];
        $loggedIn = True;
        $_SESSION['loggedIn'] = $loggedIn;
        $_SESSION['LAST_ACTIVITY'] = time(); // Update last activity time
        exit();
    }

    if ($result && mysqli_num_rows($result) > 0) {
        // User authenticated successfully
        $_SESSION['username'] = [
            'username'=> $username,
        ];
    } else {
        // Authentication failed
        echo "Invalid username or password.";
    }
}

if ($loggedIn) {
    header("Location: login.php");
} else {
    echo "Please try again.";
}

session_start();

$timeout = 900; // 15 minutes (but in seconds)


if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout) {
    // when last request was more than 15 mins ago
    session_unset();     // Unset $_SESSION
    session_destroy();   // Destroy session
    header("Location: login.php"); // Redirect to login
    exit();
}

//Session_unset() and session_destroy() basically does the same thing, but session_unset() only removes the session variables, 
// while session_destroy() destroys the session itself.

$_SESSION['LAST_ACTIVITY'] = time(); // Update last activity


?>

