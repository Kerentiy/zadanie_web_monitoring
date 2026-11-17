<?php

declare(strict_types=1);

namespace App;

final class LogoutUserRequest extends AbstractRequest
{
    protected function parameterValidators(): array
    {
        return ['_csrf_token' => [new RequiredValidator()]];
    }

    protected function validateLogic(array $payload): array
    {
        return $this->auth->hasValidCsrfToken($payload['_csrf_token'] ?? null)
            ? []
            : ['_csrf_token' => 'Форма устарела. Обновите страницу и повторите попытку.'];
    }

    protected function execute(array $payload): ResponseModel
    {
        $this->auth->logout();
        return ResponseModel::success('Вы вышли из системы.', '/');
    }
}
