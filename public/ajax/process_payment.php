<?php
require_once '../../vendor/autoload.php';

$processPaymentPath = Settings::PLATRON_YANDEX_URL . '/process-payment';

$client = new Client();

$response = $client->post($processPaymentPath . '?' . http_build_query($_GET), $_POST);

header('Content-type:application/json;charset=utf-8');
echo $response;
