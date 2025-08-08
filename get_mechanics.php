<?php
// get_mechanics.php
declare(strict_types=1);
header('Content-Type: application/json');

try {
    // 0) Include DB (gives you $pdo)
    require __DIR__ . '/DBConnect.php';

    // 1) Input validation
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Method not allowed']); exit;
    }

    $dateStr = isset($_POST['date']) ? trim($_POST['date']) : '';
    if ($dateStr === '') {
        echo json_encode(['ok' => false, 'error' => 'No date provided']); exit;
    }

    // Expecting YYYY-MM-DD from <input type="date">
    $tz     = new DateTimeZone('Asia/Dhaka');
    $today  = new DateTimeImmutable('today', $tz);
    $d      = DateTimeImmutable::createFromFormat('Y-m-d', $dateStr, $tz);
    $errors = DateTimeImmutable::getLastErrors();

    if (!$d || $errors['warning_count'] || $errors['error_count']) {
        echo json_encode(['ok' => false, 'error' => 'Invalid date format']); exit;
    }

    // Past-date check (today is allowed; change to <= to block same-day)
    if ($d < $today) {
        echo json_encode(['ok' => false, 'error' => 'Please select an upcoming date']); exit;
    }

    // 2) Query (Postgres friendly)
    // Assumed tables:
    // mechanics(id SERIAL PK, name TEXT, photo_url TEXT)
    // appointments(id SERIAL PK, mechanic_id INT, appointment_date DATE, status TEXT, ...)
    //
    // Business rule: max 4 active/pending per mechanic per day
    $sql = "
        SELECT
            m.id,
            m.name,
            m.photo_url,
            COUNT(a.id) AS bookings
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
    $rows = $stmt->fetchAll();

    // 3) Shape response
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
} catch (Throwable $e) {
    http_response_code(500);
    // For debugging temporarily, you can include $e->getMessage() — but remove in production
    echo json_encode(['ok' => false, 'error' => 'Server error']);
    // error_log((string)$e);
}
