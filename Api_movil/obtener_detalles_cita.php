<?php
// Configuración de CORS para permitir peticiones desde tu aplicación React Native
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

// Si la petición es de tipo OPTIONS, respondemos solo con los encabezados
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verificamos que la petición sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtenemos el cuerpo de la petición
$data = json_decode(file_get_contents("php://input"), true);

// Verificamos los datos recibidos
if (!isset($data['tutor_id']) || !isset($data['appointment_id'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

// Conexión a la base de datos
$host = 'localhost';
$db = 'babyvitals';
$user = 'root';
$password = 'pinguino26';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

// Preparamos la consulta
$query = "
    SELECT 
        c.id, 
        c.fecha, 
        c.hora_inicio, 
        c.hora_fin,
        c.estado,
        p.nombre_completo AS nombre_paciente, 
        d.nombre_completo AS nombre_doctor,
        d.especialidad
    FROM citas c
    JOIN pacientes p ON c.paciente_id = p.id
    JOIN doctores d ON c.doctor_id = d.id
    JOIN tutores tu ON p.tutor_id = tu.id
    WHERE c.id = :appointment_id 
    AND tu.id = :tutor_id
";

try {
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':appointment_id', $data['appointment_id'], PDO::PARAM_INT);
    $stmt->bindParam(':tutor_id', $data['tutor_id'], PDO::PARAM_INT);
    $stmt->execute();
    
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($appointment) {
        echo json_encode(['success' => true, 'appointment' => $appointment]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Cita no encontrada o no tienes permiso para verla']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error al consultar la base de datos: ' . $e->getMessage()]);
}
?>