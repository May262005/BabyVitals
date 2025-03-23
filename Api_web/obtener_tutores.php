<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Content-Type: application/json; charset=UTF-8");

// Conexión a la base de datos
$servername = "localhost";
$username = "root"; // Cambia si es necesario
$password = "pinguino26"; // Cambia si es necesario
$database = "BabyVitals";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die(json_encode(["error" => "Conexión fallida: " . $conn->connect_error]));
}

// Verifica si se recibe el id del paciente
if (!isset($_GET['id_paciente'])) {
    die(json_encode(["error" => "Falta el parámetro id_paciente"]));
}

$id_paciente = intval($_GET['id_paciente']); // Seguridad contra SQL Injection

// Consulta para obtener los tutores del paciente
$sql = "SELECT t.id, t.nombre_completo, t.correo, t.usuario, t.contrasena, t.telefono 
        FROM Tutores t
        INNER JOIN Pacientes p ON t.id = p.tutor_id
        WHERE p.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_paciente);
$stmt->execute();
$result = $stmt->get_result();

// Convertir resultado a array JSON
$tutores = [];
while ($row = $result->fetch_assoc()) {
    $tutores[] = $row;
}

// Verifica si se encontraron tutores
if (count($tutores) > 0) {
    echo json_encode($tutores);
} else {
    echo json_encode(["error" => "No se encontraron tutores para este paciente"]);
}

$stmt->close();
$conn->close();
?>
