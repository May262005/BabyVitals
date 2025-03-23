<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Conexión a la base de datos
$host = "localhost"; // Cambia esto si usas un host diferente
$user = "root"; // Usuario de MySQL
$password = "pinguino26"; // Contraseña de MySQL (ajústala según tu configuración)
$database = "babyvitals";

$conn = new mysqli($host, $user, $password, $database);

// Verificar conexión
if ($conn->connect_error) {
    die(json_encode(["error" => "Error de conexión: " . $conn->connect_error]));
}

// Obtener los parámetros GET
$paciente_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : "";

// Validar entrada
if ($paciente_id <= 0 || empty($fecha_inicio)) {
    echo json_encode(["error" => "Parámetros inválidos"]);
    exit;
}


$sql = "
SELECT 
    t.id AS tratamiento_id,
    t.padecimiento,
    t.medicamento,
    t.fecha_inicio,
    t.fecha_fin,
    d.nombre_completo AS doctor_name
        FROM tratamientos t
        INNER JOIN doctores d ON t.doctor_id = d.id
        WHERE t.paciente_id = ? AND t.fecha_inicio = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $paciente_id, $fecha_inicio);
$stmt->execute();
$result = $stmt->get_result();

$tratamientos = [];
while ($row = $result->fetch_assoc()) {
    $tratamientos[] = $row;
}

// Cerrar la conexión
$stmt->close();
$conn->close();

// Devolver la respuesta en JSON
if (count($tratamientos) > 0) {
    echo json_encode(["success" => true, "tratamientos" => $tratamientos]);
} else {
    echo json_encode(["success" => false, "message" => "No se encontraron tratamientos"]);
}
?>
