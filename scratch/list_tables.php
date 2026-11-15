<?php
require_once dirname(__DIR__) . '/config/db.php';
echo "DB Connected\n";
$res = $conn->query("SHOW TABLES");
while ($r = $res->fetch_array()) {
    echo $r[0] . "\n";
}
