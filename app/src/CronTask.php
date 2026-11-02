<?php

declare(strict_types=1);

namespace App;

interface CronTask
{
    public function name(): string;
    public function run(): void;
}
