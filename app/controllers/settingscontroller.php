<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\SystemSetting;
use App\Models\TaxRate;
use App\Models\Currency;
use function App\Core\require_auth;
use function App\Core\require_permission;
use function App\Core\verify_csrf_request;
use function App\Core\flash_set;
use function App\Core\redirect;

final class SettingsController extends Controller
{
    public function taxcurrency(): void
    {
        require_auth();
        require_permission('settings.manage');
        
        // Get current settings
        $companySettings = SystemSetting::getCompanyInfo();
        $currencySettings = SystemSetting::getCurrencySettings();
        $taxSettings = SystemSetting::getTaxSettings();
        
        // Get tax rates and currencies
        $taxRates = TaxRate::getActive();
        $currencies = Currency::getActive();
        $baseCurrency = Currency::getBase();
        
        $this->view('settings/tax_currency', [
            'page_title' => 'Taxes & Currency Settings',
            'company_settings' => $companySettings,
            'currency_settings' => $currencySettings,
            'tax_settings' => $taxSettings,
            'tax_rates' => $taxRates,
            'currencies' => $currencies,
            'base_currency' => $baseCurrency
        ]);
    }
    
    public function updateTaxCurrency(): void
    {
        require_auth();
        require_permission('settings.manage');
        
        if (!verify_csrf_request()) {
            flash_set('error', 'Invalid session token.');
            redirect('/settings/tax-currency');
        }
        
        try {
            $updated = 0;
            
            // Update company settings
            if (isset($_POST['company'])) {
                foreach ($_POST['company'] as $key => $value) {
                    $value = trim((string)$value);
                    if (SystemSetting::set($key, $value, 'string', 'company')) {
                        $updated++;
                    }
                }
            }
            
            // Update currency settings
            if (isset($_POST['currency'])) {
                foreach ($_POST['currency'] as $key => $value) {
                    $type = in_array($key, ['decimal_places']) ? 'number' : 'string';
                    if ($key === 'enable_multi_currency') {
                        $type = 'boolean';
                        $value = !empty($value);
                    }
                    
                    if (SystemSetting::set($key, $value, $type, 'currency')) {
                        $updated++;
                    }
                }
            }
            
            // Update tax settings
            if (isset($_POST['tax'])) {
                foreach ($_POST['tax'] as $key => $value) {
                    $type = ($key === 'default_tax_rate') ? 'number' : 'string';
                    if ($key === 'enable_tax_inclusive') {
                        $type = 'boolean';
                        $value = !empty($value);
                    }
                    
                    if (SystemSetting::set($key, $value, $type, 'tax')) {
                        $updated++;
                    }
                }
            }
            
            // Clear settings cache
            SystemSetting::clearCache();
            
            flash_set('success', "Settings updated successfully! ({$updated} changes)");
            
        } catch (\Throwable $e) {
            \App\Core\Logger::error('Settings update failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            flash_set('error', 'Failed to update settings. Please try again.');
        }
        
        redirect('/settings/tax-currency');
    }
    
    public function manageTaxRates(): void
    {
        require_auth();
        require_permission('settings.manage');
        
        $taxRates = TaxRate::all();
        $taxTypes = TaxRate::getTypes();
        
        $this->view('settings/tax_rates', [
            'page_title' => 'Tax Rate Management',
            'tax_rates' => $taxRates,
            'tax_types' => $taxTypes
        ]);
    }
    
    public function createTaxRate(): void
    {
        require_auth();
        require_permission('settings.manage');
        
        if (!verify_csrf_request()) {
            flash_set('error', 'Invalid session token.');
            redirect('/settings/tax-rates');
        }
        
        try {
            $data = [
                'name' => trim((string)($_POST['name'] ?? '')),
                'rate' => (float)($_POST['rate'] ?? 0),
                'type' => trim((string)($_POST['type'] ?? 'sales')),
                'is_default' => !empty($_POST['is_default']),
                'is_active' => !empty($_POST['is_active']),
                'description' => trim((string)($_POST['description'] ?? '')),
                'effective_from' => !empty($_POST['effective_from']) ? $_POST['effective_from'] : null,
                'effective_to' => !empty($_POST['effective_to']) ? $_POST['effective_to'] : null
            ];
            
            // Validation
            if (empty($data['name'])) {
                throw new \InvalidArgumentException('Tax rate name is required.');
            }
            
            if ($data['rate'] < 0 || $data['rate'] > 100) {
                throw new \InvalidArgumentException('Tax rate must be between 0% and 100%.');
            }
            
            $id = TaxRate::create($data);
            
            flash_set('success', 'Tax rate created successfully.');
            
        } catch (\Throwable $e) {
            flash_set('error', 'Failed to create tax rate: ' . $e->getMessage());
        }
        
        redirect('/settings/tax-rates');
    }
    
    public function updateTaxRate(): void
    {
        require_auth();
        require_permission('settings.manage');
        
        if (!verify_csrf_request()) {
            flash_set('error', 'Invalid session token.');
            redirect('/settings/tax-rates');
        }
        
        try {
            $id = (int)($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                throw new \InvalidArgumentException('Invalid tax rate ID.');
            }
            
            $data = [
                'name' => trim((string)($_POST['name'] ?? '')),
                'rate' => (float)($_POST['rate'] ?? 0),
                'type' => trim((string)($_POST['type'] ?? 'sales')),
                'is_default' => !empty($_POST['is_default']),
                'is_active' => !empty($_POST['is_active']),
                'description' => trim((string)($_POST['description'] ?? '')),
                'effective_from' => !empty($_POST['effective_from']) ? $_POST['effective_from'] : null,
                'effective_to' => !empty($_POST['effective_to']) ? $_POST['effective_to'] : null
            ];
            
            // Validation
            if (empty($data['name'])) {
                throw new \InvalidArgumentException('Tax rate name is required.');
            }
            
            if ($data['rate'] < 0 || $data['rate'] > 100) {
                throw new \InvalidArgumentException('Tax rate must be between 0% and 100%.');
            }
            
            TaxRate::update($id, $data);
            
            flash_set('success', 'Tax rate updated successfully.');
            
        } catch (\Throwable $e) {
            flash_set('error', 'Failed to update tax rate: ' . $e->getMessage());
        }
        
        redirect('/settings/tax-rates');
    }
    
    public function deleteTaxRate(): void
    {
        require_auth();
        require_permission('settings.manage');
        
        if (!verify_csrf_request()) {
            flash_set('error', 'Invalid session token.');
            redirect('/settings/tax-rates');
        }
        
        try {
            $id = (int)($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                throw new \InvalidArgumentException('Invalid tax rate ID.');
            }
            
            if (TaxRate::delete($id)) {
                flash_set('success', 'Tax rate deleted successfully.');
            } else {
                flash_set('error', 'Failed to delete tax rate.');
            }
            
        } catch (\Throwable $e) {
            flash_set('error', 'Failed to delete tax rate: ' . $e->getMessage());
        }
        
        redirect('/settings/tax-rates');
    }
    
    public function manageCurrencies(): void
    {
        require_auth();
        require_permission('settings.manage');
        
        $currencies = Currency::all();
        $baseCurrency = Currency::getBase();
        
        $this->view('settings/currencies', [
            'page_title' => 'Currency Management',
            'currencies' => $currencies,
            'base_currency' => $baseCurrency
        ]);
    }
    
    public function createCurrency(): void
    {
        require_auth();
        require_permission('settings.manage');
        
        if (!verify_csrf_request()) {
            flash_set('error', 'Invalid session token.');
            redirect('/settings/currencies');
        }
        
        try {
            $data = [
                'code' => strtoupper(trim((string)($_POST['code'] ?? ''))),
                'name' => trim((string)($_POST['name'] ?? '')),
                'symbol' => trim((string)($_POST['symbol'] ?? '')),
                'exchange_rate' => (float)($_POST['exchange_rate'] ?? 1.0),
                'is_base' => !empty($_POST['is_base']),
                'is_active' => !empty($_POST['is_active']),
                'decimal_places' => (int)($_POST['decimal_places'] ?? 2)
            ];
            
            // Validation
            if (empty($data['code']) || strlen($data['code']) !== 3) {
                throw new \InvalidArgumentException('Currency code must be exactly 3 characters.');
            }
            
            if (empty($data['name'])) {
                throw new \InvalidArgumentException('Currency name is required.');
            }
            
            if ($data['exchange_rate'] <= 0) {
                throw new \InvalidArgumentException('Exchange rate must be greater than 0.');
            }
            
            $id = Currency::create($data);
            
            flash_set('success', 'Currency created successfully.');
            
        } catch (\Throwable $e) {
            flash_set('error', 'Failed to create currency: ' . $e->getMessage());
        }
        
        redirect('/settings/currencies');
    }
    
    public function updateCurrency(): void
    {
        require_auth();
        require_permission('settings.manage');
        
        if (!verify_csrf_request()) {
            flash_set('error', 'Invalid session token.');
            redirect('/settings/currencies');
        }
        
        try {
            $id = (int)($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                throw new \InvalidArgumentException('Invalid currency ID.');
            }
            
            $data = [
                'code' => strtoupper(trim((string)($_POST['code'] ?? ''))),
                'name' => trim((string)($_POST['name'] ?? '')),
                'symbol' => trim((string)($_POST['symbol'] ?? '')),
                'exchange_rate' => (float)($_POST['exchange_rate'] ?? 1.0),
                'is_base' => !empty($_POST['is_base']),
                'is_active' => !empty($_POST['is_active']),
                'decimal_places' => (int)($_POST['decimal_places'] ?? 2)
            ];
            
            // Validation
            if (empty($data['code']) || strlen($data['code']) !== 3) {
                throw new \InvalidArgumentException('Currency code must be exactly 3 characters.');
            }
            
            if (empty($data['name'])) {
                throw new \InvalidArgumentException('Currency name is required.');
            }
            
            if ($data['exchange_rate'] <= 0) {
                throw new \InvalidArgumentException('Exchange rate must be greater than 0.');
            }
            
            Currency::update($id, $data);
            
            flash_set('success', 'Currency updated successfully.');
            
        } catch (\Throwable $e) {
            flash_set('error', 'Failed to update currency: ' . $e->getMessage());
        }
        
        redirect('/settings/currencies');
    }

    public function unitssequences(): void
    {
        require_auth();
        $this->view('settings/units_sequences', [
            'page_title' => 'Units & Sequences',
        ]);
    }
}

