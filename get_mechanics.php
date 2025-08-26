<?php
declare(strict_types=1);

header('Content-Type: application/json');
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/DBconnect.php';

$tz = new DateTimeZone('Asia/Dhaka');
$dateStr = $_POST['date'] ?? $_GET['date'] ?? null;

if (!$dateStr) {
    // Default to tomorrow
    $dateStr = (new DateTimeImmutable('tomorrow', $tz))->format('Y-m-d');
}

try {
    $today = new DateTimeImmutable('today', $tz);
    $d     = DateTimeImmutable::createFromFormat('Y-m-d', $dateStr, $tz);

    if (!$d) {
        echo json_encode(['ok' => false, 'error' => 'Error: Wrong Date Format']);
        exit;
    }

    if ($d < $today) {
        echo json_encode(['ok' => false, 'error' => 'Please select an upcoming date']);
        exit;
    }

    $mysqli = get_mysqli();

    $sql = "
        SELECT
            m.id,
            m.name,
            m.photo_url,
            COUNT(a.id) AS bookings
        FROM mechanics m
        LEFT JOIN appointments a
          ON a.mechanic_id = m.id
         AND a.appointment_date = ?
         AND a.status IN ('active','pending')
        GROUP BY m.id, m.name, m.photo_url
        ORDER BY m.name ASC
    ";

    $stmt = $mysqli->prepare($sql);
    $dateParam = $d->format('Y-m-d');
    $stmt->bind_param('s', $dateParam);
    $stmt->execute();

    $result = $stmt->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    $mechanics = array_map(function (array $r) {
        $bookings  = (int)$r['bookings'];
        $remaining = max(0, 4 - $bookings);
        return [
            'id'        => (string)$r['id'],
            'name'      => $r['name'],
            'photo_url' => $r['photo_url'] ?? null,
            'bookings'  => $bookings,
            'remaining' => $remaining,
            'available' => $remaining > 0,
        ];
    }, $rows ?: []);

    echo json_encode([
        'ok'          => true,
        'date'        => $d->format('Y-m-d'),
        'date_pretty' => $d->format('F j, Y'),
        'mechanics'   => $mechanics,
    ]);
    exit;

} catch (Throwable $e) {
    echo json_encode([
        'ok'    => false,
        'error' => 'Server error'
    ]);
    exit;
}
