<?php

declare(strict_types=1);

namespace Feldspar\Traits;

use Respect\Validation\Exceptions\ValidationException;
use Respect\Validation\Validatable;

trait ValidateData
{
    /**
     * Validate data against rules
     *
     * @param array<string, string> $data
     * @param array<string, Validatable> $rules
     * @return array<string, string>
     */
    protected function validateData(array $data, array $rules): array
    {
        $errors = [];
        foreach ($rules as $field => $validator) {
            try {
                $validator->check($data[$field] ?? '');
            } catch (ValidationException $e) {
                $errors[$field] = $e->getMessage();
            }
        }
        return $errors;
    }
}
