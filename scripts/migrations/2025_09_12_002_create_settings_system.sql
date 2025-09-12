-- Settings System Migration
-- Creates tables for tax rates, currency settings, and other system configurations

-- System settings table for key-value configuration
CREATE TABLE IF NOT EXISTS system_settings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  setting_key VARCHAR(100) NOT NULL,
  setting_value TEXT,
  setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
  category VARCHAR(50) DEFAULT 'general',
  description VARCHAR(255),
  is_encrypted BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY idx_settings_key (setting_key),
  KEY idx_settings_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tax rates table for managing different tax types and rates
CREATE TABLE IF NOT EXISTS tax_rates (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  type ENUM('sales', 'purchase', 'vat', 'service', 'import', 'export') DEFAULT 'sales',
  is_default BOOLEAN DEFAULT FALSE,
  is_active BOOLEAN DEFAULT TRUE,
  description TEXT,
  effective_from DATE,
  effective_to DATE NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_tax_rates_type (type),
  KEY idx_tax_rates_active (is_active),
  KEY idx_tax_rates_default (is_default),
  KEY idx_tax_rates_effective (effective_from, effective_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Currencies table for multi-currency support
CREATE TABLE IF NOT EXISTS currencies (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  code VARCHAR(3) NOT NULL,
  name VARCHAR(100) NOT NULL,
  symbol VARCHAR(10),
  exchange_rate DECIMAL(10,6) DEFAULT 1.000000,
  is_base BOOLEAN DEFAULT FALSE,
  is_active BOOLEAN DEFAULT TRUE,
  decimal_places INT DEFAULT 2,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY idx_currencies_code (code),
  KEY idx_currencies_base (is_base),
  KEY idx_currencies_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exchange rate history for tracking rate changes
CREATE TABLE IF NOT EXISTS exchange_rate_history (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  currency_id INT UNSIGNED NOT NULL,
  rate DECIMAL(10,6) NOT NULL,
  effective_date DATE NOT NULL,
  source VARCHAR(50),
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_exchange_history_currency (currency_id),
  KEY idx_exchange_history_date (effective_date),
  CONSTRAINT fk_exchange_history_currency FOREIGN KEY (currency_id) REFERENCES currencies (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default system settings
INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, category, description) VALUES
  ('company_name', 'Spare Parts Management System', 'string', 'company', 'Company or business name'),
  ('company_address', '', 'string', 'company', 'Company address for invoices and documents'),
  ('company_phone', '', 'string', 'company', 'Company phone number'),
  ('company_email', '', 'string', 'company', 'Company email address'),
  ('company_website', '', 'string', 'company', 'Company website URL'),
  ('company_logo', '', 'string', 'company', 'Path to company logo file'),
  ('default_tax_rate', '10.00', 'number', 'tax', 'Default tax rate percentage'),
  ('tax_calculation_method', 'exclusive', 'string', 'tax', 'Tax calculation method: inclusive or exclusive'),
  ('base_currency', 'EGP', 'string', 'currency', 'Base currency code'),
  ('currency_symbol', 'EGP', 'string', 'currency', 'Currency symbol for display'),
  ('currency_position', 'after', 'string', 'currency', 'Currency symbol position: before or after amount'),
  ('decimal_places', '2', 'number', 'currency', 'Number of decimal places for currency'),
  ('thousands_separator', ',', 'string', 'currency', 'Thousands separator character'),
  ('decimal_separator', '.', 'string', 'currency', 'Decimal separator character'),
  ('invoice_terms', 'Payment due within 30 days', 'string', 'invoice', 'Default invoice terms and conditions'),
  ('invoice_footer', 'Thank you for your business!', 'string', 'invoice', 'Default invoice footer text'),
  ('low_stock_threshold', '10', 'number', 'inventory', 'Default low stock alert threshold'),
  ('auto_generate_codes', 'true', 'boolean', 'inventory', 'Automatically generate product codes'),
  ('enable_multi_currency', 'false', 'boolean', 'currency', 'Enable multi-currency support'),
  ('enable_tax_inclusive', 'false', 'boolean', 'tax', 'Enable tax-inclusive pricing');

-- Insert default currencies
INSERT IGNORE INTO currencies (code, name, symbol, exchange_rate, is_base, is_active, decimal_places) VALUES
  ('EGP', 'Egyptian Pound', 'ج.م', 1.000000, TRUE, TRUE, 2),
  ('USD', 'US Dollar', '$', 30.850000, FALSE, TRUE, 2),
  ('EUR', 'Euro', '€', 33.500000, FALSE, TRUE, 2),
  ('GBP', 'British Pound', '£', 39.200000, FALSE, TRUE, 2),
  ('SAR', 'Saudi Riyal', 'ر.س', 8.220000, FALSE, TRUE, 2),
  ('AED', 'UAE Dirham', 'د.إ', 8.400000, FALSE, TRUE, 2);

-- Insert default tax rates
INSERT IGNORE INTO tax_rates (name, rate, type, is_default, is_active, description, effective_from) VALUES
  ('Standard VAT', 14.00, 'sales', TRUE, TRUE, 'Standard Value Added Tax rate for Egypt', '2024-01-01'),
  ('Service Tax', 10.00, 'service', FALSE, TRUE, 'Tax rate for services', '2024-01-01'),
  ('Zero Tax', 0.00, 'sales', FALSE, TRUE, 'Zero tax rate for exempt items', '2024-01-01'),
  ('Import Duty', 5.00, 'import', FALSE, TRUE, 'Import duty rate', '2024-01-01'),
  ('Export Tax', 0.00, 'export', FALSE, TRUE, 'Export tax rate', '2024-01-01');

-- Create indexes for better performance (MySQL compatible)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE table_name = 'system_settings' 
     AND index_name = 'idx_system_settings_key_category' 
     AND table_schema = DATABASE()) = 0,
    'ALTER TABLE system_settings ADD INDEX idx_system_settings_key_category (setting_key, category)',
    'SELECT ''Index idx_system_settings_key_category already exists'' AS message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE table_name = 'tax_rates' 
     AND index_name = 'idx_tax_rates_type_active' 
     AND table_schema = DATABASE()) = 0,
    'ALTER TABLE tax_rates ADD INDEX idx_tax_rates_type_active (type, is_active)',
    'SELECT ''Index idx_tax_rates_type_active already exists'' AS message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE table_name = 'currencies' 
     AND index_name = 'idx_currencies_code_active' 
     AND table_schema = DATABASE()) = 0,
    'ALTER TABLE currencies ADD INDEX idx_currencies_code_active (code, is_active)',
    'SELECT ''Index idx_currencies_code_active already exists'' AS message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add settings for document numbering
INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, category, description) VALUES
  ('quote_prefix', 'QT', 'string', 'numbering', 'Quote number prefix'),
  ('invoice_prefix', 'INV', 'string', 'numbering', 'Invoice number prefix'),
  ('order_prefix', 'SO', 'string', 'numbering', 'Sales order number prefix'),
  ('purchase_prefix', 'PO', 'string', 'numbering', 'Purchase order number prefix'),
  ('receipt_prefix', 'GRN', 'string', 'numbering', 'Goods receipt note prefix'),
  ('adjustment_prefix', 'ADJ', 'string', 'numbering', 'Stock adjustment prefix'),
  ('transfer_prefix', 'TRF', 'string', 'numbering', 'Stock transfer prefix'),
  ('reset_numbering_yearly', 'true', 'boolean', 'numbering', 'Reset document numbering each year'),
  ('number_padding', '4', 'number', 'numbering', 'Minimum digits for document numbers'),
  ('date_format', 'Y-m-d', 'string', 'display', 'Date format for display'),
  ('time_format', 'H:i:s', 'string', 'display', 'Time format for display'),
  ('timezone', 'Africa/Cairo', 'string', 'display', 'Default timezone');
