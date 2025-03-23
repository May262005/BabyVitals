<?php
// Configuración de CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

// Conexión a la base de datos
$servername = "localhost";
$username = "root"; // Cambia esto si es necesario
$password = "pinguino26"; // Cambia esto si tienes una contraseña
$dbname = "babyvitals";

// Crear la conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    die(json_encode(["error" => "Conexión fallida: " . $conn->connect_error]));
}

// Consultar pacientes
$sql = "SELECT id, nombre_completo, edad, IFNULL(enfermedad, 'N/P') AS enfermedad, sexo, estado FROM Pacientes WHERE estado = 0";
$result = $conn->query($sql);

// Verificar si hay resultados
if ($result->num_rows > 0) {
    // Crear un array para almacenar los pacientes
    $pacientes = [];
    while ($row = $result->fetch_assoc()) {
        $pacientes[] = $row;
    }
    echo json_encode($pacientes); // Devolver los pacientes en formato JSON
} else {
    echo json_encode(["error" => "No se encontraron pacientes"]);
}

// Cerrar la conexión
$conn->close();
?>
