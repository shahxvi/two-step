-- phpMyAdmin SQL Dump
-- version 5.2.3-2.fc44
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Nov 13, 2026 at 08:03 AM
-- Server version: 8.4.10
-- PHP Version: 8.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `two_step`
--

-- --------------------------------------------------------

--
-- Table structure for table `brand`
--

CREATE TABLE `brand` (
  `brand_id` bigint NOT NULL,
  `brand_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `brand_country` bigint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `brand`
--

INSERT INTO `brand` (`brand_id`, `brand_name`, `brand_country`) VALUES
(1, 'Puma', 0),
(2, 'Nike', 0),
(3, 'Adidas', 0);

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cart_id` bigint NOT NULL,
  `shoe_id` bigint NOT NULL,
  `user_id` bigint NOT NULL,
  `quantity` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`cart_id`, `shoe_id`, `user_id`, `quantity`) VALUES
(10, 5, 3, 1);

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `cart_items_id` bigint NOT NULL,
  `cart_id` bigint NOT NULL,
  `shoe_variant_id` bigint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `category_id` bigint NOT NULL,
  `category_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `category_description` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`category_id`, `category_name`, `category_description`) VALUES
(1, 'Sport Shoes', 'Sporty and suitable for either running nor jogging '),
(2, 'Casual', 'Comfortable for everyday routines'),
(3, 'Ballerina', 'Chic and stylish flat slip-ons'),
(4, 'High Top', 'Trendy elevated platform sneakers');

-- --------------------------------------------------------

--
-- Table structure for table `color`
--

CREATE TABLE `color` (
  `color_id` bigint NOT NULL,
  `color_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `color`
--

INSERT INTO `color` (`color_id`, `color_name`) VALUES
(1, 'Silver'),
(2, 'Cream'),
(3, 'Pink'),
(4, 'Black'),
(5, 'White'),
(6, 'Red');

-- --------------------------------------------------------

--
-- Table structure for table `customer_order`
--

CREATE TABLE `customer_order` (
  `customer_order_id` bigint NOT NULL,
  `user_id` bigint NOT NULL,
  `status_id` bigint NOT NULL,
  `order_date_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gender_target`
--

CREATE TABLE `gender_target` (
  `gender_target_id` bigint NOT NULL,
  `gender` varchar(255) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gender_target`
--

INSERT INTO `gender_target` (`gender_target_id`, `gender`) VALUES
(1, 'Women'),
(2, 'Men'),
(3, 'Unisex');

-- --------------------------------------------------------

--
-- Table structure for table `order_details`
--

CREATE TABLE `order_details` (
  `order_details_id` bigint NOT NULL,
  `customer_order_id` bigint NOT NULL,
  `shoe_variant_id` bigint NOT NULL,
  `quantity` bigint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `role`
--

CREATE TABLE `role` (
  `role_id` bigint NOT NULL,
  `role_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`role_id`, `role_name`) VALUES
(1, 'admin'),
(2, 'staff'),
(3, 'customer');

-- --------------------------------------------------------

--
-- Table structure for table `shoe`
--

CREATE TABLE `shoe` (
  `shoe_id` bigint NOT NULL,
  `brand_id` bigint NOT NULL,
  `category_id` bigint NOT NULL,
  `shoe_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `gender_target_id` bigint NOT NULL,
  `shoe_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `image_path` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'images/logo.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shoe`
--

INSERT INTO `shoe` (`shoe_id`, `brand_id`, `category_id`, `shoe_name`, `description`, `gender_target_id`, `shoe_price`, `image_path`) VALUES
(1, 2, 2, 'Silver Plate', 'Silver black runner', 3, 650.00, 'images/silver_plate.png'),
(2, 2, 1, 'Cream Cookie', 'Daily walk classic', 3, 500.00, 'images/dream_cookie.png'),
(3, 3, 2, 'Red Velvet', 'Maroon retro', 3, 480.00, 'images/red_velvet.png'),
(4, 1, 2, 'White Cat', 'Off White Speedshoe', 1, 480.00, 'images/white_cat.png'),
(5, 3, 2, 'Sea Walkers', 'Navy sea color', 1, 490.00, 'images/sea_walkers.png'),
(6, 1, 1, 'Pink Barbie', 'Bright runners', 1, 700.00, 'images/pink_barbie.png'),
(7, 1, 3, 'BlackPink Ballerina', 'Easy Walking Shoes', 1, 450.00, 'images/blackpink_ballerina.png'),
(8, 2, 4, 'Star White', 'High tall stars', 1, 550.00, 'images/star_white.png'),
(9, 1, 3, 'Dots Creamy', 'Pokadot Ballerina', 1, 700.00, 'images/dots_creamy.png'),
(10, 1, 3, 'Retro', 'Blue light grey', 2, 350.00, 'images/retro.png');

-- --------------------------------------------------------

--
-- Table structure for table `shoe_variant`
--

CREATE TABLE `shoe_variant` (
  `shoe_variant_id` bigint NOT NULL,
  `shoe_id` bigint NOT NULL,
  `size_id` bigint NOT NULL,
  `color_id` bigint NOT NULL,
  `status_id` bigint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shoe_variant`
--

INSERT INTO `shoe_variant` (`shoe_variant_id`, `shoe_id`, `size_id`, `color_id`, `status_id`) VALUES
(1, 2, 3, 1, 1),
(2, 1, 4, 2, 1),
(3, 6, 2, 3, 2),
(4, 3, 1, 6, 3);

-- --------------------------------------------------------

--
-- Table structure for table `size`
--

CREATE TABLE `size` (
  `size_id` bigint NOT NULL,
  `size_cm` bigint NOT NULL,
  `size_us` bigint NOT NULL,
  `size_uk` bigint NOT NULL,
  `size_eu` bigint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `size`
--

INSERT INTO `size` (`size_id`, `size_cm`, `size_us`, `size_uk`, `size_eu`) VALUES
(1, 23, 6, 4, 37),
(2, 24, 7, 5, 38),
(3, 25, 8, 6, 38),
(4, 26, 9, 7, 40);

-- --------------------------------------------------------

--
-- Table structure for table `status`
--

CREATE TABLE `status` (
  `status_id` bigint NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `status_description` text COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `status`
--

INSERT INTO `status` (`status_id`, `status`, `status_description`) VALUES
(1, 'In Stock', 'Shoe ready to buy.'),
(2, 'Out of Stock', 'This stock size/color is finished.'),
(3, 'Discontinued', 'This shoe no longer sold.');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` bigint NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `fullname` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nric` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `join_date` datetime DEFAULT NULL,
  `leave_date` datetime DEFAULT NULL,
  `role_id` bigint DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `username`, `password`, `fullname`, `nric`, `join_date`, `leave_date`, `role_id`) VALUES
(3, 'mad', '123456', NULL, NULL, NULL, NULL, 3),
(7, 'mar', '123456', NULL, NULL, NULL, NULL, 2),
(8, 'shahxvi', '482377', NULL, NULL, NULL, NULL, 1),
(9, 'MarCelo', '123456', NULL, NULL, NULL, NULL, 3);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `brand`
--
ALTER TABLE `brand`
  ADD PRIMARY KEY (`brand_id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `cart_user_id_foreign` (`user_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`cart_items_id`),
  ADD KEY `cart_items_cart_id_foreign` (`cart_id`),
  ADD KEY `cart_items_shoe_variant_id_foreign` (`shoe_variant_id`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `color`
--
ALTER TABLE `color`
  ADD PRIMARY KEY (`color_id`);

--
-- Indexes for table `customer_order`
--
ALTER TABLE `customer_order`
  ADD PRIMARY KEY (`customer_order_id`),
  ADD KEY `customer_order_status_id_foreign` (`status_id`),
  ADD KEY `customer_order_user_id_foreign` (`user_id`);

--
-- Indexes for table `gender_target`
--
ALTER TABLE `gender_target`
  ADD PRIMARY KEY (`gender_target_id`);

--
-- Indexes for table `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`order_details_id`),
  ADD KEY `order_details_customer_order_id_foreign` (`customer_order_id`),
  ADD KEY `order_details_shoe_variant_id_foreign` (`shoe_variant_id`);

--
-- Indexes for table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`role_id`);

--
-- Indexes for table `shoe`
--
ALTER TABLE `shoe`
  ADD PRIMARY KEY (`shoe_id`),
  ADD KEY `shoe_category_id_foreign` (`category_id`),
  ADD KEY `shoe_brand_id_foreign` (`brand_id`),
  ADD KEY `shoe_gender_target_id_foreign` (`gender_target_id`);

--
-- Indexes for table `shoe_variant`
--
ALTER TABLE `shoe_variant`
  ADD PRIMARY KEY (`shoe_variant_id`),
  ADD KEY `shoe_variant_shoe_id_foreign` (`shoe_id`),
  ADD KEY `shoe_variant_size_id_foreign` (`size_id`),
  ADD KEY `shoe_variant_color_id_foreign` (`color_id`),
  ADD KEY `shoe_variant_status_id_foreign` (`status_id`);

--
-- Indexes for table `size`
--
ALTER TABLE `size`
  ADD PRIMARY KEY (`size_id`);

--
-- Indexes for table `status`
--
ALTER TABLE `status`
  ADD PRIMARY KEY (`status_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `user_role_id_foreign` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `brand`
--
ALTER TABLE `brand`
  MODIFY `brand_id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `cart_items_id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `category_id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `color`
--
ALTER TABLE `color`
  MODIFY `color_id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `customer_order`
--
ALTER TABLE `customer_order`
  MODIFY `customer_order_id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gender_target`
--
ALTER TABLE `gender_target`
  MODIFY `gender_target_id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `order_details_id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `role`
--
ALTER TABLE `role`
  MODIFY `role_id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `shoe`
--
ALTER TABLE `shoe`
  MODIFY `shoe_id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `shoe_variant`
--
ALTER TABLE `shoe_variant`
  MODIFY `shoe_variant_id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `size`
--
ALTER TABLE `size`
  MODIFY `size_id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `status`
--
ALTER TABLE `status`
  MODIFY `status_id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_cart_id_foreign` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`cart_id`),
  ADD CONSTRAINT `cart_items_shoe_variant_id_foreign` FOREIGN KEY (`shoe_variant_id`) REFERENCES `shoe_variant` (`shoe_variant_id`);

--
-- Constraints for table `customer_order`
--
ALTER TABLE `customer_order`
  ADD CONSTRAINT `customer_order_status_id_foreign` FOREIGN KEY (`status_id`) REFERENCES `status` (`status_id`),
  ADD CONSTRAINT `customer_order_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `order_details_customer_order_id_foreign` FOREIGN KEY (`customer_order_id`) REFERENCES `customer_order` (`customer_order_id`),
  ADD CONSTRAINT `order_details_shoe_variant_id_foreign` FOREIGN KEY (`shoe_variant_id`) REFERENCES `shoe_variant` (`shoe_variant_id`);

--
-- Constraints for table `shoe`
--
ALTER TABLE `shoe`
  ADD CONSTRAINT `shoe_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brand` (`brand_id`),
  ADD CONSTRAINT `shoe_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`),
  ADD CONSTRAINT `shoe_gender_target_id_foreign` FOREIGN KEY (`gender_target_id`) REFERENCES `gender_target` (`gender_target_id`);

--
-- Constraints for table `shoe_variant`
--
ALTER TABLE `shoe_variant`
  ADD CONSTRAINT `shoe_variant_color_id_foreign` FOREIGN KEY (`color_id`) REFERENCES `color` (`color_id`),
  ADD CONSTRAINT `shoe_variant_shoe_id_foreign` FOREIGN KEY (`shoe_id`) REFERENCES `shoe` (`shoe_id`),
  ADD CONSTRAINT `shoe_variant_size_id_foreign` FOREIGN KEY (`size_id`) REFERENCES `size` (`size_id`),
  ADD CONSTRAINT `shoe_variant_status_id_foreign` FOREIGN KEY (`status_id`) REFERENCES `status` (`status_id`);

--
-- Constraints for table `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `role` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
