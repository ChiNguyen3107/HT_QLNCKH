<?php

/**
 * CSRF Protection Simple Tests
 * 
 * Simple test cases cho CSRF protection functionality
 * Không phụ thuộc vào PHPUnit
 */

require_once __DIR__ . '/../../../core/CSRF.php';
require_once __DIR__ . '/../../../app/Middleware/CSRFMiddleware.php';
require_once __DIR__ . '/../../../core/Helper.php';

class CSRFSimpleTest
{
    private $originalSession;
    private $testResults = [];
    
    public function __construct()
    {
        $this->originalSession = $_SESSION ?? [];
    }
    
    public function setUp()
    {
        // Start fresh session for each test
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        session_start();
        
        // Clear any existing CSRF tokens
        CSRF::clearAllTokens();
    }
    
    public function tearDown()
    {
        // Restore original session
        $_SESSION = $this->originalSession;
    }
    
    public function runAllTests()
    {
        echo "=== CSRF Protection Tests ===\n\n";
        
        $this->testGenerateToken();
        $this->testValidateTokenValid();
        $this->testValidateTokenInvalid();
        $this->testTokenSingleUse();
        $this->testGetTokenField();
        $this->testCheckRequestValid();
        $this->testHelperCsrfField();
        $this->testHelperCsrfToken();
        $this->testHelperFormOpen();
        
        $this->printResults();
    }
    
    private function testGenerateToken()
    {
        $this->setUp();
        
        try {
            $token = CSRF::generateToken('test_form');
            
            $this->assertTrue(is_string($token), 'Token should be string');
            $this->assertTrue(strlen($token) === 64, 'Token should be 64 characters');
            $this->assertTrue(ctype_xdigit($token), 'Token should be hexadecimal');
            
            $this->addResult('testGenerateToken', true, 'Token generation works correctly');
        } catch (Exception $e) {
            $this->addResult('testGenerateToken', false, 'Error: ' . $e->getMessage());
        }
        
        $this->tearDown();
    }
    
    private function testValidateTokenValid()
    {
        $this->setUp();
        
        try {
            $token = CSRF::generateToken('test_form');
            $isValid = CSRF::validateToken($token, 'test_form');
            
            $this->assertTrue($isValid, 'Valid token should pass validation');
            
            $this->addResult('testValidateTokenValid', true, 'Valid token validation works');
        } catch (Exception $e) {
            $this->addResult('testValidateTokenValid', false, 'Error: ' . $e->getMessage());
        }
        
        $this->tearDown();
    }
    
    private function testValidateTokenInvalid()
    {
        $this->setUp();
        
        try {
            $token = CSRF::generateToken('test_form');
            $invalidToken = 'invalid_token_1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef';
            $isValid = CSRF::validateToken($invalidToken, 'test_form');
            
            $this->assertFalse($isValid, 'Invalid token should fail validation');
            
            $this->addResult('testValidateTokenInvalid', true, 'Invalid token validation works');
        } catch (Exception $e) {
            $this->addResult('testValidateTokenInvalid', false, 'Error: ' . $e->getMessage());
        }
        
        $this->tearDown();
    }
    
    private function testTokenSingleUse()
    {
        $this->setUp();
        
        try {
            $token = CSRF::generateToken('test_form');
            
            // First use should be valid
            $isValid1 = CSRF::validateToken($token, 'test_form');
            $this->assertTrue($isValid1, 'First use should be valid');
            
            // Second use should be invalid
            $isValid2 = CSRF::validateToken($token, 'test_form');
            $this->assertFalse($isValid2, 'Second use should be invalid');
            
            $this->addResult('testTokenSingleUse', true, 'Single use token validation works');
        } catch (Exception $e) {
            $this->addResult('testTokenSingleUse', false, 'Error: ' . $e->getMessage());
        }
        
        $this->tearDown();
    }
    
    private function testGetTokenField()
    {
        $this->setUp();
        
        try {
            $field = CSRF::getTokenField('test_form');
            
            $this->assertTrue(strpos($field, '<input type="hidden"') !== false, 'Should contain hidden input');
            $this->assertTrue(strpos($field, 'name="_csrf_token"') !== false, 'Should contain correct name');
            $this->assertTrue(strpos($field, 'value="') !== false, 'Should contain value');
            
            $this->addResult('testGetTokenField', true, 'Token field generation works');
        } catch (Exception $e) {
            $this->addResult('testGetTokenField', false, 'Error: ' . $e->getMessage());
        }
        
        $this->tearDown();
    }
    
    private function testCheckRequestValid()
    {
        $this->setUp();
        
        try {
            $token = CSRF::generateToken('test_form');
            $_POST['_csrf_token'] = $token;
            
            $isValid = CSRF::checkRequest('test_form');
            $this->assertTrue($isValid, 'Valid request should pass check');
            
            $this->addResult('testCheckRequestValid', true, 'Request validation works');
        } catch (Exception $e) {
            $this->addResult('testCheckRequestValid', false, 'Error: ' . $e->getMessage());
        }
        
        $this->tearDown();
    }
    
    private function testHelperCsrfField()
    {
        $this->setUp();
        
        try {
            $field = Helper::csrfField('test_form');
            
            $this->assertTrue(strpos($field, '<input type="hidden"') !== false, 'Should contain hidden input');
            $this->assertTrue(strpos($field, 'name="_csrf_token"') !== false, 'Should contain correct name');
            
            $this->addResult('testHelperCsrfField', true, 'Helper CSRF field works');
        } catch (Exception $e) {
            $this->addResult('testHelperCsrfField', false, 'Error: ' . $e->getMessage());
        }
        
        $this->tearDown();
    }
    
    private function testHelperCsrfToken()
    {
        $this->setUp();
        
        try {
            $token = Helper::csrfToken('test_form');
            
            $this->assertTrue(is_string($token), 'Token should be string');
            $this->assertTrue(strlen($token) === 64, 'Token should be 64 characters');
            
            $this->addResult('testHelperCsrfToken', true, 'Helper CSRF token works');
        } catch (Exception $e) {
            $this->addResult('testHelperCsrfToken', false, 'Error: ' . $e->getMessage());
        }
        
        $this->tearDown();
    }
    
    private function testHelperFormOpen()
    {
        $this->setUp();
        
        try {
            $form = Helper::formOpen('/test', 'POST', ['class' => 'test-form'], 'test_form');
            
            $this->assertTrue(strpos($form, '<form') !== false, 'Should contain form tag');
            $this->assertTrue(strpos($form, 'action="/test"') !== false, 'Should contain action');
            $this->assertTrue(strpos($form, 'method="POST"') !== false, 'Should contain method');
            $this->assertTrue(strpos($form, 'class="test-form"') !== false, 'Should contain class');
            $this->assertTrue(strpos($form, '<input type="hidden"') !== false, 'Should contain CSRF field');
            
            $this->addResult('testHelperFormOpen', true, 'Helper form open works');
        } catch (Exception $e) {
            $this->addResult('testHelperFormOpen', false, 'Error: ' . $e->getMessage());
        }
        
        $this->tearDown();
    }
    
    private function assertTrue($condition, $message = '')
    {
        if (!$condition) {
            throw new Exception($message);
        }
    }
    
    private function assertFalse($condition, $message = '')
    {
        if ($condition) {
            throw new Exception($message);
        }
    }
    
    private function addResult($testName, $passed, $message)
    {
        $this->testResults[] = [
            'test' => $testName,
            'passed' => $passed,
            'message' => $message
        ];
    }
    
    private function printResults()
    {
        $passed = 0;
        $total = count($this->testResults);
        
        foreach ($this->testResults as $result) {
            $status = $result['passed'] ? 'PASS' : 'FAIL';
            $color = $result['passed'] ? "\033[32m" : "\033[31m";
            $reset = "\033[0m";
            
            echo "{$color}[{$status}]{$reset} {$result['test']}: {$result['message']}\n";
            
            if ($result['passed']) {
                $passed++;
            }
        }
        
        echo "\n=== Results ===\n";
        echo "Passed: {$passed}/{$total}\n";
        
        if ($passed === $total) {
            echo "\033[32mAll tests passed!\033[0m\n";
        } else {
            echo "\033[31mSome tests failed!\033[0m\n";
        }
    }
}

// Run tests if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new CSRFSimpleTest();
    $test->runAllTests();
}
