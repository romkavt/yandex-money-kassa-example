# Пример реализации протоколов  Яндекс.Кассы

Этот репозиторий содержит набор реализованных методов протоколов Кассы на PHP. В их число входит:

1. Обработку checkOrder- и paymentAviso-уведомлений по схеме XML/PKCS#7. 
2. Реализацию методов управления заказами:
   1. Информационные запросы: listOrders, listReturns.
   2. Финансовые операции: returnPayment, confirmPayment, cancelPayment, repeatCardPayment.

По техническим вопросам и вопросам подключения пишите нам [merchants@money.yandex.ru](mailto:merchants@money.yandex.ru).

Информацию о найденных ошибках в исходном коде, а также другие замечания и дополнения вы можете оставлять в [Issues](https://github.com/yandex-money/yandex-money-kassa-example/issues).

# Запуск

Приложение можно запустить через Apache, Nginx, либо воспользоваться [Docker](https://docs.docker.com/) и утилитой [Docker Compose](https://docs.docker.com/compose/).

Для этого в папке `docker` следует выполнить `./start.sh`(или `docker-compose up`).

После этого скопируйте `localhost:8080/paymentForm.html` в адресную строку браузера. 

Если вы используете [Boot2Docker](http://boot2docker.io/), то IP-адрес можно узнать с помощью `boot2docker ip`.