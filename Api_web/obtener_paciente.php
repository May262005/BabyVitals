<?php
// Configuración de CORS
header("Access-Control-Allow-Origin: *");  // Permitir solicitudes de cualquier origen
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE"); // Permitir estos métodos
header("Access-Control-Allow-Headers: Content-Type"); // Permitir estos encabezados

// Configuración de la base de datos
$servername = "localhost";
$username = "root"; // Reemplazar con tu usuario
$password = "pinguino26"; // Reemplazar con tu contraseña
$dbname = "babyvitals"; // Nombre de la base de datos

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener el ID del paciente de la URL
if (isset($_GET['id'])) {
    // Validar que el id sea un número entero
    $id = (int) $_GET['id'];

    // Si el id no es un número válido, devolver error
    if ($id <= 0) {
        echo json_encode(["error" => "ID inválido"]);
        exit;
    }

    // Consulta para obtener la información del paciente desde la vista
    $sql = "SELECT * FROM vista_pacientes WHERE id = ?";
    
    // Preparar la consulta
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);  // "i" indica que el parámetro es un entero
    $stmt->execute();
    
    // Obtener el resultado
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Devolver la información del paciente
        $paciente = $result->fetch_assoc();
        echo json_encode($paciente);
    } else {
        echo json_encode(["error" => "Paciente no encontrado"]);
    }

    $stmt->close(); // Cerrar la declaración
} else {
    echo json_encode(["error" => "ID no proporcionado"]);
}

// Cerrar la conexión
$conn->close();
?>
