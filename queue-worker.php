<?php
// public/queue-worker.php
ignore_user_abort(true);
set_time_limit(60);

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->call('queue:work', [
    '--once' => true,
    '--stop-when-empty' => true
]);