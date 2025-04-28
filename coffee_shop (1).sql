-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 28, 2025 at 02:15 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `coffee_shop`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `category_id` int(11) DEFAULT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'Other'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `stock`, `category_id`, `category`) VALUES
(1, 'Espresso', 125.00, 75, NULL, 'Drink'),
(2, 'Cappuccino', 138.00, 75, NULL, 'Drink'),
(3, 'Latte', 139.00, 90, NULL, 'Drink'),
(4, 'Mocha', 149.00, 92, NULL, 'Drink'),
(5, 'Americano', 149.00, 71, NULL, 'Drink'),
(6, 'Coffee Frappuccino Blended Beverage', 186.00, 77, NULL, 'Drink'),
(8, 'New York Cheesecake', 140.00, 100, NULL, 'Dessert');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `sale_order_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `sale_time` datetime DEFAULT current_timestamp(),
  `cashier_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `sale_order_id`, `product_id`, `quantity`, `total_price`, `sale_time`, `cashier_id`) VALUES
(26, NULL, 4, 1, 4.00, '2025-04-21 01:25:36', 5),
(27, NULL, 5, 1, 2.75, '2025-04-21 01:25:36', 5),
(28, NULL, 6, 2, 372.00, '2025-04-21 01:25:36', 5),
(29, NULL, 1, 1, 2.50, '2025-04-21 01:26:39', 5),
(30, NULL, 2, 1, 3.00, '2025-04-21 01:26:39', 5),
(31, NULL, 3, 1, 3.50, '2025-04-21 01:26:39', 5),
(32, NULL, 4, 1, 4.00, '2025-04-21 01:26:39', 5),
(33, NULL, 5, 1, 2.75, '2025-04-21 01:26:39', 5),
(34, NULL, 6, 2, 372.00, '2025-04-21 01:26:39', 5),
(35, NULL, 1, 1, 2.50, '2025-04-21 01:32:10', 5),
(36, NULL, 2, 1, 3.00, '2025-04-21 01:32:10', 5),
(37, NULL, 3, 1, 3.50, '2025-04-21 01:32:10', 5),
(38, NULL, 4, 1, 4.00, '2025-04-21 01:32:10', 5),
(39, NULL, 5, 1, 2.75, '2025-04-21 01:32:10', 5),
(40, NULL, 6, 2, 372.00, '2025-04-21 01:32:10', 5),
(41, NULL, 1, 1, 2.50, '2025-04-21 01:32:26', 5),
(42, NULL, 2, 1, 3.00, '2025-04-21 01:32:26', 5),
(43, NULL, 5, 1, 2.75, '2025-04-21 01:32:26', 5),
(44, NULL, 1, 1, 2.50, '2025-04-21 01:32:47', 5),
(45, NULL, 2, 1, 3.00, '2025-04-21 01:32:47', 5),
(46, NULL, 5, 1, 2.75, '2025-04-21 01:32:47', 5),
(47, NULL, 1, 1, 2.50, '2025-04-21 01:42:07', 5),
(48, NULL, 2, 1, 3.00, '2025-04-21 01:42:07', 5),
(49, NULL, 5, 1, 2.75, '2025-04-21 01:42:07', 5),
(50, NULL, 2, 1, 3.00, '2025-04-21 01:42:26', 5),
(51, NULL, 5, 1, 2.75, '2025-04-21 01:42:26', 5),
(52, NULL, 2, 1, 3.00, '2025-04-21 01:44:49', 5),
(53, NULL, 5, 1, 2.75, '2025-04-21 01:44:49', 5),
(54, NULL, 5, 1, 2.75, '2025-04-21 01:45:06', 5),
(55, NULL, 6, 1, 186.00, '2025-04-21 01:45:06', 5),
(56, NULL, 1, 1, 2.50, '2025-04-21 09:54:47', 4),
(57, NULL, 2, 2, 6.00, '2025-04-21 09:54:47', 4),
(58, NULL, 5, 1, 2.75, '2025-04-21 09:54:47', 4),
(59, NULL, 6, 1, 186.00, '2025-04-21 09:54:47', 4),
(60, NULL, 1, 1, 2.50, '2025-04-21 09:58:47', 4),
(61, NULL, 2, 2, 6.00, '2025-04-21 09:58:47', 4),
(62, NULL, 5, 1, 2.75, '2025-04-21 09:58:47', 4),
(63, NULL, 6, 1, 186.00, '2025-04-21 09:58:47', 4),
(64, NULL, 2, 1, 3.00, '2025-04-21 09:59:01', 4),
(65, NULL, 6, 1, 186.00, '2025-04-21 09:59:01', 4),
(66, NULL, 1, 1, 2.50, '2025-04-27 18:39:57', 4),
(67, NULL, 2, 1, 3.00, '2025-04-27 18:39:57', 4),
(68, NULL, 5, 3, 8.25, '2025-04-27 18:39:57', 4),
(69, NULL, 6, 1, 186.00, '2025-04-27 18:39:57', 4),
(80, 5, 1, 2, 5.00, '2025-04-27 20:11:43', 5),
(81, 5, 3, 2, 7.00, '2025-04-27 20:11:43', 5),
(82, 5, 4, 1, 4.00, '2025-04-27 20:11:43', 5),
(83, 5, 5, 3, 8.25, '2025-04-27 20:11:43', 5);

-- --------------------------------------------------------

--
-- Table structure for table `sales_orders`
--

CREATE TABLE `sales_orders` (
  `id` int(11) NOT NULL,
  `cashier_id` int(11) NOT NULL,
  `sale_time` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales_orders`
--

INSERT INTO `sales_orders` (`id`, `cashier_id`, `sale_time`) VALUES
(5, 5, '2025-04-27 20:11:43');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','cashier') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES
(4, 'admin', '$2a$10$.yPByloyuc7D0NWP5fgx6eRX8H7iU4tsv2QyYJoQPCVanAcSiU9H.', 'admin'),
(5, 'lauro', '$2y$10$6BV6k0HBreqkzIlLbC5gvuHmKGhcGC/zsncdcdVAGXJhFtmNLyivy', 'cashier');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_category` (`category_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `cashier_id` (`cashier_id`),
  ADD KEY `fk_sale_order` (`sale_order_id`);

--
-- Indexes for table `sales_orders`
--
ALTER TABLE `sales_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cashier_id` (`cashier_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `sales_orders`
--
ALTER TABLE `sales_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `fk_sale_order` FOREIGN KEY (`sale_order_id`) REFERENCES `sales_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sales_orders`
--
ALTER TABLE `sales_orders`
  ADD CONSTRAINT `sales_orders_ibfk_1` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
