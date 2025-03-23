<?php
// Habilitar CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST");

// Conectar a la base de datos
$host = "localhost";
$dbname = "babyvitals";
$username = "root";
$password = "pinguino26";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
    exit;
}

header('Content-Type: application/json');

// Verificar que la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    // Verificar si el dato que recibimos tiene la estructura correcta
    if (isset($data) && is_array($data)) {
        $doctor_id = $data[0]['doctor_id']; // Asumimos que todos los días tienen el mismo doctor_id
        $inserted = false;

        // Primero, ver si el doctor ya tiene una agenda registrada
        $stmt = $conn->prepare("SELECT * FROM disponibilidad_Citas WHERE doctor_id = :doctor_id");
        $stmt->execute(['doctor_id' => $doctor_id]);
        $existingAgenda = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Si ya existe una agenda, proceder a actualizarla
        if ($existingAgenda) {
            // Eliminar las entradas existentes de la agenda del doctor
            $stmt = $conn->prepare("DELETE FROM disponibilidad_Citas WHERE doctor_id = :doctor_id");
            $stmt->execute(['doctor_id' => $doctor_id]);
        }

        // Insertar los nuevos datos de disponibilidad
        $stmt = $conn->prepare(
            "INSERT INTO disponibilidad_Citas (doctor_id, dia_semana, hora_inicio, hora_fin) 
            VALUES (:doctor_id, :dia_semana, :hora_inicio, :hora_fin)"
        );

        foreach ($data as $entry) {
            // Insertar cada entrada de disponibilidad
            $stmt->execute([
                'doctor_id' => $entry['doctor_id'],
                'dia_semana' => $entry['dia_semana'],
                'hora_inicio' => $entry['hora_inicio'],
                'hora_fin' => $entry['hora_fin']
            ]);
        }

        // Si se han insertado correctamente los datos, retornar éxito
        $inserted = true;

        echo json_encode(['success' => $inserted]);
    } else {
        // En caso de que los datos no sean válidos
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Si es una solicitud GET, obtener la disponibilidad existente del usuario
    if (isset($_GET['doctor_id'])) {
        $doctor_id = $_GET['doctor_id'];

        // Consultar la agenda del doctor
        $stmt = $conn->prepare("SELECT * FROM disponibilidad_Citas WHERE doctor_id = :doctor_id");
        $stmt->execute(['doctor_id' => $doctor_id]);
        $agenda = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($agenda) {
            echo json_encode(['success' => true, 'agenda' => $agenda]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No hay agenda registrada']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ID de doctor no proporcionado']);
    }
}
?>
