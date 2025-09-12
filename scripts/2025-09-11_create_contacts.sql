-- Pending Migration: Contacts linked to customers

CREATE TABLE IF NOT EXISTS contacts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  customer_id INT UNSIGNED NULL,
  name VARCHAR(191) NOT NULL,
  email VARCHAR(191) NULL,
  phone VARCHAR(50) NULL,
  job_title VARCHAR(191) NULL,
  note TEXT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_contacts_customer (customer_id),
  KEY idx_contacts_name_email (name, email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Optional FK (enable if desired)
-- ALTER TABLE contacts ADD CONSTRAINT fk_contacts_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL ON UPDATE CASCADE;

