<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database connection
try {
    $pdo = new PDO('mysql:host=localhost;dbname=babyvitals', 'root', 'pinguino26');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["message" => "Error de conexión a la base de datos: " . $e->getMessage()]);
    exit;
}

// Get JSON data sent in the request
$inputData = json_decode(file_get_contents('php://input'), true);

// Log the received data for debugging
error_log(print_r($inputData, true));

// Handle both possible field names for medication
$medicamento = isset($inputData['medicamento']) ? $inputData['medicamento'] : 
               (isset($inputData['medicamentos']) ? $inputData['medicamentos'] : []);

// Verify that required treatment data is present
if (empty($inputData['doctor_id']) || 
    empty($inputData['paciente_id']) || 
    empty($inputData['padecimiento']) || 
    empty($medicamento) || 
    empty($inputData['fecha_inicio']) || 
    empty($inputData['fecha_fin']) || 
    empty($inputData['indicaciones'])) {
    echo json_encode(["message" => "Faltan datos requeridos para el tratamiento."]);
    exit;
}

// Verify that required appointment details data is present
if (empty($inputData['motivo_consulta']) || 
    empty($inputData['estado_paciente_consulta'])) {
    echo json_encode(["message" => "Faltan datos requeridos para los detalles de la cita."]);
    exit;
}

// Treatment data
$doctor_id = $inputData['doctor_id'];
$paciente_id = $inputData['paciente_id'];
$padecimiento = $inputData['padecimiento'];
$medicamentos = is_array($medicamento) ? implode(", ", $medicamento) : $medicamento;
$fecha_inicio = $inputData['fecha_inicio'];
$fecha_fin = $inputData['fecha_fin'];
$indicaciones = $inputData['indicaciones'];

// Appointment details data
$observaciones_medicas = isset($inputData['observaciones_medicas']) ? $inputData['observaciones_medicas'] : '';
$estado_paciente_consulta = $inputData['estado_paciente_consulta'];
$peso = isset($inputData['peso']) ? $inputData['peso'] : null;
$talla = isset($inputData['talla']) ? $inputData['talla'] : null;
$motivo_consulta = $inputData['motivo_consulta'];
$requiere_estudios = isset($inputData['requiere_estudios']) ? $inputData['requiere_estudios'] : false;
$estudios_solicitados = isset($inputData['estudios_solicitados']) ? $inputData['estudios_solicitados'] : '';

// Start a transaction to ensure data integrity
$pdo->beginTransaction();

try {
    // First, create a new appointment in the citas table
   // Instead of inserting a new appointment, find the existing one and update its status
        $citaQuery = "UPDATE citas SET estado = 'Realizada' WHERE paciente_id = ? AND doctor_id = ? AND fecha = CURRENT_DATE() ORDER BY id DESC LIMIT 1";
        $citaStmt = $pdo->prepare($citaQuery);
        $citaStmt->execute([$paciente_id, $doctor_id]);

        // Get the ID of the appointment we just updated
        $getIdQuery = "SELECT id FROM citas WHERE paciente_id = ? AND doctor_id = ? AND estado = 'Realizada' ORDER BY id DESC LIMIT 1";
        $getIdStmt = $pdo->prepare($getIdQuery);
        $getIdStmt->execute([$paciente_id, $doctor_id]);
        $cita_id = $getIdStmt->fetchColumn();
    
    // Then insert the appointment details
    $detallesQuery = "INSERT INTO detalles_cita 
                     (cita_id, observaciones_medicas, estado_paciente_consulta, 
                      peso, talla, motivo_consulta, requiere_estudios, estudios_solicitados)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $detallesStmt = $pdo->prepare($detallesQuery);
    $detallesStmt->execute([
        $cita_id,
        $observaciones_medicas,
        $estado_paciente_consulta,
        $peso,
        $talla,
        $motivo_consulta,
        $requiere_estudios ? 1 : 0,
        $estudios_solicitados
    ]);
    
    // Finally insert the treatment data
    $tratamientoQuery = "INSERT INTO tratamientos 
                        (doctor_id, paciente_id, padecimiento, medicamento, 
                         fecha_inicio, fecha_fin, indicaciones)
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
    $tratamientoStmt = $pdo->prepare($tratamientoQuery);
    $tratamientoStmt->execute([
        $doctor_id, 
        $paciente_id, 
        $padecimiento, 
        $medicamentos, 
        $fecha_inicio, 
        $fecha_fin, 
        $indicaciones
    ]);
    
    // Commit the transaction
    $pdo->commit();
    
    // Log success
    error_log("Tratamiento y detalles de cita creados exitosamente. Cita ID: " . $cita_id);
    
    // Return successful response
    echo json_encode([
        "message" => "Tratamiento y detalles de cita creados exitosamente.",
        "cita_id" => $cita_id,
        "tratamiento_id" => $pdo->lastInsertId()
    ]);
    
} catch (PDOException $e) {
    // Roll back the transaction on error
    $pdo->rollBack();
    
    error_log("Error al insertar datos: " . $e->getMessage());
    echo json_encode(["message" => "Error al insertar datos: " . $e->getMessage()]);
}
?>