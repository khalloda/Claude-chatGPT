-- Database Security Setup Script
-- T007 - Database Security Audit and Hardening
-- Execute as database administrator (root user)

-- ================================================================
-- 1. DATABASE USER SECURITY AUDIT AND SETUP
-- ================================================================

-- Display current database users for audit
SELECT 'CURRENT DATABASE USERS AUDIT' as step;
SELECT User, Host, account_locked, password_expired, 
       ssl_type, max_connections, max_queries_per_hour
FROM mysql.user 
WHERE User NOT IN ('mysql.sys', 'mysql.session', 'mysql.infoschema')
ORDER BY User, Host;

-- Check global privileges that should be restricted
SELECT 'CHECKING GLOBAL PRIVILEGES' as step;
SELECT User, Host, Super_priv, File_priv, Process_priv, 
       Reload_priv, Shutdown_priv, Create_user_priv
FROM mysql.user 
WHERE User NOT IN ('mysql.sys', 'mysql.session', 'mysql.infoschema', 'root')
  AND (Super_priv = 'Y' OR File_priv = 'Y' OR Process_priv = 'Y' 
       OR Reload_priv = 'Y' OR Shutdown_priv = 'Y' OR Create_user_priv = 'Y');

-- ================================================================
-- 2. CREATE SECURE DATABASE USERS
-- ================================================================

-- Variables for database and user configuration
SET @db_name = 'spare_parts_db';

-- Create application user with minimal privileges
SELECT 'CREATING APPLICATION USER' as step;
DROP USER IF EXISTS 'spare_parts_app'@'localhost';
CREATE USER 'spare_parts_app'@'localhost' 
IDENTIFIED WITH caching_sha2_password BY 'CHANGE_THIS_STRONG_PASSWORD'
REQUIRE SSL
PASSWORD EXPIRE INTERVAL 90 DAY
FAILED_LOGIN_ATTEMPTS 5
PASSWORD_LOCK_TIME 10;

-- Grant minimal application privileges
GRANT SELECT, INSERT, UPDATE, DELETE ON spare_parts_db.* TO 'spare_parts_app'@'localhost';
GRANT EXECUTE ON spare_parts_db.* TO 'spare_parts_app'@'localhost';

-- Create read-only reporting user
SELECT 'CREATING READ-ONLY USER' as step;
DROP USER IF EXISTS 'spare_parts_readonly'@'localhost';
CREATE USER 'spare_parts_readonly'@'localhost'
IDENTIFIED WITH caching_sha2_password BY 'CHANGE_THIS_READONLY_PASSWORD'
REQUIRE SSL
PASSWORD EXPIRE INTERVAL 90 DAY
FAILED_LOGIN_ATTEMPTS 3
PASSWORD_LOCK_TIME 5;

-- Grant read-only privileges
GRANT SELECT ON spare_parts_db.* TO 'spare_parts_readonly'@'localhost';

-- Create backup user with specific privileges
SELECT 'CREATING BACKUP USER' as step;
DROP USER IF EXISTS 'spare_parts_backup'@'localhost';
CREATE USER 'spare_parts_backup'@'localhost'
IDENTIFIED WITH caching_sha2_password BY 'CHANGE_THIS_BACKUP_PASSWORD'
REQUIRE SSL
PASSWORD EXPIRE INTERVAL 180 DAY;

-- Grant backup-specific privileges
GRANT SELECT, SHOW VIEW, TRIGGER, LOCK TABLES ON spare_parts_db.* TO 'spare_parts_backup'@'localhost';
GRANT RELOAD, PROCESS ON *.* TO 'spare_parts_backup'@'localhost';

-- Create monitoring user for health checks
SELECT 'CREATING MONITORING USER' as step;
DROP USER IF EXISTS 'spare_parts_monitor'@'localhost';
CREATE USER 'spare_parts_monitor'@'localhost'
IDENTIFIED WITH caching_sha2_password BY 'CHANGE_THIS_MONITOR_PASSWORD'
REQUIRE SSL
PASSWORD EXPIRE INTERVAL 365 DAY;

-- Grant minimal monitoring privileges
GRANT PROCESS, REPLICATION CLIENT ON *.* TO 'spare_parts_monitor'@'localhost';
GRANT SELECT ON performance_schema.* TO 'spare_parts_monitor'@'localhost';
GRANT SELECT ON information_schema.* TO 'spare_parts_monitor'@'localhost';

-- ================================================================
-- 3. SSL/TLS SECURITY CONFIGURATION
-- ================================================================

SELECT 'CONFIGURING SSL/TLS SECURITY' as step;

-- Check SSL status
SELECT @@have_ssl as ssl_available, 
       @@ssl_cert as ssl_cert_file,
       @@ssl_key as ssl_key_file,
       @@ssl_ca as ssl_ca_file;

-- Show SSL status variables
SHOW STATUS LIKE 'Ssl_%';

-- Require SSL for all spare_parts users
SELECT 'ENFORCING SSL FOR ALL SPARE PARTS USERS' as step;
ALTER USER 'spare_parts_app'@'localhost' REQUIRE SSL;
ALTER USER 'spare_parts_readonly'@'localhost' REQUIRE SSL;
ALTER USER 'spare_parts_backup'@'localhost' REQUIRE SSL;
ALTER USER 'spare_parts_monitor'@'localhost' REQUIRE SSL;

-- ================================================================
-- 4. DATABASE AUDIT LOGGING SETUP
-- ================================================================

SELECT 'SETTING UP DATABASE AUDIT LOGGING' as step;

-- Create audit log tables
USE spare_parts_db;

CREATE TABLE IF NOT EXISTS database_audit_log (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    operation ENUM('INSERT', 'UPDATE', 'DELETE', 'SELECT', 'LOGIN', 'LOGOUT') NOT NULL,
    table_name VARCHAR(64) NOT NULL,
    record_id INT UNSIGNED NULL,
    user_id INT UNSIGNED NULL,
    user_email VARCHAR(191) NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    request_uri VARCHAR(500) NULL,
    execution_time_ms INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    INDEX idx_table_record (table_name, record_id),
    INDEX idx_user (user_id),
    INDEX idx_user_email (user_email),
    INDEX idx_operation (operation),
    INDEX idx_created_at (created_at),
    INDEX idx_ip_address (ip_address),
    INDEX idx_execution_time (execution_time_ms)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Database operation audit log';

CREATE TABLE IF NOT EXISTS security_audit_log (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    event_type VARCHAR(64) NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    description TEXT NOT NULL,
    details JSON NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    user_id INT UNSIGNED NULL,
    session_id VARCHAR(128) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    INDEX idx_event_type (event_type),
    INDEX idx_severity (severity),
    INDEX idx_created_at (created_at),
    INDEX idx_ip_address (ip_address),
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Security events audit log';

-- Create stored procedures for audit log maintenance
DELIMITER //

CREATE OR REPLACE PROCEDURE CleanupAuditLogs(IN retention_days INT)
BEGIN
    DECLARE old_date DATE DEFAULT DATE_SUB(CURDATE(), INTERVAL retention_days DAY);
    DECLARE affected_rows INT DEFAULT 0;
    
    -- Archive old audit logs before deletion (optional)
    SET SQL_SAFE_UPDATES = 0;
    
    -- Delete old database audit logs
    DELETE FROM database_audit_log WHERE created_at < old_date;
    SELECT ROW_COUNT() INTO affected_rows;
    
    -- Delete old security audit logs  
    DELETE FROM security_audit_log WHERE created_at < old_date;
    SET affected_rows = affected_rows + ROW_COUNT();
    
    -- Optimize tables after cleanup
    OPTIMIZE TABLE database_audit_log;
    OPTIMIZE TABLE security_audit_log;
    
    SET SQL_SAFE_UPDATES = 1;
    
    SELECT CONCAT('Cleaned up ', affected_rows, ' audit log records older than ', retention_days, ' days') as result;
END //

CREATE OR REPLACE PROCEDURE GetSecuritySummary(IN days_back INT)
BEGIN
    DECLARE start_date DATETIME DEFAULT DATE_SUB(NOW(), INTERVAL days_back DAY);
    
    -- Security events summary
    SELECT 
        'SECURITY_EVENTS_SUMMARY' as report_type,
        event_type,
        severity,
        COUNT(*) as event_count,
        COUNT(DISTINCT ip_address) as unique_ips,
        MIN(created_at) as first_event,
        MAX(created_at) as last_event
    FROM security_audit_log
    WHERE created_at >= start_date
    GROUP BY event_type, severity
    ORDER BY event_count DESC;
    
    -- Database operations summary
    SELECT 
        'DATABASE_OPERATIONS_SUMMARY' as report_type,
        operation,
        table_name,
        COUNT(*) as operation_count,
        COUNT(DISTINCT user_email) as unique_users,
        AVG(execution_time_ms) as avg_execution_time_ms
    FROM database_audit_log
    WHERE created_at >= start_date
    GROUP BY operation, table_name
    ORDER BY operation_count DESC;
    
    -- Top users by activity
    SELECT 
        'TOP_USERS_BY_ACTIVITY' as report_type,
        user_email,
        COUNT(*) as total_operations,
        COUNT(DISTINCT table_name) as tables_accessed,
        COUNT(DISTINCT ip_address) as ip_addresses,
        MAX(created_at) as last_activity
    FROM database_audit_log
    WHERE created_at >= start_date AND user_email IS NOT NULL
    GROUP BY user_email
    ORDER BY total_operations DESC
    LIMIT 20;
END //

DELIMITER ;

-- ================================================================
-- 5. DATABASE SECURITY CONSTRAINTS AND RULES
-- ================================================================

SELECT 'APPLYING SECURITY CONSTRAINTS' as step;

-- Enable strict mode for data integrity
SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';

-- Set secure defaults for key system variables
SET GLOBAL local_infile = OFF;
SET GLOBAL secure_file_priv = '/secure/mysql-secure-file-priv/';

-- Configure connection and query limits
SET GLOBAL max_connections = 200;
SET GLOBAL max_user_connections = 50;
SET GLOBAL max_connect_errors = 10;
SET GLOBAL connect_timeout = 10;
SET GLOBAL interactive_timeout = 300;
SET GLOBAL wait_timeout = 300;

-- Configure query security
SET GLOBAL max_allowed_packet = 16777216; -- 16MB
SET GLOBAL max_execution_time = 30000; -- 30 seconds

-- ================================================================
-- 6. VALIDATE SECURITY CONFIGURATION
-- ================================================================

SELECT 'VALIDATING SECURITY CONFIGURATION' as step;

-- Verify user creation and SSL requirements
SELECT 'USER_VALIDATION' as check_type,
       User, Host, ssl_type, account_locked, password_expired,
       password_lifetime, failed_login_attempts, password_lock_time
FROM mysql.user 
WHERE User LIKE 'spare_parts_%'
ORDER BY User;

-- Check user privileges
SELECT 'PRIVILEGE_VALIDATION' as check_type;
SHOW GRANTS FOR 'spare_parts_app'@'localhost';
SHOW GRANTS FOR 'spare_parts_readonly'@'localhost';
SHOW GRANTS FOR 'spare_parts_backup'@'localhost';
SHOW GRANTS FOR 'spare_parts_monitor'@'localhost';

-- Verify audit tables exist
SELECT 'AUDIT_TABLE_VALIDATION' as check_type,
       TABLE_NAME, TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'spare_parts_db' 
  AND TABLE_NAME IN ('database_audit_log', 'security_audit_log');

-- Check SSL configuration
SELECT 'SSL_VALIDATION' as check_type,
       @@have_ssl as ssl_enabled,
       @@require_secure_transport as require_ssl_transport;

-- Show security-related system variables
SELECT 'SECURITY_VARIABLES' as check_type;
SHOW VARIABLES WHERE Variable_name IN (
    'local_infile', 'secure_file_priv', 'max_connections', 
    'max_user_connections', 'max_connect_errors', 'connect_timeout',
    'interactive_timeout', 'wait_timeout', 'max_allowed_packet'
);

-- ================================================================
-- 7. FLUSH PRIVILEGES AND FINALIZE
-- ================================================================

FLUSH PRIVILEGES;

SELECT 'DATABASE SECURITY HARDENING COMPLETED' as status,
       NOW() as completion_time;

-- ================================================================
-- 8. SECURITY RECOMMENDATIONS SUMMARY
-- ================================================================

SELECT 'SECURITY_RECOMMENDATIONS' as info_type, '
CRITICAL ACTIONS REQUIRED AFTER RUNNING THIS SCRIPT:

1. CHANGE DEFAULT PASSWORDS:
   - spare_parts_app: CHANGE_THIS_STRONG_PASSWORD
   - spare_parts_readonly: CHANGE_THIS_READONLY_PASSWORD  
   - spare_parts_backup: CHANGE_THIS_BACKUP_PASSWORD
   - spare_parts_monitor: CHANGE_THIS_MONITOR_PASSWORD

2. UPDATE APPLICATION CONFIGURATION:
   - Update .env file with new database credentials
   - Configure SSL certificate paths in application
   - Test SSL connectivity from application

3. CONFIGURE SSL CERTIFICATES:
   - Ensure MySQL server has valid SSL certificates
   - Verify certificate paths in MySQL configuration
   - Test SSL connections from application server

4. SET UP BACKUP ENCRYPTION:
   - Configure GPG keys for backup encryption
   - Test encrypted backup and restore procedures
   - Set up automated backup monitoring

5. CONFIGURE SECURITY MONITORING:
   - Set up automated security monitoring cron jobs
   - Configure email alerts for security events
   - Test security anomaly detection

6. SCHEDULE MAINTENANCE:
   - Schedule regular audit log cleanup
   - Plan periodic security audits
   - Update security procedures documentation

VERIFY SECURITY:
- Test application connectivity with new users
- Verify SSL encryption is working
- Test backup and restore procedures
- Review audit logs are being populated
' as recommendations;