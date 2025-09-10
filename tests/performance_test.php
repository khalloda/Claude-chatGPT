<?php declare(strict_types=1);

require_once __DIR__ . '/../app/core/autoload.php';

use App\Services\QueryProfiler;
use App\Services\ReferenceDataCache;
use App\Services\QueryCache;
use App\Services\CustomerAging;
use App\Models\Product;
use App\Models\Customer;
use App\Controllers\ProductsController;
use App\Controllers\CustomersController;

/**
 * Performance Testing Script for N+1 Query Optimization
 * 
 * Tests various scenarios before and after optimization to validate
 * that N+1 query issues have been resolved.
 */
class PerformanceTest
{
    private array $results = [];
    
    public function runAllTests(): void
    {
        echo "=== N+1 Query Performance Testing ===\n\n";
        
        // Clear caches for consistent testing
        ReferenceDataCache::clearAll();
        QueryCache::flush();
        
        // Run individual test scenarios
        $this->testProductListing();
        $this->testProductCreationForm();
        $this->testCustomerAging();
        $this->testCustomerStatement();
        $this->testReferenceDataCaching();
        
        // Generate final report
        $this->generateReport();
    }
    
    private function testProductListing(): void
    {
        echo "Testing Product Listing Performance...\n";
        
        // Test without cache
        $result1 = QueryProfiler::testScenario('Product Listing - No Cache', function() {
            ReferenceDataCache::clearAll();
            return Product::all();
        });
        
        // Test with cache
        $result2 = QueryProfiler::testScenario('Product Listing - With Cache', function() {
            ReferenceDataCache::warmUp();
            return Product::all();
        });
        
        $this->results['product_listing'] = [
            'without_cache' => $result1,
            'with_cache' => $result2,
            'improvement' => [
                'query_reduction' => $result1['total_queries'] - $result2['total_queries'],
                'time_reduction' => ($result1['total_time'] - $result2['total_time']) * 1000
            ]
        ];
        
        echo "- Without Cache: {$result1['total_queries']} queries, " . 
             number_format($result1['total_time'] * 1000, 2) . "ms\n";
        echo "- With Cache: {$result2['total_queries']} queries, " . 
             number_format($result2['total_time'] * 1000, 2) . "ms\n";
        echo "- Improvement: -{$this->results['product_listing']['improvement']['query_reduction']} queries, " .
             "-" . number_format($this->results['product_listing']['improvement']['time_reduction'], 2) . "ms\n\n";
    }
    
    private function testProductCreationForm(): void
    {
        echo "Testing Product Form Loading Performance...\n";
        
        // Simulate controller method calls
        $result1 = QueryProfiler::testScenario('Product Form - Without Cache', function() {
            ReferenceDataCache::clearAll();
            // Simulate loading form data
            $categories = \App\Models\Category::all();
            $makes = \App\Models\Make::options();
            $models = \App\Models\VehicleModel::all();
            return ['categories' => count($categories), 'makes' => count($makes), 'models' => count($models)];
        });
        
        $result2 = QueryProfiler::testScenario('Product Form - With Cache', function() {
            ReferenceDataCache::warmUp();
            $categories = ReferenceDataCache::getCategories();
            $makes = ReferenceDataCache::getMakes();
            $models = ReferenceDataCache::getModels();
            return ['categories' => count($categories), 'makes' => count($makes), 'models' => count($models)];
        });
        
        $this->results['product_form'] = [
            'without_cache' => $result1,
            'with_cache' => $result2,
            'improvement' => [
                'query_reduction' => $result1['total_queries'] - $result2['total_queries'],
                'time_reduction' => ($result1['total_time'] - $result2['total_time']) * 1000
            ]
        ];
        
        echo "- Without Cache: {$result1['total_queries']} queries, " . 
             number_format($result1['total_time'] * 1000, 2) . "ms\n";
        echo "- With Cache: {$result2['total_queries']} queries, " . 
             number_format($result2['total_time'] * 1000, 2) . "ms\n";
        echo "- Improvement: -{$this->results['product_form']['improvement']['query_reduction']} queries, " .
             "-" . number_format($this->results['product_form']['improvement']['time_reduction'], 2) . "ms\n\n";
    }
    
    private function testCustomerAging(): void
    {
        echo "Testing Customer Aging Calculation Performance...\n";
        
        // Get a customer ID for testing
        $customers = Customer::all();
        if (empty($customers)) {
            echo "- No customers found for testing\n\n";
            return;
        }
        
        $customerId = (int)$customers[0]['id'];
        
        // Test old approach (multiple queries)
        $result1 = QueryProfiler::testScenario('Customer Aging - Multiple Queries', function() use ($customerId) {
            $pdo = \App\Core\DB::conn();
            
            // Simulate old approach with multiple queries
            $st1 = $pdo->prepare("SELECT COALESCE(SUM(total),0) FROM invoices WHERE customer_id=?");
            $st1->execute([$customerId]);
            $invoiceTotal = $st1->fetchColumn();
            
            $st2 = $pdo->prepare("SELECT COALESCE(SUM(p.amount),0) FROM invoice_payments p 
                                  JOIN invoices i ON i.id=p.invoice_id WHERE i.customer_id=?");
            $st2->execute([$customerId]);
            $paymentTotal = $st2->fetchColumn();
            
            $st3 = $pdo->prepare("SELECT COALESCE(SUM(sr.total),0) FROM sales_returns sr 
                                  JOIN invoices i ON i.id=sr.sales_invoice_id WHERE i.customer_id=?");
            $st3->execute([$customerId]);
            $returnTotal = $st3->fetchColumn();
            
            return ['invoice_total' => $invoiceTotal, 'payment_total' => $paymentTotal, 'return_total' => $returnTotal];
        });
        
        // Test optimized approach
        $result2 = QueryProfiler::testScenario('Customer Aging - Optimized', function() use ($customerId) {
            return CustomerAging::getCustomerAging($customerId);
        });
        
        $this->results['customer_aging'] = [
            'multiple_queries' => $result1,
            'optimized' => $result2,
            'improvement' => [
                'query_reduction' => $result1['total_queries'] - $result2['total_queries'],
                'time_reduction' => ($result1['total_time'] - $result2['total_time']) * 1000
            ]
        ];
        
        echo "- Multiple Queries: {$result1['total_queries']} queries, " . 
             number_format($result1['total_time'] * 1000, 2) . "ms\n";
        echo "- Optimized: {$result2['total_queries']} queries, " . 
             number_format($result2['total_time'] * 1000, 2) . "ms\n";
        echo "- Improvement: -{$this->results['customer_aging']['improvement']['query_reduction']} queries, " .
             "-" . number_format($this->results['customer_aging']['improvement']['time_reduction'], 2) . "ms\n\n";
    }
    
    private function testCustomerStatement(): void
    {
        echo "Testing Customer Statement Performance...\n";
        
        $customers = Customer::all();
        if (empty($customers)) {
            echo "- No customers found for testing\n\n";
            return;
        }
        
        $customerId = (int)$customers[0]['id'];
        $fromDate = date('Y-m-01');
        $toDate = date('Y-m-d');
        
        // Test old approach
        $result1 = QueryProfiler::testScenario('Customer Statement - Multiple Queries', function() use ($customerId, $fromDate, $toDate) {
            $pdo = \App\Core\DB::conn();
            
            // Simulate old approach with separate queries for invoices, payments, returns
            $st1 = $pdo->prepare("SELECT * FROM invoices WHERE customer_id=? AND DATE(created_at) BETWEEN ? AND ?");
            $st1->execute([$customerId, $fromDate, $toDate]);
            $invoices = $st1->fetchAll();
            
            $st2 = $pdo->prepare("SELECT p.* FROM invoice_payments p JOIN invoices i ON i.id=p.invoice_id 
                                  WHERE i.customer_id=? AND DATE(p.paid_at) BETWEEN ? AND ?");
            $st2->execute([$customerId, $fromDate, $toDate]);
            $payments = $st2->fetchAll();
            
            $st3 = $pdo->prepare("SELECT sr.* FROM sales_returns sr JOIN invoices i ON i.id=sr.sales_invoice_id 
                                  WHERE i.customer_id=? AND DATE(sr.created_at) BETWEEN ? AND ?");
            $st3->execute([$customerId, $fromDate, $toDate]);
            $returns = $st3->fetchAll();
            
            return ['invoices' => count($invoices), 'payments' => count($payments), 'returns' => count($returns)];
        });
        
        // Test optimized approach
        $result2 = QueryProfiler::testScenario('Customer Statement - Optimized', function() use ($customerId, $fromDate, $toDate) {
            return CustomerAging::getCustomerStatement($customerId, $fromDate, $toDate);
        });
        
        $this->results['customer_statement'] = [
            'multiple_queries' => $result1,
            'optimized' => $result2,
            'improvement' => [
                'query_reduction' => $result1['total_queries'] - $result2['total_queries'],
                'time_reduction' => ($result1['total_time'] - $result2['total_time']) * 1000
            ]
        ];
        
        echo "- Multiple Queries: {$result1['total_queries']} queries, " . 
             number_format($result1['total_time'] * 1000, 2) . "ms\n";
        echo "- Optimized: {$result2['total_queries']} queries, " . 
             number_format($result2['total_time'] * 1000, 2) . "ms\n";
        echo "- Improvement: -{$this->results['customer_statement']['improvement']['query_reduction']} queries, " .
             "-" . number_format($this->results['customer_statement']['improvement']['time_reduction'], 2) . "ms\n\n";
    }
    
    private function testReferenceDataCaching(): void
    {
        echo "Testing Reference Data Caching Performance...\n";
        
        $result1 = QueryProfiler::testScenario('Reference Data - No Cache', function() {
            ReferenceDataCache::clearAll();
            
            // Simulate multiple requests for reference data
            for ($i = 0; $i < 10; $i++) {
                \App\Models\Category::all();
                \App\Models\Make::options();
                \App\Models\VehicleModel::all();
            }
            
            return ['iterations' => 10];
        });
        
        $result2 = QueryProfiler::testScenario('Reference Data - With Cache', function() {
            ReferenceDataCache::clearAll();
            ReferenceDataCache::warmUp();
            
            // Simulate multiple requests for cached reference data
            for ($i = 0; $i < 10; $i++) {
                ReferenceDataCache::getCategories();
                ReferenceDataCache::getMakes();
                ReferenceDataCache::getModels();
            }
            
            return ['iterations' => 10];
        });
        
        $this->results['reference_caching'] = [
            'no_cache' => $result1,
            'with_cache' => $result2,
            'improvement' => [
                'query_reduction' => $result1['total_queries'] - $result2['total_queries'],
                'time_reduction' => ($result1['total_time'] - $result2['total_time']) * 1000
            ]
        ];
        
        echo "- No Cache: {$result1['total_queries']} queries, " . 
             number_format($result1['total_time'] * 1000, 2) . "ms\n";
        echo "- With Cache: {$result2['total_queries']} queries, " . 
             number_format($result2['total_time'] * 1000, 2) . "ms\n";
        echo "- Improvement: -{$this->results['reference_caching']['improvement']['query_reduction']} queries, " .
             "-" . number_format($this->results['reference_caching']['improvement']['time_reduction'], 2) . "ms\n\n";
    }
    
    private function generateReport(): void
    {
        echo "=== PERFORMANCE TESTING SUMMARY ===\n\n";
        
        $totalQueryReduction = 0;
        $totalTimeReduction = 0;
        
        foreach ($this->results as $testName => $result) {
            if (isset($result['improvement'])) {
                $totalQueryReduction += $result['improvement']['query_reduction'];
                $totalTimeReduction += $result['improvement']['time_reduction'];
            }
        }
        
        echo "Overall Improvements:\n";
        echo "- Total Query Reduction: {$totalQueryReduction} queries\n";
        echo "- Total Time Reduction: " . number_format($totalTimeReduction, 2) . "ms\n\n";
        
        echo "Test Results Summary:\n";
        foreach ($this->results as $testName => $result) {
            if (isset($result['improvement'])) {
                echo "- " . ucwords(str_replace('_', ' ', $testName)) . ": ";
                echo "-{$result['improvement']['query_reduction']} queries, ";
                echo "-" . number_format($result['improvement']['time_reduction'], 2) . "ms\n";
            }
        }
        
        echo "\nConclusions:\n";
        if ($totalQueryReduction > 0) {
            echo "✓ N+1 query optimizations are working effectively\n";
        }
        if ($totalTimeReduction > 0) {
            echo "✓ Performance improvements achieved through caching and optimization\n";
        }
        
        $avgReduction = count($this->results) > 0 ? $totalQueryReduction / count($this->results) : 0;
        if ($avgReduction >= 2) {
            echo "✓ Significant query reduction achieved (avg " . number_format($avgReduction, 1) . " queries per scenario)\n";
        }
        
        echo "\nRecommendations:\n";
        echo "- Enable ReferenceDataCache in production\n";
        echo "- Use QueryCache for frequently accessed data\n";
        echo "- Monitor query patterns using QueryProfiler\n";
        echo "- Regularly run this performance test suite\n";
    }
}

// Run tests if script is executed directly
if (php_sapi_name() === 'cli') {
    try {
        $test = new PerformanceTest();
        $test->runAllTests();
    } catch (Exception $e) {
        echo "Error running performance tests: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
}