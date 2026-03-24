<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sensor_db";

// Get data from ESP32
$temperature = isset($_GET['temperature']) ? floatval($_GET['temperature']) : null;
$humidity = isset($_GET['humidity']) ? floatval($_GET['humidity']) : null;

if ($temperature !== null && $humidity !== null) {
    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Insert data
    $sql = "INSERT INTO sensor_readings (temperature, humidity) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("dd", $temperature, $humidity);
    
    if ($stmt->execute()) {
        echo "Data inserted successfully";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo "Invalid data";
}
?>