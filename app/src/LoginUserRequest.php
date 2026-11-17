<?php

declare(strict_types=1);

namespace App;

final class LoginUserRequest extends AbstractRequest
{
    protected function parameterValidators(): array
    {
        return [
            'email' => [new RequiredValidator(), new EmailValidator(), new StringLengthValidator(6, 254)],
            'password' => [new RequiredValidator(), new StringLengthValidator(8, 128)],
            '_csrf_token' => [new RequiredValidator()],
        ];
    }

    protected function validateLogic(array $payload): array
    {
        return $this->auth->hasValidCsrfToken($payload['_csrf_token'] ?? null)
            ? []
            : ['_csrf_token' => 'Форма устарела. Обновите страницу и повторите попытку.'];
    }

    protected function execute(array $payload): ResponseModel
    {
        if (!$this->auth->login((string) $payload['email'], (string) $payload['password'])) {
            return ResponseModel::failure('Неверный email или пароль.', [], 401);
        }
        return ResponseModel::success('Вы вошли в систему.', '/');
    }
}
