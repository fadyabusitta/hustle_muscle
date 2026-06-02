<?php
/*
    Hustle Muscle Database Connection

    This file automatically connects to:
    1. Local XAMPP database when running on localhost
    2. InfinityFree database when uploaded online
*/

$host_name = $_SERVER["HTTP_HOST"] ?? "";

/*
    LOCAL XAMPP SETTINGS
*/
if (
    $host_name === "localhost" ||
    $host_name === "127.0.0.1" ||
    str_starts_with($host_name, "localhost:")
) {
    $db_host = "localhost";
    $db_user = "root";
    $db_pass = "";
    $db_name = "hustle_muscle";
    $db_port = 3306;
}

/*
    ONLINE INFINITYFREE SETTINGS
*/
else {
   $db_host = getenv("MYSQLHOST");
   $db_user = getenv("MYSQLUSER");
   $db_pass = getenv("MYSQLPASSWORD");
   $db_name = getenv("MYSQLDATABASE");
   $db_port = intval(getenv("MYSQLPORT"));
}

/*
    CREATE CONNECTION
*/
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

/*
    CHECK CONNECTION
*/
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

/*
    SET CHARACTER ENCODING
*/
$conn->set_charset("utf8mb4");
?>