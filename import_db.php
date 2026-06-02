<?php
set_time_limit(300);
ini_set('memory_limit', '512M');

require_once "db.php";

/*
    Simple protection so random people cannot run this.
    Change this key if you want.
*/
$key = $_GET["key"] ?? "";

if ($key !== "import123") {
    die("Access denied.");
}

$sql_file = __DIR__ . "/hustle_muscle.sql";

if (!file_exists($sql_file)) {
    die("SQL file not found.");
}

$sql = file_get_contents($sql_file);

if ($sql === false || trim($sql) === "") {
    die("SQL file is empty or unreadable.");
}

/*
    Remove database creation/use commands just in case.
*/
$sql = preg_replace('/CREATE DATABASE.*?;/is', '', $sql);
$sql = preg_replace('/USE\s+`?.*?`?\s*;/is', '', $sql);

/*
    Execute full SQL dump.
*/
if ($conn->multi_query($sql)) {
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());

    if ($conn->errno) {
        echo "Import finished with MySQL error:<br>";
        echo htmlspecialchars($conn->error);
    } else {
        echo "Database imported successfully.";
    }
} else {
    echo "Import failed:<br>";
    echo htmlspecialchars($conn->error);
}
?>