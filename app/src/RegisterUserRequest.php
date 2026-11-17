<?php

declare(strict_types=1);

namespace App;

final class RegisterUserRequest extends AbstractRequest
{
    protected function parameterValidators(): array
    {
        return [
            'email' => [new RequiredValidator(), new EmailValidator(), new StringLengthValidator(6, 254)],
            'password' => [new RequiredValidator(), new StringLengthValidator(8, 128)],
            'password_confirmation' => [new RequiredValidator(), new StringLengthValidator(8, 128)],
            '_csrf_token' => [new RequiredValidator()],
        ];
    }

    protected function validateLogic(array $payload): array
    {
        $errors = [];
        if (($payload['password'] ?? null) !== ($payload['password_confirmation'] ?? null)) {
            $errors['password_confirmation'] = 'Пароли не совпадают.';
        }
        if (!$this->auth->hasValidCsrfToken($payload['_csrf_token'] ?? null)) {
            $errors['_csrf_token'] = 'Форма устарела. Обновите страницу и повторите попытку.';
        }
        return $errors;
    }

    protected function execute(array $payload): ResponseModel
    {
        if (!$this->auth->register((string) $payload['email'], (string) $payload['password'])) {
            return ResponseModel::failure('Пользователь с таким email уже зарегистрирован.', ['email' => 'Этот email уже занят.'], 409);
        }
        return ResponseModel::success('Регистрация прошла успешно.', '/');
    }
}
