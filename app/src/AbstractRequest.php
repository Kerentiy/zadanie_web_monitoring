<?php

declare(strict_types=1);

namespace App;

/**
 * Base class for use-case requests: validates parameters, then cross-field/business
 * rules, and only then executes the useful payload.
 */
abstract class AbstractRequest
{
    public function __construct(
        protected readonly Request $request,
        protected readonly AuthService $auth,
    ) {}

    /** @return array<string, list<ParameterValidator>> */
    abstract protected function parameterValidators(): array;

    /** @param array<string, mixed> $payload @return array<string, string> field => error */
    protected function validateLogic(array $payload): array
    {
        return [];
    }

    /** @param array<string, mixed> $payload */
    abstract protected function execute(array $payload): ResponseModel;

    public function run(): ResponseModel
    {
        if ($this->request->method !== 'POST') {
            return ResponseModel::failure('Для этого действия требуется POST.', [], 405);
        }

        $payload = $this->request->post;
        $errors = [];
        foreach ($this->parameterValidators() as $name => $validators) {
            $value = $payload[$name] ?? null;
            foreach ($validators as $validator) {
                $error = $validator->validate($name, $value);
                if ($error !== null) {
                    $errors[$name] = $error;
                    break;
                }
            }
        }

        foreach ($this->validateLogic($payload) as $name => $error) {
            $errors[$name] ??= $error;
        }

        if ($errors !== []) {
            return ResponseModel::failure('Проверьте введённые данные.', $errors, 422);
        }

        return $this->execute($payload);
    }
}
