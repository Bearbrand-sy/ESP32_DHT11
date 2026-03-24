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

// Get last 24 hours of data
$sql = "SELECT temperature, humidity, reading_time FROM sensor_readings 
        WHERE reading_time >= NOW() - INTERVAL 24 HOUR 
        ORDER BY id DESC LIMIT 100";
$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);

$conn->close();
?>