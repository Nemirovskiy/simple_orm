<?php

declare(strict_types=1);

use SimpleOrm\Db\Connection;
use SimpleOrm\Repository\UserRepository;

require __DIR__ . '/../vendor/autoload.php';

$dbConfig = require __DIR__ . '/../config/db.php';

$connection = new Connection(
    $dbConfig['dbname'] ?? '',
    $dbConfig['user'],
    $dbConfig['password'],
    $dbConfig['options'] ?? []
);

$userRepository = new UserRepository($connection);

// Однократная установка структуры таблицы (создаёт таблицу, если её нет)
// Раскомментируйте строку ниже при первом запуске:
// $userRepository->install();

// Пример: получить список активных пользователей старше 18 лет
$users = $userRepository
    ->createQuery()
    ->select(['ID', 'NAME', 'EMAIL'])
    ->filter([
        'ACTIVE' => 'Y',
        '>=AGE' => 18,
    ])
    ->order(['ID' => 'DESC'])
    ->limit(20, 0)
    ->fetchAll();

echo "Активные пользователи старше 18:\n";
foreach ($users as $user) {
    echo sprintf(
        "[%s] %s <%s>\n",
        $user['ID'] ?? '',
        $user['NAME'] ?? '',
        $user['EMAIL'] ?? ''
    );
}

// Пример: добавление пользователя
/*
$newUserId = $userRepository->add([
    'NAME'   => 'Ivan',
    'EMAIL'  => 'ivan@example.com',
    'ACTIVE' => 'Y',
    'AGE'    => 25,
]);

echo \"\\nДобавлен пользователь с ID: {$newUserId}\\n\";
*/

