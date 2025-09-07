<?php declare(strict_types=1);

namespace App\Core;

/**
 * Validation result container with error handling
 */
class ValidationResult
{
    private array $errors = [];
    private array $validatedData = [];

    public function __construct(array $errors = [], array $validatedData = [])
    {
        $this->errors = $errors;
        $this->validatedData = $validatedData;
    }

    /**
     * Check if validation passed (no errors)
     */
    public function isValid(): bool
    {
        return empty($this->errors);
    }

    /**
     * Check if validation failed (has errors)
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get all validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get errors for a specific field
     */
    public function getFieldErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * Get the first error for a field
     */
    public function getFirstFieldError(string $field): ?string
    {
        $fieldErrors = $this->getFieldErrors($field);
        return !empty($fieldErrors) ? $fieldErrors[0] : null;
    }

    /**
     * Get all error messages as a flat array
     */
    public function getAllErrorMessages(): array
    {
        $messages = [];
        foreach ($this->errors as $field => $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $messages[] = $error;
            }
        }
        return $messages;
    }

    /**
     * Get the first error message from any field
     */
    public function getFirstErrorMessage(): ?string
    {
        $allMessages = $this->getAllErrorMessages();
        return !empty($allMessages) ? $allMessages[0] : null;
    }

    /**
     * Get validated and cleaned data
     */
    public function getValidatedData(): array
    {
        return $this->validatedData;
    }

    /**
     * Get a specific validated field value
     */
    public function getValidatedField(string $field, $default = null)
    {
        return $this->validatedData[$field] ?? $default;
    }

    /**
     * Add an error for a specific field
     */
    public function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    /**
     * Set validated data for a field
     */
    public function setValidatedField(string $field, $value): void
    {
        $this->validatedData[$field] = $value;
    }

    /**
     * Convert to array format for easy debugging
     */
    public function toArray(): array
    {
        return [
            'isValid' => $this->isValid(),
            'errors' => $this->errors,
            'validatedData' => $this->validatedData
        ];
    }
}