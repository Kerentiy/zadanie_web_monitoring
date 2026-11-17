<?php

declare(strict_types=1);

namespace App;

interface ParameterValidator
{
    /** Return an error message, or null when the value is valid. */
    public function validate(string $name, mixed $value): ?string;
}
