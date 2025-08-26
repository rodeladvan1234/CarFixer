<?php
declare(strict_types=1);

function get_mysqli(): mysqli {
    if (!empty($GLOBALS['__mysqli']) && $GLOBALS['__mysqli'] instanceof mysqli) {
        return $GLOBALS['__mysqli'];
    }

    $url = getenv('DATABASE_URL') ?: '';
    $host = 'localhost';
    $port = 3306;
    $user = 'root';
    $pass = '';
    $db   = 'carDB';
    $charset = 'utf8mb4';

    if ($url) {
        $parts = parse_url($url); //parts is an associative array
        if ($parts === false) { //indicates parse_url failed
            throw new RuntimeException('Invalid DATABASE_URL');
        }
        $scheme = $parts['scheme'] ?? '';
        if ($scheme !== 'mysql') {
            throw new RuntimeException('DATABASE_URL must be mysql:// for mysqli');
        }
        $host = $parts['host'] ?? $host;
        $port = isset($parts['port']) ? (int)$parts['port'] : $port;
        $user = $parts['user'] ?? $user;
        $pass = $parts['pass'] ?? $pass;
        $db   = isset($parts['path']) ? ltrim($parts['path'], '/') : $db;

        if (!empty($parts['query'])) {
            parse_str($parts['query'], $q);
            if (!empty($q['charset'])) $charset = $q['charset'];
        }
    }

    // IMPORTANT: don’t let mysqli print warnings to output (we’ll handle errors via exceptions)
    mysqli_report(MYSQLI_REPORT_OFF);

    $mysqli = @new mysqli($host, $user, $pass, $db, $port);
    if ($mysqli->connect_errno) {
        // Log for you; return generic error to client
        error_log('MySQL connect error: [' . $mysqli->connect_errno . '] ' . $mysqli->connect_error);
        throw new RuntimeException('Database connection failed');
    }

    if (!$mysqli->set_charset($charset)) {
        error_log('MySQL set_charset error: ' . $mysqli->error);
        // not fatal; continue
    }

    $GLOBALS['__mysqli'] = $mysqli;
    return $mysqli;
}
