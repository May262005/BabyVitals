<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Database configuration
$host = 'localhost';
$db   = 'babyvitals';
$user = 'root';
$pass = 'pinguino26';
$charset = 'utf8mb4';

// Connection options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Create a PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, $options);
    
    // Check if patient_id is provided
    if (!isset($_GET['paciente_id'])) {
        throw new Exception("Paciente ID no proporcionado");
    }
    
    $paciente_id = $_GET['paciente_id'];
    
    // Prepare and execute queries for each vital sign
    $queries = [
        'ritmoCardiaco' => "SELECT * FROM signos_vitales_ritmo_cardiaco WHERE paciente_id = ? ORDER BY hora",
        'oxigenacion' => "SELECT * FROM signos_vitales_oxigenacion WHERE paciente_id = ? ORDER BY hora",
        'temperatura' => "SELECT * FROM signos_vitales_temperatura WHERE paciente_id = ? ORDER BY hora"
    ];
    
    $vitalSigns = [];
    
    foreach ($queries as $key => $query) {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$paciente_id]);
        $vitalSigns[$key] = $stmt->fetchAll();
    }
    
    // Return JSON response
    echo json_encode($vitalSigns);
    
} catch (PDOException $e) {
    // Handle database connection errors
    http_response_code(500);
    echo json_encode([
        "message" => "Error de conexión a la base de datos: " . $e->getMessage(),
        "error" => true
    ]);
} catch (Exception $e) {
    // Handle other errors
    http_response_code(400);
    echo json_encode([
        "message" => $e->getMessage(),
        "error" => true
    ]);
}
?>