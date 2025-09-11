-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 10, 2025 at 07:55 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `chatgpt2_mi`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

DROP TABLE IF EXISTS `activity_log`;
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `actor` varchar(191) NOT NULL,
  `action` varchar(64) NOT NULL,
  `entity_type` varchar(64) NOT NULL,
  `entity_id` int UNSIGNED NOT NULL,
  `meta` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_entity` (`entity_type`,`entity_id`),
  KEY `idx_action` (`action`),
  KEY `idx_activity_log_entity_action_date` (`entity_type`,`entity_id`,`action`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `actor`, `action`, `entity_type`, `entity_id`, `meta`, `created_at`) VALUES
(1, 'admin@example.com', 'ap.add', 'purchase_invoice', 1, '{\"payment_id\":1,\"amount\":650,\"method\":\"bank\",\"reference\":\"TR56775\"}', '2025-08-31 08:10:00'),
(2, 'admin@example.com', 'ap.add', 'purchase_invoice', 1, '{\"payment_id\":2,\"amount\":1000,\"method\":\"bank\",\"reference\":\"TR89885\"}', '2025-08-31 08:13:06');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` int UNSIGNED DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_slug` (`slug`),
  KEY `idx_categories_parent` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Body Parts', 'body-parts', '2025-08-29 15:28:25', NULL),
(2, NULL, 'Paints', 'paints', '2025-08-29 15:55:21', NULL),
(3, NULL, 'Electronics', 'electronics', '2025-08-29 15:55:38', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cogs_entries`
--

DROP TABLE IF EXISTS `cogs_entries`;
CREATE TABLE IF NOT EXISTS `cogs_entries` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `warehouse_id` int UNSIGNED NOT NULL,
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

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
CREATE TABLE IF NOT EXISTS `customers` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `address` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_customers_name_email` (`name`,`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `phone`, `email`, `address`, `created_at`, `updated_at`) VALUES
(1, 'Khaled Mohamed Helmy', '01007847333', 'khelmy@sarieldin.com', 'KM 28 Cairo Alex Desert Road B 19 - Smart Village\r\nSarieldin & Partners', '2025-08-29 15:41:56', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `doc_sequences`
--

DROP TABLE IF EXISTS `doc_sequences`;
CREATE TABLE IF NOT EXISTS `doc_sequences` (
  `prefix` varchar(10) NOT NULL,
  `y` int NOT NULL,
  `last_no` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`prefix`,`y`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `doc_sequences`
--

INSERT INTO `doc_sequences` (`prefix`, `y`, `last_no`) VALUES
('po', 2025, 20),
('q', 2025, 10);

-- --------------------------------------------------------

--
-- Table structure for table `inventory_ledger`
--

DROP TABLE IF EXISTS `inventory_ledger`;
CREATE TABLE IF NOT EXISTS `inventory_ledger` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` int UNSIGNED NOT NULL,
  `warehouse_id` int UNSIGNED NOT NULL,
  `doc_type` enum('receipt','transfer_out','transfer_in','adjustment','sale','sales_return','purchase_return') NOT NULL,
  `doc_id` int UNSIGNED NOT NULL,
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

--
-- Dumping data for table `inventory_ledger`
--

INSERT INTO `inventory_ledger` (`id`, `product_id`, `warehouse_id`, `doc_type`, `doc_id`, `qty_delta`, `unit_cost`, `value_delta`, `created_at`) VALUES
(1, 3, 1, 'receipt', 8, 5, 1250.0000, 6250.0000, '2025-09-02 07:23:35'),
(2, 3, 2, 'receipt', 9, 1, 2000.0000, 2000.0000, '2025-09-02 08:15:30'),
(3, 1, 2, 'receipt', 10, 1, 1500.0000, 1500.0000, '2025-09-02 08:15:55'),
(4, 4, 1, 'receipt', 8, 5, 100.0000, 500.0000, '2025-09-10 17:24:49'),
(5, 4, 1, 'receipt', 8, 5, 100.0000, 500.0000, '2025-09-10 18:25:17');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
CREATE TABLE IF NOT EXISTS `invoices` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `inv_no` varchar(32) NOT NULL,
  `sales_order_id` int UNSIGNED NOT NULL,
  `customer_id` int UNSIGNED NOT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `inv_no`, `sales_order_id`, `customer_id`, `tax_rate`, `subtotal`, `tax_amount`, `total`, `paid_amount`, `status`, `created_at`, `updated_at`, `cogs_total`) VALUES
(1, 'INV2025-0001', 1, 1, 10.00, 6000.00, 600.00, 6600.00, 6600.00, 'paid', '2025-08-30 09:35:44', '2025-08-30 10:00:51', 0.00),
(2, 'INV2025-0006', 7, 1, 10.00, 650.00, 65.00, 715.00, 0.00, 'unpaid', '2025-09-02 11:37:20', '2025-09-02 12:26:59', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

DROP TABLE IF EXISTS `invoice_items`;
CREATE TABLE IF NOT EXISTS `invoice_items` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `warehouse_id` int UNSIGNED NOT NULL,
  `qty` int UNSIGNED NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `line_total` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_invoice_items_invoice` (`invoice_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_id`, `warehouse_id`, `qty`, `price`, `line_total`) VALUES
(1, 1, 2, 1, 3, 650.00, 1950.00),
(2, 1, 3, 1, 1, 1050.00, 1050.00),
(3, 1, 1, 1, 2, 1500.00, 3000.00),
(4, 2, 2, 1, 1, 650.00, 650.00);

-- --------------------------------------------------------

--
-- Table structure for table `invoice_payments`
--

DROP TABLE IF EXISTS `invoice_payments`;
CREATE TABLE IF NOT EXISTS `invoice_payments` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_id` int UNSIGNED NOT NULL,
  `paid_at` datetime NOT NULL,
  `method` varchar(50) NOT NULL,
  `reference` varchar(191) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payments_invoice` (`invoice_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `invoice_payments`
--

INSERT INTO `invoice_payments` (`id`, `invoice_id`, `paid_at`, `method`, `reference`, `amount`, `note`, `created_at`) VALUES
(1, 1, '2025-08-30 12:46:00', 'cash', '', 1000.00, '', '2025-08-30 09:46:46'),
(2, 1, '2025-08-29 12:50:00', 'cash', '', 1000.00, '', '2025-08-30 10:00:10'),
(3, 1, '2025-08-30 13:00:00', 'Wire', '', 4600.00, '', '2025-08-30 10:00:51');

-- --------------------------------------------------------

--
-- Table structure for table `makes`
--

DROP TABLE IF EXISTS `makes`;
CREATE TABLE IF NOT EXISTS `makes` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_makes_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `makes`
--

INSERT INTO `makes` (`id`, `name`, `slug`, `created_at`, `updated_at`) VALUES
(1, 'Fiat', 'fiat', '2025-08-29 15:12:05', NULL),
(2, 'Toyota', 'toyota', '2025-08-29 15:12:18', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notes`
--

DROP TABLE IF EXISTS `notes`;
CREATE TABLE IF NOT EXISTS `notes` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity_type` enum('quote','sales_order','sales_invoice','purchase_order','purchase_invoice','customer','category','warehouse','product') NOT NULL,
  `entity_id` int UNSIGNED NOT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT '0',
  `body` text NOT NULL,
  `created_by` varchar(191) NOT NULL,
  `created_by_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_entity` (`entity_type`,`entity_id`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notes`
--

INSERT INTO `notes` (`id`, `entity_type`, `entity_id`, `is_public`, `body`, `created_by`, `created_by_id`, `created_at`) VALUES
(1, 'quote', 1, 0, 'This order will be late.', '', NULL, '2025-08-30 06:21:59'),
(2, 'quote', 1, 1, 'Transfer will be on HSBC', '', NULL, '2025-08-30 06:22:18');

-- --------------------------------------------------------

--
-- Table structure for table `optimization_log`
--

DROP TABLE IF EXISTS `optimization_log`;
CREATE TABLE IF NOT EXISTS `optimization_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_type` varchar(50) NOT NULL,
  `message` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_event_type` (`event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(191) NOT NULL,
  `category_id` int UNSIGNED DEFAULT NULL,
  `make_id` int UNSIGNED DEFAULT NULL,
  `model_id` int UNSIGNED DEFAULT NULL,
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
  KEY `idx_products_name_code` (`name`,`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `code`, `name`, `category_id`, `make_id`, `model_id`, `cost`, `price`, `created_at`, `updated_at`) VALUES
(1, 'PRD0001', 'Spoiler', 1, 1, 1, 1000.00, 1500.00, '2025-08-29 15:28:48', NULL),
(2, 'PRD0002', 'Coil', 3, 1, 1, 500.00, 650.00, '2025-08-29 15:56:03', NULL),
(3, 'PRD0003', 'Red Polish', 2, 1, 1, 850.00, 1050.00, '2025-08-29 15:56:33', NULL),
(4, 'PRD0004', 'Sparks Plug', 3, 2, 2, 0.00, 0.00, '2025-09-10 17:08:02', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_stocks`
--

DROP TABLE IF EXISTS `product_stocks`;
CREATE TABLE IF NOT EXISTS `product_stocks` (
  `product_id` int UNSIGNED NOT NULL,
  `warehouse_id` int UNSIGNED NOT NULL,
  `qty_on_hand` int UNSIGNED NOT NULL DEFAULT '0',
  `qty_reserved` int UNSIGNED NOT NULL DEFAULT '0',
  `avg_cost` decimal(12,4) NOT NULL DEFAULT '0.0000',
  PRIMARY KEY (`product_id`,`warehouse_id`),
  KEY `fk_ps_warehouse` (`warehouse_id`),
  KEY `idx_product_stocks_warehouse_qty` (`warehouse_id`,`qty_on_hand`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `product_stocks`
--

INSERT INTO `product_stocks` (`product_id`, `warehouse_id`, `qty_on_hand`, `qty_reserved`, `avg_cost`) VALUES
(1, 1, 9, 1, 0.0000),
(1, 2, 1, 0, 1500.0000),
(2, 1, 9, 1, 0.0000),
(2, 2, 1, 0, 0.0000),
(3, 1, 14, 1, 446.4286),
(3, 2, 1, 0, 2000.0000),
(4, 1, 8, 0, 100.0000);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_invoices`
--

DROP TABLE IF EXISTS `purchase_invoices`;
CREATE TABLE IF NOT EXISTS `purchase_invoices` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `pi_no` varchar(32) NOT NULL,
  `purchase_order_id` int UNSIGNED NOT NULL,
  `supplier_id` int UNSIGNED NOT NULL,
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

--
-- Dumping data for table `purchase_invoices`
--

INSERT INTO `purchase_invoices` (`id`, `pi_no`, `purchase_order_id`, `supplier_id`, `subtotal`, `tax_rate`, `tax_amount`, `total`, `created_at`, `paid_amount`, `status`) VALUES
(1, 'PI2025-0001', 2, 1, 1500.00, 10.00, 150.00, 1650.00, '2025-08-30 11:12:24', 1650.00, 'paid'),
(2, 'PI2025-0006', 3, 1, 14250.00, 10.00, 1425.00, 15675.00, '2025-09-02 07:23:06', 0.00, 'unpaid'),
(4, 'PI2025-0019', 10, 1, 2000.00, 10.00, 200.00, 2200.00, '2025-09-02 08:15:02', 0.00, 'unpaid'),
(5, 'PI2025-0018', 9, 1, 1500.00, 10.00, 150.00, 1650.00, '2025-09-02 08:15:50', 0.00, 'unpaid'),
(8, 'PI2025-0020', 11, 1, 1000.00, 0.00, 0.00, 1000.00, '2025-09-10 17:13:06', 0.00, 'unpaid');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
CREATE TABLE IF NOT EXISTS `purchase_orders` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `po_no` varchar(32) NOT NULL,
  `supplier_id` int UNSIGNED NOT NULL,
  `status` enum('draft','ordered','received','closed') NOT NULL DEFAULT 'draft',
  `tax_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `po_no` (`po_no`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`id`, `po_no`, `supplier_id`, `status`, `tax_rate`, `subtotal`, `tax_amount`, `total`, `created_at`) VALUES
(2, 'PO2025-0001', 1, 'closed', 10.00, 1500.00, 150.00, 1650.00, '2025-08-30 10:59:34'),
(3, 'PO2025-0006', 1, 'received', 10.00, 14250.00, 1425.00, 15675.00, '2025-09-02 07:22:47'),
(6, 'PO2025-0015', 1, 'draft', 10.00, 1300.00, 130.00, 1430.00, '2025-09-02 07:59:09'),
(9, 'PO2025-0018', 1, 'received', 10.00, 1500.00, 150.00, 1650.00, '2025-09-02 08:09:52'),
(10, 'PO2025-0019', 1, 'received', 10.00, 2000.00, 200.00, 2200.00, '2025-09-02 08:10:22'),
(11, 'PO2025-0020', 1, 'received', 0.00, 1000.00, 0.00, 1000.00, '2025-09-10 17:09:12');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

DROP TABLE IF EXISTS `purchase_order_items`;
CREATE TABLE IF NOT EXISTS `purchase_order_items` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_order_id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `warehouse_id` int UNSIGNED NOT NULL,
  `qty` int UNSIGNED NOT NULL,
  `received_qty` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `price` decimal(12,2) NOT NULL,
  `line_total` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_poi_po` (`purchase_order_id`),
  KEY `idx_poi_product` (`product_id`),
  KEY `idx_poi_warehouse` (`warehouse_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `purchase_order_items`
--

INSERT INTO `purchase_order_items` (`id`, `purchase_order_id`, `product_id`, `warehouse_id`, `qty`, `received_qty`, `price`, `line_total`) VALUES
(1, 2, 1, 1, 10, 0.0000, 150.00, 1500.00),
(2, 3, 3, 1, 5, 0.0000, 1250.00, 6250.00),
(3, 3, 3, 1, 5, 0.0000, 1250.00, 6250.00),
(4, 9, 1, 2, 1, 0.0000, 1500.00, 1500.00),
(5, 10, 3, 2, 1, 0.0000, 2000.00, 2000.00),
(6, 11, 4, 1, 10, 10.0000, 100.00, 1000.00);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_returns`
--

DROP TABLE IF EXISTS `purchase_returns`;
CREATE TABLE IF NOT EXISTS `purchase_returns` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `pr_no` varchar(32) NOT NULL,
  `purchase_invoice_id` int UNSIGNED NOT NULL,
  `supplier_id` int UNSIGNED NOT NULL,
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

--
-- Dumping data for table `purchase_returns`
--

INSERT INTO `purchase_returns` (`id`, `pr_no`, `purchase_invoice_id`, `supplier_id`, `subtotal`, `tax_rate`, `tax_amount`, `total`, `created_at`) VALUES
(1, 'PR2025-0001', 1, 1, 1200.00, 10.00, 120.00, 1320.00, '2025-08-31 09:50:42'),
(2, 'PR2025-0006', 8, 1, 200.00, 0.00, 0.00, 200.00, '2025-09-10 18:25:41');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_return_items`
--

DROP TABLE IF EXISTS `purchase_return_items`;
CREATE TABLE IF NOT EXISTS `purchase_return_items` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_return_id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `warehouse_id` int UNSIGNED NOT NULL,
  `qty` int UNSIGNED NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `line_total` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pri_pr` (`purchase_return_id`),
  KEY `idx_pri_prod` (`product_id`),
  KEY `idx_pri_wh` (`warehouse_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `purchase_return_items`
--

INSERT INTO `purchase_return_items` (`id`, `purchase_return_id`, `product_id`, `warehouse_id`, `qty`, `price`, `line_total`) VALUES
(1, 1, 1, 1, 8, 150.00, 1200.00),
(2, 2, 4, 1, 2, 100.00, 200.00);

-- --------------------------------------------------------

--
-- Table structure for table `quotes`
--

DROP TABLE IF EXISTS `quotes`;
CREATE TABLE IF NOT EXISTS `quotes` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `quote_no` varchar(32) NOT NULL,
  `customer_id` int UNSIGNED NOT NULL,
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
  KEY `idx_quotes_customer` (`customer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `quotes`
--

INSERT INTO `quotes` (`id`, `quote_no`, `customer_id`, `status`, `tax_rate`, `subtotal`, `tax_amount`, `total`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'Q2025-0001', 1, 'ordered', 10.00, 6000.00, 600.00, 6600.00, '2025-09-04', '2025-08-29 16:21:31', '2025-08-29 17:26:28'),
(3, 'Q2025-1844', 1, 'sent', 10.00, 3200.00, 320.00, 3520.00, '2025-09-05', '2025-09-02 08:39:30', '2025-09-02 10:06:54'),
(6, 'Q2025-0002', 1, 'sent', 10.00, 2150.00, 215.00, 2365.00, NULL, '2025-09-02 09:31:04', '2025-09-02 09:58:03'),
(7, 'Q2025-0003', 1, 'sent', 10.00, 2150.00, 215.00, 2365.00, NULL, '2025-09-02 09:41:18', '2025-09-02 09:58:12'),
(8, 'Q2025-0004', 1, 'sent', 10.00, 1500.00, 150.00, 1650.00, '2025-09-10', '2025-09-02 09:49:27', '2025-09-02 09:58:16'),
(9, 'Q2025-0005', 1, 'sent', 10.00, 650.00, 65.00, 715.00, '2025-09-18', '2025-09-02 09:58:58', '2025-09-02 10:05:50'),
(10, 'Q2025-0006', 1, 'accepted', 10.00, 650.00, 65.00, 715.00, '2025-09-27', '2025-09-02 10:04:01', '2025-09-02 11:37:16'),
(11, 'Q2025-0007', 1, 'expired', 0.00, 1050.00, 0.00, 1050.00, NULL, '2025-09-02 10:09:39', '2025-09-02 10:23:09'),
(12, 'Q2025-0008', 1, 'cancelled', 0.00, 1050.00, 0.00, 1050.00, NULL, '2025-09-02 10:10:43', '2025-09-02 10:16:42'),
(13, 'Q2025-0009', 1, 'accepted', 0.00, 1050.00, 0.00, 1050.00, NULL, '2025-09-02 10:23:54', '2025-09-02 11:01:22'),
(14, 'Q2025-0010', 1, 'draft', 10.00, 14700.00, 1470.00, 16170.00, NULL, '2025-09-10 19:41:38', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `quote_items`
--

DROP TABLE IF EXISTS `quote_items`;
CREATE TABLE IF NOT EXISTS `quote_items` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `quote_id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `warehouse_id` int UNSIGNED NOT NULL,
  `qty` int UNSIGNED NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `line_total` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_qi_quote` (`quote_id`),
  KEY `fk_qi_product` (`product_id`),
  KEY `fk_qi_wh` (`warehouse_id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `quote_items`
--

INSERT INTO `quote_items` (`id`, `quote_id`, `product_id`, `warehouse_id`, `qty`, `price`, `line_total`) VALUES
(1, 1, 2, 1, 3, 650.00, 1950.00),
(2, 1, 3, 1, 1, 1050.00, 1050.00),
(3, 1, 1, 1, 2, 1500.00, 3000.00),
(5, 3, 2, 1, 1, 650.00, 650.00),
(6, 3, 3, 1, 1, 1050.00, 1050.00),
(7, 3, 1, 1, 1, 1500.00, 1500.00),
(8, 6, 1, 1, 1, 1500.00, 1500.00),
(9, 6, 2, 2, 1, 650.00, 650.00),
(10, 7, 1, 1, 1, 1500.00, 1500.00),
(11, 7, 2, 1, 1, 650.00, 650.00),
(12, 8, 1, 1, 1, 1500.00, 1500.00),
(13, 9, 2, 1, 1, 650.00, 650.00),
(14, 10, 2, 1, 1, 650.00, 650.00),
(15, 11, 3, 1, 1, 1050.00, 1050.00),
(16, 12, 3, 1, 1, 1050.00, 1050.00),
(17, 13, 3, 1, 1, 1050.00, 1050.00),
(18, 14, 1, 1, 2, 1500.00, 3000.00),
(19, 14, 2, 2, 18, 650.00, 11700.00);

-- --------------------------------------------------------

--
-- Table structure for table `receipts`
--

DROP TABLE IF EXISTS `receipts`;
CREATE TABLE IF NOT EXISTS `receipts` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_invoice_id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `warehouse_id` int UNSIGNED NOT NULL,
  `qty` int UNSIGNED NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_receipts_pi` (`purchase_invoice_id`),
  KEY `idx_receipts_product` (`product_id`),
  KEY `idx_receipts_wh` (`warehouse_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `receipts`
--

INSERT INTO `receipts` (`id`, `purchase_invoice_id`, `product_id`, `warehouse_id`, `qty`, `price`, `created_at`) VALUES
(6, 1, 1, 1, 5, 150.00, '2025-08-30 11:22:54'),
(7, 1, 1, 1, 5, 150.00, '2025-08-31 07:32:28'),
(8, 2, 3, 1, 5, 1250.00, '2025-09-02 07:23:35'),
(9, 4, 3, 2, 1, 2000.00, '2025-09-02 08:15:30'),
(10, 5, 1, 2, 1, 1500.00, '2025-09-02 08:15:55'),
(11, 8, 4, 1, 5, 100.00, '2025-09-10 17:24:49'),
(12, 8, 4, 1, 5, 100.00, '2025-09-10 18:25:17');

-- --------------------------------------------------------

--
-- Table structure for table `sales_orders`
--

DROP TABLE IF EXISTS `sales_orders`;
CREATE TABLE IF NOT EXISTS `sales_orders` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `so_no` varchar(32) NOT NULL,
  `quote_id` int UNSIGNED NOT NULL,
  `customer_id` int UNSIGNED NOT NULL,
  `status` enum('open','closed','cancelled') NOT NULL DEFAULT 'open',
  `tax_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `so_no` (`so_no`),
  KEY `idx_so_quote` (`quote_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sales_orders`
--

INSERT INTO `sales_orders` (`id`, `so_no`, `quote_id`, `customer_id`, `status`, `tax_rate`, `subtotal`, `tax_amount`, `total`, `created_at`, `updated_at`) VALUES
(1, 'SO2025-0001', 1, 1, 'closed', 10.00, 6000.00, 600.00, 6600.00, '2025-08-29 17:26:28', '2025-09-02 12:28:50'),
(6, 'SO2025-0009', 13, 1, '', 0.00, 1050.00, 0.00, 1050.00, '2025-09-02 11:01:22', NULL),
(7, 'SO2025-0006', 10, 1, '', 10.00, 650.00, 65.00, 715.00, '2025-09-02 11:37:16', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sales_order_items`
--

DROP TABLE IF EXISTS `sales_order_items`;
CREATE TABLE IF NOT EXISTS `sales_order_items` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `sales_order_id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `warehouse_id` int UNSIGNED NOT NULL,
  `qty` int UNSIGNED NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `line_total` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_soi_so` (`sales_order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sales_order_items`
--

INSERT INTO `sales_order_items` (`id`, `sales_order_id`, `product_id`, `warehouse_id`, `qty`, `price`, `line_total`) VALUES
(1, 1, 2, 1, 3, 650.00, 1950.00),
(2, 1, 3, 1, 1, 1050.00, 1050.00),
(3, 1, 1, 1, 2, 1500.00, 3000.00),
(4, 6, 3, 1, 1, 1050.00, 1050.00),
(5, 7, 2, 1, 1, 650.00, 650.00);

-- --------------------------------------------------------

--
-- Table structure for table `sales_returns`
--

DROP TABLE IF EXISTS `sales_returns`;
CREATE TABLE IF NOT EXISTS `sales_returns` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `sr_no` varchar(32) NOT NULL,
  `sales_invoice_id` int UNSIGNED NOT NULL,
  `client_id` int UNSIGNED NOT NULL,
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

--
-- Dumping data for table `sales_returns`
--

INSERT INTO `sales_returns` (`id`, `sr_no`, `sales_invoice_id`, `client_id`, `subtotal`, `tax_rate`, `tax_amount`, `total`, `created_at`) VALUES
(1, 'SR2025-0001', 1, 1, 1950.00, 10.00, 195.00, 2145.00, '2025-08-31 08:56:33');

-- --------------------------------------------------------

--
-- Table structure for table `sales_return_items`
--

DROP TABLE IF EXISTS `sales_return_items`;
CREATE TABLE IF NOT EXISTS `sales_return_items` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `sales_return_id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `warehouse_id` int UNSIGNED NOT NULL,
  `qty` int UNSIGNED NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `line_total` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sri_sr` (`sales_return_id`),
  KEY `idx_sri_product` (`product_id`),
  KEY `idx_sri_warehouse` (`warehouse_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sales_return_items`
--

INSERT INTO `sales_return_items` (`id`, `sales_return_id`, `product_id`, `warehouse_id`, `qty`, `price`, `line_total`) VALUES
(1, 1, 2, 1, 3, 650.00, 1950.00);

-- --------------------------------------------------------

--
-- Table structure for table `schema_migrations`
--

DROP TABLE IF EXISTS `schema_migrations`;
CREATE TABLE IF NOT EXISTS `schema_migrations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `checksum` varchar(64) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `filename` (`filename`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `schema_migrations`
--

INSERT INTO `schema_migrations` (`id`, `filename`, `checksum`, `applied_at`) VALUES
(1, '2025-09-10_drop_duplicate_product_stocks_keys.sql', 'cf6a4e560bcf868ec26adbb41bcf50876fac5ef806a061633a6b200a81d8cbc5', '2025-09-10 16:38:50'),
(2, '2025-09-10_create_optimization_log_and_views.sql', 'b1b071636a7f6e6386ff3cdc2f53a05d75efec2ea8bc687f52731a6d6412dcf5', '2025-09-10 16:54:51'),
(3, '2025-09-10_idx_activity_log_entity_action_date.sql', 'cc0324bea843711f8b3660c536865572c91ba0aa1d15f4d35480df8c861a9c4e', '2025-09-10 16:54:51'),
(4, '2025-09-10_idx_cogs_entries_invoice_product_created.sql', '0714918d2f30329a88daae2bdac95766a0246466dc22a5ba834d46bc5ef7b20f', '2025-09-10 16:54:51'),
(5, '2025-09-10_idx_customers_name_email.sql', 'cddd5e26b6d6e6d32378789b34ec2adb3891650fa9bc1b37b6f54a304d793ff3', '2025-09-10 16:54:51'),
(6, '2025-09-10_idx_inventory_ledger_product_date.sql', 'c45676960e88ed929c8396d5e5f4dc49c18b1f3651f1bc08c3965a88443d231a', '2025-09-10 16:54:51'),
(7, '2025-09-10_idx_invoices_customer_date_status.sql', '14da870ec97918e6d50a9c2466c4e072e2ce958280743af6a089f9e89691e49e', '2025-09-10 16:54:51'),
(8, '2025-09-10_idx_product_stocks_warehouse_qty.sql', 'afd3c62a5b8d52b17a5960a03fdd0dbf930e2dcb57e5650da04953961adca9a8', '2025-09-10 16:54:51'),
(9, '2025-09-10_idx_products_category_make_model.sql', 'ab7065de1e209934f0a35ab0f26885eae30f13916b3380a28640c0c4bc5bb591', '2025-09-10 16:54:51'),
(10, '2025-09-10_idx_products_name_code.sql', 'a63401f8c931faa3f9f7d6f87a85a2030170211bf2b1f85106405206f66faddf', '2025-09-10 16:54:51'),
(11, '2025-09-10_idx_purchase_invoices_supplier_date_status.sql', '5f52056f312af7ac39314961c8adfde33b2c212b97ccc09a9356d0aafd85ae07', '2025-09-10 16:54:51');

-- --------------------------------------------------------

--
-- Table structure for table `stock_adjustments`
--

DROP TABLE IF EXISTS `stock_adjustments`;
CREATE TABLE IF NOT EXISTS `stock_adjustments` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `adj_no` varchar(32) NOT NULL,
  `warehouse_id` int UNSIGNED NOT NULL,
  `reason` enum('count','damage','shrink','other') NOT NULL DEFAULT 'count',
  `note` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `adj_no` (`adj_no`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `stock_adjustments`
--

INSERT INTO `stock_adjustments` (`id`, `adj_no`, `warehouse_id`, `reason`, `note`, `created_at`) VALUES
(2, 'AD250901-0002', 1, 'damage', '', '2025-09-01 11:19:17');

-- --------------------------------------------------------

--
-- Table structure for table `stock_adjustment_items`
--

DROP TABLE IF EXISTS `stock_adjustment_items`;
CREATE TABLE IF NOT EXISTS `stock_adjustment_items` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `stock_adjustment_id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `qty_change` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `stock_adjustment_id` (`stock_adjustment_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `stock_adjustment_items`
--

INSERT INTO `stock_adjustment_items` (`id`, `stock_adjustment_id`, `product_id`, `qty_change`, `created_at`) VALUES
(1, 2, 1, -1, '2025-09-01 11:19:17');

-- --------------------------------------------------------

--
-- Table structure for table `stock_transfers`
--

DROP TABLE IF EXISTS `stock_transfers`;
CREATE TABLE IF NOT EXISTS `stock_transfers` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `tr_no` varchar(32) NOT NULL,
  `from_warehouse_id` int UNSIGNED NOT NULL,
  `to_warehouse_id` int UNSIGNED NOT NULL,
  `note` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tr_no` (`tr_no`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `stock_transfers`
--

INSERT INTO `stock_transfers` (`id`, `tr_no`, `from_warehouse_id`, `to_warehouse_id`, `note`, `created_at`) VALUES
(2, 'TR250901-0002', 1, 2, '', '2025-09-01 11:19:57');

-- --------------------------------------------------------

--
-- Table structure for table `stock_transfer_items`
--

DROP TABLE IF EXISTS `stock_transfer_items`;
CREATE TABLE IF NOT EXISTS `stock_transfer_items` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `stock_transfer_id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `qty` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `stock_transfer_id` (`stock_transfer_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `stock_transfer_items`
--

INSERT INTO `stock_transfer_items` (`id`, `stock_transfer_id`, `product_id`, `qty`, `created_at`) VALUES
(1, 2, 2, 1, '2025-09-01 11:19:57');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `phone`, `email`, `address`) VALUES
(1, 'Ahmed Abdel Salam', '01120121343', 'ahmedfuture445@gmail.com', 'Nozha');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_payments`
--

DROP TABLE IF EXISTS `supplier_payments`;
CREATE TABLE IF NOT EXISTS `supplier_payments` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `supplier_id` int UNSIGNED NOT NULL,
  `purchase_invoice_id` int UNSIGNED NOT NULL,
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

--
-- Dumping data for table `supplier_payments`
--

INSERT INTO `supplier_payments` (`id`, `supplier_id`, `purchase_invoice_id`, `paid_at`, `method`, `reference`, `amount`, `note`, `created_at`) VALUES
(1, 1, 1, '2025-08-30 11:09:00', 'bank', 'TR56775', 650.00, '', '2025-08-31 08:10:00'),
(2, 1, 1, '2025-08-31 11:12:00', 'bank', 'TR89885', 1000.00, '', '2025-08-31 08:13:06');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` varchar(191) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'admin',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `role`, `created_at`) VALUES
(1, 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2025-08-29 13:37:55');

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_models`
--

DROP TABLE IF EXISTS `vehicle_models`;
CREATE TABLE IF NOT EXISTS `vehicle_models` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `make_id` int UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vm_make_slug` (`make_id`,`slug`),
  KEY `idx_vm_make` (`make_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `vehicle_models`
--

INSERT INTO `vehicle_models` (`id`, `make_id`, `name`, `slug`, `created_at`, `updated_at`) VALUES
(1, 1, 'Punto 2008', 'punto-2008', '2025-08-29 15:12:42', NULL),
(2, 2, 'Corolla 2019', 'corolla-2019', '2025-08-29 15:13:00', NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_index_performance`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `v_index_performance`;
CREATE TABLE IF NOT EXISTS `v_index_performance` (
`TABLE_NAME` varchar(64)
,`INDEX_NAME` varchar(64)
,`CARDINALITY` bigint
,`TABLE_ROWS` bigint unsigned
,`selectivity_percent` decimal(26,2)
,`selectivity_rating` varchar(20)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_table_performance`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `v_table_performance`;
CREATE TABLE IF NOT EXISTS `v_table_performance` (
`TABLE_NAME` varchar(64)
,`ENGINE` varchar(64)
,`TABLE_ROWS` bigint unsigned
,`data_mb` decimal(24,2)
,`index_mb` decimal(24,2)
,`free_mb` decimal(24,2)
,`index_ratio_percent` decimal(27,2)
,`UPDATE_TIME` datetime
,`CHECK_TIME` datetime
);

-- --------------------------------------------------------

--
-- Table structure for table `warehouses`
--

DROP TABLE IF EXISTS `warehouses`;
CREATE TABLE IF NOT EXISTS `warehouses` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(191) NOT NULL,
  `location` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `warehouses`
--

INSERT INTO `warehouses` (`id`, `code`, `name`, `location`, `created_at`, `updated_at`) VALUES
(1, 'WH01', 'Main Store', 'Nasr City - Industrial Zone', '2025-08-29 15:27:39', '2025-09-01 11:07:46'),
(2, 'WH02', 'Ramsis Warehouse', 'Ramsis Square', '2025-09-01 11:07:34', NULL);

-- --------------------------------------------------------

--
-- Structure for view `v_index_performance`
--
DROP TABLE IF EXISTS `v_index_performance`;

DROP VIEW IF EXISTS `v_index_performance`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_index_performance`  AS SELECT `information_schema`.`s`.`TABLE_NAME` AS `TABLE_NAME`, `information_schema`.`s`.`INDEX_NAME` AS `INDEX_NAME`, `information_schema`.`s`.`CARDINALITY` AS `CARDINALITY`, `information_schema`.`t`.`TABLE_ROWS` AS `TABLE_ROWS`, round((case when (`information_schema`.`t`.`TABLE_ROWS` > 0) then ((`information_schema`.`s`.`CARDINALITY` / `information_schema`.`t`.`TABLE_ROWS`) * 100) else 0 end),2) AS `selectivity_percent`, (case when (`information_schema`.`t`.`TABLE_ROWS` = 0) then 'No rows' when ((`information_schema`.`s`.`CARDINALITY` / `information_schema`.`t`.`TABLE_ROWS`) > 0.8) then 'High selectivity' when ((`information_schema`.`s`.`CARDINALITY` / `information_schema`.`t`.`TABLE_ROWS`) > 0.5) then 'Medium selectivity' when ((`information_schema`.`s`.`CARDINALITY` / `information_schema`.`t`.`TABLE_ROWS`) > 0.1) then 'Low selectivity' else 'Very low selectivity' end) AS `selectivity_rating` FROM (`information_schema`.`STATISTICS` `s` join `information_schema`.`TABLES` `t` on(((`information_schema`.`t`.`TABLE_NAME` = `information_schema`.`s`.`TABLE_NAME`) and (`information_schema`.`t`.`TABLE_SCHEMA` = `information_schema`.`s`.`TABLE_SCHEMA`)))) WHERE ((`information_schema`.`s`.`TABLE_SCHEMA` = database()) AND (`information_schema`.`s`.`INDEX_NAME` <> 'PRIMARY')) ORDER BY `information_schema`.`s`.`TABLE_NAME` ASC, round((case when (`information_schema`.`t`.`TABLE_ROWS` > 0) then ((`information_schema`.`s`.`CARDINALITY` / `information_schema`.`t`.`TABLE_ROWS`) * 100) else 0 end),2) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `v_table_performance`
--
DROP TABLE IF EXISTS `v_table_performance`;

DROP VIEW IF EXISTS `v_table_performance`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_table_performance`  AS SELECT `information_schema`.`tables`.`TABLE_NAME` AS `TABLE_NAME`, `information_schema`.`tables`.`ENGINE` AS `ENGINE`, `information_schema`.`tables`.`TABLE_ROWS` AS `TABLE_ROWS`, round(((`information_schema`.`tables`.`DATA_LENGTH` / 1024) / 1024),2) AS `data_mb`, round(((`information_schema`.`tables`.`INDEX_LENGTH` / 1024) / 1024),2) AS `index_mb`, round(((`information_schema`.`tables`.`DATA_FREE` / 1024) / 1024),2) AS `free_mb`, round((case when (`information_schema`.`tables`.`DATA_LENGTH` > 0) then ((`information_schema`.`tables`.`INDEX_LENGTH` / `information_schema`.`tables`.`DATA_LENGTH`) * 100) else 0 end),2) AS `index_ratio_percent`, `information_schema`.`tables`.`UPDATE_TIME` AS `UPDATE_TIME`, `information_schema`.`tables`.`CHECK_TIME` AS `CHECK_TIME` FROM `information_schema`.`TABLES` WHERE ((`information_schema`.`tables`.`TABLE_SCHEMA` = database()) AND (`information_schema`.`tables`.`ENGINE` = 'InnoDB')) ORDER BY `information_schema`.`tables`.`DATA_LENGTH` DESC ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `fk_invoice_items_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `invoice_payments`
--
ALTER TABLE `invoice_payments`
  ADD CONSTRAINT `fk_payments_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_products_make` FOREIGN KEY (`make_id`) REFERENCES `makes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_products_model` FOREIGN KEY (`model_id`) REFERENCES `vehicle_models` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `product_stocks`
--
ALTER TABLE `product_stocks`
  ADD CONSTRAINT `fk_ps_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ps_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `quotes`
--
ALTER TABLE `quotes`
  ADD CONSTRAINT `fk_quotes_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `quote_items`
--
ALTER TABLE `quote_items`
  ADD CONSTRAINT `fk_qi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_qi_quote` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_qi_wh` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `sales_orders`
--
ALTER TABLE `sales_orders`
  ADD CONSTRAINT `fk_so_quote` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `sales_order_items`
--
ALTER TABLE `sales_order_items`
  ADD CONSTRAINT `fk_soi_so` FOREIGN KEY (`sales_order_id`) REFERENCES `sales_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `vehicle_models`
--
ALTER TABLE `vehicle_models`
  ADD CONSTRAINT `fk_vm_make` FOREIGN KEY (`make_id`) REFERENCES `makes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
