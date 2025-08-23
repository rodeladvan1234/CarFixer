<?php
// DBConnect.php
//
// Centralised database connection helper.
//
// This file exposes a single function which returns a pooled PDO instance
// configured for Neon PostgreSQL. Connection details are read from the
// `DATABASE_URL` environment variable to avoid hard‑coding secrets in
// source control. If the environment variable is not defined, sensible
// defaults may be provided for local development via a `.env.local` file or
// your web server configuration.
//
// The function caches the PDO instance in a static variable so that
// repeated includes of this file during a single request will not open
// multiple connections. Persistent connections are enabled to allow the
// underlying driver to reuse connections across requests when running
// behind a connection pooler such as PgBouncer (used by Neon and Vercel).

declare(strict_types=1);

/**
 * Parse a standard PostgreSQL connection URI into connection parameters.
 *
 * Supported URI format:
 *   postgres://user:pass@host:port/dbname?sslmode=require&<other-options>
 *
 * @param string $url The PostgreSQL connection URL
 * @return array{host:string,port:int,user:string,pass:string,dbname:string,sslmode:string}
 */
function parsePostgresUrl(string $url): array
{
    $parts = parse_url($url);
    if ($parts === false || !isset($parts['scheme']) || !isset($parts['host'])) {
        throw new InvalidArgumentException('Invalid DATABASE_URL format');
    }

    //extracting user credentials here
    $user = $parts['user'] ?? '';
    $pass = $parts['pass'] ?? '';
    $host = $parts['host'];
    $port = isset($parts['port']) ? (int)$parts['port'] : 5432;
    // path includes leading slash
    $path = $parts['path'] ?? '';
    $dbname = ltrim($path, '/'); //ltrim basically ensures no leading slash in dbname
    // Default sslmode is required for Neon
    $sslmode = 'require';
    if (isset($parts['query'])) {
        parse_str($parts['query'], $query); // parse query string into associative array
        if (!empty($query['sslmode'])) {
            $sslmode = (string)$query['sslmode'];
        }
    }
    return [
        'host'    => $host,
        'port'    => $port,
        'user'    => $user,
        'pass'    => $pass,
        'dbname'  => $dbname,
        'sslmode' => $sslmode,
    ];
}

/**
 * Obtain a PDO connection to the database.
 *
 * The returned connection is configured with sensible defaults:
 *  - Persistent connections enabled (ATTR_PERSISTENT)
 *  - Exceptions thrown on error (ATTR_ERRMODE)
 *  - Associative array fetch mode (ATTR_DEFAULT_FETCH_MODE)
 *
 * @return PDO
 */
function getPdo(): PDO //the function does 
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    // Prefer DATABASE_URL for connection details
    $databaseUrl = getenv('DATABASE_URL') ?: ($_ENV['DATABASE_URL'] ?? '');
    if ($databaseUrl === '') {
        // If DATABASE_URL is not set, attempt to construct one from separate env vars
        $host    = getenv('DB_HOST') ?: '127.0.0.1';
        $port    = (int)(getenv('DB_PORT') ?: 5432);
        $user    = getenv('DB_USER') ?: 'postgres';
        $pass    = getenv('DB_PASS') ?: '';
        $dbname  = getenv('DB_NAME') ?: 'postgres';
        $sslmode = getenv('DB_SSLMODE') ?: 'require';
    } else {
        $parsed = parsePostgresUrl($databaseUrl);
        $host    = $parsed['host'];
        $port    = $parsed['port'];
        $user    = $parsed['user'];
        $pass    = $parsed['pass'];
        $dbname  = $parsed['dbname'];
        $sslmode = $parsed['sslmode'];
    }
    $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s;sslmode=%s', $host, $port, $dbname, $sslmode);
    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_PERSISTENT         => true,
        ]);
    } catch (PDOException $e) {
        // Bubble up errors with a generic message – avoid leaking credentials
        throw new RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
    }
    return $pdo;
}

// Initialise a PDO instance in $pdo for legacy compatibility
$pdo = getPdo();