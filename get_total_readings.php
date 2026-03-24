<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sensor_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed"]));
}

// Get total count of readings
$sql = "SELECT COUNT(*) as total FROM sensor_readings";
$result = $conn->query($sql);
$row = $result->fetch_assoc();

echo json_encode(["total" => $row['total']]);

$conn->close();
?>