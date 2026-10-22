<?php
// CLI only: inspect a release without changing configuration or sending mail.
if (PHP_SAPI !== 'cli') { exit(1); }
require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!app()->environment('production') || !config('farm.inquiries_enabled')
    || config('farm.preview_require_login') || config('farm.verification_mode')
    || config('mail.default') !== 'smtp' || !config('seo.indexable')
    || config('farm.restrict_mail_recipients')
    || config('database.default') !== 'mysql'
    && config('database.default') !== 'mariadb') {
    throw new RuntimeException('Production configuration is not suitable for routine publishing.');
}
$connection = Illuminate\Support\Facades\DB::connection();
if ($connection->getDatabaseName() !== 'mitamurafarm_prod') {
    throw new RuntimeException('Unexpected production database.');
}
$completed = $connection->table('migrations')->pluck('migration')->all();
foreach (glob(getcwd().'/database/migrations/*.php') as $file) {
    if (!in_array(basename($file, '.php'), $completed, true)) {
        throw new RuntimeException('A database migration is pending. Routine publishing stopped.');
    }
}
// Only compare non-secret settings; never emit APP_KEY, credentials or customer data.
echo json_encode([
    'url' => config('app.url'),
    'analytics' => config('analytics.enabled'),
    'measurement_id' => config('analytics.measurement_id'),
    'mail' => config('mail.default'),
    'indexable' => config('seo.indexable'),
], JSON_THROW_ON_ERROR).PHP_EOL;
