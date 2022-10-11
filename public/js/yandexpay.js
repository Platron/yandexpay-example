function initYandexPay(customer, paymentSystemName, paymentData) {
    const processPaymentUrl = '/ajax/process_payment.php?customer=' + customer + '&psName=' + paymentSystemName;
    const resultUrl = platronBaseUrl + '/result?customer=' + customer;

    // Обработчик на получение платежного токена
    function onPaymentProcess(event) {
        // если номер телефона не был передан при инициализации платежа, то его надо передать вместе с параметрами события
        // event.phone = getUserPhone();

        processPayment(event, processPaymentUrl)
            .then(function (response) {
                if (response['status'] === 'retry') {
                    console.log('processPayment: RETRY');
                    alert(response['message']);
                    return;
                }
                if (response['status'] === 'redirect') {
                    console.log('processPayment: REDIRECT');
                    window.location.href = response['url'];
                    return;
                }
                if (response['status'] === 'ok') {
                    console.log('completePayment: SUCCESS');
                } else {
                    console.log('completePayment: FAILURE');
                }
                window.location.href = resultUrl;
            }, function (response) {
                console.log('completePayment: FAILURE');
                window.location.href = resultUrl;
            })
            .catch(function (e) {
                console.log('Error in processPayment', e)
            })
        return true;
    }

    // Обработчик на ошибки при оплате
    function onPaymentError(event) {
        // Вывести информацию о недоступности оплаты в данный момент
        // и предложить пользователю другой способ оплаты.
    }

    // Обработчик на отмену оплаты
    function onPaymentAbort(event) {
        // Пользователь закрыл форму Yandex Pay.
        // Предложить пользователю другой способ оплаты.
    }

    // Создать платежную сессию.
    YaPay.createSession(paymentData, {
        onProcess: onPaymentProcess,
        onAbort: onPaymentAbort,
        onError: onPaymentError,
    }).then(function (paymentSession) {
        // Показать кнопку Yandex.Pay на странице.
        paymentSession.mountButton(
            document.querySelector('#button_container'),
            {
                type: YaPay.ButtonType.Pay,
                theme: YaPay.ButtonTheme.Black,
                width: YaPay.ButtonWidth.Auto,
            }
        );
    }).catch(function (err) {
        // Не получилось создать платежную сессию.
    });
}