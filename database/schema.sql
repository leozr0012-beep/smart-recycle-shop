CREATE DATABASE IF NOT EXISTS `used_appliance_shop` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `used_appliance_shop`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `phone` VARCHAR(32) NOT NULL,
  `email` VARCHAR(180) NOT NULL,
  `line_id` VARCHAR(80) DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('customer','admin','technician','driver') NOT NULL DEFAULT 'customer',
  `avatar` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `user_addresses` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `address` TEXT NOT NULL,
  `city` VARCHAR(120) DEFAULT NULL,
  `zipcode` VARCHAR(20) DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_user_addresses_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(180) NOT NULL,
  `category` ENUM('fridge','washer','ac','tv') NOT NULL,
  `grade` ENUM('A','B','C') NOT NULL DEFAULT 'A',
  `color` VARCHAR(50) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `original_price` DECIMAL(10,2) DEFAULT NULL,
  `stock` INT UNSIGNED NOT NULL DEFAULT 1,
  `featured` TINYINT(1) NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `short_description` TEXT,
  `description` TEXT,
  `warranty_info` VARCHAR(255) DEFAULT '30 days warranty',
  `line_contact` VARCHAR(80) DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `product_images` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `url` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_product_image_product` (`product_id`),
  CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `product_specs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `spec_key` VARCHAR(120) NOT NULL,
  `spec_value` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_spec_product` (`product_id`),
  CONSTRAINT `fk_product_specs_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `status` ENUM('pending','paid','shipping','done') NOT NULL DEFAULT 'pending',
  `total_amount` DECIMAL(12,2) NOT NULL,
  `shipping_cost` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `distance_km` DECIMAL(5,2) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  `phone_contact` VARCHAR(32) NOT NULL,
  `delivery_address` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_user` (`user_id`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED DEFAULT NULL,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `total_price` DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_item_order` (`order_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL,
  `paid_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_payment_order` (`order_id`),
  CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `preorders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_type` VARCHAR(80) NOT NULL,
  `budget_range` VARCHAR(120) NOT NULL,
  `brand_preference` VARCHAR(120) DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `preorder_contacts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `preorder_id` INT UNSIGNED NOT NULL,
  `phone` VARCHAR(32) NOT NULL,
  `email` VARCHAR(180) DEFAULT NULL,
  `line_id` VARCHAR(80) DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_preorder_contact_preorder` (`preorder_id`),
  CONSTRAINT `fk_preorder_contacts_preorder` FOREIGN KEY (`preorder_id`) REFERENCES `preorders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `shipments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `status` VARCHAR(50) NOT NULL,
  `tracking_code` VARCHAR(120) NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_shipment_order` (`order_id`),
  CONSTRAINT `fk_shipments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `shipment_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `shipment_id` INT UNSIGNED NOT NULL,
  `status` VARCHAR(50) NOT NULL,
  `note` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_shipment_logs_shipment` (`shipment_id`),
  CONSTRAINT `fk_shipment_logs_shipment` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `warranties` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `status` ENUM('active','expired') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_warranty_user` (`user_id`),
  CONSTRAINT `fk_warranties_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_warranties_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_warranties_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `warranty_claims` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `warranty_id` INT UNSIGNED NOT NULL,
  `issue` TEXT NOT NULL,
  `submitted_at` DATETIME NOT NULL,
  `status` ENUM('open','resolved','rejected') NOT NULL DEFAULT 'open',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_warranty_claims_warranty` (`warranty_id`),
  CONSTRAINT `fk_warranty_claims_warranty` FOREIGN KEY (`warranty_id`) REFERENCES `warranties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `repair_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `notes` TEXT NOT NULL,
  `technician_id` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_repair_order` (`order_id`),
  CONSTRAINT `fk_repair_logs_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `admin_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` INT UNSIGNED NOT NULL,
  `action` VARCHAR(255) NOT NULL,
  `record_type` VARCHAR(80) NOT NULL,
  `record_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sales_analytics` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `total_amount` DECIMAL(12,2) NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sales_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO users (name, phone, email, line_id, password, role, created_at) VALUES
('Admin User', '0900000000', 'admin@example.com', 'admin_line', '$2y$10$TbtWwAWLy7dGmOX4hn2gneeFkLy/cO6Hd21m4fIhO6rw3UyvTtnpm', 'admin', NOW()),
('Demo Customer', '0912345678', 'customer@example.com', 'demo_line', '$2y$10$tu3lZ4WoJD0mnKGmC0sasOgp5upO7SDKm3px1lhBirJ7n/K8pLdFe', 'customer', NOW());

INSERT IGNORE INTO products (name, category, grade, color, price, stock, featured, status, short_description, description, warranty_info, line_contact, created_at) VALUES
('Silver Energy Star Fridge', 'fridge', 'A', 'silver', 15990.00, 5, 1, 1, 'A premium second-hand fridge with energy efficiency.', 'Large capacity fridge with frost-free cooling and digital controls.', '30 days warranty', 'fridge_line', NOW()),
('Compact Washer Dryer', 'washer', 'B', 'white', 8990.00, 4, 1, 1, 'Space-saving washer dryer ideal for apartment living.', 'Fully functional washer dryer with multiple wash modes and gentle care.', '30 days warranty', 'washer_line', NOW()),
('Smart LED TV 50 inch', 'tv', 'A', 'black', 12500.00, 7, 1, 1, 'High-definition LED TV with smart streaming support.', 'Excellent picture quality with HDMI and Wi-Fi connectivity.', '30 days warranty', 'tv_line', NOW());

INSERT IGNORE INTO product_images (product_id, url, created_at) VALUES
(1, 'https://images.unsplash.com/photo-1581093448795-7ad6ab38261f?auto=format&fit=crop&w=800&q=80', NOW()),
(1, 'https://images.unsplash.com/photo-1570129477492-45c003edd2be?auto=format&fit=crop&w=800&q=80', NOW()),
(2, 'https://images.unsplash.com/photo-1519710164239-da123dc03ef4?auto=format&fit=crop&w=800&q=80', NOW()),
(2, 'https://images.unsplash.com/photo-1556905055-8f358a7a47b3?auto=format&fit=crop&w=800&q=80', NOW()),
(3, 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=800&q=80', NOW()),
(3, 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=800&q=80', NOW());

INSERT IGNORE INTO product_specs (product_id, spec_key, spec_value) VALUES
(1, 'Capacity', '320L'),
(1, 'Type', 'Frost Free'),
(1, 'Energy rating', 'A++'),
(2, 'Capacity', '9 kg'),
(2, 'Spin speed', '1200 rpm'),
(2, 'Programmes', '12 wash cycles'),
(3, 'Screen size', '50 inch'),
(3, 'Resolution', '4K UHD'),
(3, 'Connectivity', 'Wi-Fi / HDMI');

INSERT IGNORE INTO preorders (product_type, budget_range, brand_preference, created_at) VALUES
('ac', '10000-15000', 'Panasonic', NOW());

INSERT IGNORE INTO preorder_contacts (preorder_id, phone, email, line_id, created_at) VALUES
(1, '0923456789', 'preorder@example.com', 'line_preorder', NOW());
