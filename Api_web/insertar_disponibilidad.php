<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, DELETE");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Conexión a la base de datos
$servername = "localhost";
$username = "root";  
$password = "pinguino26";  
$dbname = "babyvitals";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Conexión fallida: " . $conn->connect_error]));
}

// Obtener los datos enviados desde React
$inputJSON = file_get_contents("php://input");
$data = json_decode($inputJSON, true);

// Verificar el método de la solicitud
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'DELETE') {
    // Procesamiento para eliminar disponibilidad
    if (!isset($data["doctor_id"]) || !isset($data["dia_semana"])) {
        echo json_encode(["success" => false, "message" => "Faltan datos necesarios para eliminar"]);
        exit;
    }

    $doctor_id = $data["doctor_id"];
    $dia_semana = $data["dia_semana"];

    try {
        // Preparar la sentencia para eliminar
        $deleteStmt = $conn->prepare("DELETE FROM disponibilidad_Citas WHERE doctor_id = ? AND dia_semana = ?");
        $deleteStmt->bind_param("is", $doctor_id, $dia_semana);
        
        // Ejecutar la sentencia
        if ($deleteStmt->execute()) {
            if ($deleteStmt->affected_rows > 0) {
                echo json_encode(["success" => true, "message" => "Disponibilidad eliminada correctamente"]);
            } else {
                echo json_encode(["success" => false, "message" => "No se encontró la disponibilidad para eliminar"]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "Error al eliminar la disponibilidad"]);
        }
        
        $deleteStmt->close();
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Error al eliminar la disponibilidad", "error" => $e->getMessage()]);
    }
} else if ($method === 'POST') {
    // Código existente para insertar/actualizar
    if (!isset($data) || empty($data)) {
        echo json_encode(["success" => false, "message" => "No se recibieron datos"]);
        exit;
    }

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        foreach ($data as $disponibilidad) {
            $doctor_id = $disponibilidad["doctor_id"];
            $dia_semana = $disponibilidad["dia_semana"];
            $hora_inicio = $disponibilidad["hora_inicio"];
            $hora_fin = $disponibilidad["hora_fin"];

            // Verificar si ya existe el registro
            $checkStmt = $conn->prepare("SELECT id FROM disponibilidad_Citas WHERE doctor_id = ? AND dia_semana = ?");
            $checkStmt->bind_param("is", $doctor_id, $dia_semana);
            $checkStmt->execute();
            $result = $checkStmt->get_result();

            if ($result->num_rows > 0) {
                // Si existe, actualizar
                $updateStmt = $conn->prepare("UPDATE disponibilidad_Citas SET hora_inicio = ?, hora_fin = ? WHERE doctor_id = ? AND dia_semana = ?");
                $updateStmt->bind_param("ssis", $hora_inicio, $hora_fin, $doctor_id, $dia_semana);
                $updateStmt->execute();
                $updateStmt->close();
            } else {
                // Si no existe, insertar
                $insertStmt = $conn->prepare("INSERT INTO disponibilidad_Citas (doctor_id, dia_semana, hora_inicio, hora_fin) VALUES (?, ?, ?, ?)");
                $insertStmt->bind_param("isss", $doctor_id, $dia_semana, $hora_inicio, $hora_fin);
                $insertStmt->execute();
                $insertStmt->close();
            }
            $checkStmt->close();
        }
        
        $conn->commit();
        echo json_encode(["success" => true, "message" => "Disponibilidad guardada/actualizada correctamente"]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["success" => false, "message" => "Error al guardar la disponibilidad", "error" => $e->getMessage()]);
    }
} else {
    // Método no permitido
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
}

// Cerrar conexión
$conn->close();
?>