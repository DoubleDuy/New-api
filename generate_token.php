<?php
require 'vendor/autoload.php';

use Google\Client;

// === ตั้งค่า ===
$client = new Client();
$client->setAuthConfig('credentials.json');
$client->setAccessType('offline'); // ขอ refresh token ด้วย
$client->setPrompt('select_account consent');
$client->addScope(Google\Service\Drive::DRIVE_READONLY);

// === ตรวจว่าเคยมี token แล้วหรือยัง ===
$tokenPath = 'token.json';
if (file_exists($tokenPath)) {
    echo "✅ Token file already exists: $tokenPath\n";
    $accessToken = json_decode(file_get_contents($tokenPath), true);
    $client->setAccessToken($accessToken);
    if ($client->isAccessTokenExpired()) {
        echo "🔄 Token expired, refreshing...\n";
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
            echo "✅ Token refreshed and saved.\n";
        } else {
            echo "⚠️ No refresh token, please delete token.json and run again.\n";
        }
    } else {
        echo "✅ Token is still valid.\n";
    }
    exit;
}

// === ถ้ายังไม่มี token.json ให้สร้างใหม่ ===
$authUrl = $client->createAuthUrl();
echo "🔗 เปิดลิงก์นี้ในเบราว์เซอร์:\n$authUrl\n\n";
echo "👉 จากนั้นคัดลอก 'authorization code' ที่ได้มากรอกที่นี่:\n";
echo "Code: ";
$authCode = trim(fgets(STDIN));

// ขอ access token จาก Google
$accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

// ตรวจสอบ error
if (array_key_exists('error', $accessToken)) {
    echo "❌ Error fetching token: " . $accessToken['error'] . "\n";
    exit;
}

// บันทึก token.json
if (!file_exists(dirname($tokenPath))) {
    mkdir(dirname($tokenPath), 0700, true);
}
file_put_contents($tokenPath, json_encode($accessToken));
echo "✅ Token saved to $tokenPath\n";
?>