<?php
// Permitir CORS en todas las solicitudes
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "pinguino26";
$dbname = "babyvitals";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["error" => "Conexión fallida: " . $conn->connect_error]));
}

// Obtener datos del JSON recibido
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["error" => "Datos no válidos"]);
    exit;
}

$nombrePaciente = $data['nombrePaciente'];
$enfermedad = $data['enfermedad'] ?? NULL;
$codigoDispositivo = $data['codigoDispositivo'];
$sexo = $data['sexo'];
$nombreTutor = $data['nombreTutor'];
$telefonoTutor = $data['telefonoTutor'];
$correoTutor = $data['correoTutor'];
$fechaNacimiento = $data['fechaNacimiento'];

// Verificar si el tutor ya existe
$stmt = $conn->prepare("SELECT id FROM Tutores WHERE correo = ?");
$stmt->bind_param("s", $correoTutor);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $tutorId = $row['id'];
} else {
    // Insertar tutor si no existe
    $stmt = $conn->prepare("INSERT INTO Tutores (nombre_completo, correo, telefono) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nombreTutor, $correoTutor, $telefonoTutor);
    if ($stmt->execute()) {
        $tutorId = $stmt->insert_id;
    } else {
        echo json_encode(["error" => "Error al registrar el tutor"]);
        exit;
    }
}

// Asignar estado por defecto
$estado = 1;

// Insertar paciente con fecha de nacimiento - REMOVE edad parameter
$stmt = $conn->prepare("INSERT INTO Pacientes (nombre_completo, enfermedad, codigo_dispositivo, tutor_id, sexo, fecha_nacimiento, estado) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssi", $nombrePaciente, $enfermedad, $codigoDispositivo, $tutorId, $sexo, $fechaNacimiento, $estado);

if ($stmt->execute()) {
    // Llamar al procedimiento almacenado para generar usuario y contraseña después de insertar el tutor
    $stmt = $conn->prepare("CALL GenerarUsuarioContraseña(?)");
    $stmt->bind_param("i", $tutorId);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => "Registro exitoso y procedimiento ejecutado"]);
    } else {
        echo json_encode(["error" => "Error al ejecutar el procedimiento almacenado"]);
    }
} else {
    echo json_encode(["error" => "Error al registrar el paciente: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>