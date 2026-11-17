<?php

declare(strict_types=1);

namespace App;

final class RequiredValidator implements ParameterValidator
{
    public function validate(string $name, mixed $value): ?string
    {
        return is_string($value) && trim($value) !== ''
            ? null
            : 'Поле обязательно для заполнения.';
    }
}
