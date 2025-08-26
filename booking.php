<?php
// booking.php (mysqli)
// Renders a form on GET (with mechanic + date), processes insert on POST
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/DBconnect.php';

function json_exit(array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

try {
    $mysqli = get_mysqli();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Expect mechanic_id + date from query string
        $mechanicId = isset($_GET['mechanic_id']) ? (int)$_GET['mechanic_id'] : 0;
        $dateStr    = $_GET['date'] ?? '';

        if ($mechanicId <= 0 || $dateStr === '') {
            // simple HTML response for user (no JSON here since it’s a page)
            http_response_code(400);
            echo "<h2>Invalid booking link.</h2>";
            exit;
        }

        // Validate date
        $tz    = new DateTimeZone('Asia/Dhaka');
        $today = new DateTimeImmutable('today', $tz);
        $d     = DateTimeImmutable::createFromFormat('Y-m-d', $dateStr, $tz);
        if (!$d || $d < $today) {
            http_response_code(400);
            echo "<h2>Invalid or past date.</h2>";
            exit;
        }

        // Load mechanic
        $stmt = $mysqli->prepare("SELECT id, name, photo_url FROM mechanics WHERE id = ?");
        $stmt->bind_param('i', $mechanicId);
        $stmt->execute();
        $mech = $stmt->get_result()->fetch_assoc();
        if (!$mech) {
            http_response_code(404);
            echo "<h2>Mechanic not found.</h2>";
            exit;
        }

        // Check remaining capacity
        $sqlCnt = "
            SELECT COUNT(id) AS bookings
            FROM appointments
            WHERE mechanic_id = ?
              AND appointment_date = ?
              AND status IN ('active','pending')
        ";
        $stmt = $mysqli->prepare($sqlCnt);
        $dateParam = $d->format('Y-m-d');
        $stmt->bind_param('is', $mechanicId, $dateParam);
        $stmt->execute();
        $cntRow = $stmt->get_result()->fetch_assoc();
        $bookings = (int)($cntRow['bookings'] ?? 0);
        $remaining = max(0, 4 - $bookings);

        if ($remaining <= 0) {
            echo "<h2>Sorry, {$mech['name']} is fully booked on {$d->format('F j, Y')}.</h2>";
            echo "<p><a href='index.html'>Choose another mechanic/date</a></p>";
            exit;
        }

        // Render a simple form: mechanic image + inputs
        $photo = htmlspecialchars($mech['photo_url'] ?: 'images/placeholder.png', ENT_QUOTES);
        $mname = htmlspecialchars($mech['name'], ENT_QUOTES);
        $pretty = htmlspecialchars($d->format('F j, Y'), ENT_QUOTES);

        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8"> 
            <!-- UTF8 indicates character set desc generally used everywhere -->
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <!-- meta tags are for browser and SEO stuff. The above line zooms at 100% at init -->
            <title>Bookings! - Carfixer</title>
            <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
            <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
            <link rel="stylesheet" href="style.css">
        </head>
        
        <body>
        
        <header id ="header">
            <!-- <img src="images/profile.png" alt="Rodel Advan Photo" id="profile-pic"> -->
            <!-- <img src="images/profile1.png" alt="Rodel Advan Photo" id="profile-pic"> -->
            <h1 id="topName">CarFixer</h1>
            <h4 id="topProfession">Book your mechanic today!</h3>
            <button id="darkModeToggle" onclick="toggleMode()">🌙</button>
        </header>
        
        <!-- 4. Clearly labeled sections (e.g., "Personal Info," "About Me," "Education," "Technical Skills")-->
        <nav>
            <ul id="navList">
                <li><a href="index.html">About Us</a></li>
                <li><a href="index.html#mechanics">Mechanics</a></li>
                <li><a href="contact.html">Contact</a></li>
            </ul>
        </nav>

        <main>
            <div class="book-wrap">
                <div>
                    <img style="border-radius: 10px; object-fit: cover; width: 200px; height: auto;" src="<?php echo $photo; ?>" alt="<?php echo $mname; ?>">
                    <h2><?php echo $mname; ?></h2>
                    <p>Date: <strong><?php echo $pretty; ?></strong></p>
                    <p>Remaining slots: <strong><?php echo $remaining; ?>/4</strong></p>
                </div>

                <form class="book-form" method="POST" action="booking.php">
                    <input type="hidden" name="mechanic_id" value="<?php echo (int)$mech['id']; ?>">
                    <input type="hidden" name="date" value="<?php echo htmlspecialchars($d->format('Y-m-d'), ENT_QUOTES); ?>">

                    <label>Full Name</label>
                    <input type="text" name="client_name" required>

                    <label>Address</label>
                    <textarea name="address" rows="3" required></textarea>

                    <label>Phone</label>
                    <input type="text" name="phone" required>

                    <label>Car License Number</label>
                    <input type="text" name="car_license" required>

                    <label>Car Engine Number</label>
                    <input type="text" name="car_engine" required>

                    <button type="submit">Confirm Booking</button>
                    <p class="hint">If you later want to cancel or change, please call our office.</p>
                </form>
            </div>
        </main>
        </body>
        </html>
        <?php
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Read and validate fields
        $mechanicId  = isset($_POST['mechanic_id']) ? (int)$_POST['mechanic_id'] : 0;
        $dateStr     = trim($_POST['date'] ?? '');
        $clientName  = trim($_POST['client_name'] ?? '');
        $address     = trim($_POST['address'] ?? '');
        $phone       = trim($_POST['phone'] ?? '');
        $carLicense  = trim($_POST['car_license'] ?? '');
        $carEngine   = trim($_POST['car_engine'] ?? '');

        if ($mechanicId <= 0 || $dateStr === '' || $clientName === '' || $address === '' || $phone === '' || $carLicense === '' || $carEngine === '') {
            http_response_code(400);
            echo "<div class='error'><strong>Error:</strong> All fields are required.</div>";
            echo "<p><a href='index.html'>Back</a></p>";
            exit;
        }

        $tz    = new DateTimeZone('Asia/Dhaka');
        $today = new DateTimeImmutable('today', $tz);
        $d     = DateTimeImmutable::createFromFormat('Y-m-d', $dateStr, $tz);
        if (!$d || $d < $today) {
            http_response_code(400);
            echo "<div class='error'><strong>Error:</strong> Invalid or past date.</div>";
            echo "<p><a href='index.html'>Back</a></p>";
            exit;
        }
        $dateParam = $d->format('Y-m-d');

        // Guard 1: Duplicate client on same date?
        $dupSql = "
            SELECT COUNT(*) AS cnt
            FROM appointments
            WHERE appointment_date = ?
              AND phone = ?
              AND status IN ('active','pending')
        ";
        $stmt = $mysqli->prepare($dupSql);
        $stmt->bind_param('ss', $dateParam, $phone);
        $stmt->execute();
        $dup = $stmt->get_result()->fetch_assoc();
        if ((int)$dup['cnt'] > 0) {
            echo "<div class='error'><strong>Notice:</strong> You already have an appointment on {$d->format('F j, Y')}.</div>";
            echo "<p><a href='index.html'>Back</a></p>";
            exit;
        }

        // Guard 2: Capacity check for the mechanic
        $capSql = "
            SELECT COUNT(*) AS bookings
            FROM appointments
            WHERE mechanic_id = ?
              AND appointment_date = ?
              AND status IN ('active','pending')
        ";
        $stmt = $mysqli->prepare($capSql);
        $stmt->bind_param('is', $mechanicId, $dateParam);
        $stmt->execute();
        $cRow = $stmt->get_result()->fetch_assoc();
        $bookings = (int)($cRow['bookings'] ?? 0);
        if ($bookings >= 4) {
            echo "<div class='error'><strong>Sorry:</strong> This mechanic is fully booked for {$d->format('F j, Y')}.</div>";
            echo "<p><a href='index.html'>Choose another mechanic/date</a></p>";
            exit;
        }

        // Insert appointment
        $insSql = "
            INSERT INTO appointments
            (mechanic_id, appointment_date, status, client_name, address, phone, car_license, car_engine, created_at)
            VALUES (?, ?, 'active', ?, ?, ?, ?, ?, NOW())
        ";
        $stmt = $mysqli->prepare($insSql);
        $stmt->bind_param('issssss', $mechanicId, $dateParam, $clientName, $address, $phone, $carLicense, $carEngine);
        $stmt->execute();

        // Success screen
        echo "<div class='success'><h2>Booking Confirmed!</h2>
              <p>Date: <strong>{$d->format('F j, Y')}</strong></p>
              <p>We’ve saved your appointment. To change/cancel, please call our office.</p>
              </div>";
        echo "<p><a href='index.html'>Back to home</a></p>";
        exit;
    }

    // Any other method
    http_response_code(405);
    echo "Method Not Allowed";
    exit;

} catch (Throwable $e) {
    error_log('booking.php error: ' . $e->getMessage());
    http_response_code(500);
    echo "<h2>Server error. Please try again later.</h2>";
    exit;
}
