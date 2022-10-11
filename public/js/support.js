/**
 * Заглушка для получения номера телефона пользователя
 * @returns {string}
 */
function getUserPhone() {
    return '79009999999';
}

/**
 * Обработка платежа
 * @param {*} payment
 * @param {*} processPaymentUrl
 */
function processPayment(payment, processPaymentUrl) {
    return new Promise(function (resolve, reject) {
        var xhr = new XMLHttpRequest();
        xhr.onload = function () {
            if (this.status >= 200 && this.status < 300) {
                resolve(JSON.parse(xhr.response));
            } else {
                reject({
                    status: this.status,
                    statusText: xhr.statusText
                });
            }
        };
        xhr.onerror = function () {
            reject({
                status: this.status,
                statusText: xhr.statusText
            });
        };
        xhr.open('POST', processPaymentUrl);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.send('paymentDataJson=' + encodeURIComponent(JSON.stringify(payment)));
    });
}
