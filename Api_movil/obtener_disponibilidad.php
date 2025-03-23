<?php
// Headers for cross-origin requests and JSON response
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// Database connection parameters
$servername = "localhost";
$username = "root"; // Update with your MySQL username
$password = "pinguino26"; // Update with your MySQL password
$dbname = "babyvitals";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode([
        "success" => false,
        "message" => "Connection failed: " . $conn->connect_error
    ]));
}

// Set character set
$conn->set_charset("utf8");

// Check if doctor_id is provided
if (!isset($_GET['doctor_id']) || empty($_GET['doctor_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "Se requiere un ID de doctor"
    ]);
    exit;
}

$doctor_id = $conn->real_escape_string($_GET['doctor_id']);

// Query to get doctor's availability
$sql = "SELECT 
            id,
            doctor_id,
            dia_semana,
            TIME_FORMAT(hora_inicio, '%h:%i %p') AS hora_inicio_formato,
            TIME_FORMAT(hora_fin, '%h:%i %p') AS hora_fin_formato,
            hora_inicio,
            hora_fin
        FROM disponibilidad_citas 
        WHERE doctor_id = ?
        ORDER BY FIELD(dia_semana, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'), 
                 hora_inicio";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $disponibilidad = [];
    
    while ($row = $result->fetch_assoc()) {
        $disponibilidad[] = [
            "id" => $row["id"],
            "doctor_id" => $row["doctor_id"],
            "dia_semana" => $row["dia_semana"],
            "hora_inicio" => $row["hora_inicio_formato"],
            "hora_fin" => $row["hora_fin_formato"],
            "hora_inicio_raw" => $row["hora_inicio"],
            "hora_fin_raw" => $row["hora_fin"]
        ];
    }
    
    echo json_encode([
        "success" => true,
        "data" => $disponibilidad
    ]);
} else {
    echo json_encode([
        "success" => true,
        "data" => [],
        "message" => "El doctor no tiene horarios de disponibilidad registrados"
    ]);
}

$stmt->close();
$conn->close();
?>