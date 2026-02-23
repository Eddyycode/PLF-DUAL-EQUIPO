<?php
// Mandatory Debugging: Enable strict error reporting for mysqli to throw exceptions
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Parsing generic Environment Variables (e.g. from Railway or Docker)
    $server = getenv("MYSQLHOST") ?: "127.0.0.1";
    $user = getenv("MYSQLUSER") ?: "root";
    $pass = getenv("MYSQLPASSWORD") !== false ? getenv("MYSQLPASSWORD") : "78910";
    $db = getenv("MYSQLDATABASE") ?: "plf";
    $port = getenv("MYSQLPORT") ?: "3306";

    // Establishing the database connection
    $con = mysqli_connect($server, $user, $pass, $db, $port);
    mysqli_set_charset($con, "utf8mb4"); // utf8mb4 is safer for modern apps (emojis, etc)
}
catch (mysqli_sql_exception $e) {
    // If this file is included in an API context, we should return a generic JSON error
    // instead of a raw text error or HTML crash.
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed. Please try again later.'
    ]);
    exit;
}
?>