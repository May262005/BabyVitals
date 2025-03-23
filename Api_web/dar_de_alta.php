<?php
header("Access-Control-Allow-Origin: *"); // Permite cualquier origen
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS"); // Métodos permitidos
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Encabezados permitidos

// Si la solicitud es un "preflight" (OPTIONS), simplemente se responde
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Conexión a la base de datos
$servername = "localhost";
$username = "root";  // Cambia esto si es necesario
$password = "pinguino26";  // Cambia esto si tienes una contraseña
$dbname = "babyvitals";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["error" => "Conexión fallida: " . $conn->connect_error]));
}

// Obtener datos del JSON recibido
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['id_paciente'])) {
    echo json_encode(["error" => "ID del paciente no especificado o datos inválidos"]);
    exit;
}

$id_paciente = $data['id_paciente'];

// Consulta para cambiar el estado del paciente a baja (estado = 0)
$sql = "UPDATE pacientes SET estado = 0 WHERE id = ?";

if ($stmt = $conn->prepare($sql)) {
    // Vincular parámetros
    $stmt->bind_param("i", $id_paciente);

    // Ejecutar la consulta
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Paciente dado de baja correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al dar de baja al paciente.']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Error al preparar la consulta.']);
}

$conn->close();
?>
