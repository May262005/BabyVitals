<?php
// obtener_tratamientos.php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Conectar a la base de datos
$servername = "localhost";
$username = "root"; // Ajusta según tu configuración
$password = "pinguino26"; // Ajusta según tu configuración
$dbname = "babyvitals"; // Ajusta según tu configuración de la base de datos

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die(json_encode(['error' => 'Conexión fallida: ' . $conn->connect_error]));
}

// Verificar si se recibió el ID del paciente
if (!isset($_GET['paciente_id'])) {
    die(json_encode(['error' => 'ID de paciente no proporcionado']));
}

$paciente_id = $_GET['paciente_id'];

// Preparar la consulta SQL simplificada sin el JOIN para evitar el error
$sql = "SELECT * FROM tratamientos WHERE paciente_id = ? ORDER BY fecha_inicio DESC";

// Preparar la sentencia y enlazar parámetros
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $paciente_id);

// Ejecutar la consulta
$stmt->execute();
$result = $stmt->get_result();

$tratamientos = [];
// Obtener resultados
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Convertir medicamento de string a array si está almacenado como string separado por comas
        if (isset($row['medicamento'])) {
            $row['medicamentos'] = explode(",", $row['medicamento']);
        } else {
            $row['medicamentos'] = [];
        }
        $tratamientos[] = $row;
    }
    echo json_encode(['success' => true, 'tratamientos' => $tratamientos]);
} else {
    echo json_encode(['success' => true, 'tratamientos' => []]);
}

$stmt->close();
$conn->close();
?>