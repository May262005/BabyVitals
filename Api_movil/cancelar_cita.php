<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$servername = "localhost";
$username = "root";
$password = "pinguino26";
$dbname = "babyvitals";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Función para obtener todas las citas
function obtenerCitas($conn) {
    $sql = "SELECT * FROM citas";
    $result = $conn->query($sql);
    $citas = [];
    while ($row = $result->fetch_assoc()) {
        $citas[] = $row;
    }
    return json_encode($citas);
}

// Función para cancelar una cita
function cancelarCita($conn, $id) {
    $sql = "UPDATE citas SET estado='Cancelada' WHERE id=$id";
    if ($conn->query($sql) === TRUE) {
        return json_encode(["success" => true]);
    } else {
        return json_encode(["success" => false, "error" => $conn->error]);
    }
}

// Manejo de solicitudes
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo obtenerCitas($conn);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    if (isset($data['id'])) {
        echo cancelarCita($conn, $data['id']);
    }
}

$conn->close();
?>
