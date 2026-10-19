<?php

namespace App\Services;

use PDO;

class ProductionDatabaseCheck
{
    public function check(string $password): void
    {
        // Destination is fixed; browser input cannot choose a database or host.
        $pdo=new PDO('mysql:host=localhost;port=3306;dbname=mitamurafarm_prod;charset=utf8mb4', 'mitamurafarm_prd', $password, [
            PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT=>10,
            PDO::ATTR_EMULATE_PREPARES=>false,
        ]);
        if ($pdo->query('SELECT DATABASE()')->fetchColumn() !== 'mitamurafarm_prod') {
            throw new \RuntimeException('Unexpected database.');
        }
        $pdo->query('SELECT 1')->fetchColumn();
    }
}
