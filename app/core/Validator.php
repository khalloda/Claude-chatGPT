<?php declare(strict_types=1);

namespace App\Core;

/**
 * Comprehensive input validation framework
 * Provides centralized, consistent validation across the application
 */
class Validator
{
    private array $data = [];
    private array $rules = [];
    private array $customMessages = [];
    private array $customRules = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Create a new validator instance
     */
    public static function make(array $data, array $rules, array $customMessages = []): self
    {
        $validator = new self($data);
        $validator->rules = $rules;
        $validator->customMessages = $customMessages;
        return $validator;
    }

    /**
     * Validate data against rules and return ValidationResult
     */
    public function validate(): ValidationResult
    {
        $errors = [];
        $validatedData = [];

        foreach ($this->rules as $field => $fieldRules) {
            $value = $this->data[$field] ?? null;
            $fieldErrors = $this->validateField($field, $value, $fieldRules);
            
            if (!empty($fieldErrors)) {
                $errors[$field] = $fieldErrors;
            } else {
                // Store cleaned/validated data
                $validatedData[$field] = $this->getCleanedValue($field, $value, $fieldRules);
            }
        }

        return new ValidationResult($errors, $validatedData);
    }

    /**
     * Quick validation method - returns true if valid, false if not
     */
    public function passes(): bool
    {
        return $this->validate()->isValid();
    }

    /**
     * Quick validation method - returns true if invalid, false if valid
     */
    public function fails(): bool
    {
        return $this->validate()->hasErrors();
    }

    /**
     * Validate a single field against its rules
     */
    private function validateField(string $field, $value, array $rules): array
    {
        $errors = [];

        foreach ($rules as $rule) {
            $result = $this->applyRule($field, $value, $rule);
            if ($result !== true) {
                $errors[] = $result;
                // Stop on first error for required fields
                if ($rule === 'required') {
                    break;
                }
            }
        }

        return $errors;
    }

    /**
     * Apply a single validation rule
     */
    private function applyRule(string $field, $value, string $rule): string|bool
    {
        // Handle parameterized rules (e.g., "min:3", "max:255")
        $ruleParameters = [];
        if (str_contains($rule, ':')) {
            [$ruleName, $parameterString] = explode(':', $rule, 2);
            $ruleParameters = explode(',', $parameterString);
        } else {
            $ruleName = $rule;
        }

        // Check custom rules first
        if (isset($this->customRules[$ruleName])) {
            return $this->customRules[$ruleName]($field, $value, $ruleParameters, $this->data);
        }

        // Normalize numeric parameters for rules that expect numbers
        if (in_array($ruleName, ['min','max','between'], true)) {
            foreach ($ruleParameters as $i => $p) {
                if (is_string($p) && is_numeric($p)) {
                    // cast to float; validate* methods accept int|float
                    $ruleParameters[$i] = $p + 0; // numeric cast
                }
            }
        }

        // Apply built-in rules
        return match ($ruleName) {
            'required' => $this->validateRequired($field, $value),
            'email' => $this->validateEmail($field, $value),
            'numeric' => $this->validateNumeric($field, $value),
            'integer' => $this->validateInteger($field, $value),
            'string' => $this->validateString($field, $value),
            'min' => $this->validateMin($field, $value, $ruleParameters[0] ?? 0),
            'max' => $this->validateMax($field, $value, $ruleParameters[0] ?? 0),
            'between' => $this->validateBetween($field, $value, $ruleParameters[0] ?? 0, $ruleParameters[1] ?? 0),
            'in' => $this->validateIn($field, $value, $ruleParameters),
            'not_in' => $this->validateNotIn($field, $value, $ruleParameters),
            'regex' => $this->validateRegex($field, $value, $ruleParameters[0] ?? ''),
            'confirmed' => $this->validateConfirmed($field, $value),
            'url' => $this->validateUrl($field, $value),
            'date' => $this->validateDate($field, $value),
            'boolean' => $this->validateBoolean($field, $value),
            'array' => $this->validateArray($field, $value),
            'nullable' => true, // Always passes, allows null values
            default => $this->getCustomMessage($field, $ruleName) ?: "Unknown validation rule: {$ruleName}"
        };
    }

    /**
     * Get cleaned/processed value after validation
     */
    private function getCleanedValue(string $field, $value, array $rules)
    {
        // Apply cleaning based on rules
        if (in_array('string', $rules) && is_string($value)) {
            return trim($value);
        }
        
        if (in_array('integer', $rules)) {
            return (int) $value;
        }
        
        if (in_array('numeric', $rules)) {
            return is_numeric($value) ? (float) $value : $value;
        }
        
        if (in_array('boolean', $rules)) {
            return (bool) $value;
        }

        // String trimming for most values
        if (is_string($value)) {
            return trim($value);
        }

        return $value;
    }

    // Built-in validation rules

    private function validateRequired(string $field, $value): string|bool
    {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            return $this->getCustomMessage($field, 'required') ?: "The {$field} field is required.";
        }
        return true;
    }

    private function validateEmail(string $field, $value): string|bool
    {
        if ($value === null || $value === '') {
            return true; // Use required rule to check for presence
        }
        
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $this->getCustomMessage($field, 'email') ?: "The {$field} field must be a valid email address.";
        }
        return true;
    }

    private function validateNumeric(string $field, $value): string|bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        
        if (!is_numeric($value)) {
            return $this->getCustomMessage($field, 'numeric') ?: "The {$field} field must be a number.";
        }
        return true;
    }

    private function validateInteger(string $field, $value): string|bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        
        if (!filter_var($value, FILTER_VALIDATE_INT)) {
            return $this->getCustomMessage($field, 'integer') ?: "The {$field} field must be an integer.";
        }
        return true;
    }

    private function validateString(string $field, $value): string|bool
    {
        if ($value === null) {
            return true;
        }
        
        if (!is_string($value)) {
            return $this->getCustomMessage($field, 'string') ?: "The {$field} field must be a string.";
        }
        return true;
    }

    private function validateMin(string $field, $value, int|float $min): string|bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (is_string($value)) {
            $length = mb_strlen($value);
            if ($length < $min) {
                return $this->getCustomMessage($field, 'min') ?: "The {$field} field must be at least {$min} characters.";
            }
        } elseif (is_numeric($value)) {
            if ((float) $value < $min) {
                return $this->getCustomMessage($field, 'min') ?: "The {$field} field must be at least {$min}.";
            }
        }

        return true;
    }

    private function validateMax(string $field, $value, int|float $max): string|bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (is_string($value)) {
            $length = mb_strlen($value);
            if ($length > $max) {
                return $this->getCustomMessage($field, 'max') ?: "The {$field} field may not be greater than {$max} characters.";
            }
        } elseif (is_numeric($value)) {
            if ((float) $value > $max) {
                return $this->getCustomMessage($field, 'max') ?: "The {$field} field may not be greater than {$max}.";
            }
        }

        return true;
    }

    private function validateBetween(string $field, $value, int|float $min, int|float $max): string|bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (is_string($value)) {
            $length = mb_strlen($value);
            if ($length < $min || $length > $max) {
                return $this->getCustomMessage($field, 'between') ?: "The {$field} field must be between {$min} and {$max} characters.";
            }
        } elseif (is_numeric($value)) {
            $numValue = (float) $value;
            if ($numValue < $min || $numValue > $max) {
                return $this->getCustomMessage($field, 'between') ?: "The {$field} field must be between {$min} and {$max}.";
            }
        }

        return true;
    }

    private function validateIn(string $field, $value, array $allowedValues): string|bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (!in_array($value, $allowedValues, true)) {
            $allowed = implode(', ', $allowedValues);
            return $this->getCustomMessage($field, 'in') ?: "The selected {$field} is invalid. Allowed values: {$allowed}.";
        }
        return true;
    }

    private function validateNotIn(string $field, $value, array $forbiddenValues): string|bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (in_array($value, $forbiddenValues, true)) {
            return $this->getCustomMessage($field, 'not_in') ?: "The selected {$field} is not allowed.";
        }
        return true;
    }

    private function validateRegex(string $field, $value, string $pattern): string|bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (!preg_match($pattern, (string) $value)) {
            return $this->getCustomMessage($field, 'regex') ?: "The {$field} field format is invalid.";
        }
        return true;
    }

    private function validateConfirmed(string $field, $value): string|bool
    {
        $confirmationField = $field . '_confirmation';
        $confirmationValue = $this->data[$confirmationField] ?? null;

        if ($value !== $confirmationValue) {
            return $this->getCustomMessage($field, 'confirmed') ?: "The {$field} confirmation does not match.";
        }
        return true;
    }

    private function validateUrl(string $field, $value): string|bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return $this->getCustomMessage($field, 'url') ?: "The {$field} field must be a valid URL.";
        }
        return true;
    }

    private function validateDate(string $field, $value): string|bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (!strtotime((string) $value)) {
            return $this->getCustomMessage($field, 'date') ?: "The {$field} field must be a valid date.";
        }
        return true;
    }

    private function validateBoolean(string $field, $value): string|bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        $validBooleans = [true, false, 1, 0, '1', '0', 'true', 'false', 'on', 'off', 'yes', 'no'];
        if (!in_array($value, $validBooleans, true)) {
            return $this->getCustomMessage($field, 'boolean') ?: "The {$field} field must be true or false.";
        }
        return true;
    }

    private function validateArray(string $field, $value): string|bool
    {
        if ($value === null) {
            return true;
        }

        if (!is_array($value)) {
            return $this->getCustomMessage($field, 'array') ?: "The {$field} field must be an array.";
        }
        return true;
    }

    /**
     * Get custom error message for field and rule
     */
    private function getCustomMessage(string $field, string $rule): ?string
    {
        return $this->customMessages["{$field}.{$rule}"] 
               ?? $this->customMessages[$rule] 
               ?? null;
    }

    /**
     * Add a custom validation rule
     */
    public function addRule(string $name, callable $callback): void
    {
        $this->customRules[$name] = $callback;
    }

    /**
     * Static helper to validate arbitrary data
     * Renamed to avoid clashing with the instance method validate().
     */
    public static function validateData(array $data, array $rules, array $customMessages = []): ValidationResult
    {
        return self::make($data, $rules, $customMessages)->validate();
    }

    /**
     * Helper method for common product validation
     */
    public static function validateProduct(array $data): ValidationResult
    {
        $rules = [
            'code' => ['required', 'string', 'min:1', 'max:50'],
            'name' => ['required', 'string', 'min:1', 'max:255'],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'make_id' => ['nullable', 'integer', 'min:1'],
            'model_id' => ['nullable', 'integer', 'min:1'],
            'cost' => ['numeric', 'min:0'],
            'price' => ['numeric', 'min:0'],
        ];

        $messages = [
            'code.required' => 'Product code is required.',
            'name.required' => 'Product name is required.',
            'cost.min' => 'Cost cannot be negative.',
            'price.min' => 'Price cannot be negative.',
        ];

        return self::validateData($data, $rules, $messages);
    }

    /**
     * Helper method for user validation
     */
    public static function validateUser(array $data): ValidationResult
    {
        $rules = [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'password_confirmation' => ['required', 'confirmed'],
        ];

        return self::validateData($data, $rules);
    }

    /**
     * Helper method for customer/supplier validation
     */
    public static function validateContact(array $data): ValidationResult
    {
        $rules = [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
        ];

        return self::validateData($data, $rules);
    }
}
