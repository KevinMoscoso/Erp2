<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Erpia2\Core\App;

$app = App::bootstrap();
$app->run();