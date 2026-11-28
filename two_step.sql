-- ========================================================
-- Database: `two_step`
-- ========================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- 1. CREATE TABLES (Parent Tables First)
-- --------------------------------------------------------

CREATE TABLE `brand` (
  `brand_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `brand_name` varchar(255) NOT NULL,
  `brand_country` bigint(20) NOT NULL,
  PRIMARY KEY (`brand_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `category` (
  `category_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(255) NOT NULL,
  `category_description` text DEFAULT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `color` (
  `color_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `color_name` varchar(255) NOT NULL,
  PRIMARY KEY (`color_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `gender_target` (
  `gender_target_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `gender` varchar(255) NOT NULL,
  PRIMARY KEY (`gender_target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `role` (
  `role_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(255) NOT NULL,
  PRIMARY KEY (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `size` (
  `size_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `size_cm` bigint(20) NOT NULL,
  `size_us` bigint(20) NOT NULL,
  `size_uk` bigint(20) NOT NULL,
  `size_eu` bigint(20) NOT NULL,
  PRIMARY KEY (`size_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `status` (
  `status_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `status` varchar(255) NOT NULL,
  `status_description` text NOT NULL,
  PRIMARY KEY (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 2. CREATE TABLES (Child & Transaction Tables)
-- --------------------------------------------------------

CREATE TABLE `user` (
  `user_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `nric` varchar(255) DEFAULT NULL,
  `join_date` datetime DEFAULT NULL,
  `leave_date` datetime DEFAULT NULL,
  `role_id` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  KEY `user_role_id_foreign` (`role_id`),
  CONSTRAINT `user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `role` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `shoe` (
  `shoe_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `brand_id` bigint(20) NOT NULL,
  `category_id` bigint(20) NOT NULL,
  `shoe_name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL DEFAULT '',
  `gender_target_id` bigint(20) NOT NULL,
  `image_path` varchar(255) NOT NULL DEFAULT 'images/logo.png',
  PRIMARY KEY (`shoe_id`),
  KEY `shoe_category_id_foreign` (`category_id`),
  KEY `shoe_brand_id_foreign` (`brand_id`),
  KEY `shoe_gender_target_id_foreign` (`gender_target_id`),
  CONSTRAINT `shoe_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brand` (`brand_id`),
  CONSTRAINT `shoe_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`),
  CONSTRAINT `shoe_gender_target_id_foreign` FOREIGN KEY (`gender_target_id`) REFERENCES `gender_target` (`gender_target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `shoe_variant` (
  `shoe_variant_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `shoe_id` bigint(20) NOT NULL,
  `shoe_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `size_id` bigint(20) NOT NULL,
  `color_id` bigint(20) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `status_id` bigint(20) NOT NULL,
  PRIMARY KEY (`shoe_variant_id`),
  UNIQUE KEY `shoe_variant_shoe_size_color_unique` (`shoe_id`, `size_id`, `color_id`),
  KEY `shoe_variant_shoe_id_foreign` (`shoe_id`),
  KEY `shoe_variant_size_id_foreign` (`size_id`),
  KEY `shoe_variant_color_id_foreign` (`color_id`),
  KEY `shoe_variant_status_id_foreign` (`status_id`),
  CONSTRAINT `shoe_variant_color_id_foreign` FOREIGN KEY (`color_id`) REFERENCES `color` (`color_id`),
  CONSTRAINT `shoe_variant_shoe_id_foreign` FOREIGN KEY (`shoe_id`) REFERENCES `shoe` (`shoe_id`),
  CONSTRAINT `shoe_variant_size_id_foreign` FOREIGN KEY (`size_id`) REFERENCES `size` (`size_id`),
  CONSTRAINT `shoe_variant_status_id_foreign` FOREIGN KEY (`status_id`) REFERENCES `status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `cart` (
  `cart_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `shoe_id` bigint(20) NOT NULL,
  `shoe_variant_id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`cart_id`),
  KEY `cart_user_id_foreign` (`user_id`),
  CONSTRAINT `cart_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `cart_items` (
  `cart_items_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `cart_id` bigint(20) NOT NULL,
  `shoe_variant_id` bigint(20) NOT NULL,
  PRIMARY KEY (`cart_items_id`),
  KEY `cart_items_cart_id_foreign` (`cart_id`),
  KEY `cart_items_shoe_variant_id_foreign` (`shoe_variant_id`),
  CONSTRAINT `cart_items_cart_id_foreign` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`cart_id`),
  CONSTRAINT `cart_items_shoe_variant_id_foreign` FOREIGN KEY (`shoe_variant_id`) REFERENCES `shoe_variant` (`shoe_variant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `customer_order` (
  `customer_order_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `status_id` bigint(20) NOT NULL,
  `order_date_time` datetime NOT NULL,
  PRIMARY KEY (`customer_order_id`),
  KEY `customer_order_status_id_foreign` (`status_id`),
  KEY `customer_order_user_id_foreign` (`user_id`),
  CONSTRAINT `customer_order_status_id_foreign` FOREIGN KEY (`status_id`) REFERENCES `status` (`status_id`),
  CONSTRAINT `customer_order_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `order_details` (
  `order_details_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `customer_order_id` bigint(20) NOT NULL,
  `shoe_variant_id` bigint(20) NOT NULL,
  `quantity` bigint(20) NOT NULL,
  PRIMARY KEY (`order_details_id`),
  KEY `order_details_customer_order_id_foreign` (`customer_order_id`),
  KEY `order_details_shoe_variant_id_foreign` (`shoe_variant_id`),
  CONSTRAINT `order_details_customer_order_id_foreign` FOREIGN KEY (`customer_order_id`) REFERENCES `customer_order` (`customer_order_id`),
  CONSTRAINT `order_details_shoe_variant_id_foreign` FOREIGN KEY (`shoe_variant_id`) REFERENCES `shoe_variant` (`shoe_variant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 3. INSERT DATA (Ordered by Foreign Key Dependencies)
-- --------------------------------------------------------

INSERT INTO `brand` (`brand_id`, `brand_name`, `brand_country`) VALUES
(1, 'Puma', 0),
(2, 'Nike', 0),
(3, 'Adidas', 0),
(4, 'New Balance', 0);

INSERT INTO `category` (`category_id`, `category_name`, `category_description`) VALUES
(1, 'Sport Shoes', 'Sporty and suitable for either running nor jogging '),
(2, 'Casual', 'Comfortable for everyday routines'),
(3, 'Ballerina', 'Chic and stylish flat slip-ons'),
(4, 'High Top', 'Trendy elevated platform sneakers');

INSERT INTO `color` (`color_id`, `color_name`) VALUES
(1, 'Silver'),
(2, 'Cream'),
(3, 'Pink'),
(4, 'Black'),
(5, 'White'),
(6, 'Red'),
(7, 'Neon Yellow');

INSERT INTO `gender_target` (`gender_target_id`, `gender`) VALUES
(1, 'Women'),
(2, 'Men'),
(3, 'Unisex');

INSERT INTO `role` (`role_id`, `role_name`) VALUES
(1, 'admin'),
(2, 'staff'),
(3, 'customer');

INSERT INTO `size` (`size_id`, `size_cm`, `size_us`, `size_uk`, `size_eu`) VALUES
(1, 23, 6, 4, 37),
(2, 24, 7, 5, 38),
(3, 25, 8, 6, 38),
(4, 26, 9, 7, 40);

INSERT INTO `status` (`status_id`, `status`, `status_description`) VALUES
(1, 'In Stock', 'Shoe ready to buy.'),
(2, 'Out of Stock', 'This stock size/color is finished.'),
(3, 'Discontinued', 'This shoe no longer sold.');

INSERT INTO `user` (`user_id`, `username`, `password`, `fullname`, `nric`, `join_date`, `leave_date`, `role_id`) VALUES
(3, 'mad', '123456', NULL, NULL, NULL, NULL, 3),
(7, 'mar', '123456', NULL, NULL, NULL, NULL, 2),
(8, 'shahxvi', '482377', NULL, NULL, NULL, NULL, 1),
(9, 'MarCelo', '123456', NULL, NULL, NULL, NULL, 3);

INSERT INTO `shoe` (`shoe_id`, `brand_id`, `category_id`, `shoe_name`, `description`, `gender_target_id`, `image_path`) VALUES
(1, 4, 2, '530', 'New Balance 530', 3, 'images/silver_plate.png'),
(2, 2, 1, 'Cream Cookie', 'Daily walk classic', 3, 'images/dream_cookie.png'),
(3, 3, 2, 'Gazelle', 'Adidas Gazelle', 3, 'images/red_velvet.png'),
(4, 1, 2, 'Speedcat OG - Pink', 'Puma Speedcat OG', 1, 'images/white_cat.png'),
(6, 1, 1, 'Speedcat OG - Hot Pink', 'Puma Speedcat OG', 1, 'images/pink_barbie.png'),
(7, 1, 3, 'Speedcat Ballet', 'Puma Speedcat Ballet', 1, 'images/blackpink_ballerina.png'),
(8, 2, 4, 'Star White', 'High tall stars', 1, 'images/star_white.png'),
(9, 1, 3, 'Dots Creamy', 'Pokadot Ballerina', 1, 'images/dots_creamy.png');

INSERT INTO `shoe_variant` (`shoe_id`, `shoe_price`, `size_id`, `color_id`, `quantity`, `status_id`)
SELECT
    s.shoe_id,
    CASE s.shoe_id
        WHEN 1 THEN 519.00
        WHEN 2 THEN 500.00
        WHEN 3 THEN 569.00
        WHEN 4 THEN 549.00
        WHEN 6 THEN 549.00
        WHEN 7 THEN 379.00
        WHEN 8 THEN 550.00
        WHEN 9 THEN 700.00
    END + ((sz.size_uk - 4) * 5.00),
    sz.size_id,
    c.color_id,
    CASE
        WHEN (s.shoe_id = 1 AND sz.size_uk = 6 AND c.color_id = 5)
          OR (s.shoe_id = 2 AND sz.size_uk = 4 AND c.color_id = 4)
          OR (s.shoe_id = 3 AND sz.size_uk = 5 AND c.color_id = 7)
          OR (s.shoe_id = 4 AND sz.size_uk = 6 AND c.color_id = 7)
          OR (s.shoe_id = 6 AND sz.size_uk = 7 AND c.color_id = 7)
          OR (s.shoe_id = 7 AND sz.size_uk = 7 AND c.color_id = 5)
          OR (s.shoe_id = 8 AND sz.size_uk = 4 AND c.color_id = 7)
          OR (s.shoe_id = 8 AND sz.size_uk = 6 AND c.color_id = 1)
          OR (s.shoe_id = 9 AND sz.size_uk = 5 AND c.color_id = 7)
          OR (s.shoe_id = 9 AND sz.size_uk = 6 AND c.color_id = 4) THEN 0
        ELSE 1
    END,
    CASE
        WHEN (s.shoe_id = 1 AND sz.size_uk = 6 AND c.color_id = 5)
          OR (s.shoe_id = 2 AND sz.size_uk = 4 AND c.color_id = 4)
          OR (s.shoe_id = 3 AND sz.size_uk = 5 AND c.color_id = 7)
          OR (s.shoe_id = 4 AND sz.size_uk = 6 AND c.color_id = 7)
          OR (s.shoe_id = 6 AND sz.size_uk = 7 AND c.color_id = 7)
          OR (s.shoe_id = 7 AND sz.size_uk = 7 AND c.color_id = 5)
          OR (s.shoe_id = 8 AND sz.size_uk = 4 AND c.color_id = 7)
          OR (s.shoe_id = 8 AND sz.size_uk = 6 AND c.color_id = 1)
          OR (s.shoe_id = 9 AND sz.size_uk = 5 AND c.color_id = 7)
          OR (s.shoe_id = 9 AND sz.size_uk = 6 AND c.color_id = 4) THEN 2
        ELSE 1
    END
FROM shoe AS s
CROSS JOIN size AS sz
CROSS JOIN color AS c
ORDER BY s.shoe_id, sz.size_uk, c.color_id;

INSERT INTO `customer_order` (`customer_order_id`, `user_id`, `status_id`, `order_date_time`) VALUES
(5, 3, 1, '2026-11-19 11:41:18');

-- --------------------------------------------------------
-- 4. SET AUTO_INCREMENT VALUES
-- --------------------------------------------------------

ALTER TABLE `brand` AUTO_INCREMENT = 5;
ALTER TABLE `cart` AUTO_INCREMENT = 23;
ALTER TABLE `cart_items` AUTO_INCREMENT = 2;
ALTER TABLE `category` AUTO_INCREMENT = 5;
ALTER TABLE `color` AUTO_INCREMENT = 8;
ALTER TABLE `customer_order` AUTO_INCREMENT = 6;
ALTER TABLE `gender_target` AUTO_INCREMENT = 4;
ALTER TABLE `order_details` AUTO_INCREMENT = 7;
ALTER TABLE `role` AUTO_INCREMENT = 4;
ALTER TABLE `shoe` AUTO_INCREMENT = 11;
ALTER TABLE `shoe_variant` AUTO_INCREMENT = 225;
ALTER TABLE `size` AUTO_INCREMENT = 5;
ALTER TABLE `status` AUTO_INCREMENT = 4;
ALTER TABLE `user` AUTO_INCREMENT = 10;

/* Alter table for staff dashboard (manage order) */
ALTER TABLE customer_order ADD COLUMN is_completed TINYINT(1) NOT NULL DEFAULT 0;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;