# Пример размещения кнопки оплаты через Yandex Pay
*Представленный код указан в качестве примера, в нем могут отсутствовать необходимые проверки и валидация данных.*

## Описание
Пример демонстрирует возможность проведения оплаты через Yandex Pay с размещением кнопки оплаты на стороне магазина.

### Создание платежа
Для проведения платежа с помощью Yandex Pay сначала необходимо создать платеж в Платроне. Для этого необходимо отправить запрос на `init_payment.php` с указанием необходимых параметров [:link:](https://front.platron.ru/docs/api/initialize_payment/).
#### Обязательные параметры
Номер телефона плательщика является обязательным параметром для платежа в Платроне. Его необходимо передать в параметре `pg_user_phone` в запросе на создание платежа.
Email адрес плательщика не является обязательным параметром, его можно передать либо в параметре `pg_user_contact_email` в запросе на создание платежа, либо запросить из Yandex Pay (см. далее).  
Так как пользователь не попадает на сторону Платрона, то необходимо передать IP адрес пользователя в параметре `pg_user_ip`, IP адрес используется для проверки платежа на фрод.
#### Обработка ответа
Ответ на запрос создания платежа будет получен в виде XML, в котором присутствует элемент `pg_redirect_url` в котором указан url для редиректа клиента. Из этого url необходимо получить значение параметра `customer`, это значение понадобится далее. Например, был получен следующий url:
```
https://www.platron.ru/payment_params.php?customer=ccaa41a4f425d124a23c3a53a3140bdc15826
```
Значением параметра `customer` из этого url является следующая строка:
```
ccaa41a4f425d124a23c3a53a3140bdc15826
```

### Yandex Pay Web SDK
Для взаимодействия с Yandex Pay используется Yandex Pay Web SDK. Для совершения платежа необходимо создать платежную сессию и указать обработчик события `onProcess`.
> Браузеры блокируют кросс-доменные ajax запросы. Поэтому, в данном примере, ajax запрос отправляется на текущий сервер, а сервер отправляет данные в Платрон.

#### Создание платежной сессии
В метод `YaPay.createSession` необходимо передать данные платежа.
```javascript
const paymentData = {
    env: YaPay.PaymentEnv.Sandbox, // окружение для работы. Для разработки используется тестовое окружение SANDBOX. Для боевого режима надо использовать Production.
    version: 2, // версия Yandex Pay API
    countryCode: YaPay.CountryCode.Ru, // код страны магазина
    currencyCode: YaPay.CurrencyCode.Rub, //  код валюты платежа
    merchant: {
        id: yandexPayMerchantId, // идентификатор, полученный при регистрации в Yandex Pay.
        name: 'test-merchant-name', // имя магазина
        url: 'https://test-merchant-url.ru' // сайт магазина
    },
    order: {
        id: paymentId, // идентификатор заказа, должен быть равен идентификатору платежа в Платрон 
        total: {
            amount: amount, // Сумма платежа
            label: description // Описание платежа
        },
    },
    paymentMethods: [
        {
            type: YaPay.PaymentMethodType.Card,
            gateway: 'platron', // идентификатор платежного шлюза Платрон в Yandex Pay
            gatewayMerchantId: yandexPayGatewayMerchantId, // идентификатор магазина в Платрон
            allowedAuthMethods: [ // типы токенизации
                YaPay.AllowedAuthMethod.PanOnly,
                YaPay.AllowedAuthMethod.CloudToken
            ],
            allowedCardNetworks: [ // принимаемые типы карт
                YaPay.AllowedCardNetwork.Visa,
                YaPay.AllowedCardNetwork.Mastercard,
                YaPay.AllowedCardNetwork.Mir,
                YaPay.AllowedCardNetwork.Maestro,
                YaPay.AllowedCardNetwork.VisaElectron
            ]
        }
    ],
};
```
Номер телефона плательщика является обязательным параметром для платежа в Платрон. Если он не был указан при создании транзакции, то его надо запросить на вашей форме оплаты и передать в параметре `'phone'` вместе с платежными данными. 
Email адрес плательщика не является обязательным, но, если настроена отправка чеков через Платрон и в ОФД не настроена отправка чеков по СМС, плательщик не получит чек.
Его можно запросить в Yandex Pay, если в paymentData добавить параметры:
```javascript
requiredFields: {
    billingContact: { email: true }
}
```

#### Обработка события `onProcess`
В объекте события присутствует свойства:
* `token` - платежный токен необходимый для совершения платежа
* `paymentMethodInfo` - информация о методе оплаты
* `billingContact` - объект содержащий email клиента, если был использован `requiredFields` при создании платежной сессии

Этот объект необходимо отправить в Платрон в виде JSON строки для проведения платежа. Данное значение необходимо отправить в теле POST запроса по следующему адресу:
```
https://platron.ru/index.php/web/yandex-pay/process-payment?customer=<customer>&psName=<psName>
``` 
где параметр `customer` - это значение полученное из ответа на создание платежа, а значение параметра `psName` - это название платежной системы Yandex Pay в Платроне.
В теле запроса необходимо передать следующие данные:
```
paymentDataJson=<payment_as_json>
```
где `<payment_as_json>` - это объект события преобразованный в JSON.

В ответ на запрос будет возвращен объект содержащий:
* `status` - с возможными значениями:
1. `ok` - оплата прошла успешно
1. `fail` - оплата не прошла, транзакция в Платрон завершена. Для повторной оплаты необходимо создать новую транзакцию в Платрон.
Так же в ответе будет содержаться сообщение в параметре `message`.
1. `redirect` - необходимо перекинуть клиента на сторону Платрона для дальнейшего прохождения 3дс авторизации.
Адрес для перекидывания будет указан в параметре `url`.
1. `retry` - ошибка обработки платежного токена, можно попробовать получить новый платежный токен и повторить попытку оплаты


## Инструкция по запуску
*Для запуска примера необходим сервер с PHP*
1. Разместить код примера на сервере так, чтобы `DOCUMENT_ROOT` указывал на папку `public`
1. Переименовать файл `classes/Settings.php.sample` в `classes/Settings.php`
1. Указать в файле `classes/Settings.php` данные магазина и используемую платежную систему
1. Выполнить команду `composer install` в папке с примером


## Ссылки
* Yandex Pay Web SDK - https://pay.yandex.ru/ru/docs/psp/web-sdk/
* Правила оформления бренда - https://pay.yandex.ru/ru/docs/branding
