<?php
// Configuración de la conexión a la base de datos
$servername = "localhost";
$username = "root"; // Cambia esto por tu nombre de usuario de MySQL
$password = "pinguino26"; // Cambia esto por tu contraseña de MySQL
$dbname = "babyvitals";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    // Preparar respuesta de error en formato JSON
    $response = array(
        "success" => false,
        "message" => "Error de conexión: " . $conn->connect_error,
        "data" => null
    );
    
    // Establecer tipo de contenido como JSON y enviar respuesta
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Establecer el conjunto de caracteres a utf8
$conn->set_charset("utf8");

// Consulta SQL para obtener todos los doctores
$sql = "SELECT id, nombre_completo, correo, especialidad, created_at 
        FROM doctores 
        ORDER BY nombre_completo ASC";

// Ejecutar consulta
$result = $conn->query($sql);

// Verificar si se obtuvieron resultados
if ($result->num_rows > 0) {
    // Array para almacenar los datos de los doctores
    $doctores = array();
    
    // Obtener datos de cada fila
    while($row = $result->fetch_assoc()) {
        // Añadir cada doctor al array
        $doctores[] = array(
            "id" => $row["id"],
            "nombre_completo" => $row["nombre_completo"],
            "correo" => $row["correo"],
            "especialidad" => $row["especialidad"],
            "created_at" => $row["created_at"]
        );
    }
    
    // Preparar respuesta exitosa
    $response = array(
        "success" => true,
        "message" => "Doctores obtenidos correctamente",
        "data" => $doctores
    );
} else {
    // Preparar respuesta cuando no hay doctores
    $response = array(
        "success" => true,
        "message" => "No se encontraron doctores",
        "data" => array()
    );
}

// Cerrar conexión
$conn->close();

// Establecer tipo de contenido como JSON antes de enviar la respuesta
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");
// Enviar respuesta
echo json_encode($response);
?>
