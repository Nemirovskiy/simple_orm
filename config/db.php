<?php

declare(strict_types=1);

return [
    // Обязательный параметр
    'dbname'   => 'table_name',

    // Учётные данные
    'user'     => 'root',
    'password' => '',

    'options'  => [
        // Необязательные параметры подключения (есть значения по умолчанию)
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'charset' => 'utf8mb4',

        // Дополнительные PDO-настройки (опционально)
        // PDO::ATTR_TIMEOUT => 5,
    ],
];

