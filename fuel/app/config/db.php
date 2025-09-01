<?php
return [
  'default' => [
    'type'       => 'pdo',
    'connection' => [
      'dsn'      => 'mysql:host=db;port=3306;dbname=recipe_app;charset=utf8mb4',
      'username' => 'root',
      'password' => 'root',
    ],
    'charset'   => 'utf8mb4',
    'profiling' => true,
  ],
];
