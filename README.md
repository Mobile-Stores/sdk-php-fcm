# Biblioteca Api Firebase Cloud Messaging
Biblioteca PHP v7.2 para manipulação da API do FCM

# Instalar
```console
composer require mobilestores/sdk-php-fcm
```

# Como utilizar

```php
<?php

include "vendor/autoload.php";

use Firebase\CloudMessaging;

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('America/Sao_Paulo');

$apiFcm = new CloudMessaging();

$apiFcm->setProjectName("")
    ->setConfigJson("");

$payload = [
    "{key}" => "{value}"//string
];

$ttl = null;

//or

$ttl = "Y-m-d H:i:s";

$apiFcm->sendToTopic("{topic name}", "{title}", "{body}", $payload, $ttl);

//---- or -----

$firebaseIds = [
    "FIREBASE DEVICE ID",
    "FIREBASE DEVICE ID"
    //...
];

$apiFcm->sendToTopic($firebaseIds, "{title}", "{body}", $payload, $ttl);

//---- or -----

$apiFcm
    ->to("{topic name}")
    ->title("{title}")
    ->body("{body}")
    //->payload($payload) optional
    //->ttl($ttl) optional
    ->send();

//---- or -----

$apiFcm
    ->to($firebaseIds)
    ->title("{title}")
    ->body("{body}")
    //->payload($payload) optional
    //->ttl($ttl) optional
    ->send();

//---- or -----

$apiFcm
    ->condition("'stock-GOOG' in topics || 'industry-tech' in topics")
    ->title("{title}")
    ->body("{body}")
    //->payload($payload) optional
    //->ttl($ttl) optional
    ->send();

```
