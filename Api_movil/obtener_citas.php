<?php
// Configuración de la conexión a la base de datos
$host = "localhost";
$user = "root";
$password = "pinguino26";
$database = "babyvitals";

// Encabezados para permitir CORS y especificar tipo de contenido
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Crear conexión a la base de datos
$conn = new mysqli($host, $user, $password, $database);

// Verificar la conexión
if ($conn->connect_error) {
    die(json_encode(array("message" => "Error de conexión: " . $conn->connect_error)));
}

// Verificar si se ha proporcionado un ID de paciente
if (isset($_GET['paciente_id'])) {
    $paciente_id = $_GET['paciente_id'];
   
    // Consulta SQL actualizada para usar la estructura modificada de las tablas
    $sql = "SELECT c.id, c.fecha, c.hora_inicio, c.hora_fin, c.estado
           FROM citas c
           WHERE c.paciente_id = ?
           ORDER BY c.fecha, c.hora_inicio";
   
    // Preparar la consulta
    $stmt = $conn->prepare($sql);
   
    // Verificar si la preparación fue exitosa
    if ($stmt === false) {
        die(json_encode(array("message" => "Error en la preparación de la consulta: " . $conn->error)));
    }
   
    // Vincular el parámetro
    $stmt->bind_param("i", $paciente_id);
   
    // Ejecutar la consulta
    $stmt->execute();
   
    // Obtener resultados
    $result = $stmt->get_result();
   
    // Verificar si hay resultados
    if ($result->num_rows > 0) {
        $citas = array();
       
        // Recorrer resultados y añadirlos al array
        while ($row = $result->fetch_assoc()) {
            // Formatear fecha para mejor visualización
            $fecha_formateada = date("d M Y", strtotime($row['fecha']));
           
            $cita = array(
                "id" => $row['id'],
                "fecha_original" => $row['fecha'],
                "hora_inicio" => $row['hora_inicio'],
                "hora_fin" => $row['hora_fin'],
                "estado" => $row['estado']
            );
           
            array_push($citas, $cita);
        }
       
        // Devolver las citas en formato JSON
        echo json_encode($citas);
    } else {
        // No se encontraron citas
        echo json_encode(array("message" => "No se encontraron citas para este paciente."));
    }
   
    // Cerrar declaración
    $stmt->close();
} else {
    // No se proporcionó ID de paciente
    echo json_encode(array("message" => "Se requiere un ID de paciente."));
}

// Cerrar conexión
$conn->close();
?>