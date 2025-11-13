<?php
function sendSMS($phone, $message) {
    $api_token = "ee6793e83deb764b28bd49eb781ebc267a8f2d30";

    if (substr($phone, 0, 1) === "0") {
        $phone = "63" . substr($phone, 1);
    }

    $url = "https://sms.iprogtech.com/api/v1/sms_messages";

    $data = [
        "api_token"    => $api_token,
        "phone_number" => $phone,
        "message"      => $message
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        return ["success" => false, "error" => $error_msg];
    }

    curl_close($ch);
    return ["success" => true, "response" => $response];
}
?>
