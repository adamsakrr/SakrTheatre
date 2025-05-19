<?php
require_once __DIR__ . '/../includes/db_config.php';

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "Connected to database successfully...<br>";

$sqlFile = file_get_contents(__DIR__ . '/update_users_table.sql');
$sqlStatements = explode(';', $sqlFile);

foreach ($sqlStatements as $sql) {
    $sql = trim($sql);
    if (empty($sql)) continue;
    
    try {
        if (mysqli_query($conn, $sql)) {
            echo "Query executed successfully: " . substr($sql, 0, 50) . "...<br>";
        } else {
            echo "Error executing query: " . mysqli_error($conn) . "<br>Query was: " . $sql . "<br>";
        }
    } catch (Exception $e) {
        if (strpos($e->getMessage(), "Duplicate column") !== false) {
            echo "Column already exists, skipping: " . $e->getMessage() . "<br>";
        } else {
            echo "Error: " . $e->getMessage() . "<br>";
        }
    }
}

echo "Database update process completed.<br>";
mysqli_close($conn);
?>