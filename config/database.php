<?php
return [
    'host' => 'fd42:7811:ed73:f4c:216:3eff:fe1e:29b9',
    'dbname' => 'cashflow_db',
    'username' => 'postgres',
    'password' => '',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];
