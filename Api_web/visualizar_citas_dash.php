<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Database connection parameters
$host = "localhost";
$user = "root"; // Change if you have a different MySQL user
$password = "pinguino26"; // Change if your MySQL has a password
$database = "babyvitals";

// Connect to the database
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

$conn->set_charset("utf8"); // Set encoding to UTF-8

// Check if user_id is received
if (!isset($_GET["user_id"])) {
    echo json_encode(["error" => "Missing parameters"]);
    exit();
}

$user_id = intval($_GET["user_id"]); // Get the user_id parameter

// Get the current date to compare with the cita's fecha
$current_date = date("Y-m-d");

// Query to get appointments for the user with future or today dates
$sql = "SELECT c.id, p.nombre_completo AS paciente, d.nombre_completo AS doctor, c.fecha, c.hora_inicio
        FROM citas c
        INNER JOIN pacientes p ON c.paciente_id = p.id
        INNER JOIN doctores d ON c.doctor_id = d.id
        WHERE (c.doctor_id = ? OR p.tutor_id = ?)
        AND c.fecha >= ?"; // Filter for future or today's appointments

$stmt = $conn->prepare($sql);
$stmt->bind_param("iis", $user_id, $user_id, $current_date); // Bind the user_id and current date
$stmt->execute();
$result = $stmt->get_result();

$citas = [];
while ($row = $result->fetch_assoc()) {
    $citas[] = $row;
}

// Get the count of future or today's appointments
$citas_count = count($citas);

$response = [
    "citas_count" => $citas_count, // Add the count of appointments
    "citas" => $citas // Return the appointments
];

echo json_encode($response);

// Close connection
$stmt->close();
$conn->close();

?>
