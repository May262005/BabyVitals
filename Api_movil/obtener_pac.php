<?php
// Configurar CORS
header("Access-Control-Allow-Origin: *"); // Permite solicitudes desde cualquier dominio
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Establecer la cabecera para la respuesta en formato JSON
header('Content-Type: application/json');

// Configuración de la conexión a la base de datos
$host = 'localhost';
$dbname = 'babyvitals'; // Nombre de la base de datos
$username = 'root'; // Usuario de la base de datos
$password = 'pinguino26'; // Contraseña de la base de datos

try {
    // Conectar a la base de datos utilizando PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verificar que se recibió un 'tutor_id' en los parámetros de la URL
    if (isset($_GET['tutor_id'])) {
        $tutorId = $_GET['tutor_id'];


        $sql = "
        SELECT 
            p.id, 
            p.nombre_completo, 
            p.edad, 
            p.enfermedad, 
            p.sexo,
            COALESCE(COUNT(tr.id), 0) AS num_tratamientos  -- Si no tiene tratamientos, será 0
        FROM vista_pacientes p
        LEFT JOIN tratamientos tr ON p.id = tr.paciente_id 
        JOIN tutores t ON p.tutor_id = t.id 
        WHERE t.id = :tutor_id
        GROUP BY p.id 
        ORDER BY num_tratamientos DESC;  
    ";
    


        // Preparar la consulta
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':tutor_id', $tutorId, PDO::PARAM_INT); // Vincular el parámetro correctamente
        $stmt->execute();

        // Verificar si hay resultados
        $pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($pacientes) {
            // Si se encontraron pacientes, devolverlos como JSON
            echo json_encode($pacientes);
        } else {
            // Si no se encontraron pacientes, enviar un mensaje
            echo json_encode(['message' => 'No se encontraron pacientes para este tutor']);
        }
    } else {
        // Si no se pasó el 'tutor_id', enviar un error
        echo json_encode(['error' => 'Falta el ID del tutor']);
    }

} catch (PDOException $e) {
    // Si ocurre un error en la conexión o la consulta, devolverlo
    echo json_encode(['error' => 'Error en la conexión a la base de datos: ' . $e->getMessage()]);
}
?>
