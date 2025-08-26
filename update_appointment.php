<?php
// update_appointment.php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/DBconnect.php';

// Enforce login
if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true) {
    header('Location: login.html');
    exit;
}

$_SESSION['LAST_ACTIVITY'] = time();
$mysqli = get_mysqli();

function render_error(string $message): void {
    echo '<h2>Error</h2><p>' . htmlspecialchars($message) . '</p>';
    echo '<p><a href="admin.php">Back to admin</a></p>';
    exit;
}

try {
    // DELETE action
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
        $appId = (int)($_POST['id'] ?? 0);
        if ($appId <= 0) render_error('Invalid appointment ID.');

        $stmt = $mysqli->prepare('DELETE FROM appointments WHERE id = ?');
        $stmt->bind_param('i', $appId);
        $stmt->execute();

        echo '<div class="success"><h2>Appointment Deleted!</h2></div>';
        echo '<p><a href="admin.php">Back to admin dashboard</a></p>';
        exit;
    }

    // UPDATE action
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
        $appId      = (int)($_POST['id'] ?? 0);
        $newDateStr = trim($_POST['date'] ?? '');
        $newMechId  = (int)($_POST['mechanic_id'] ?? 0);

        $clientName = trim($_POST['client_name'] ?? '');
        $phone      = trim($_POST['phone'] ?? '');
        $carLicense = trim($_POST['car_license'] ?? '');
        $carEngine  = trim($_POST['car_engine'] ?? '');

        if ($appId <= 0 || $newDateStr === '' || $newMechId <= 0 || 
            $clientName === '' || $phone === '' || $carLicense === '' || $carEngine === '') {
            render_error('All fields are required.');
        }

        // Load existing appointment to get client phone for duplicate check
        $stmt = $mysqli->prepare('SELECT phone FROM appointments WHERE id = ?');
        $stmt->bind_param('i', $appId);
        $stmt->execute();
        $appt = $stmt->get_result()->fetch_assoc();
        if (!$appt) render_error('Appointment not found.');
        $oldPhone = $appt['phone'];

        // Validate date
        $tz    = new DateTimeZone('Asia/Dhaka');
        $today = new DateTimeImmutable('today', $tz);
        $d     = DateTimeImmutable::createFromFormat('Y-m-d', $newDateStr, $tz);
        if (!$d || $d < $today) render_error('Invalid or past date.');
        $dateParam = $d->format('Y-m-d');

        // duplicate check (only if phone unchanged or updated)
        $dupSql = 'SELECT COUNT(*) AS cnt FROM appointments WHERE phone = ? AND appointment_date = ? AND id <> ? AND status IN ("active","pending")';
        $stmt = $mysqli->prepare($dupSql);
        $stmt->bind_param('ssi', $phone, $dateParam, $appId);
        $stmt->execute();
        $dupRow = $stmt->get_result()->fetch_assoc();
        if ((int)$dupRow['cnt'] > 0) render_error('Client already has an appointment that date.');

        // capacity check
        $capSql = 'SELECT COUNT(*) AS bookings FROM appointments WHERE mechanic_id = ? AND appointment_date = ? AND id <> ? AND status IN ("active","pending")';
        $stmt = $mysqli->prepare($capSql);
        $stmt->bind_param('isi', $newMechId, $dateParam, $appId);
        $stmt->execute();
        $capRow = $stmt->get_result()->fetch_assoc();
        if ((int)$capRow['bookings'] >= 4) render_error('Selected mechanic fully booked.');

        // Perform the update
        $updateSql = 'UPDATE appointments 
                      SET mechanic_id = ?, appointment_date = ?, client_name = ?, phone = ?, car_license = ?, car_engine = ? 
                      WHERE id = ?';
        $stmt = $mysqli->prepare($updateSql);
        $stmt->bind_param('isssssi', $newMechId, $dateParam, $clientName, $phone, $carLicense, $carEngine, $appId);
        $stmt->execute();

        echo '<div class="success"><h2>Appointment Updated!</h2>';
        echo '<p>Date: <strong>' . htmlspecialchars($d->format('F j, Y')) . '</strong></p>';
        echo '</div>';
        echo '<p><a href="admin.php">Back to admin dashboard</a></p>';
        exit;
    }

    // GET: Show form
    $appId = (int)($_GET['id'] ?? 0);
    if ($appId <= 0) render_error('Invalid appointment ID.');

    $stmt = $mysqli->prepare('SELECT * FROM appointments WHERE id = ?');
    $stmt->bind_param('i', $appId);
    $stmt->execute();
    $appointment = $stmt->get_result()->fetch_assoc();
    if (!$appointment) render_error('Appointment not found.');

    $mechsRes = $mysqli->query('SELECT id, name FROM mechanics ORDER BY name ASC');
    $mechanics = $mechsRes->fetch_all(MYSQLI_ASSOC);

    $currentDate = htmlspecialchars($appointment['appointment_date'], ENT_QUOTES);
    $currentMechanic = (int)$appointment['mechanic_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Update Appointment</title>
<link rel="stylesheet" href="style.css">
<style>
.update-wrap { max-width: 600px; margin:40px auto; background:#fff; padding:20px; border-radius:12px; }
.update-wrap label { display:block; margin-top:12px; font-weight:bold; }
.update-wrap input, .update-wrap select { width:100%; padding:8px; border:1px solid #ccc; border-radius:6px; }
.update-wrap button { margin-top:20px; padding:10px 16px; border:none; border-radius:6px; cursor:pointer; }
.update-wrap button[name="update"] { background:#003366; color:#fff; }
.update-wrap button[name="delete"] { background:#bb0000; color:#fff; margin-left:10px; }
</style>
</head>
<body>
<div class="update-wrap">
    <h2>Edit Appointment #<?php echo (int)$appointment['id']; ?></h2>
    <form method="POST" action="update_appointment.php">
        <input type="hidden" name="id" value="<?php echo (int)$appointment['id']; ?>">

        <label for="date">Appointment Date</label>
        <input type="date" id="date" name="date" value="<?php echo $currentDate; ?>" required>

        <label for="mechanic_id">Assign Mechanic</label>
        <select id="mechanic_id" name="mechanic_id" required>
            <?php foreach ($mechanics as $m): ?>
                <option value="<?php echo (int)$m['id']; ?>" <?php echo $m['id']==$currentMechanic?'selected':''; ?>>
                    <?php echo htmlspecialchars($m['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="client_name">Client Name</label>
        <input type="text" id="client_name" name="client_name" value="<?php echo htmlspecialchars($appointment['client_name']); ?>" required>

        <label for="phone">Phone</label>
        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($appointment['phone']); ?>" required>

        <label for="car_license">Car License Number</label>
        <input type="text" id="car_license" name="car_license" value="<?php echo htmlspecialchars($appointment['car_license']); ?>" required>

        <label for="car_engine">Car Engine Number</label>
        <input type="text" id="car_engine" name="car_engine" value="<?php echo htmlspecialchars($appointment['car_engine']); ?>" required>

        <button type="submit" name="update">Save Changes</button>
        <button type="submit" name="delete" onclick="return confirm('Are you sure?')">Delete Appointment</button>
    </form>
</div>
</body>
</html>
<?php
exit;
} catch (Throwable $e) {
    error_log('update_appointment.php error: ' . $e->getMessage());
    render_error('Server error. Please try again later.');
}
