<?php
// Establecer encabezados para permitir CORS y especificar el tipo de contenido
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Conexión a la base de datos
$servername = "localhost"; // Cambia esto según tu configuración
$username = "root"; // Cambia esto según tu configuración
$password = "pinguino26"; // Cambia esto según tu configuración
$dbname = "babyvitals";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die(json_encode(["error" => "Conexión fallida: " . $conn->connect_error]));
}

// Establecer el conjunto de caracteres
$conn->set_charset("utf8");

// Verificar que se proporciona el ID del paciente
if (!isset($_GET['paciente_id']) || empty($_GET['paciente_id'])) {
    echo json_encode(["error" => "Se requiere el ID del paciente"]);
    exit;
}

$paciente_id = $conn->real_escape_string($_GET['paciente_id']);
$fecha_actual = date('Y-m-d');

// Consulta para obtener los tratamientos actuales (fecha actual entre fecha_inicio y fecha_fin)
$sql = "SELECT t.*, d.nombre_completo AS nombre_doctor 
        FROM tratamientos t
        JOIN doctores d ON t.doctor_id = d.id
        WHERE t.paciente_id = '$paciente_id' 
        AND '$fecha_actual' BETWEEN t.fecha_inicio AND t.fecha_fin";

$result = $conn->query($sql);

if ($result === false) {
    echo json_encode(["error" => "Error en la consulta: " . $conn->error]);
    exit;
}

$tratamientos = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $tratamientos[] = $row;
    }
}

// Devolver los resultados en formato JSON
echo json_encode($tratamientos);

// Cerrar conexión
$conn->close();
?>