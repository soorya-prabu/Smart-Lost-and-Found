<?php
/**
 * Database Connection Setup & Secure Prepared Statement Engine
 */

require_once __DIR__ . '/env.php';

// Support cloud database connection URLs (e.g. JAWSDB_URL, CLEARDB_DATABASE_URL, DATABASE_URL, MYSQL_URL)
$cloudUrl = env('JAWSDB_URL', env('CLEARDB_DATABASE_URL', env('DATABASE_URL', env('MYSQL_URL'))));

$db_host = 'localhost';
$db_port = 3306;
$db_user = 'root';
$db_pass = 'soorya25.';
$db_name = 'smart_lostfound';
$db_ssl = false;

if ($cloudUrl && strpos($cloudUrl, '://') !== false) {
    $parsedUrl = parse_url($cloudUrl);
    if (!empty($parsedUrl['host'])) {
        $db_host = $parsedUrl['host'];
    }
    if (!empty($parsedUrl['port'])) {
        $db_port = (int)$parsedUrl['port'];
    }
    if (isset($parsedUrl['user'])) {
        $db_user = urldecode($parsedUrl['user']);
    }
    if (isset($parsedUrl['pass'])) {
        $db_pass = urldecode($parsedUrl['pass']);
    }
    if (!empty($parsedUrl['path'])) {
        $db_name = ltrim(urldecode($parsedUrl['path']), '/');
    }
    if (!empty($parsedUrl['query'])) {
        parse_str($parsedUrl['query'], $queryParts);
        if (!empty($queryParts['ssl']) || !empty($queryParts['ssl-mode']) || !empty($queryParts['sslmode'])) {
            $db_ssl = true;
        }
    }
} else {
    $db_host = env('DB_HOST', $db_host);
    $db_port = (int)env('DB_PORT', $db_port);
    $db_user = env('DB_USERNAME', env('DB_USER', $db_user));
    $db_pass = env('DB_PASSWORD', env('DB_PASS', $db_pass));
    $db_name = env('DB_DATABASE', env('DB_NAME', $db_name));
}

if (env('DB_SSL') === 'true' || env('DB_SSL') === true || env('MYSQL_SSL') === 'true') {
    $db_ssl = true;
}

// Initialize MySQLi connection object
$conn = mysqli_init();
if (!$conn) {
    die("<h3>Database initialization failed</h3><p>mysqli_init() failed.</p>");
}

// Enable SSL if cloud database requires it (e.g. TiDB, Aiven, PlanetScale)
if ($db_ssl) {
    mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
    @mysqli_real_connect($conn, $db_host, $db_user, $db_pass, $db_name, $db_port, NULL, MYSQLI_CLIENT_SSL);
} else {
    @mysqli_real_connect($conn, $db_host, $db_user, $db_pass, $db_name, $db_port);
}

if (mysqli_connect_errno()) {
    $err = mysqli_connect_error();
    $conn = false;
    if (php_sapi_name() === 'cli') {
        fwrite(STDERR, "Database connection error: " . $err . PHP_EOL);
    } else {
        $isProd = (env('APP_ENV') === 'production' || env('APP_DEBUG') === false || env('APP_DEBUG') === 'false');
        if ($isProd) {
            die("<h3>Database connection error</h3><p>We are experiencing technical difficulties connecting to the database. Please try again later.</p>");
        } else {
            die("<h3>Database connection failed</h3><p>" . htmlspecialchars($err) . "</p><p>Please check your database credentials in <code>config/database.php</code>, <code>.env</code>, or cloud environment variables.</p>");
        }
    }
}

if ($conn && is_object($conn)) {
    @mysqli_set_charset($conn, 'utf8mb4');
}

/**
 * Execute a secure SQL statement with parameterized inputs (Full SQL Injection Prevention)
 *
 * @param mysqli $conn Active database connection
 * @param string $sql SQL query with ? placeholders
 * @param array $params Ordered list of bound parameters
 * @return mysqli_result|bool
 */
function dbQuery($conn, $sql, $params = []) {
    if (!$conn || !is_object($conn)) {
        return false;
    }

    if (empty($params)) {
        return mysqli_query($conn, $sql);
    }

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        error_log("Database prepare error: " . mysqli_error($conn) . " in SQL: " . $sql);
        return false;
    }

    $types = '';
    foreach ($params as $p) {
        if (is_int($p)) {
            $types .= 'i';
        } elseif (is_float($p)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
    }

    mysqli_stmt_bind_param($stmt, $types, ...$params);

    if (!mysqli_stmt_execute($stmt)) {
        error_log("Database execute error: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return false;
    }

    $res = mysqli_stmt_get_result($stmt);
    if ($res !== false) {
        mysqli_stmt_close($stmt);
        return $res;
    }

    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    return $affected >= 0;
}

/**
 * Execute a prepared INSERT statement and return the inserted auto_increment ID
 *
 * @param mysqli $conn
 * @param string $sql
 * @param array $params
 * @return int|bool Insert ID on success, false on failure
 */
function dbInsert($conn, $sql, $params = []) {
    if (!$conn || !is_object($conn)) {
        return false;
    }

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        error_log("Database prepare error: " . mysqli_error($conn) . " in SQL: " . $sql);
        return false;
    }

    if (!empty($params)) {
        $types = '';
        foreach ($params as $p) {
            $types .= is_int($p) ? 'i' : (is_float($p) ? 'd' : 's');
        }
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        error_log("Database execute error: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return false;
    }

    $insertId = mysqli_stmt_insert_id($stmt);
    mysqli_stmt_close($stmt);
    return $insertId > 0 ? (int)$insertId : true;
}

/**
 * Fetch a single associative row from a prepared query
 *
 * @param mysqli $conn
 * @param string $sql
 * @param array $params
 * @return array|null
 */
function dbFetchOne($conn, $sql, $params = []) {
    $res = dbQuery($conn, $sql, $params);
    if ($res && is_object($res)) {
        $row = mysqli_fetch_assoc($res);
        return $row !== null ? $row : null;
    }
    return null;
}

/**
 * Fetch all associative rows from a prepared query
 *
 * @param mysqli $conn
 * @param string $sql
 * @param array $params
 * @return array
 */
function dbFetchAll($conn, $sql, $params = []) {
    $res = dbQuery($conn, $sql, $params);
    $rows = [];
    if ($res && is_object($res)) {
        while ($row = mysqli_fetch_assoc($res)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

/**
 * Execute a query and return a single scalar count/value (e.g. SELECT COUNT(*))
 *
 * @param mysqli $conn
 * @param string $sql
 * @param array $params
 * @return int
 */
function dbCount($conn, $sql, $params = []) {
    $res = dbQuery($conn, $sql, $params);
    if ($res && is_object($res)) {
        $row = mysqli_fetch_row($res);
        return $row ? (int)$row[0] : 0;
    }
    return 0;
}

/**
 * Execute an UPDATE or DELETE query with prepared statement
 *
 * @param mysqli $conn
 * @param string $sql
 * @param array $params
 * @return int|bool Affected rows count or false on failure
 */
function dbExecute($conn, $sql, $params = []) {
    if (!$conn || !is_object($conn)) {
        return false;
    }

    if (empty($params)) {
        $res = mysqli_query($conn, $sql);
        return $res ? mysqli_affected_rows($conn) : false;
    }

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        error_log("Database prepare error: " . mysqli_error($conn) . " in SQL: " . $sql);
        return false;
    }

    $types = '';
    foreach ($params as $p) {
        $types .= is_int($p) ? 'i' : (is_float($p) ? 'd' : 's');
    }
    mysqli_stmt_bind_param($stmt, $types, ...$params);

    if (!mysqli_stmt_execute($stmt)) {
        error_log("Database execute error: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return false;
    }

    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    return $affected >= 0 ? $affected : true;
}
