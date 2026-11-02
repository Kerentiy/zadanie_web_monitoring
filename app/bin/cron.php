<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\CleanupSessionsTask;
use App\CronRunner;

exit((new CronRunner(new CleanupSessionsTask()))->run());
