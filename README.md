# API Helper

Классы для работы с API различных сервисов.

~~~
$fbClient = new \ApiHelper\Client\FacebookClient(['client_id' => '...', 'client_secret' => '...']);
$user = $fbClient->request('me');
~~~

