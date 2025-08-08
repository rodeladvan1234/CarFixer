<?php
// get_mechanics.php
header('Content-Type: application/json');

try {
    // ---- 1) Input ----
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['ok' => false, 'error' => 'Invalid request method.']); exit;
    }

    $date = isset($_POST['date']) ? trim($_POST['date']) : '';
    if (!$date) {
        echo json_encode(['ok' => false, 'error' => 'No date provided.']); exit;
    }

    // Expecting HTML date input: YYYY-MM-DD
    $tz = new DateTimeZone('Asia/Dhaka');
    $today = new DateTimeImmutable('today', $tz);

    // Validate format
    $d = DateTimeImmutable::createFromFormat('Y-m-d', $date, $tz);
    $errors = DateTimeImmutable::getLastErrors();
    if (!$d || $errors['warning_count'] || $errors['error_count']) {
        echo json_encode(['ok' => false, 'error' => 'Invalid date format.']); exit;
    }

    // Past check (allow today)
    if ($d < $today) {
        echo json_encode(['ok' => false, 'error' => 'Please select an upcoming date.']); exit;
    }

    // ---- 2) DB ----
    // TODO: replace with your real credentials
    $dsn = 'mysql:host=localhost;dbname=carfixer;charset=utf8mb4';
    $user = 'your_user';
    $pass = 'your_pass';

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Assumptions:
    // mechanics(id, name, photo_url)
    // appointments(id, mechanic_id, appointment_date DATE, status ENUM('active','pending','cancelled',...) )
    // Business rule: max 4 active/pending per mechanic per day
    $sql = "
        SELECT
            m.id,
            m.name,
            m.photo_url,
            COALESCE(COUNT(a.id), 0) AS bookings
        FROM mechanics m
        LEFT JOIN appointments a
            ON a.mechanic_id = m.id
           AND a.appointment_date = :date
           AND a.status IN ('active','pending')
        GROUP BY m.id, m.name, m.photo_url
        ORDER BY m.name ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':date' => $d->format('Y-m-d')]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $mechanics = array_map(function($r) {
        $bookings = (int)$r['bookings'];
        $remaining = max(0, 4 - $bookings);
        return [
            'id' => $r['id'],
            'name' => $r['name'],
            'photo_url' => $r['photo_url'],
            'bookings' => $bookings,
            'remaining' => $remaining,
            'available' => $remaining > 0
        ];
    }, $rows);

    echo json_encode([
        'ok' => true,
        'date' => $d->format('Y-m-d'),
        'date_pretty' => $d->format('F j, Y'),
        'mechanics' => $mechanics
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server error.']);
    // Optionally log: error_log($e);
}