<?php
// ============================================================
//  config.php  —  Database connection settings
//  Edit these values to match your XAMPP/WAMP setup
// ============================================================

// Prevent HTML error output — ensure only JSON is sent
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ob_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // default XAMPP user
define('DB_PASS', '');            // default XAMPP password (empty)
define('DB_NAME', 'testflow');
define('DB_PORT', 3306);

// CORS — allow the HTML file to call this API
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]);
            exit();
        }
    }
    return $pdo;
}

function respond($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

function respondError(string $msg, int $code = 400): void {
    respond(['error' => $msg], $code);
}

// Clean up any accidental output and ensure JSON-only responses
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_end_clean();
        http_response_code(500);
        echo json_encode(['error' => 'Server error: ' . $error['message']]);
    }
});
