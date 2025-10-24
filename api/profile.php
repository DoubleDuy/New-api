<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

require '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$servername = $_ENV['DB_HOST'];
$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASS'];
$dbname = $_ENV['DB_NAME'];

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  die(json_encode(["error" => "Database connection failed."]));
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
  $data = json_decode(file_get_contents("php://input"), true);

  // ตรวจว่ามี record id=1 หรือยัง
  $check = $conn->query("SELECT id FROM users WHERE id=1");

  if ($check->num_rows > 0) {
    // ถ้ามีแล้ว → อัปเดต
    $sql = "UPDATE users SET 
      prefix='{$data['prefix']}',
      first_name='{$data['first_name']}',
      last_name='{$data['last_name']}',
      position='{$data['position']}',
      start_date='{$data['start_date']}',
      work_start='{$data['work_start']}',
      license_no='{$data['license_no']}',
      member_no='{$data['member_no']}',
      email='{$data['email']}',
      phone='{$data['phone']}'
      WHERE id=1";
  } else {
    // ถ้ายังไม่มี → เพิ่มใหม่
    $sql = "INSERT INTO users 
      (prefix, first_name, last_name, position, start_date, work_start, license_no, member_no, email, phone)
      VALUES (
        '{$data['prefix']}',
        '{$data['first_name']}',
        '{$data['last_name']}',
        '{$data['position']}',
        '{$data['start_date']}',
        '{$data['work_start']}',
        '{$data['license_no']}',
        '{$data['member_no']}',
        '{$data['email']}',
        '{$data['phone']}'
      )";
  }

  if ($conn->query($sql)) {
    echo json_encode(["message" => "✅ Data saved successfully"]);
  } else {
    echo json_encode(["error" => $conn->error, "sql" => $sql]);
  }
}

$conn->close();
?>