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

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["error" => "Conexión fallida: " . $conn->connect_error]));
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['doctor_id']) || !isset($data['paciente_id']) || !isset($data['fecha']) || !isset($data['hora_inicio'])) {
    echo json_encode(["error" => "Faltan datos requeridos"]);
    exit();
}

// Escapar los datos
$doctor_id = $conn->real_escape_string($data['doctor_id']);
$paciente_id = $conn->real_escape_string($data['paciente_id']);
$fecha = $conn->real_escape_string($data['fecha']);
$hora_inicio = $conn->real_escape_string($data['hora_inicio']);

// Verificar si el doctor y el paciente existen
$doctor_check = $conn->query("SELECT * FROM doctores WHERE id = '$doctor_id'");
$paciente_check = $conn->query("SELECT * FROM pacientes WHERE id = '$paciente_id'");

if ($doctor_check->num_rows == 0) {
    echo json_encode(["error" => "El doctor no existe"]);
    exit();
}

if ($paciente_check->num_rows == 0) {
    echo json_encode(["error" => "El paciente no existe"]);
    exit();
}

// Convertir la fecha a un timestamp y obtener el día de la semana
$timestamp = strtotime($fecha);
$dia_semana = date('l', $timestamp); // Obtiene el nombre del día de la semana en inglés

// Convertir el día de la semana a español
switch ($dia_semana) {
    case 'Monday':
        $dia_semana = 'Lunes';
        break;
    case 'Tuesday':
        $dia_semana = 'Martes';
        break;
    case 'Wednesday':
        $dia_semana = 'Miércoles';
        break;
    case 'Thursday':
        $dia_semana = 'Jueves';
        break;
    case 'Friday':
        $dia_semana = 'Viernes';
        break;
    case 'Saturday':
        $dia_semana = 'Sábado';
        break;
    case 'Sunday':
        $dia_semana = 'Domingo';
        break;
}

// Calcular hora_fin (30 minutos después de hora_inicio)
$hora_fin = date('H:i:s', strtotime($hora_inicio . ' + 30 minutes'));

// Verificar disponibilidad del doctor
$disponibilidad_check = $conn->query("SELECT * FROM disponibilidad_Citas 
    WHERE doctor_id = '$doctor_id' 
    AND dia_semana = '$dia_semana' 
    AND hora_inicio <= '$hora_inicio' 
    AND hora_fin >= '$hora_fin'");

if ($disponibilidad_check->num_rows == 0) {
    echo json_encode(["error" => "El doctor no está disponible en esta fecha y hora."]);
    exit();
}

// Verificar si hay citas conflictivas manualmente (igual que en el trigger)
$cita_conflictiva = $conn->query("
    SELECT COUNT(*) as conflicto
    FROM citas
    WHERE doctor_id = '$doctor_id'
    AND fecha = '$fecha'
    AND ('$hora_inicio' < hora_fin AND '$hora_fin' > hora_inicio)
");

$conflicto = $cita_conflictiva->fetch_assoc();
if ($conflicto['conflicto'] > 0) {
    echo json_encode(["error" => "Ya hay una cita en ese horario, por favor elige otro."]);
    exit();
}

// Verificar si la fecha es pasada
$fecha_actual = date('Y-m-d');
if ($fecha < $fecha_actual) {
    echo json_encode(["error" => "No puedes registrar citas en fechas pasadas."]);
    exit();
}



// Aquí se realiza la inserción con la tabla actualizada que incluye hora_fin
$sql = "INSERT INTO citas (doctor_id, paciente_id, fecha, hora_inicio, hora_fin, estado) 
        VALUES ('$doctor_id', '$paciente_id', '$fecha', '$hora_inicio', '$hora_fin', 'Pendiente')";

if ($conn->query($sql) === TRUE) {
    echo json_encode(["success" => "Cita registrada exitosamente"]);
} else {
    // Capturar errores que pueda generar el trigger
    if (strpos($conn->error, "No puedes registrar citas en fechas pasadas") !== false) {
        echo json_encode(["error" => "No puedes registrar citas en fechas pasadas."]);
    } else if (strpos($conn->error, "El horario no está disponible") !== false) {
        echo json_encode(["error" => "El horario no está disponible para este doctor en esta fecha."]);
    } else if (strpos($conn->error, "Ya hay una cita en ese horario") !== false) {
        echo json_encode(["error" => "Ya hay una cita en ese horario, por favor elige otro."]);
    } else {
        echo json_encode(["error" => "Error al registrar cita: " . $conn->error]);
    }
}

$conn->close();
?>