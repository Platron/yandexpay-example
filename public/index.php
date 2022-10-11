<?php
require_once '../vendor/autoload.php';

error_reporting(E_ALL);

$paymentAmount = '1.50';
$description = 'Test Yandex Pay payment';

$client = new Client();
$parameters = [
	'pg_amount' => $paymentAmount,
	'pg_description' => $description,
	'pg_user_phone' => '79009999999', // Номер телефона необходимо указывать при создании транзакции или передавать вместе с платежными данными Yandex Pay
	'pg_user_contact_email' => 'test@test.ru',
	'pg_user_ip' => '5.255.255.80', // IP необходим для прохождения фрод фильтров
	'pg_merchant_id' => Settings::MERCHANT_ID,
	'pg_secret_key' => Settings::MERCHANT_SECRET_KEY,
	'pg_salt' => 'random_salt',
];
$parameters['pg_sig'] = \platron\Signature::make('init_payment.php', $parameters, Settings::MERCHANT_SECRET_KEY);
$response = new SimpleXMLElement($client->get(Settings::PLATRON_BASE_URL . '/init_payment.php', $parameters));

$redirectUrl = (string)$response->pg_redirect_url;
$paymentId = (string)$response->pg_payment_id;

$matches = [];
preg_match('/customer=([^&]+)/', $redirectUrl, $matches);
$customer = $matches[1];

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Yandex Pay example</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <script src="https://pay.yandex.ru/sdk/v1/pay.js"></script>
    <script src="js/support.js"></script>
    <script src="js/yandexpay.js"></script>
    <script type="text/javascript">
        const platronBaseUrl = '<?= Settings::PLATRON_YANDEX_URL ?>';

        const customer = '<?= $customer ?>';
        const paymentId = '<?= $paymentId ?>';
        const amount = '<?= $paymentAmount ?>';
        const description = '<?= $description ?>';

        const paymentSystemName = '<?= Settings::PAYMENT_SYSTEM_NAME ?>';
        const yandexPayMerchantId = '<?= Settings::YANDEXPAY_MERCHANT_ID ?>';
        const yandexPayGatewayMerchantId = '<?= Settings::MERCHANT_ID ?>';


        const YaPay = window.YaPay;

        // Сформировать данные платежа.
        const paymentData = {
            env: YaPay.PaymentEnv.Sandbox,
            version: 2,
            countryCode: YaPay.CountryCode.Ru,
            currencyCode: YaPay.CurrencyCode.Rub,
            merchant: {
                id: yandexPayMerchantId,
                name: 'test-merchant-name',
                url: 'https://test-merchant-url.ru'
            },
            order: {
                id: paymentId,
                total: {
                    amount: amount,
                    label: description
                },
            },
            paymentMethods: [{
                type: YaPay.PaymentMethodType.Card,
                gateway: 'platron',
                gatewayMerchantId: yandexPayGatewayMerchantId,
                allowedAuthMethods: [
                    YaPay.AllowedAuthMethod.PanOnly,
                    YaPay.AllowedAuthMethod.CloudToken
                ],
                allowedCardNetworks: [
                    YaPay.AllowedCardNetwork.Visa,
                    YaPay.AllowedCardNetwork.Mastercard,
                    YaPay.AllowedCardNetwork.Mir,
                    YaPay.AllowedCardNetwork.Maestro,
                    YaPay.AllowedCardNetwork.VisaElectron
                ]
            }],
        };

        initYandexPay(customer, paymentSystemName, paymentData)
    </script>
</head>
<body>
<div class="yandex-pay">
    <h2>Оплатить с помощью Yandex&nbsp;Pay</h2>
    <p>
        Это простой пример сайта демонстрирующий оплату с помощью Yandex&nbsp;Pay.
    </p>
    <p>
        Поддерживающие Yandex&nbsp;Pay браузеры отобразят кнопку ниже.
    </p>
    <div id="button_container" class="yandex-pay-button"></div>
</div>
</body>
</html>
