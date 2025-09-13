-- MySQL dump 10.13  Distrib 8.0.43, for Win64 (x86_64)
--
-- Host: localhost    Database: chatgpt2_mi
-- ------------------------------------------------------
-- Server version	9.1.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_log`
--

DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `actor` varchar(191) NOT NULL,
  `action` varchar(64) NOT NULL,
  `entity_type` varchar(64) NOT NULL,
  `entity_id` int unsigned NOT NULL,
  `meta` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_entity` (`entity_type`,`entity_id`),
  KEY `idx_action` (`action`),
  KEY `idx_activity_log_entity_action_date` (`entity_type`,`entity_id`,`action`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_log`
--

LOCK TABLES `activity_log` WRITE;
/*!40000 ALTER TABLE `activity_log` DISABLE KEYS */;
INSERT INTO `activity_log` VALUES (1,'admin@example.com','ap.add','purchase_invoice',1,'{\"payment_id\":1,\"amount\":650,\"method\":\"bank\",\"reference\":\"TR56775\"}','2025-08-31 08:10:00'),(2,'admin@example.com','ap.add','purchase_invoice',1,'{\"payment_id\":2,\"amount\":1000,\"method\":\"bank\",\"reference\":\"TR89885\"}','2025-08-31 08:13:06'),(3,'admin@example.com','created','user',2,'{\"email\":\"khaled.h87@gmail.com\",\"role\":\"staff\",\"status\":\"active\"}','2025-09-12 16:56:01');
/*!40000 ALTER TABLE `activity_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int unsigned DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_slug` (`slug`),
  KEY `idx_categories_parent` (`parent_id`),
  CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,NULL,'Body Parts','body-parts','2025-08-29 15:28:25',NULL),(2,NULL,'Paints','paints','2025-08-29 15:55:21',NULL),(3,NULL,'Electronics','electronics','2025-08-29 15:55:38',NULL);
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cogs_entries`
--

DROP TABLE IF EXISTS `cogs_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cogs_entries` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `warehouse_id` int unsigned NOT NULL,
  `qty` int NOT NULL,
  `unit_cost` decimal(12,4) NOT NULL,
  `line_cost` decimal(14,4) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `invoice_id` (`invoice_id`),
  KEY `product_id` (`product_id`),
  KEY `warehouse_id` (`warehouse_id`),
  KEY `idx_cogs_entries_invoice_product` (`invoice_id`,`product_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cogs_entries`
--

LOCK TABLES `cogs_entries` WRITE;
/*!40000 ALTER TABLE `cogs_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `cogs_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contacts`
--

DROP TABLE IF EXISTS `contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contacts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int unsigned DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `email` varchar(191) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `job_title` varchar(191) DEFAULT NULL,
  `note` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contacts_customer` (`customer_id`),
  KEY `idx_contacts_name_email` (`name`,`email`),
  CONSTRAINT `fk_contacts_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contacts`
--

LOCK TABLES `contacts` WRITE;
/*!40000 ALTER TABLE `contacts` DISABLE KEYS */;
INSERT INTO `contacts` VALUES (1,1,'Doaa Nassar','dnassar@sarieldin.com','0235352424','Manager','Call before proceed','2025-09-12 07:04:04',NULL);
/*!40000 ALTER TABLE `contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `currencies`
--

DROP TABLE IF EXISTS `currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `currencies` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `symbol` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exchange_rate` decimal(10,6) DEFAULT '1.000000',
  `is_base` tinyint(1) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `decimal_places` int DEFAULT '2',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_currencies_code` (`code`),
  KEY `idx_currencies_base` (`is_base`),
  KEY `idx_currencies_active` (`is_active`),
  KEY `idx_currencies_code_active` (`code`,`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `currencies`
--

LOCK TABLES `currencies` WRITE;
/*!40000 ALTER TABLE `currencies` DISABLE KEYS */;
INSERT INTO `currencies` VALUES (1,'EGP','Egyptian Pound','ج.م',1.000000,1,1,2,'2025-09-12 17:01:21','2025-09-12 17:01:21'),(2,'USD','US Dollar','$',0.020491,0,1,2,'2025-09-12 17:01:21','2025-09-12 17:18:23'),(3,'EUR','Euro','€',33.500000,0,1,2,'2025-09-12 17:01:21','2025-09-12 17:01:21'),(4,'GBP','British Pound','£',39.200000,0,1,2,'2025-09-12 17:01:21','2025-09-12 17:01:21'),(5,'SAR','Saudi Riyal','ر.س',8.220000,0,1,2,'2025-09-12 17:01:21','2025-09-12 17:01:21'),(6,'AED','UAE Dirham','د.إ',8.400000,0,1,2,'2025-09-12 17:01:21','2025-09-12 17:01:21');
/*!40000 ALTER TABLE `currencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `address` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_customers_name_email` (`name`,`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (1,'Khaled Mohamed Helmy','01007847333','khelmy@sarieldin.com','KM 28 Cairo Alex Desert Road B 19 - Smart Village\r\nSarieldin & Partners','2025-08-29 15:41:56',NULL);
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_sequences`
--

DROP TABLE IF EXISTS `doc_sequences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `doc_sequences` (
  `prefix` varchar(10) NOT NULL,
  `y` int NOT NULL,
  `last_no` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`prefix`,`y`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_sequences`
--

LOCK TABLES `doc_sequences` WRITE;
/*!40000 ALTER TABLE `doc_sequences` DISABLE KEYS */;
INSERT INTO `doc_sequences` VALUES ('po',2025,20),('q',2025,16);
/*!40000 ALTER TABLE `doc_sequences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exchange_rate_history`
--

DROP TABLE IF EXISTS `exchange_rate_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `exchange_rate_history` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `currency_id` int unsigned NOT NULL,
  `rate` decimal(10,6) NOT NULL,
  `effective_date` date NOT NULL,
  `source` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_exchange_history_currency` (`currency_id`),
  KEY `idx_exchange_history_date` (`effective_date`),
  CONSTRAINT `fk_exchange_history_currency` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exchange_rate_history`
--

LOCK TABLES `exchange_rate_history` WRITE;
/*!40000 ALTER TABLE `exchange_rate_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `exchange_rate_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_ledger`
--

DROP TABLE IF EXISTS `inventory_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_ledger` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int unsigned NOT NULL,
  `warehouse_id` int unsigned NOT NULL,
  `doc_type` enum('receipt','transfer_out','transfer_in','adjustment','sale','sales_return','purchase_return') NOT NULL,
  `doc_id` int unsigned NOT NULL,
  `qty_delta` int NOT NULL,
  `unit_cost` decimal(12,4) NOT NULL,
  `value_delta` decimal(14,4) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `warehouse_id` (`warehouse_id`),
  KEY `doc_type` (`doc_type`),
  KEY `doc_id` (`doc_id`),
  KEY `idx_inventory_ledger_product_date` (`product_id`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_ledger`
--

LOCK TABLES `inventory_ledger` WRITE;
/*!40000 ALTER TABLE `inventory_ledger` DISABLE KEYS */;
INSERT INTO `inventory_ledger` VALUES (1,3,1,'receipt',8,5,1250.0000,6250.0000,'2025-09-02 07:23:35'),(2,3,2,'receipt',9,1,2000.0000,2000.0000,'2025-09-02 08:15:30'),(3,1,2,'receipt',10,1,1500.0000,1500.0000,'2025-09-02 08:15:55'),(4,4,1,'receipt',8,5,100.0000,500.0000,'2025-09-10 17:24:49'),(5,4,1,'receipt',8,5,100.0000,500.0000,'2025-09-10 18:25:17');
/*!40000 ALTER TABLE `inventory_ledger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoice_items`
--

DROP TABLE IF EXISTS `invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `warehouse_id` int unsigned NOT NULL,
  `qty` int unsigned NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `line_total` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_invoice_items_invoice` (`invoice_id`),
  CONSTRAINT `fk_invoice_items_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoice_items`
--

LOCK TABLES `invoice_items` WRITE;
/*!40000 ALTER TABLE `invoice_items` DISABLE KEYS */;
INSERT INTO `invoice_items` VALUES (1,1,2,1,3,650.00,1950.00),(2,1,3,1,1,1050.00,1050.00),(3,1,1,1,2,1500.00,3000.00),(4,2,2,1,1,650.00,650.00),(5,3,4,1,3,150.00,450.00),(6,4,4,1,2,150.00,300.00);
/*!40000 ALTER TABLE `invoice_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoice_payments`
--

DROP TABLE IF EXISTS `invoice_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_payments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` int unsigned NOT NULL,
  `paid_at` datetime NOT NULL,
  `method` varchar(50) NOT NULL,
  `reference` varchar(191) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payments_invoice` (`invoice_id`),
  CONSTRAINT `fk_payments_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoice_payments`
--

LOCK TABLES `invoice_payments` WRITE;
/*!40000 ALTER TABLE `invoice_payments` DISABLE KEYS */;
INSERT INTO `invoice_payments` VALUES (1,1,'2025-08-30 12:46:00','cash','',1000.00,'','2025-08-30 09:46:46'),(2,1,'2025-08-29 12:50:00','cash','',1000.00,'','2025-08-30 10:00:10'),(3,1,'2025-08-30 13:00:00','Wire','',4600.00,'','2025-08-30 10:00:51'),(6,4,'2025-09-12 00:00:00','Cash',NULL,300.00,NULL,'2025-09-12 10:33:21');
/*!40000 ALTER TABLE `invoice_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `inv_no` varchar(32) NOT NULL,
  `sales_order_id` int unsigned NOT NULL,
  `customer_id` int unsigned NOT NULL,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `paid_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` enum('unpaid','partial','paid','void') NOT NULL DEFAULT 'unpaid',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `cogs_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `inv_no` (`inv_no`),
  KEY `idx_invoices_order` (`sales_order_id`),
  KEY `idx_invoices_customer_date_status` (`customer_id`,`created_at`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
INSERT INTO `invoices` VALUES (1,'INV2025-0001',1,1,10.00,6000.00,600.00,6600.00,6600.00,'paid','2025-08-30 09:35:44','2025-08-30 10:00:51',0.00),(2,'INV2025-0006',7,1,10.00,650.00,65.00,715.00,0.00,'unpaid','2025-09-02 11:37:20','2025-09-02 12:26:59',0.00),(3,'INV2025-0012',9,1,20.00,450.00,90.00,540.00,0.00,'','2025-09-11 08:09:45',NULL,0.00),(4,'INV2025-0016',10,1,0.00,300.00,0.00,300.00,300.00,'paid','2025-09-11 10:44:42','2025-09-12 10:33:21',0.00);
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `makes`
--

DROP TABLE IF EXISTS `makes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `makes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_makes_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `makes`
--

LOCK TABLES `makes` WRITE;
/*!40000 ALTER TABLE `makes` DISABLE KEYS */;
INSERT INTO `makes` VALUES (1,'Fiat','fiat','2025-08-29 15:12:05',NULL),(2,'Toyota','toyota','2025-08-29 15:12:18',NULL);
/*!40000 ALTER TABLE `makes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notes`
--

DROP TABLE IF EXISTS `notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `entity_type` enum('quote','sales_order','sales_invoice','purchase_order','purchase_invoice','customer','category','warehouse','product') NOT NULL,
  `entity_id` int unsigned NOT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT '0',
  `body` text NOT NULL,
  `created_by` varchar(191) NOT NULL,
  `created_by_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_entity` (`entity_type`,`entity_id`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notes`
--

LOCK TABLES `notes` WRITE;
/*!40000 ALTER TABLE `notes` DISABLE KEYS */;
INSERT INTO `notes` VALUES (1,'quote',1,0,'This order will be late.','',NULL,'2025-08-30 06:21:59'),(2,'quote',1,1,'Transfer will be on HSBC','',NULL,'2025-08-30 06:22:18'),(3,'sales_invoice',4,1,'Send to client ASAP','',NULL,'2025-09-12 10:31:55'),(4,'sales_invoice',4,1,'Send to Client ASAP','',NULL,'2025-09-12 10:32:40');
/*!40000 ALTER TABLE `notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_preferences`
--

DROP TABLE IF EXISTS `notification_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_preferences` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `channel` enum('in_app','email','sms','webhook') NOT NULL,
  `type` enum('info','success','warning','error') NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_channel_type` (`user_id`,`channel`,`type`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_preferences`
--

LOCK TABLES `notification_preferences` WRITE;
/*!40000 ALTER TABLE `notification_preferences` DISABLE KEYS */;
/*!40000 ALTER TABLE `notification_preferences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_templates`
--

DROP TABLE IF EXISTS `notification_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_templates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_templates`
--

LOCK TABLES `notification_templates` WRITE;
/*!40000 ALTER TABLE `notification_templates` DISABLE KEYS */;
INSERT INTO `notification_templates` VALUES (1,'Low Stock Alert','low_stock_alert','Low Stock Alert: {product_name}','Product {product_name} is running low on stock. Current quantity: {current_qty}, Reorder level: {reorder_level}','warning','in_app',NULL,1,'2025-09-13 06:07:22',NULL),(2,'Order Status Update','order_status_update','Order {order_reference} Status Update','Your order {order_reference} status has been updated to {status}','info','in_app',NULL,1,'2025-09-13 06:07:22',NULL),(3,'Payment Received','payment_received','Payment Received','Payment of {amount} has been received for invoice {invoice_reference}','success','in_app',NULL,1,'2025-09-13 06:07:22',NULL),(4,'System Maintenance','system_maintenance','System Maintenance Notice','Scheduled system maintenance will occur on {date} from {start_time} to {end_time}','info','in_app',NULL,1,'2025-09-13 06:07:22',NULL),(5,'New User Registration','new_user_registration','New User Registration','A new user {user_name} has registered with email {user_email}','info','in_app',NULL,1,'2025-09-13 06:07:22',NULL);
/*!40000 ALTER TABLE `notification_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') NOT NULL DEFAULT 'info',
  `channel` enum('in_app','email','sms','webhook') NOT NULL DEFAULT 'in_app',
  `target_type` enum('all','user','role','group') NOT NULL DEFAULT 'all',
  `target_id` int unsigned DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_by` int unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_target` (`target_type`,`target_id`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_scheduled` (`scheduled_at`),
  KEY `idx_active` (`is_active`),
  KEY `idx_type` (`type`),
  KEY `idx_channel` (`channel`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,'New Item Added','There is a new item added.','info','in_app','all',NULL,0,1,NULL,NULL,1,'2025-09-13 09:58:32',NULL);
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `optimization_log`
--

DROP TABLE IF EXISTS `optimization_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `optimization_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_type` varchar(50) NOT NULL,
  `message` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_event_type` (`event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `optimization_log`
--

LOCK TABLES `optimization_log` WRITE;
/*!40000 ALTER TABLE `optimization_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `optimization_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'general',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_permissions_slug` (`slug`),
  KEY `idx_permissions_category` (`category`),
  KEY `idx_permissions_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'Manage Users','users.manage','Create, edit, and delete users','user_management','2025-09-12 16:48:27','2025-09-12 16:48:27'),(2,'View Users','users.view','View user listings and profiles','user_management','2025-09-12 16:48:27','2025-09-12 16:48:27'),(3,'Manage Roles','roles.manage','Create, edit, and delete roles','user_management','2025-09-12 16:48:27','2025-09-12 16:48:27'),(4,'View Roles','roles.view','View role listings','user_management','2025-09-12 16:48:27','2025-09-12 16:48:27'),(5,'Manage Permissions','permissions.manage','Create, edit, and delete permissions','user_management','2025-09-12 16:48:27','2025-09-12 16:48:27'),(6,'View Permissions','permissions.view','View permission listings','user_management','2025-09-12 16:48:27','2025-09-12 16:48:27'),(7,'View Reports','reports.view','Access system reports','reporting','2025-09-12 16:48:27','2025-09-12 16:48:27'),(8,'Manage Sales','sales.manage','Create and manage sales transactions','sales','2025-09-12 16:48:27','2025-09-12 16:48:27'),(9,'View Sales','sales.view','View sales data and reports','sales','2025-09-12 16:48:27','2025-09-12 16:48:27'),(10,'Manage Purchasing','purchasing.manage','Create and manage purchase orders','purchasing','2025-09-12 16:48:27','2025-09-12 16:48:27'),(11,'View Purchasing','purchasing.view','View purchasing data and reports','purchasing','2025-09-12 16:48:27','2025-09-12 16:48:27'),(12,'Manage Inventory','inventory.manage','Manage stock levels and movements','inventory','2025-09-12 16:48:27','2025-09-12 16:48:27'),(13,'View Inventory','inventory.view','View inventory data and reports','inventory','2025-09-12 16:48:27','2025-09-12 16:48:27'),(14,'Manage Settings','settings.manage','Modify system settings','administration','2025-09-12 16:48:27','2025-09-12 16:48:27'),(15,'Manage Customers','customers.manage','Create, edit, and delete customers','crm','2025-09-12 16:48:27','2025-09-12 16:48:27'),(16,'View Customers','customers.view','View customer data','crm','2025-09-12 16:48:27','2025-09-12 16:48:27'),(17,'Manage Suppliers','suppliers.manage','Create, edit, and delete suppliers','purchasing','2025-09-12 16:48:27','2025-09-12 16:48:27'),(18,'View Suppliers','suppliers.view','View supplier data','purchasing','2025-09-12 16:48:27','2025-09-12 16:48:27'),(19,'Manage Products','products.manage','Create, edit, and delete products','catalog','2025-09-12 16:48:27','2025-09-12 16:48:27'),(20,'View Products','products.view','View product catalog','catalog','2025-09-12 16:48:27','2025-09-12 16:48:27');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_stocks`
--

DROP TABLE IF EXISTS `product_stocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_stocks` (
  `product_id` int unsigned NOT NULL,
  `warehouse_id` int unsigned NOT NULL,
  `qty_on_hand` int unsigned NOT NULL DEFAULT '0',
  `qty_reserved` int unsigned NOT NULL DEFAULT '0',
  `qty_reserved_quote` int unsigned NOT NULL DEFAULT '0',
  `qty_reserved_order` int unsigned NOT NULL DEFAULT '0',
  `avg_cost` decimal(12,4) NOT NULL DEFAULT '0.0000',
  PRIMARY KEY (`product_id`,`warehouse_id`),
  KEY `fk_ps_warehouse` (`warehouse_id`),
  KEY `idx_product_stocks_warehouse_qty` (`warehouse_id`,`qty_on_hand`),
  CONSTRAINT `fk_ps_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ps_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_stocks`
--

LOCK TABLES `product_stocks` WRITE;
/*!40000 ALTER TABLE `product_stocks` DISABLE KEYS */;
INSERT INTO `product_stocks` VALUES (1,1,9,0,0,1,0.0000),(1,2,1,0,0,0,1500.0000),(2,1,9,0,0,1,0.0000),(2,2,1,0,0,0,0.0000),(3,1,14,0,0,1,446.4286),(3,2,1,0,0,0,2000.0000),(4,1,6,0,0,0,100.0000);
/*!40000 ALTER TABLE `product_stocks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(191) NOT NULL,
  `category_id` int unsigned DEFAULT NULL,
  `make_id` int unsigned DEFAULT NULL,
  `model_id` int unsigned DEFAULT NULL,
  `cost` decimal(12,2) NOT NULL DEFAULT '0.00',
  `price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_products_category` (`category_id`),
  KEY `idx_products_make` (`make_id`),
  KEY `idx_products_model` (`model_id`),
  KEY `idx_products_category_make_model` (`category_id`,`make_id`,`model_id`),
  KEY `idx_products_name_code` (`name`,`code`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_products_make` FOREIGN KEY (`make_id`) REFERENCES `makes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_products_model` FOREIGN KEY (`model_id`) REFERENCES `vehicle_models` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'PRD0001','Spoiler',1,1,1,1000.00,1500.00,'2025-08-29 15:28:48',NULL),(2,'PRD0002','Coil',3,1,1,500.00,650.00,'2025-08-29 15:56:03',NULL),(3,'PRD0003','Red Polish',2,1,1,850.00,1050.00,'2025-08-29 15:56:33',NULL),(4,'PRD0004','Sparks Plug',3,2,2,100.00,150.00,'2025-09-10 17:08:02','2025-09-11 08:04:15');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_invoices`
--

DROP TABLE IF EXISTS `purchase_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_invoices` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `pi_no` varchar(32) NOT NULL,
  `purchase_order_id` int unsigned NOT NULL,
  `supplier_id` int unsigned NOT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tax_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `paid_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` enum('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
  PRIMARY KEY (`id`),
  UNIQUE KEY `pi_no` (`pi_no`),
  KEY `idx_pi_po` (`purchase_order_id`),
  KEY `idx_pi_supplier` (`supplier_id`),
  KEY `idx_purchase_invoices_supplier_date_status` (`supplier_id`,`created_at`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_invoices`
--

LOCK TABLES `purchase_invoices` WRITE;
/*!40000 ALTER TABLE `purchase_invoices` DISABLE KEYS */;
INSERT INTO `purchase_invoices` VALUES (1,'PI2025-0001',2,1,1500.00,10.00,150.00,1650.00,'2025-08-30 11:12:24',1650.00,'paid'),(2,'PI2025-0006',3,1,14250.00,10.00,1425.00,15675.00,'2025-09-02 07:23:06',0.00,'unpaid'),(4,'PI2025-0019',10,1,2000.00,10.00,200.00,2200.00,'2025-09-02 08:15:02',0.00,'unpaid'),(5,'PI2025-0018',9,1,1500.00,10.00,150.00,1650.00,'2025-09-02 08:15:50',0.00,'unpaid'),(8,'PI2025-0020',11,1,1000.00,0.00,0.00,1000.00,'2025-09-10 17:13:06',0.00,'unpaid');
/*!40000 ALTER TABLE `purchase_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_order_items`
--

DROP TABLE IF EXISTS `purchase_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_order_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `warehouse_id` int unsigned NOT NULL,
  `qty` int unsigned NOT NULL,
  `received_qty` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `price` decimal(12,2) NOT NULL,
  `line_total` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_poi_po` (`purchase_order_id`),
  KEY `idx_poi_product` (`product_id`),
  KEY `idx_poi_warehouse` (`warehouse_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_order_items`
--

LOCK TABLES `purchase_order_items` WRITE;
/*!40000 ALTER TABLE `purchase_order_items` DISABLE KEYS */;
INSERT INTO `purchase_order_items` VALUES (1,2,1,1,10,0.0000,150.00,1500.00),(2,3,3,1,5,0.0000,1250.00,6250.00),(3,3,3,1,5,0.0000,1250.00,6250.00),(4,9,1,2,1,0.0000,1500.00,1500.00),(5,10,3,2,1,0.0000,2000.00,2000.00),(6,11,4,1,10,10.0000,100.00,1000.00);
/*!40000 ALTER TABLE `purchase_order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_orders` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `po_no` varchar(32) NOT NULL,
  `supplier_id` int unsigned NOT NULL,
  `status` enum('draft','ordered','received','closed') NOT NULL DEFAULT 'draft',
  `tax_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `po_no` (`po_no`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_orders`
--

LOCK TABLES `purchase_orders` WRITE;
/*!40000 ALTER TABLE `purchase_orders` DISABLE KEYS */;
INSERT INTO `purchase_orders` VALUES (2,'PO2025-0001',1,'closed',10.00,1500.00,150.00,1650.00,'2025-08-30 10:59:34'),(3,'PO2025-0006',1,'received',10.00,14250.00,1425.00,15675.00,'2025-09-02 07:22:47'),(6,'PO2025-0015',1,'draft',10.00,1300.00,130.00,1430.00,'2025-09-02 07:59:09'),(9,'PO2025-0018',1,'received',10.00,1500.00,150.00,1650.00,'2025-09-02 08:09:52'),(10,'PO2025-0019',1,'received',10.00,2000.00,200.00,2200.00,'2025-09-02 08:10:22'),(11,'PO2025-0020',1,'received',0.00,1000.00,0.00,1000.00,'2025-09-10 17:09:12');
/*!40000 ALTER TABLE `purchase_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_return_items`
--

DROP TABLE IF EXISTS `purchase_return_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_return_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `purchase_return_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `warehouse_id` int unsigned NOT NULL,
  `qty` int unsigned NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `line_total` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pri_pr` (`purchase_return_id`),
  KEY `idx_pri_prod` (`product_id`),
  KEY `idx_pri_wh` (`warehouse_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_return_items`
--

LOCK TABLES `purchase_return_items` WRITE;
/*!40000 ALTER TABLE `purchase_return_items` DISABLE KEYS */;
INSERT INTO `purchase_return_items` VALUES (1,1,1,1,8,150.00,1200.00),(2,2,4,1,2,100.00,200.00);
/*!40000 ALTER TABLE `purchase_return_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_returns`
--

DROP TABLE IF EXISTS `purchase_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_returns` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `pr_no` varchar(32) NOT NULL,
  `purchase_invoice_id` int unsigned NOT NULL,
  `supplier_id` int unsigned NOT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tax_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pr_no` (`pr_no`),
  KEY `idx_pr_pi` (`purchase_invoice_id`),
  KEY `idx_pr_sup` (`supplier_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_returns`
--

LOCK TABLES `purchase_returns` WRITE;
/*!40000 ALTER TABLE `purchase_returns` DISABLE KEYS */;
INSERT INTO `purchase_returns` VALUES (1,'PR2025-0001',1,1,1200.00,10.00,120.00,1320.00,'2025-08-31 09:50:42'),(2,'PR2025-0006',8,1,200.00,0.00,0.00,200.00,'2025-09-10 18:25:41');
/*!40000 ALTER TABLE `purchase_returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quote_items`
--

DROP TABLE IF EXISTS `quote_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quote_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `quote_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `warehouse_id` int unsigned NOT NULL,
  `qty` int unsigned NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `line_total` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_qi_quote` (`quote_id`),
  KEY `fk_qi_product` (`product_id`),
  KEY `fk_qi_wh` (`warehouse_id`),
  CONSTRAINT `fk_qi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_qi_quote` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_qi_wh` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quote_items`
--

LOCK TABLES `quote_items` WRITE;
/*!40000 ALTER TABLE `quote_items` DISABLE KEYS */;
INSERT INTO `quote_items` VALUES (1,1,2,1,3,650.00,1950.00),(2,1,3,1,1,1050.00,1050.00),(3,1,1,1,2,1500.00,3000.00),(5,3,2,1,1,650.00,650.00),(6,3,3,1,1,1050.00,1050.00),(7,3,1,1,1,1500.00,1500.00),(8,6,1,1,1,1500.00,1500.00),(9,6,2,2,1,650.00,650.00),(10,7,1,1,1,1500.00,1500.00),(11,7,2,1,1,650.00,650.00),(12,8,1,1,1,1500.00,1500.00),(13,9,2,1,1,650.00,650.00),(14,10,2,1,1,650.00,650.00),(15,11,3,1,1,1050.00,1050.00),(16,12,3,1,1,1050.00,1050.00),(17,13,3,1,1,1050.00,1050.00),(20,15,2,1,1,650.00,650.00),(21,16,4,1,3,150.00,450.00),(22,17,4,1,2,150.00,300.00),(23,18,4,1,2,150.00,300.00),(24,19,4,1,2,150.00,300.00),(25,20,4,1,2,150.00,300.00);
/*!40000 ALTER TABLE `quote_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quotes`
--

DROP TABLE IF EXISTS `quotes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quotes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `quote_no` varchar(32) NOT NULL,
  `customer_id` int unsigned NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `tax_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `expires_at` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quote_no` (`quote_no`),
  KEY `idx_quotes_customer` (`customer_id`),
  CONSTRAINT `fk_quotes_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quotes`
--

LOCK TABLES `quotes` WRITE;
/*!40000 ALTER TABLE `quotes` DISABLE KEYS */;
INSERT INTO `quotes` VALUES (1,'Q2025-0001',1,'ordered',10.00,6000.00,600.00,6600.00,'2025-09-04','2025-08-29 16:21:31','2025-08-29 17:26:28'),(3,'Q2025-1844',1,'sent',10.00,3200.00,320.00,3520.00,'2025-09-05','2025-09-02 08:39:30','2025-09-02 10:06:54'),(6,'Q2025-0002',1,'sent',10.00,2150.00,215.00,2365.00,NULL,'2025-09-02 09:31:04','2025-09-02 09:58:03'),(7,'Q2025-0003',1,'sent',10.00,2150.00,215.00,2365.00,NULL,'2025-09-02 09:41:18','2025-09-02 09:58:12'),(8,'Q2025-0004',1,'sent',10.00,1500.00,150.00,1650.00,'2025-09-10','2025-09-02 09:49:27','2025-09-02 09:58:16'),(9,'Q2025-0005',1,'sent',10.00,650.00,65.00,715.00,'2025-09-18','2025-09-02 09:58:58','2025-09-02 10:05:50'),(10,'Q2025-0006',1,'accepted',10.00,650.00,65.00,715.00,'2025-09-27','2025-09-02 10:04:01','2025-09-02 11:37:16'),(11,'Q2025-0007',1,'expired',0.00,1050.00,0.00,1050.00,NULL,'2025-09-02 10:09:39','2025-09-02 10:23:09'),(12,'Q2025-0008',1,'cancelled',0.00,1050.00,0.00,1050.00,NULL,'2025-09-02 10:10:43','2025-09-02 10:16:42'),(13,'Q2025-0009',1,'accepted',0.00,1050.00,0.00,1050.00,NULL,'2025-09-02 10:23:54','2025-09-02 11:01:22'),(15,'Q2025-0011',1,'accepted',10.00,650.00,65.00,715.00,'2025-09-17','2025-09-11 07:50:48','2025-09-11 08:00:09'),(16,'Q2025-0012',1,'accepted',20.00,450.00,90.00,540.00,NULL,'2025-09-11 08:05:50','2025-09-11 08:09:06'),(17,'Q2025-0013',1,'cancelled',10.00,300.00,30.00,330.00,NULL,'2025-09-11 09:06:07','2025-09-11 09:20:39'),(18,'Q2025-0014',1,'cancelled',0.00,300.00,0.00,300.00,NULL,'2025-09-11 09:22:01','2025-09-11 09:22:26'),(19,'Q2025-0015',1,'cancelled',0.00,300.00,0.00,300.00,NULL,'2025-09-11 09:23:24','2025-09-11 09:40:31'),(20,'Q2025-0016',1,'accepted',0.00,300.00,0.00,300.00,NULL,'2025-09-11 09:43:16','2025-09-11 09:43:54');
/*!40000 ALTER TABLE `quotes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `receipts`
--

DROP TABLE IF EXISTS `receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `receipts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `purchase_invoice_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `warehouse_id` int unsigned NOT NULL,
  `qty` int unsigned NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_receipts_pi` (`purchase_invoice_id`),
  KEY `idx_receipts_product` (`product_id`),
  KEY `idx_receipts_wh` (`warehouse_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `receipts`
--

LOCK TABLES `receipts` WRITE;
/*!40000 ALTER TABLE `receipts` DISABLE KEYS */;
INSERT INTO `receipts` VALUES (6,1,1,1,5,150.00,'2025-08-30 11:22:54'),(7,1,1,1,5,150.00,'2025-08-31 07:32:28'),(8,2,3,1,5,1250.00,'2025-09-02 07:23:35'),(9,4,3,2,1,2000.00,'2025-09-02 08:15:30'),(10,5,1,2,1,1500.00,'2025-09-02 08:15:55'),(11,8,4,1,5,100.00,'2025-09-10 17:24:49'),(12,8,4,1,5,100.00,'2025-09-10 18:25:17');
/*!40000 ALTER TABLE `receipts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_permissions` (
  `role_id` int unsigned NOT NULL,
  `permission_id` int unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `idx_role_permissions_role` (`role_id`),
  KEY `idx_role_permissions_permission` (`permission_id`),
  CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES (1,1,'2025-09-12 16:48:27'),(1,2,'2025-09-12 16:48:27'),(1,3,'2025-09-12 16:48:27'),(1,4,'2025-09-12 16:48:27'),(1,5,'2025-09-12 16:48:27'),(1,6,'2025-09-12 16:48:27'),(1,7,'2025-09-12 16:48:27'),(1,8,'2025-09-12 16:48:27'),(1,9,'2025-09-12 16:48:27'),(1,10,'2025-09-12 16:48:27'),(1,11,'2025-09-12 16:48:27'),(1,12,'2025-09-12 16:48:27'),(1,13,'2025-09-12 16:48:27'),(1,14,'2025-09-12 16:48:27'),(1,15,'2025-09-12 16:48:27'),(1,16,'2025-09-12 16:48:27'),(1,17,'2025-09-12 16:48:27'),(1,18,'2025-09-12 16:48:27'),(1,19,'2025-09-12 16:48:27'),(1,20,'2025-09-12 16:48:27'),(2,2,'2025-09-12 16:48:27'),(2,4,'2025-09-12 16:48:27'),(2,6,'2025-09-12 16:48:27'),(2,7,'2025-09-12 16:48:27'),(2,8,'2025-09-12 16:48:27'),(2,9,'2025-09-12 16:48:27'),(2,10,'2025-09-12 16:48:27'),(2,11,'2025-09-12 16:48:27'),(2,12,'2025-09-12 16:48:27'),(2,13,'2025-09-12 16:48:27'),(2,14,'2025-09-12 16:48:27'),(2,15,'2025-09-12 16:48:27'),(2,16,'2025-09-12 16:48:27'),(2,17,'2025-09-12 16:48:27'),(2,18,'2025-09-12 16:48:27'),(2,19,'2025-09-12 16:48:27'),(2,20,'2025-09-12 16:48:27'),(3,2,'2025-09-12 16:48:27'),(3,4,'2025-09-12 16:48:27'),(3,6,'2025-09-12 16:48:27'),(3,7,'2025-09-12 16:48:27'),(3,8,'2025-09-12 16:48:27'),(3,9,'2025-09-12 16:48:27'),(3,10,'2025-09-12 16:48:27'),(3,11,'2025-09-12 16:48:27'),(3,12,'2025-09-12 16:48:27'),(3,13,'2025-09-12 16:48:27'),(3,15,'2025-09-12 16:48:27'),(3,16,'2025-09-12 16:48:27'),(3,17,'2025-09-12 16:48:27'),(3,18,'2025-09-12 16:48:27'),(3,19,'2025-09-12 16:48:27'),(3,20,'2025-09-12 16:48:27'),(4,7,'2025-09-12 16:48:27'),(4,8,'2025-09-12 16:48:27'),(4,9,'2025-09-12 16:48:27'),(4,13,'2025-09-12 16:48:27'),(4,15,'2025-09-12 16:48:27'),(4,16,'2025-09-12 16:48:27'),(4,20,'2025-09-12 16:48:27'),(5,7,'2025-09-12 16:48:27'),(5,12,'2025-09-12 16:48:27'),(5,13,'2025-09-12 16:48:27'),(5,17,'2025-09-12 16:48:27'),(5,18,'2025-09-12 16:48:27'),(5,19,'2025-09-12 16:48:27'),(5,20,'2025-09-12 16:48:27'),(6,2,'2025-09-12 16:48:27'),(6,4,'2025-09-12 16:48:27'),(6,6,'2025-09-12 16:48:27'),(6,7,'2025-09-12 16:48:27'),(6,9,'2025-09-12 16:48:27'),(6,11,'2025-09-12 16:48:27'),(6,13,'2025-09-12 16:48:27'),(6,16,'2025-09-12 16:48:27'),(6,18,'2025-09-12 16:48:27'),(6,20,'2025-09-12 16:48:27');
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_roles_slug` (`slug`),
  KEY `idx_roles_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Super Administrator','super_admin','Full system access with all permissions','2025-09-12 16:48:27','2025-09-12 16:48:27'),(2,'Administrator','admin','Administrative access to most system functions','2025-09-12 16:48:27','2025-09-12 16:48:27'),(3,'Manager','manager','Managerial access to core business functions','2025-09-12 16:48:27','2025-09-12 16:48:27'),(4,'Sales Staff','sales_staff','Access to sales and customer management functions','2025-09-12 16:48:27','2025-09-12 16:48:27'),(5,'Inventory Staff','inventory_staff','Access to inventory and warehouse functions','2025-09-12 16:48:27','2025-09-12 16:48:27'),(6,'Viewer','viewer','Read-only access to system data','2025-09-12 16:48:27','2025-09-12 16:48:27');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_order_items`
--

DROP TABLE IF EXISTS `sales_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_order_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `sales_order_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `warehouse_id` int unsigned NOT NULL,
  `qty` int unsigned NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `line_total` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_soi_so` (`sales_order_id`),
  CONSTRAINT `fk_soi_so` FOREIGN KEY (`sales_order_id`) REFERENCES `sales_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_order_items`
--

LOCK TABLES `sales_order_items` WRITE;
/*!40000 ALTER TABLE `sales_order_items` DISABLE KEYS */;
INSERT INTO `sales_order_items` VALUES (1,1,2,1,3,650.00,1950.00),(2,1,3,1,1,1050.00,1050.00),(3,1,1,1,2,1500.00,3000.00),(4,6,3,1,1,1050.00,1050.00),(5,7,2,1,1,650.00,650.00),(6,8,2,1,1,650.00,650.00),(7,9,4,1,3,150.00,450.00),(8,10,4,1,2,150.00,300.00);
/*!40000 ALTER TABLE `sales_order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_orders`
--

DROP TABLE IF EXISTS `sales_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_orders` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `so_no` varchar(32) NOT NULL,
  `quote_id` int unsigned NOT NULL,
  `customer_id` int unsigned NOT NULL,
  `status` enum('open','closed','cancelled') NOT NULL DEFAULT 'open',
  `tax_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `so_no` (`so_no`),
  KEY `idx_so_quote` (`quote_id`),
  CONSTRAINT `fk_so_quote` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_orders`
--

LOCK TABLES `sales_orders` WRITE;
/*!40000 ALTER TABLE `sales_orders` DISABLE KEYS */;
INSERT INTO `sales_orders` VALUES (1,'SO2025-0001',1,1,'closed',10.00,6000.00,600.00,6600.00,'2025-08-29 17:26:28','2025-09-02 12:28:50'),(6,'SO2025-0009',13,1,'',0.00,1050.00,0.00,1050.00,'2025-09-02 11:01:22',NULL),(7,'SO2025-0006',10,1,'',10.00,650.00,65.00,715.00,'2025-09-02 11:37:16',NULL),(8,'SO2025-0011',15,1,'',10.00,650.00,65.00,715.00,'2025-09-11 08:00:09',NULL),(9,'SO2025-0012',16,1,'',20.00,450.00,90.00,540.00,'2025-09-11 08:09:06',NULL),(10,'SO2025-0016',20,1,'',0.00,300.00,0.00,300.00,'2025-09-11 09:43:54',NULL);
/*!40000 ALTER TABLE `sales_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_return_items`
--

DROP TABLE IF EXISTS `sales_return_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_return_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `sales_return_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `warehouse_id` int unsigned NOT NULL,
  `qty` int unsigned NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `line_total` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sri_sr` (`sales_return_id`),
  KEY `idx_sri_product` (`product_id`),
  KEY `idx_sri_warehouse` (`warehouse_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_return_items`
--

LOCK TABLES `sales_return_items` WRITE;
/*!40000 ALTER TABLE `sales_return_items` DISABLE KEYS */;
INSERT INTO `sales_return_items` VALUES (1,1,2,1,3,650.00,1950.00);
/*!40000 ALTER TABLE `sales_return_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_returns`
--

DROP TABLE IF EXISTS `sales_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_returns` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `sr_no` varchar(32) NOT NULL,
  `sales_invoice_id` int unsigned NOT NULL,
  `client_id` int unsigned NOT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tax_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sr_no` (`sr_no`),
  KEY `idx_sr_invoice` (`sales_invoice_id`),
  KEY `idx_sr_client` (`client_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_returns`
--

LOCK TABLES `sales_returns` WRITE;
/*!40000 ALTER TABLE `sales_returns` DISABLE KEYS */;
INSERT INTO `sales_returns` VALUES (1,'SR2025-0001',1,1,1950.00,10.00,195.00,2145.00,'2025-08-31 08:56:33');
/*!40000 ALTER TABLE `sales_returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schema_migrations`
--

DROP TABLE IF EXISTS `schema_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `schema_migrations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `checksum` varchar(64) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `filename` (`filename`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schema_migrations`
--

LOCK TABLES `schema_migrations` WRITE;
/*!40000 ALTER TABLE `schema_migrations` DISABLE KEYS */;
INSERT INTO `schema_migrations` VALUES (1,'2025-09-10_drop_duplicate_product_stocks_keys.sql','cf6a4e560bcf868ec26adbb41bcf50876fac5ef806a061633a6b200a81d8cbc5','2025-09-10 16:38:50'),(2,'2025-09-10_create_optimization_log_and_views.sql','b1b071636a7f6e6386ff3cdc2f53a05d75efec2ea8bc687f52731a6d6412dcf5','2025-09-10 16:54:51'),(3,'2025-09-10_idx_activity_log_entity_action_date.sql','cc0324bea843711f8b3660c536865572c91ba0aa1d15f4d35480df8c861a9c4e','2025-09-10 16:54:51'),(4,'2025-09-10_idx_cogs_entries_invoice_product_created.sql','0714918d2f30329a88daae2bdac95766a0246466dc22a5ba834d46bc5ef7b20f','2025-09-10 16:54:51'),(5,'2025-09-10_idx_customers_name_email.sql','cddd5e26b6d6e6d32378789b34ec2adb3891650fa9bc1b37b6f54a304d793ff3','2025-09-10 16:54:51'),(6,'2025-09-10_idx_inventory_ledger_product_date.sql','c45676960e88ed929c8396d5e5f4dc49c18b1f3651f1bc08c3965a88443d231a','2025-09-10 16:54:51'),(7,'2025-09-10_idx_invoices_customer_date_status.sql','14da870ec97918e6d50a9c2466c4e072e2ce958280743af6a089f9e89691e49e','2025-09-10 16:54:51'),(8,'2025-09-10_idx_product_stocks_warehouse_qty.sql','afd3c62a5b8d52b17a5960a03fdd0dbf930e2dcb57e5650da04953961adca9a8','2025-09-10 16:54:51'),(9,'2025-09-10_idx_products_category_make_model.sql','ab7065de1e209934f0a35ab0f26885eae30f13916b3380a28640c0c4bc5bb591','2025-09-10 16:54:51'),(10,'2025-09-10_idx_products_name_code.sql','a63401f8c931faa3f9f7d6f87a85a2030170211bf2b1f85106405206f66faddf','2025-09-10 16:54:51'),(11,'2025-09-10_idx_purchase_invoices_supplier_date_status.sql','5f52056f312af7ac39314961c8adfde33b2c212b97ccc09a9356d0aafd85ae07','2025-09-10 16:54:51'),(12,'2025_09_12_001_create_rbac_tables.sql','e206ebec5bee8b8763be3c0b1d787a0694897f848e25b41de021bce7476f09bc','2025-09-12 16:48:27'),(13,'2025_09_12_002_create_settings_system.sql','6a51510f00455408125bdd53be280c683933e6511a5de22ec13d41637461a394','2025-09-12 17:01:37');
/*!40000 ALTER TABLE `schema_migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_adjustment_items`
--

DROP TABLE IF EXISTS `stock_adjustment_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_adjustment_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `stock_adjustment_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `qty_change` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `stock_adjustment_id` (`stock_adjustment_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_adjustment_items`
--

LOCK TABLES `stock_adjustment_items` WRITE;
/*!40000 ALTER TABLE `stock_adjustment_items` DISABLE KEYS */;
INSERT INTO `stock_adjustment_items` VALUES (1,2,1,-1,'2025-09-01 11:19:17');
/*!40000 ALTER TABLE `stock_adjustment_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_adjustments`
--

DROP TABLE IF EXISTS `stock_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_adjustments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `adj_no` varchar(32) NOT NULL,
  `warehouse_id` int unsigned NOT NULL,
  `reason` enum('count','damage','shrink','other') NOT NULL DEFAULT 'count',
  `note` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `adj_no` (`adj_no`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_adjustments`
--

LOCK TABLES `stock_adjustments` WRITE;
/*!40000 ALTER TABLE `stock_adjustments` DISABLE KEYS */;
INSERT INTO `stock_adjustments` VALUES (2,'AD250901-0002',1,'damage','','2025-09-01 11:19:17');
/*!40000 ALTER TABLE `stock_adjustments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_transfer_items`
--

DROP TABLE IF EXISTS `stock_transfer_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_transfer_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `stock_transfer_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `qty` int unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `stock_transfer_id` (`stock_transfer_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_transfer_items`
--

LOCK TABLES `stock_transfer_items` WRITE;
/*!40000 ALTER TABLE `stock_transfer_items` DISABLE KEYS */;
INSERT INTO `stock_transfer_items` VALUES (1,2,2,1,'2025-09-01 11:19:57');
/*!40000 ALTER TABLE `stock_transfer_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_transfers`
--

DROP TABLE IF EXISTS `stock_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_transfers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tr_no` varchar(32) NOT NULL,
  `from_warehouse_id` int unsigned NOT NULL,
  `to_warehouse_id` int unsigned NOT NULL,
  `note` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tr_no` (`tr_no`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_transfers`
--

LOCK TABLES `stock_transfers` WRITE;
/*!40000 ALTER TABLE `stock_transfers` DISABLE KEYS */;
INSERT INTO `stock_transfers` VALUES (2,'TR250901-0002',1,2,'','2025-09-01 11:19:57');
/*!40000 ALTER TABLE `stock_transfers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier_payments`
--

DROP TABLE IF EXISTS `supplier_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `supplier_payments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` int unsigned NOT NULL,
  `purchase_invoice_id` int unsigned NOT NULL,
  `paid_at` datetime NOT NULL,
  `method` varchar(50) NOT NULL,
  `reference` varchar(64) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `note` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sp_supplier` (`supplier_id`),
  KEY `idx_sp_pi` (`purchase_invoice_id`),
  KEY `idx_sp_paid_at` (`paid_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_payments`
--

LOCK TABLES `supplier_payments` WRITE;
/*!40000 ALTER TABLE `supplier_payments` DISABLE KEYS */;
INSERT INTO `supplier_payments` VALUES (1,1,1,'2025-08-30 11:09:00','bank','TR56775',650.00,'','2025-08-31 08:10:00'),(2,1,1,'2025-08-31 11:12:00','bank','TR89885',1000.00,'','2025-08-31 08:13:06');
/*!40000 ALTER TABLE `supplier_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,'Ahmed Abdel Salam','01120121343','ahmedfuture445@gmail.com','Nozha');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_settings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `setting_type` enum('string','number','boolean','json') COLLATE utf8mb4_unicode_ci DEFAULT 'string',
  `category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'general',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_encrypted` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_settings_key` (`setting_key`),
  KEY `idx_settings_category` (`category`),
  KEY `idx_system_settings_key_category` (`setting_key`,`category`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES (1,'company_name','Spare Parts Management System','string','company','Company or business name',0,'2025-09-12 17:01:21','2025-09-12 17:01:21'),(2,'company_address','','string','company','Company address for invoices and documents',0,'2025-09-12 17:01:21','2025-09-12 17:01:21'),(3,'company_phone','','string','company','Company phone number',0,'2025-09-12 17:01:21','2025-09-12 17:01:21'),(4,'company_email','','string','company','Company email address',0,'2025-09-12 17:01:21','2025-09-12 17:01:21'),(5,'company_website','','string','company','Company website URL',0,'2025-09-12 17:01:21','2025-09-12 17:01:21'),(6,'company_logo','','string','company','Path to company logo file',0,'2025-09-12 17:01:21','2025-09-12 17:01:21'),(7,'default_tax_rate','10.00','number','tax','Default tax rate percentage',0,'2025-09-12 17:01:21','2025-09-12 17:01:21'),(8,'tax_calculation_method','exclusive','string','tax','Tax calculation method: inclusive or exclusive',0,'2025-09-12 17:01:21','2025-09-12 17:01:21'),(9,'base_currency','EGP','string','currency','Base currency code',0,'2025-09-12 17:01:21','2025-09-12 17:01:21'),(10,'currency_symbol','EGP','string','currency','Currency symbol for display',0,'2025-09-12 17:01:21','2025-09-12 17:01:21'),(11,'currency_position','after','string','currency','Currency symbol position: before or after amount',0,'2025-09-12 17:01:21','2025-09-12 17:01:21'),(12,'decimal_places','2','number','currency','Number of decimal places for currency',0,'2025-09-12 17:01:21','2025-09-12 17:01:21'),(13,'thousands_separator',',','string','currency','Thousands separator character',0,'2025-09-12 17:01:21','2025-09-12 17:01:21'),(14,'decimal_separator','.','string','currency','Decimal separator character',0,'2025-09-12 17:01:21','2025-09-12 17:01:21'),(15,'invoice_terms','Payment due within 30 days','string','invoice','Default invoice terms and conditions',0,'2025-09-12 17:01:21','2025-09-12 17:01:21'),(16,'invoice_footer','Thank you for your business!','string','invoice','Default invoice footer text',0,'2025-09-12 17:01:21','2025-09-12 17:01:21'),(17,'low_stock_threshold','10','number','inventory','Default low stock alert threshold',0,'2025-09-12 17:01:21','2025-09-12 17:01:21'),(18,'auto_generate_codes','true','boolean','inventory','Automatically generate product codes',0,'2025-09-12 17:01:21','2025-09-12 17:01:21'),(19,'enable_multi_currency','false','boolean','currency','Enable multi-currency support',0,'2025-09-12 17:01:21','2025-09-12 17:01:21'),(20,'enable_tax_inclusive','false','boolean','tax','Enable tax-inclusive pricing',0,'2025-09-12 17:01:21','2025-09-12 17:01:21'),(41,'quote_prefix','QT','string','numbering','Quote number prefix',0,'2025-09-12 17:01:37','2025-09-12 17:01:37'),(42,'invoice_prefix','INV','string','numbering','Invoice number prefix',0,'2025-09-12 17:01:37','2025-09-12 17:01:37'),(43,'order_prefix','SO','string','numbering','Sales order number prefix',0,'2025-09-12 17:01:37','2025-09-12 17:01:37'),(44,'purchase_prefix','PO','string','numbering','Purchase order number prefix',0,'2025-09-12 17:01:37','2025-09-12 17:01:37'),(45,'receipt_prefix','GRN','string','numbering','Goods receipt note prefix',0,'2025-09-12 17:01:37','2025-09-12 17:01:37'),(46,'adjustment_prefix','ADJ','string','numbering','Stock adjustment prefix',0,'2025-09-12 17:01:37','2025-09-12 17:01:37'),(47,'transfer_prefix','TRF','string','numbering','Stock transfer prefix',0,'2025-09-12 17:01:37','2025-09-12 17:01:37'),(48,'reset_numbering_yearly','true','boolean','numbering','Reset document numbering each year',0,'2025-09-12 17:01:37','2025-09-12 17:01:37'),(49,'number_padding','4','number','numbering','Minimum digits for document numbers',0,'2025-09-12 17:01:37','2025-09-12 17:01:37'),(50,'date_format','Y-m-d','string','display','Date format for display',0,'2025-09-12 17:01:37','2025-09-12 17:01:37'),(51,'time_format','H:i:s','string','display','Time format for display',0,'2025-09-12 17:01:37','2025-09-12 17:01:37'),(52,'timezone','Africa/Cairo','string','display','Default timezone',0,'2025-09-12 17:01:37','2025-09-12 17:01:37');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tax_rates`
--

DROP TABLE IF EXISTS `tax_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tax_rates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `type` enum('sales','purchase','vat','service','import','export') COLLATE utf8mb4_unicode_ci DEFAULT 'sales',
  `is_default` tinyint(1) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `description` text COLLATE utf8mb4_unicode_ci,
  `effective_from` date DEFAULT NULL,
  `effective_to` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tax_rates_type` (`type`),
  KEY `idx_tax_rates_active` (`is_active`),
  KEY `idx_tax_rates_default` (`is_default`),
  KEY `idx_tax_rates_effective` (`effective_from`,`effective_to`),
  KEY `idx_tax_rates_type_active` (`type`,`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_rates`
--

LOCK TABLES `tax_rates` WRITE;
/*!40000 ALTER TABLE `tax_rates` DISABLE KEYS */;
INSERT INTO `tax_rates` VALUES (1,'Standard VAT',14.00,'sales',1,1,'Standard Value Added Tax rate for Egypt','2024-01-01',NULL,'2025-09-12 17:01:21','2025-09-12 17:01:21'),(2,'Service Tax',10.00,'service',0,1,'Tax rate for services','2024-01-01',NULL,'2025-09-12 17:01:21','2025-09-12 17:01:21'),(3,'Zero Tax',0.00,'sales',0,1,'Zero tax rate for exempt items','2024-01-01',NULL,'2025-09-12 17:01:21','2025-09-12 17:01:21'),(4,'Import Duty',5.00,'import',0,1,'Import duty rate','2024-01-01',NULL,'2025-09-12 17:01:21','2025-09-12 17:01:21'),(5,'Export Tax',0.00,'export',0,1,'Export tax rate','2024-01-01',NULL,'2025-09-12 17:01:21','2025-09-12 17:01:21'),(6,'Standard VAT',14.00,'sales',1,1,'Standard Value Added Tax rate for Egypt','2024-01-01',NULL,'2025-09-12 17:01:37','2025-09-12 17:01:37'),(7,'Service Tax',10.00,'service',0,1,'Tax rate for services','2024-01-01',NULL,'2025-09-12 17:01:37','2025-09-12 17:01:37'),(8,'Zero Tax',0.00,'sales',0,1,'Zero tax rate for exempt items','2024-01-01',NULL,'2025-09-12 17:01:37','2025-09-12 17:01:37'),(9,'Import Duty',5.00,'import',0,1,'Import duty rate','2024-01-01',NULL,'2025-09-12 17:01:37','2025-09-12 17:01:37'),(10,'Export Tax',0.00,'export',0,1,'Export tax rate','2024-01-01',NULL,'2025-09-12 17:01:37','2025-09-12 17:01:37');
/*!40000 ALTER TABLE `tax_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_roles`
--

DROP TABLE IF EXISTS `user_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_roles` (
  `user_id` int unsigned NOT NULL,
  `role_id` int unsigned NOT NULL,
  `assigned_by` int unsigned DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `idx_user_roles_user` (`user_id`),
  KEY `idx_user_roles_role` (`role_id`),
  KEY `idx_user_roles_assigned_by` (`assigned_by`),
  KEY `idx_user_roles_expires` (`expires_at`),
  CONSTRAINT `fk_user_roles_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_roles`
--

LOCK TABLES `user_roles` WRITE;
/*!40000 ALTER TABLE `user_roles` DISABLE KEYS */;
INSERT INTO `user_roles` VALUES (1,1,1,'2025-09-12 16:48:27',NULL),(2,5,1,'2025-09-12 16:56:01',NULL);
/*!40000 ALTER TABLE `user_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(191) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'admin',
  `status` enum('active','inactive','suspended','pending') DEFAULT 'active',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_status` (`status`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_last_login` (`last_login_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin','active','2025-09-13 09:50:51','2025-09-13 09:50:51','2025-08-29 13:37:55'),(2,'khaled.h87@gmail.com','$2y$12$EgKnCgsDgOwDxsq6aP3g..PHDTBD3d8Nty/wxR7HZLq6kfLclRyzq','staff','active',NULL,'2025-09-12 16:56:01','2025-09-12 16:56:01');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary view structure for view `v_index_performance`
--

DROP TABLE IF EXISTS `v_index_performance`;
/*!50001 DROP VIEW IF EXISTS `v_index_performance`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_index_performance` AS SELECT 
 1 AS `TABLE_NAME`,
 1 AS `INDEX_NAME`,
 1 AS `CARDINALITY`,
 1 AS `TABLE_ROWS`,
 1 AS `selectivity_percent`,
 1 AS `selectivity_rating`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_table_performance`
--

DROP TABLE IF EXISTS `v_table_performance`;
/*!50001 DROP VIEW IF EXISTS `v_table_performance`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_table_performance` AS SELECT 
 1 AS `TABLE_NAME`,
 1 AS `ENGINE`,
 1 AS `TABLE_ROWS`,
 1 AS `data_mb`,
 1 AS `index_mb`,
 1 AS `free_mb`,
 1 AS `index_ratio_percent`,
 1 AS `UPDATE_TIME`,
 1 AS `CHECK_TIME`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `vehicle_models`
--

DROP TABLE IF EXISTS `vehicle_models`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicle_models` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `make_id` int unsigned NOT NULL,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vm_make_slug` (`make_id`,`slug`),
  KEY `idx_vm_make` (`make_id`),
  CONSTRAINT `fk_vm_make` FOREIGN KEY (`make_id`) REFERENCES `makes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicle_models`
--

LOCK TABLES `vehicle_models` WRITE;
/*!40000 ALTER TABLE `vehicle_models` DISABLE KEYS */;
INSERT INTO `vehicle_models` VALUES (1,1,'Punto 2008','punto-2008','2025-08-29 15:12:42',NULL),(2,2,'Corolla 2019','corolla-2019','2025-08-29 15:13:00',NULL);
/*!40000 ALTER TABLE `vehicle_models` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouses`
--

DROP TABLE IF EXISTS `warehouses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `warehouses` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(191) NOT NULL,
  `location` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouses`
--

LOCK TABLES `warehouses` WRITE;
/*!40000 ALTER TABLE `warehouses` DISABLE KEYS */;
INSERT INTO `warehouses` VALUES (1,'WH01','Main Store','Nasr City - Industrial Zone','2025-08-29 15:27:39','2025-09-01 11:07:46'),(2,'WH02','Ramsis Warehouse','Ramsis Square','2025-09-01 11:07:34',NULL);
/*!40000 ALTER TABLE `warehouses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'chatgpt2_mi'
--

--
-- Dumping routines for database 'chatgpt2_mi'
--

--
-- Final view structure for view `v_index_performance`
--

/*!50001 DROP VIEW IF EXISTS `v_index_performance`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_index_performance` AS select `information_schema`.`s`.`TABLE_NAME` AS `TABLE_NAME`,`information_schema`.`s`.`INDEX_NAME` AS `INDEX_NAME`,`information_schema`.`s`.`CARDINALITY` AS `CARDINALITY`,`information_schema`.`t`.`TABLE_ROWS` AS `TABLE_ROWS`,round((case when (`information_schema`.`t`.`TABLE_ROWS` > 0) then ((`information_schema`.`s`.`CARDINALITY` / `information_schema`.`t`.`TABLE_ROWS`) * 100) else 0 end),2) AS `selectivity_percent`,(case when (`information_schema`.`t`.`TABLE_ROWS` = 0) then 'No rows' when ((`information_schema`.`s`.`CARDINALITY` / `information_schema`.`t`.`TABLE_ROWS`) > 0.8) then 'High selectivity' when ((`information_schema`.`s`.`CARDINALITY` / `information_schema`.`t`.`TABLE_ROWS`) > 0.5) then 'Medium selectivity' when ((`information_schema`.`s`.`CARDINALITY` / `information_schema`.`t`.`TABLE_ROWS`) > 0.1) then 'Low selectivity' else 'Very low selectivity' end) AS `selectivity_rating` from (`information_schema`.`STATISTICS` `s` join `information_schema`.`TABLES` `t` on(((`information_schema`.`t`.`TABLE_NAME` = `information_schema`.`s`.`TABLE_NAME`) and (`information_schema`.`t`.`TABLE_SCHEMA` = `information_schema`.`s`.`TABLE_SCHEMA`)))) where ((`information_schema`.`s`.`TABLE_SCHEMA` = database()) and (`information_schema`.`s`.`INDEX_NAME` <> 'PRIMARY')) order by `information_schema`.`s`.`TABLE_NAME`,round((case when (`information_schema`.`t`.`TABLE_ROWS` > 0) then ((`information_schema`.`s`.`CARDINALITY` / `information_schema`.`t`.`TABLE_ROWS`) * 100) else 0 end),2) desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_table_performance`
--

/*!50001 DROP VIEW IF EXISTS `v_table_performance`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_table_performance` AS select `information_schema`.`tables`.`TABLE_NAME` AS `TABLE_NAME`,`information_schema`.`tables`.`ENGINE` AS `ENGINE`,`information_schema`.`tables`.`TABLE_ROWS` AS `TABLE_ROWS`,round(((`information_schema`.`tables`.`DATA_LENGTH` / 1024) / 1024),2) AS `data_mb`,round(((`information_schema`.`tables`.`INDEX_LENGTH` / 1024) / 1024),2) AS `index_mb`,round(((`information_schema`.`tables`.`DATA_FREE` / 1024) / 1024),2) AS `free_mb`,round((case when (`information_schema`.`tables`.`DATA_LENGTH` > 0) then ((`information_schema`.`tables`.`INDEX_LENGTH` / `information_schema`.`tables`.`DATA_LENGTH`) * 100) else 0 end),2) AS `index_ratio_percent`,`information_schema`.`tables`.`UPDATE_TIME` AS `UPDATE_TIME`,`information_schema`.`tables`.`CHECK_TIME` AS `CHECK_TIME` from `information_schema`.`TABLES` where ((`information_schema`.`tables`.`TABLE_SCHEMA` = database()) and (`information_schema`.`tables`.`ENGINE` = 'InnoDB')) order by `information_schema`.`tables`.`DATA_LENGTH` desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-09-13 15:13:12
