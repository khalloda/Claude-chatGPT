<?php declare(strict_types=1);

namespace Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use App\Core\Validator;
use App\Core\ValidationResult;

/**
 * Comprehensive tests for the Validator class
 */
class ValidatorTest extends TestCase
{
    /**
     * Test basic validation passes
     */
    public function testValidationPasses(): void
    {
        $data = ['name' => 'John Doe', 'email' => 'john@example.com'];
        $rules = ['name' => ['required'], 'email' => ['required', 'email']];
        
        $result = Validator::validate($data, $rules);
        
        $this->assertTrue($result->isValid());
        $this->assertFalse($result->hasErrors());
        $this->assertEmpty($result->getErrors());
        $this->assertEquals('John Doe', $result->getValidatedField('name'));
        $this->assertEquals('john@example.com', $result->getValidatedField('email'));
    }

    /**
     * Test validation fails with errors
     */
    public function testValidationFails(): void
    {
        $data = ['name' => '', 'email' => 'invalid-email'];
        $rules = ['name' => ['required'], 'email' => ['required', 'email']];
        
        $result = Validator::validate($data, $rules);
        
        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrors());
        $this->assertCount(2, $result->getErrors());
        
        // Check specific field errors
        $this->assertStringContains('required', $result->getFirstFieldError('name'));
        $this->assertStringContains('email', $result->getFirstFieldError('email'));
    }

    /**
     * Test required validation rule
     */
    public function testRequiredValidation(): void
    {
        $testCases = [
            ['value' => 'valid', 'expected' => true],
            ['value' => '', 'expected' => false],
            ['value' => null, 'expected' => false],
            ['value' => [], 'expected' => false],
            ['value' => 0, 'expected' => true],
            ['value' => '0', 'expected' => true],
        ];

        foreach ($testCases as $case) {
            $data = ['field' => $case['value']];
            $rules = ['field' => ['required']];
            $result = Validator::validate($data, $rules);
            
            $this->assertEquals($case['expected'], $result->isValid(), 
                "Failed for value: " . var_export($case['value'], true));
        }
    }

    /**
     * Test email validation rule
     */
    public function testEmailValidation(): void
    {
        $validEmails = [
            'test@example.com',
            'user.name@domain.co.uk',
            'user+tag@example.org'
        ];

        $invalidEmails = [
            'invalid-email',
            '@example.com',
            'user@',
            'user@.com',
            'spaces @example.com'
        ];

        foreach ($validEmails as $email) {
            $result = Validator::validate(['email' => $email], ['email' => ['email']]);
            $this->assertTrue($result->isValid(), "Failed for valid email: {$email}");
        }

        foreach ($invalidEmails as $email) {
            $result = Validator::validate(['email' => $email], ['email' => ['email']]);
            $this->assertFalse($result->isValid(), "Failed for invalid email: {$email}");
        }
    }

    /**
     * Test numeric validation rule
     */
    public function testNumericValidation(): void
    {
        $validNumbers = ['123', '123.45', '-456', '0', '0.0'];
        $invalidNumbers = ['abc', '123abc', '', 'not-a-number'];

        foreach ($validNumbers as $number) {
            $result = Validator::validate(['num' => $number], ['num' => ['numeric']]);
            $this->assertTrue($result->isValid(), "Failed for valid number: {$number}");
        }

        foreach ($invalidNumbers as $number) {
            $result = Validator::validate(['num' => $number], ['num' => ['numeric']]);
            $this->assertFalse($result->isValid(), "Failed for invalid number: {$number}");
        }
    }

    /**
     * Test integer validation rule
     */
    public function testIntegerValidation(): void
    {
        $validIntegers = ['123', '-456', '0'];
        $invalidIntegers = ['123.45', 'abc', '123abc', ''];

        foreach ($validIntegers as $integer) {
            $result = Validator::validate(['int' => $integer], ['int' => ['integer']]);
            $this->assertTrue($result->isValid(), "Failed for valid integer: {$integer}");
        }

        foreach ($invalidIntegers as $integer) {
            $result = Validator::validate(['int' => $integer], ['int' => ['integer']]);
            $this->assertFalse($result->isValid(), "Failed for invalid integer: {$integer}");
        }
    }

    /**
     * Test min validation rule
     */
    public function testMinValidation(): void
    {
        // String length validation
        $result = Validator::validate(['text' => 'hello'], ['text' => ['min:3']]);
        $this->assertTrue($result->isValid());

        $result = Validator::validate(['text' => 'hi'], ['text' => ['min:3']]);
        $this->assertFalse($result->isValid());

        // Numeric validation
        $result = Validator::validate(['num' => '10'], ['num' => ['min:5']]);
        $this->assertTrue($result->isValid());

        $result = Validator::validate(['num' => '3'], ['num' => ['min:5']]);
        $this->assertFalse($result->isValid());
    }

    /**
     * Test max validation rule
     */
    public function testMaxValidation(): void
    {
        // String length validation
        $result = Validator::validate(['text' => 'hello'], ['text' => ['max:10']]);
        $this->assertTrue($result->isValid());

        $result = Validator::validate(['text' => 'this is too long'], ['text' => ['max:10']]);
        $this->assertFalse($result->isValid());

        // Numeric validation
        $result = Validator::validate(['num' => '5'], ['num' => ['max:10']]);
        $this->assertTrue($result->isValid());

        $result = Validator::validate(['num' => '15'], ['num' => ['max:10']]);
        $this->assertFalse($result->isValid());
    }

    /**
     * Test between validation rule
     */
    public function testBetweenValidation(): void
    {
        // String length validation
        $result = Validator::validate(['text' => 'hello'], ['text' => ['between:3,10']]);
        $this->assertTrue($result->isValid());

        $result = Validator::validate(['text' => 'hi'], ['text' => ['between:3,10']]);
        $this->assertFalse($result->isValid());

        // Numeric validation
        $result = Validator::validate(['num' => '7'], ['num' => ['between:5,10']]);
        $this->assertTrue($result->isValid());

        $result = Validator::validate(['num' => '15'], ['num' => ['between:5,10']]);
        $this->assertFalse($result->isValid());
    }

    /**
     * Test in validation rule
     */
    public function testInValidation(): void
    {
        $result = Validator::validate(['status' => 'active'], ['status' => ['in:active,inactive,pending']]);
        $this->assertTrue($result->isValid());

        $result = Validator::validate(['status' => 'deleted'], ['status' => ['in:active,inactive,pending']]);
        $this->assertFalse($result->isValid());
    }

    /**
     * Test regex validation rule
     */
    public function testRegexValidation(): void
    {
        // Test phone number pattern
        $phonePattern = '/^[0-9]{3}-[0-9]{3}-[0-9]{4}$/';
        
        $result = Validator::validate(['phone' => '123-456-7890'], ['phone' => ["regex:{$phonePattern}"]]);
        $this->assertTrue($result->isValid());

        $result = Validator::validate(['phone' => '1234567890'], ['phone' => ["regex:{$phonePattern}"]]);
        $this->assertFalse($result->isValid());
    }

    /**
     * Test confirmed validation rule
     */
    public function testConfirmedValidation(): void
    {
        $data = [
            'password' => 'secret123',
            'password_confirmation' => 'secret123'
        ];
        $result = Validator::validate($data, ['password' => ['confirmed']]);
        $this->assertTrue($result->isValid());

        $data = [
            'password' => 'secret123',
            'password_confirmation' => 'different'
        ];
        $result = Validator::validate($data, ['password' => ['confirmed']]);
        $this->assertFalse($result->isValid());
    }

    /**
     * Test nullable validation
     */
    public function testNullableValidation(): void
    {
        $result = Validator::validate(['field' => null], ['field' => ['nullable', 'email']]);
        $this->assertTrue($result->isValid());

        $result = Validator::validate(['field' => ''], ['field' => ['nullable', 'email']]);
        $this->assertTrue($result->isValid());

        $result = Validator::validate(['field' => 'test@example.com'], ['field' => ['nullable', 'email']]);
        $this->assertTrue($result->isValid());

        $result = Validator::validate(['field' => 'invalid-email'], ['field' => ['nullable', 'email']]);
        $this->assertFalse($result->isValid());
    }

    /**
     * Test custom error messages
     */
    public function testCustomErrorMessages(): void
    {
        $data = ['name' => ''];
        $rules = ['name' => ['required']];
        $messages = [
            'name.required' => 'Please provide your name.'
        ];

        $result = Validator::validate($data, $rules, $messages);
        
        $this->assertFalse($result->isValid());
        $this->assertEquals('Please provide your name.', $result->getFirstFieldError('name'));
    }

    /**
     * Test data cleaning after validation
     */
    public function testDataCleaning(): void
    {
        $data = [
            'name' => '  John Doe  ',
            'age' => '25',
            'price' => '123.45',
            'active' => '1'
        ];

        $rules = [
            'name' => ['required', 'string'],
            'age' => ['required', 'integer'],
            'price' => ['required', 'numeric'],
            'active' => ['required', 'boolean']
        ];

        $result = Validator::validate($data, $rules);
        
        $this->assertTrue($result->isValid());
        
        $validated = $result->getValidatedData();
        $this->assertEquals('John Doe', $validated['name']); // Trimmed
        $this->assertEquals(25, $validated['age']); // Converted to int
        $this->assertEquals(123.45, $validated['price']); // Converted to float
        $this->assertEquals(true, $validated['active']); // Converted to boolean
    }

    /**
     * Test product validation helper
     */
    public function testProductValidationHelper(): void
    {
        $validProduct = [
            'code' => 'PROD001',
            'name' => 'Test Product',
            'category_id' => 1,
            'make_id' => 2,
            'model_id' => 3,
            'cost' => 10.50,
            'price' => 15.99
        ];

        $result = Validator::validateProduct($validProduct);
        $this->assertTrue($result->isValid());

        $invalidProduct = [
            'code' => '',  // Required field missing
            'name' => '',  // Required field missing
            'cost' => -5,  // Negative cost
            'price' => -10 // Negative price
        ];

        $result = Validator::validateProduct($invalidProduct);
        $this->assertFalse($result->isValid());
        $this->assertCount(4, $result->getErrors());
    }

    /**
     * Test ValidationResult methods
     */
    public function testValidationResult(): void
    {
        $errors = [
            'name' => ['Name is required'],
            'email' => ['Email is invalid', 'Email is too long']
        ];
        
        $validatedData = ['age' => 25];
        
        $result = new ValidationResult($errors, $validatedData);
        
        // Test error methods
        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrors());
        $this->assertEquals($errors, $result->getErrors());
        $this->assertEquals(['Name is required'], $result->getFieldErrors('name'));
        $this->assertEquals('Name is required', $result->getFirstFieldError('name'));
        $this->assertEquals('Email is invalid', $result->getFirstFieldError('email'));
        $this->assertNull($result->getFirstFieldError('nonexistent'));
        
        // Test error messages
        $allMessages = $result->getAllErrorMessages();
        $this->assertCount(3, $allMessages);
        $this->assertEquals('Name is required', $result->getFirstErrorMessage());
        
        // Test validated data
        $this->assertEquals($validatedData, $result->getValidatedData());
        $this->assertEquals(25, $result->getValidatedField('age'));
        $this->assertEquals('default', $result->getValidatedField('nonexistent', 'default'));
    }

    /**
     * Test custom validation rules
     */
    public function testCustomValidationRules(): void
    {
        $validator = new Validator(['username' => 'admin']);
        
        // Add custom rule for forbidden usernames
        $validator->addRule('not_admin', function ($field, $value, $parameters, $data) {
            if ($value === 'admin') {
                return "The {$field} cannot be 'admin'.";
            }
            return true;
        });

        $validator->rules = ['username' => ['not_admin']];
        $result = $validator->validate();
        
        $this->assertFalse($result->isValid());
        $this->assertStringContains('cannot be', $result->getFirstFieldError('username'));
    }

    /**
     * Test edge cases and security
     */
    public function testSecurityAndEdgeCases(): void
    {
        // Test with malicious input
        $maliciousData = [
            'script' => '<script>alert("xss")</script>',
            'sql' => "'; DROP TABLE users; --",
            'null_byte' => "test\0injection",
        ];

        $rules = [
            'script' => ['string', 'max:50'],
            'sql' => ['string', 'max:100'],
            'null_byte' => ['string', 'max:20']
        ];

        $result = Validator::validate($maliciousData, $rules);
        
        // Should handle malicious input safely
        $validated = $result->getValidatedData();
        $this->assertIsString($validated['script']);
        $this->assertIsString($validated['sql']);
        
        // Test with very long input
        $longString = str_repeat('a', 1000);
        $result = Validator::validate(['text' => $longString], ['text' => ['max:100']]);
        $this->assertFalse($result->isValid());
    }

    /**
     * Test performance with large datasets
     */
    public function testPerformance(): void
    {
        $startTime = microtime(true);
        
        // Test with 100 fields
        $data = [];
        $rules = [];
        for ($i = 0; $i < 100; $i++) {
            $data["field_{$i}"] = "value_{$i}";
            $rules["field_{$i}"] = ['required', 'string', 'min:5', 'max:50'];
        }

        $result = Validator::validate($data, $rules);
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        $this->assertTrue($result->isValid());
        $this->assertLessThan(1.0, $duration); // Should complete in under 1 second
        $this->assertCount(100, $result->getValidatedData());
    }
}