<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Manejar solicitudes preflight (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$servername = "localhost";
$username = "root";  // Usuario de MySQL
$password = "pinguino26"; // Contraseña de MySQL
$dbname = "babyvitals"; // Nombre de la base de datos

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["error" => "Error de conexión: " . $conn->connect_error]));
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->identificador) || !isset($data->contrasena)) {
    echo json_encode(["error" => "Faltan datos"]);
    exit();
}

$identificador = $data->identificador;
$contrasena = $data->contrasena;

$sql = "SELECT * FROM tutores WHERE correo = ? OR usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $identificador, $identificador);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $usuario = $result->fetch_assoc();
    
    if ($contrasena === $usuario["contrasena"]) {
        echo json_encode(["mensaje" => "Inicio de sesión exitoso", "id" => $usuario["id"], "nombre" => $usuario["nombre_completo"]]);
    } else {
        echo json_encode(["error" => "Contraseña incorrecta"]);
    }
    
}
$conn->close();
?>