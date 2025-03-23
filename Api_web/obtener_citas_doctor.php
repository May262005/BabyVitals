<?php
// obtener_citas_doctor.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");

// Clase de conexión embebida
class Conexion {
    private $host = "localhost";
    private $db_name = "babyvitals"; // Asumiendo este nombre de la base de datos
    private $username = "root";
    private $password = "pinguino26";
    public $conn;
    
    // Constructor para obtener la conexión a la base de datos
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}

// Obtener el ID del doctor de la solicitud
$doctor_id = isset($_GET['doctor_id']) ? $_GET['doctor_id'] : '';

if (empty($doctor_id)) {
    // Si no se proporciona ID de doctor
    echo json_encode(
        array("success" => false, "message" => "Falta el ID del doctor")
    );
    exit;
}

try {
    $database = new Conexion();
    $db = $database->getConnection();


$query = "SELECT id, doctor_id, paciente_id, fecha, hora_inicio, hora_fin, estado,
       (SELECT nombre_completo FROM pacientes p WHERE p.id = c.paciente_id) AS nombre_paciente
            FROM citas c
            WHERE c.doctor_id = :doctor_id;
            ";
    
$stmt = $db->prepare($query);
$stmt->bindParam(":doctor_id", $doctor_id);
$stmt->execute();
    
    $num = $stmt->rowCount();
    
    if ($num > 0) {
        // Si hay citas
        $citas_arr = array();
        $citas_arr["success"] = true;
        $citas_arr["citas"] = array();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            extract($row);
                    
            $cita_item = array(
                "id" => $id,
                "doctor_id" => $doctor_id,
                "paciente_id" => $paciente_id,
                "nombre_paciente" => $nombre_paciente,
                "fecha_cita" => $fecha,
                "hora_inicio" => $hora_inicio,
                "hora_fin" => $hora_fin,
                "estado" => $estado
            );
                    
            array_push($citas_arr["citas"], $cita_item);
        }
        
        echo json_encode($citas_arr);
    } else {
        // Si no hay citas
        echo json_encode(
            array("success" => true, "message" => "No se encontraron citas", "citas" => array())
        );
    }
} catch (Exception $e) {
    echo json_encode(
        array("success" => false, "message" => "Error: " . $e->getMessage())
    );
}
?>