-- phpMyAdmin SQL Dump
-- SaaS ERP and E-Commerce Platform Schema
-- Ensure you run this in an empty database

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------

--
-- Table structure for table `stores`
--

DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `stores`;

CREATE TABLE `stores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_name` varchar(255) NOT NULL,
  `subdomain` varchar(100) NOT NULL UNIQUE,
  `custom_domain` varchar(255) DEFAULT NULL,
  `owner_email` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','suspended','pending') DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  `role` enum('owner','admin','customer') NOT NULL DEFAULT 'customer',
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `store_id` (`store_id`),
  FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `store_id` (`store_id`),
  FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `low_stock_threshold` int(11) NOT NULL DEFAULT 5,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `store_id` (`store_id`),
  KEY `category_id` (`category_id`),
  FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `payment_status` enum('unpaid','paid','refunded') DEFAULT 'unpaid',
  `shipping_address` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `store_id` (`store_id`),
  KEY `customer_id` (`customer_id`),
  FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_at_time` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Seed Data for Stores
-- --------------------------------------------------------

INSERT INTO `stores` (`id`, `store_name`, `subdomain`, `owner_email`) VALUES
(1, 'Demo Electronics Store', 'electronics', 'admin@electronics.com'),
(2, 'Demo Clothing Boutique', 'clothing', 'admin@clothing.com');

-- --------------------------------------------------------
-- Seed Data for Owners & Customers
-- --------------------------------------------------------

-- Owners
INSERT INTO `users` (`id`, `store_id`, `role`, `name`, `email`, `password_hash`) VALUES
(1, 1, 'owner', 'Admin User', 'admin@electronics.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'), /* password: password */
(2, 2, 'owner', 'Admin User', 'admin@clothing.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Customers for Store 1 (Electronics)
INSERT INTO `users` (`id`, `store_id`, `role`, `name`, `email`, `password_hash`, `phone`, `address`) VALUES
(3, 1, 'customer', 'Alex Rivers', 'alex@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+1 (555) 019-2834', '124 Pine St, Seattle, WA 98101'),
(4, 1, 'customer', 'Morgan Lee', 'morgan@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+1 (555) 014-9988', '789 Oak Ave, San Francisco, CA 94102'),
(5, 1, 'customer', 'Taylor Swift', 'taylor@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+1 (555) 012-3456', '221B Baker St, London, UK');

-- Customers for Store 2 (Clothing)
INSERT INTO `users` (`id`, `store_id`, `role`, `name`, `email`, `password_hash`, `phone`, `address`) VALUES
(6, 2, 'customer', 'Jordan Blake', 'jordan@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+1 (555) 017-8822', '456 Fashion Blvd, New York, NY 10001'),
(7, 2, 'customer', 'Sam Smith', 'sam@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+1 (555) 015-4433', '12 Broadway, New York, NY 10003');

-- --------------------------------------------------------
-- Seed Data for Categories
-- --------------------------------------------------------

-- Store 1 Categories
INSERT INTO `categories` (`id`, `store_id`, `name`, `slug`) VALUES
(1, 1, 'Audio Devices', 'audio'),
(2, 1, 'Wearables', 'wearables'),
(3, 1, 'High-Speed Storage', 'storage'),
(4, 1, 'Accessories', 'accessories');

-- Store 2 Categories
INSERT INTO `categories` (`id`, `store_id`, `name`, `slug`) VALUES
(5, 2, 'Premium Apparel', 'apparel'),
(6, 2, 'Footwear', 'footwear'),
(7, 2, 'Bags & Packs', 'bags'),
(8, 2, 'Accessories', 'accessories');

-- --------------------------------------------------------
-- Seed Data for Products
-- --------------------------------------------------------

-- Store 1 Products (Electronics)
INSERT INTO `products` (`id`, `store_id`, `category_id`, `name`, `slug`, `description`, `price`, `stock_quantity`, `low_stock_threshold`, `image_url`) VALUES
(1, 1, 1, 'Pro ANC Wireless Headphones', 'pro-anc-headphones', 'Experience pure sound with active noise-cancelling wireless headphones. 40-hour battery life and fast charging.', 199.99, 45, 5, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&auto=format&fit=crop&q=60'),
(2, 1, 4, 'RGB Mechanical Keyboard', 'rgb-mech-keyboard', 'Tactile blue switches, customize lighting per key, aluminum frame, perfect for gaming or working.', 129.99, 12, 5, 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?w=500&auto=format&fit=crop&q=60'),
(3, 1, 3, 'Ultra Portable 1TB SSD', 'portable-ssd-1tb', 'Superfast read speeds up to 1050MB/s, durable drop-resistant shell, USB-C connectivity.', 89.99, 3, 5, 'https://images.unsplash.com/photo-1597872200969-2b65d56bd16b?w=500&auto=format&fit=crop&q=60'),
(4, 1, 2, 'Smart Fitness Watch V2', 'smart-watch-v2', 'All-day activity tracking, heart rate monitor, sleep analysis, and built-in GPS with a vibrant AMOLED display.', 149.99, 18, 5, 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=500&auto=format&fit=crop&q=60');

-- Store 2 Products (Clothing)
INSERT INTO `products` (`id`, `store_id`, `category_id`, `name`, `slug`, `description`, `price`, `stock_quantity`, `low_stock_threshold`, `image_url`) VALUES
(5, 2, 5, 'Minimalist Cotton Hoodie', 'minimalist-cotton-hoodie', 'Ultra-soft organic cotton hoodie in a relaxed premium fit. Ethically manufactured, perfect for everyday wear.', 59.99, 60, 10, 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=500&auto=format&fit=crop&q=60'),
(6, 2, 6, 'Handcrafted Leather Boots', 'handcrafted-leather-boots', 'Durable, waterproof full-grain leather boots. Hand-stitched welt, comfortable support, ages beautifully.', 179.99, 8, 3, 'https://images.unsplash.com/photo-1520639888713-7851133b1ed0?w=500&auto=format&fit=crop&q=60'),
(7, 2, 7, 'Waterproof Canvas Backpack', 'waterproof-canvas-backpack', 'Spacious roll-top backpack, heavy-duty waxed canvas, dedicated 15-inch laptop sleeve.', 89.99, 4, 3, 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=500&auto=format&fit=crop&q=60'),
(8, 2, 5, 'Vintage Denim Jacket', 'vintage-denim-jacket', 'Classic relaxed denim jacket in a faded wash. Features heavy-duty metal buttons and reinforced pockets.', 79.99, 32, 5, 'https://images.unsplash.com/photo-1576995853123-5a10305d93c0?w=500&auto=format&fit=crop&q=60');

-- --------------------------------------------------------
-- Seed Data for Orders (Aggregated over past months to drive Analytics)
-- --------------------------------------------------------

-- Store 1 Orders (Jan, Feb, Mar, Apr, May 2026)
INSERT INTO `orders` (`id`, `store_id`, `customer_id`, `total_amount`, `status`, `payment_status`, `shipping_address`, `created_at`) VALUES
(1, 1, 3, 399.98, 'delivered', 'paid', '124 Pine St, Seattle, WA 98101', '2026-01-15 10:30:00'),
(2, 1, 4, 89.99, 'delivered', 'paid', '789 Oak Ave, San Francisco, CA 94102', '2026-02-12 14:15:00'),
(3, 1, 5, 279.98, 'delivered', 'paid', '221B Baker St, London, UK', '2026-03-20 09:45:00'),
(4, 1, 3, 149.99, 'delivered', 'paid', '124 Pine St, Seattle, WA 98101', '2026-04-05 18:22:00'),
(5, 1, 4, 349.98, 'processing', 'paid', '789 Oak Ave, San Francisco, CA 94102', '2026-05-28 11:05:00'),
(6, 1, 5, 199.99, 'pending', 'unpaid', '221B Baker St, London, UK', '2026-05-30 08:00:00');

-- Store 2 Orders (Jan, Feb, Mar, Apr, May 2026)
INSERT INTO `orders` (`id`, `store_id`, `customer_id`, `total_amount`, `status`, `payment_status`, `shipping_address`, `created_at`) VALUES
(7, 2, 6, 239.98, 'delivered', 'paid', '456 Fashion Blvd, New York, NY 10001', '2026-01-20 16:30:00'),
(8, 2, 7, 59.99, 'delivered', 'paid', '12 Broadway, New York, NY 10003', '2026-02-18 11:00:00'),
(9, 2, 6, 179.99, 'delivered', 'paid', '456 Fashion Blvd, New York, NY 10001', '2026-03-25 15:45:00'),
(10, 2, 7, 149.98, 'delivered', 'paid', '12 Broadway, New York, NY 10003', '2026-04-10 13:20:00'),
(11, 2, 6, 269.98, 'shipped', 'paid', '456 Fashion Blvd, New York, NY 10001', '2026-05-25 10:10:00');

-- --------------------------------------------------------
-- Seed Data for Order Items
-- --------------------------------------------------------

-- Store 1 Order Line Items
INSERT INTO `order_items` (`order_id`, `product_id`, `quantity`, `price_at_time`) VALUES
(1, 1, 2, 199.99), -- 2x ANC Headphones
(2, 3, 1, 89.99),  -- 1x SSD
(3, 4, 1, 149.99), -- 1x Smart Watch
(3, 2, 1, 129.99), -- 1x RGB Keyboard
(4, 4, 1, 149.99), -- 1x Smart Watch
(5, 1, 1, 199.99), -- 1x ANC Headphones
(5, 4, 1, 149.99), -- 1x Smart Watch
(6, 1, 1, 199.99); -- 1x ANC Headphones

-- Store 2 Order Line Items
INSERT INTO `order_items` (`order_id`, `product_id`, `quantity`, `price_at_time`) VALUES
(7, 5, 1, 59.99),  -- 1x Hoodie
(7, 6, 1, 179.99), -- 1x Boots
(8, 5, 1, 59.99),  -- 1x Hoodie
(9, 6, 1, 179.99), -- 1x Boots
(10, 8, 1, 79.99), -- 1x Jacket
(10, 5, 1, 59.99), -- 1x Hoodie
(11, 6, 1, 179.99),-- 1x Boots
(11, 7, 1, 89.99); -- 1x Canvas Backpack

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
