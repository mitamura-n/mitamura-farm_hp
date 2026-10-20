<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class ProductionAdminSetup
{
    public function run(string $operation, array $data = []): array
    {
        if (!in_array($operation, ['status', 'create'], true)) throw new \RuntimeException('Invalid operation.');
        // Do not inherit the preview APP_KEY, environment or database settings.
        $process = new Process(['/usr/bin/env', '-i', 'PATH=/usr/bin:/bin', '/usr/bin/php8.4',
            base_path('scripts/setup-production-admin.php'), $operation]);
        $process->setInput(json_encode($data, JSON_THROW_ON_ERROR));
        $process->setTimeout(30);
        $process->run();
        if (!$process->isSuccessful()) throw new \RuntimeException('Production admin preparation failed.');
        $result = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($result) || !isset($result['configured'])) throw new \RuntimeException('Invalid preparation response.');
        return $result;
    }
}
