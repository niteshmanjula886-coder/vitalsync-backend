<?php
// VitalSync Root Login Proxy
if (file_exists(__DIR__ . '/auth/login.php')) {
    require_once __DIR__ . '/auth/login.php';
} elseif (file_exists(__DIR__ . '/db_connect.php')) {
    require_once __DIR__ . '/db_connect.php';
} elseif (file_exists(__DIR__ . '/config/db.php')) {
    require_once __DIR__ . '/config/db.php';
}
