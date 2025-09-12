-- RBAC (Role-Based Access Control) Tables Migration
-- Creates roles, permissions, and junction tables for user management

-- Roles table
CREATE TABLE IF NOT EXISTS roles (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL,
  description TEXT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY idx_roles_slug (slug),
  KEY idx_roles_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permissions table
CREATE TABLE IF NOT EXISTS permissions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(150) NOT NULL,
  slug VARCHAR(150) NOT NULL,
  description TEXT NULL,
  category VARCHAR(50) DEFAULT 'general',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY idx_permissions_slug (slug),
  KEY idx_permissions_category (category),
  KEY idx_permissions_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Role-Permission junction table
CREATE TABLE IF NOT EXISTS role_permissions (
  role_id INT UNSIGNED NOT NULL,
  permission_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (role_id, permission_id),
  KEY idx_role_permissions_role (role_id),
  KEY idx_role_permissions_permission (permission_id),
  CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE,
  CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User-Role junction table
CREATE TABLE IF NOT EXISTS user_roles (
  user_id INT UNSIGNED NOT NULL,
  role_id INT UNSIGNED NOT NULL,
  assigned_by INT UNSIGNED NULL,
  assigned_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at TIMESTAMP NULL,
  PRIMARY KEY (user_id, role_id),
  KEY idx_user_roles_user (user_id),
  KEY idx_user_roles_role (role_id),
  KEY idx_user_roles_assigned_by (assigned_by),
  KEY idx_user_roles_expires (expires_at),
  CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_user_roles_role FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE,
  CONSTRAINT fk_user_roles_assigned_by FOREIGN KEY (assigned_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add status column to users table for account management (MySQL doesn't support IF NOT EXISTS for columns)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'users' 
     AND column_name = 'status' 
     AND table_schema = DATABASE()) = 0,
    'ALTER TABLE users ADD COLUMN status ENUM(''active'', ''inactive'', ''suspended'', ''pending'') DEFAULT ''active'' AFTER role',
    'SELECT ''Column status already exists'' AS message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'users' 
     AND column_name = 'last_login_at' 
     AND table_schema = DATABASE()) = 0,
    'ALTER TABLE users ADD COLUMN last_login_at TIMESTAMP NULL AFTER status',
    'SELECT ''Column last_login_at already exists'' AS message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'users' 
     AND column_name = 'updated_at' 
     AND table_schema = DATABASE()) = 0,
    'ALTER TABLE users ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER last_login_at',
    'SELECT ''Column updated_at already exists'' AS message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create indexes on users table (with proper IF NOT EXISTS handling)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE table_name = 'users' 
     AND index_name = 'idx_users_status' 
     AND table_schema = DATABASE()) = 0,
    'ALTER TABLE users ADD INDEX idx_users_status (status)',
    'SELECT ''Index idx_users_status already exists'' AS message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE table_name = 'users' 
     AND index_name = 'idx_users_role' 
     AND table_schema = DATABASE()) = 0,
    'ALTER TABLE users ADD INDEX idx_users_role (role)',
    'SELECT ''Index idx_users_role already exists'' AS message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE table_name = 'users' 
     AND index_name = 'idx_users_last_login' 
     AND table_schema = DATABASE()) = 0,
    'ALTER TABLE users ADD INDEX idx_users_last_login (last_login_at)',
    'SELECT ''Index idx_users_last_login already exists'' AS message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Seed core permissions
INSERT IGNORE INTO permissions (id, name, slug, description, category) VALUES
  (1, 'Manage Users', 'users.manage', 'Create, edit, and delete users', 'user_management'),
  (2, 'View Users', 'users.view', 'View user listings and profiles', 'user_management'),
  (3, 'Manage Roles', 'roles.manage', 'Create, edit, and delete roles', 'user_management'),
  (4, 'View Roles', 'roles.view', 'View role listings', 'user_management'),
  (5, 'Manage Permissions', 'permissions.manage', 'Create, edit, and delete permissions', 'user_management'),
  (6, 'View Permissions', 'permissions.view', 'View permission listings', 'user_management'),
  (7, 'View Reports', 'reports.view', 'Access system reports', 'reporting'),
  (8, 'Manage Sales', 'sales.manage', 'Create and manage sales transactions', 'sales'),
  (9, 'View Sales', 'sales.view', 'View sales data and reports', 'sales'),
  (10, 'Manage Purchasing', 'purchasing.manage', 'Create and manage purchase orders', 'purchasing'),
  (11, 'View Purchasing', 'purchasing.view', 'View purchasing data and reports', 'purchasing'),
  (12, 'Manage Inventory', 'inventory.manage', 'Manage stock levels and movements', 'inventory'),
  (13, 'View Inventory', 'inventory.view', 'View inventory data and reports', 'inventory'),
  (14, 'Manage Settings', 'settings.manage', 'Modify system settings', 'administration'),
  (15, 'Manage Customers', 'customers.manage', 'Create, edit, and delete customers', 'crm'),
  (16, 'View Customers', 'customers.view', 'View customer data', 'crm'),
  (17, 'Manage Suppliers', 'suppliers.manage', 'Create, edit, and delete suppliers', 'purchasing'),
  (18, 'View Suppliers', 'suppliers.view', 'View supplier data', 'purchasing'),
  (19, 'Manage Products', 'products.manage', 'Create, edit, and delete products', 'catalog'),
  (20, 'View Products', 'products.view', 'View product catalog', 'catalog');

-- Seed default roles
INSERT IGNORE INTO roles (id, name, slug, description) VALUES
  (1, 'Super Administrator', 'super_admin', 'Full system access with all permissions'),
  (2, 'Administrator', 'admin', 'Administrative access to most system functions'),
  (3, 'Manager', 'manager', 'Managerial access to core business functions'),
  (4, 'Sales Staff', 'sales_staff', 'Access to sales and customer management functions'),
  (5, 'Inventory Staff', 'inventory_staff', 'Access to inventory and warehouse functions'),
  (6, 'Viewer', 'viewer', 'Read-only access to system data');

-- Assign permissions to Super Administrator (all permissions)
INSERT IGNORE INTO role_permissions (role_id, permission_id)
  SELECT 1, p.id FROM permissions p;

-- Assign permissions to Administrator (all except user management)
INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES
  (2, 2), (2, 4), (2, 6), (2, 7), (2, 8), (2, 9), (2, 10), (2, 11), 
  (2, 12), (2, 13), (2, 14), (2, 15), (2, 16), (2, 17), (2, 18), (2, 19), (2, 20);

-- Assign permissions to Manager (core business functions)
INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES
  (3, 2), (3, 4), (3, 6), (3, 7), (3, 8), (3, 9), (3, 10), (3, 11), 
  (3, 12), (3, 13), (3, 15), (3, 16), (3, 17), (3, 18), (3, 19), (3, 20);

-- Assign permissions to Sales Staff
INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES
  (4, 7), (4, 8), (4, 9), (4, 13), (4, 15), (4, 16), (4, 20);

-- Assign permissions to Inventory Staff
INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES
  (5, 7), (5, 12), (5, 13), (5, 17), (5, 18), (5, 19), (5, 20);

-- Assign permissions to Viewer (read-only)
INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES
  (6, 2), (6, 4), (6, 6), (6, 7), (6, 9), (6, 11), (6, 13), (6, 16), (6, 18), (6, 20);

-- Migrate existing users to RBAC system
-- Assign Super Administrator role to users with 'admin' role
INSERT IGNORE INTO user_roles (user_id, role_id, assigned_by)
  SELECT u.id, 1, u.id FROM users u WHERE u.role = 'admin';

-- Assign Manager role to users with 'manager' role  
INSERT IGNORE INTO user_roles (user_id, role_id, assigned_by)
  SELECT u.id, 3, (SELECT id FROM users WHERE role = 'admin' LIMIT 1) FROM users u WHERE u.role = 'manager';

-- Assign Sales Staff role to users with 'staff' role
INSERT IGNORE INTO user_roles (user_id, role_id, assigned_by)
  SELECT u.id, 4, (SELECT id FROM users WHERE role = 'admin' LIMIT 1) FROM users u WHERE u.role = 'staff';
