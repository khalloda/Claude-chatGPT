-- Create notifications table
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') NOT NULL DEFAULT 'info',
  `channel` enum('in_app','email','sms','webhook') NOT NULL DEFAULT 'in_app',
  `target_type` enum('all','user','role','group') NOT NULL DEFAULT 'all',
  `target_id` int UNSIGNED DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_by` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_target` (`target_type`,`target_id`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_scheduled` (`scheduled_at`),
  KEY `idx_active` (`is_active`),
  KEY `idx_type` (`type`),
  KEY `idx_channel` (`channel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create notification preferences table
CREATE TABLE IF NOT EXISTS `notification_preferences` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `channel` enum('in_app','email','sms','webhook') NOT NULL,
  `type` enum('info','success','warning','error') NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_channel_type` (`user_id`,`channel`,`type`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create notification templates table
CREATE TABLE IF NOT EXISTS `notification_templates` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `title_template` varchar(500) NOT NULL,
  `message_template` text NOT NULL,
  `type` enum('info','success','warning','error') NOT NULL DEFAULT 'info',
  `channel` enum('in_app','email','sms','webhook') NOT NULL DEFAULT 'in_app',
  `variables` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`),
  KEY `idx_active` (`is_active`),
  KEY `idx_type` (`type`),
  KEY `idx_channel` (`channel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Insert default notification templates
INSERT INTO `notification_templates` (`name`, `slug`, `title_template`, `message_template`, `type`, `channel`) VALUES
('Low Stock Alert', 'low_stock_alert', 'Low Stock Alert: {product_name}', 'Product {product_name} is running low on stock. Current quantity: {current_qty}, Reorder level: {reorder_level}', 'warning', 'in_app'),
('Order Status Update', 'order_status_update', 'Order {order_reference} Status Update', 'Your order {order_reference} status has been updated to {status}', 'info', 'in_app'),
('Payment Received', 'payment_received', 'Payment Received', 'Payment of {amount} has been received for invoice {invoice_reference}', 'success', 'in_app'),
('System Maintenance', 'system_maintenance', 'System Maintenance Notice', 'Scheduled system maintenance will occur on {date} from {start_time} to {end_time}', 'info', 'in_app'),
('New User Registration', 'new_user_registration', 'New User Registration', 'A new user {user_name} has registered with email {user_email}', 'info', 'in_app');
