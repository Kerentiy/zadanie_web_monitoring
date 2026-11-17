<?php

declare(strict_types=1);

namespace App;

final class EmailValidator implements ParameterValidator
{
    public function validate(string $name, mixed $value): ?string
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false
            ? null
            : 'Введите корректный адрес электронной почты.';
    }
}
