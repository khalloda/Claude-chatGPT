-- RBAC schema for user/role/permission management (pending migration)

CREATE TABLE IF NOT EXISTS roles (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS permissions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(150) NOT NULL,
  slug VARCHAR(150) NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS role_permissions (
  role_id INT UNSIGNED NOT NULL,
  permission_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (role_id, permission_id),
  KEY idx_rp_perm (permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_roles (
  user_id INT UNSIGNED NOT NULL,
  role_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id, role_id),
  KEY idx_ur_role (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed base permissions
INSERT IGNORE INTO permissions (id, name, slug) VALUES
  (1,'Manage Users','users.manage'),
  (2,'Manage Roles','roles.manage'),
  (3,'Manage Permissions','permissions.manage'),
  (4,'View Reports','reports.view'),
  (5,'Manage Sales','sales.manage'),
  (6,'View Sales','sales.view'),
  (7,'Manage Purchasing','purchasing.manage'),
  (8,'View Purchasing','purchasing.view'),
  (9,'Manage Inventory','inventory.manage'),
  (10,'View Inventory','inventory.view'),
  (11,'Manage Settings','settings.manage');

-- Seed roles
INSERT IGNORE INTO roles (id, name, slug) VALUES
  (1,'Administrator','admin'),
  (2,'Manager','manager'),
  (3,'Staff','staff');

-- Grant all to Administrator (mirror legacy users.role='admin')
INSERT IGNORE INTO role_permissions (role_id, permission_id)
  SELECT 1, p.id FROM permissions p;

-- Manager: view+manage core (not permissions)
INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES
  (2,4),(2,5),(2,6),(2,7),(2,8),(2,9),(2,10),(2,11),(2,1),(2,2);

-- Staff: view only
INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES
  (3,4),(3,6),(3,8),(3,10);

