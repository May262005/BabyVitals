<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$servername = "localhost";
$username = "root";
$password = "pinguino26";
$dbname = "babyvitals";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["message" => "Error de conexión a la base de datos"]));
}

$data = json_decode(file_get_contents("php://input"), true);

// Agregar un log para ver los datos recibidos
error_log(print_r($data, true));

if (isset($data["correo"], $data["contrasena"])) {
    $correo = trim($data["correo"]);
    $contrasena = $data["contrasena"];

    // Validar si los campos están vacíos
    if (empty($correo) || empty($contrasena)) {
        echo json_encode(["message" => "Correo y contraseña son requeridos"]);
        exit();
    }

    // Validar las credenciales (email y contraseña)
    $sql = "SELECT * FROM doctores WHERE correo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $doctor = $result->fetch_assoc();
        if (password_verify($contrasena, $doctor["contrasena"])) {
            echo json_encode(["message" => "Inicio de sesión exitoso", "doctor" => $doctor]);
        } else {
            echo json_encode(["message" => "Contraseña incorrecta"]);
        }
    } else {
        echo json_encode(["message" => "Usuario no encontrado"]);
    }

    $stmt->close();
} elseif (isset($data["facebook_token"])) {
    // Verificar el token de Facebook
    $facebook_token = $data["facebook_token"];
    $url = 'https://graph.facebook.com/me?access_token=' . $facebook_token . '&fields=id,name,email,picture';

    // Usar cURL en vez de file_get_contents para mayor control
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $facebook_response = curl_exec($ch);
    curl_close($ch);

    $facebook_user = json_decode($facebook_response, true);

    if (isset($facebook_user["email"])) {
        // Buscar si el usuario existe en la base de datos
        $email = $facebook_user["email"];
        $sql = "SELECT * FROM doctores WHERE correo = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $doctor = $result->fetch_assoc();
            echo json_encode(["message" => "Inicio de sesión exitoso con Facebook", "doctor" => $doctor]);
        } else {
            echo json_encode(["message" => "Usuario no encontrado con Facebook"]);
        }

        $stmt->close();
    } else {
        echo json_encode(["message" => "Token de Facebook inválido"]);
    }
} else {
    echo json_encode(["message" => "Datos incompletos"]);
}

$conn->close();
?>
