<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

require '../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use Google\Client;
use Google\Service\Drive;

try {
    // === 1️⃣ สร้าง Client ===
    $client = new Client();
    $client->setAuthConfig('../credentials.json');
    $client->addScope(Drive::DRIVE_READONLY);
    $client->setAccessType('offline'); // ขอ refresh token ด้วย
    $client->setPrompt('select_account consent');

    // === 2️⃣ โหลด token.json ===
    $tokenPath = '../token.json';
    if (!file_exists($tokenPath)) {
        throw new Exception("❌ ไม่พบไฟล์ token.json — โปรดรัน generate_token.php ก่อน");
    }

    $accessToken = json_decode(file_get_contents($tokenPath), true);
    $client->setAccessToken($accessToken);

    // === 3️⃣ ตรวจว่าหมดอายุหรือยัง ===
    if ($client->isAccessTokenExpired()) {
        // ถ้ามี refresh token → ขอ access token ใหม่อัตโนมัติ
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            // บันทึก token ใหม่ทับเดิม
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        } else {
            throw new Exception("⚠️ Token หมดอายุและไม่มี refresh token — โปรดสร้างใหม่ด้วย generate_token.php");
        }
    }

    // === 4️⃣ ใช้ Drive API ===
    $service = new Drive($client);

    // 📁 ใส่ Folder ID ที่เก็บวุฒิการศึกษา
    $folderId = '1TN6G-_B1zD21rWBWGY_i6wsD8a7PECkd';

    // ดึงรายชื่อไฟล์ในโฟลเดอร์
    $response = $service->files->listFiles([
        'q' => "'$folderId' in parents and trashed = false",
        'fields' => 'files(id, name, webViewLink)',
        'pageSize' => 50
    ]);

    $files = [];
    foreach ($response->getFiles() as $file) {
        $files[] = [
            'id' => $file->getId(),
            'name' => $file->getName(),
            'webViewLink' => $file->getWebViewLink()
        ];
    }

    echo json_encode($files, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>