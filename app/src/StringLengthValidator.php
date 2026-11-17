<?php

declare(strict_types=1);

namespace App;

final class StringLengthValidator implements ParameterValidator
{
    public function __construct(
        private readonly int $minimum,
        private readonly int $maximum,
    ) {
        if ($minimum < 0 || $maximum < $minimum) {
            throw new \InvalidArgumentException('Invalid string length bounds.');
        }
    }

    public function validate(string $name, mixed $value): ?string
    {
        if (!is_string($value)) {
            return 'Значение должно быть строкой.';
        }

        // PCRE counts UTF-8 code points and avoids requiring the mbstring extension.
        $length = preg_match_all('/./us', $value);
        if ($length === false) {
            return 'Поле содержит некорректный UTF-8.';
        }
        if ($length < $this->minimum || $length > $this->maximum) {
            return sprintf('Длина поля должна быть от %d до %d символов.', $this->minimum, $this->maximum);
        }
        return null;
    }
}
