<?php
// Manejar solicitudes OPTIONS para la preflight request de CORS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit(0); // No continuar con la ejecución del script
}

// Permitir CORS en todas las solicitudes
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Conexión a la base de datos
$servername = "localhost";
$username = "root";  // Cambia esto si tienes otro usuario
$password = "pinguino26";      // Cambia esto si tienes una contraseña
$dbname = "babyvitals";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["message" => "Error de conexión a la base de datos"]));
}

// Obtener datos del JSON enviado desde React
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data["nombre_completo"], $data["correo"], $data["contrasena"], $data["especialidad"])) {
    $nombre_completo = $data["nombre_completo"];
    $correo = $data["correo"];
    $contrasena = password_hash($data["contrasena"], PASSWORD_DEFAULT);
    $especialidad = $data["especialidad"];

    // Insertar los datos del doctor en la base de datos
    $sql = "INSERT INTO doctores (nombre_completo, correo, contrasena, especialidad) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $nombre_completo, $correo, $contrasena, $especialidad);

    if ($stmt->execute()) {
        echo json_encode(["message" => "Doctor registrado correctamente"]);
    } else {
        echo json_encode(["message" => "Error al registrar doctor"]);
    }

    $stmt->close();
} else {
    echo json_encode(["message" => "Datos incompletos"]);
}

$conn->close();
?>
