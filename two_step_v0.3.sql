CREATE DATABASE TWO_STEP;

USE TWO_STEP;

CREATE TABLE `USER`(
    `user_id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `fullname` VARCHAR(255) NULL,
    `nric` VARCHAR(255) NULL,
    `join_date` DATETIME NULL,
    `leave_date` DATETIME NULL,
    `role_id` BIGINT NULL
);
CREATE TABLE `ROLE`(
    `role_id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `role_name` VARCHAR(255) NOT NULL,
    `role_description` TEXT NULL
);
CREATE TABLE `BRAND`(
    `brand_id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `brand_name` VARCHAR(255) NOT NULL,
    `brand_country` BIGINT NOT NULL
);
CREATE TABLE `CATEGORY`(
    `category_id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `category_name` VARCHAR(255) NOT NULL,
    `category_description` TEXT NULL
);
CREATE TABLE `GENDER_TARGET`(
    `gender_target_id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `gender` VARCHAR(255) NOT NULL
);
CREATE TABLE `SHOE`(
    `shoe_id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `brand_id` BIGINT NOT NULL,
    `category_id` BIGINT NOT NULL,
    `shoe_name` VARCHAR(255) NOT NULL,
    `gender_target_id` BIGINT NOT NULL
);
CREATE TABLE `SHOE_VARIANT`(
    `shoe_variant_id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `shoe_id` BIGINT NOT NULL,
    `size_id` BIGINT NOT NULL,
    `color_id` BIGINT NOT NULL,
    `status_id` BIGINT NOT NULL
);
CREATE TABLE `COLOR`(
    `color_id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `color_name` VARCHAR(255) NOT NULL
);
CREATE TABLE `SIZE`(
    `size_id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `size_cm` BIGINT NOT NULL,
    `size_us` BIGINT NOT NULL,
    `size_uk` BIGINT NOT NULL,
    `size_eu` BIGINT NOT NULL
);
CREATE TABLE `STATUS`(
    `status_id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `status` VARCHAR(255) NOT NULL,
    `status_description` TEXT NOT NULL
);
CREATE TABLE `CUSTOMER_ORDER`(
    `customer_order_id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT NOT NULL,
    `status_id` BIGINT NOT NULL,
    `order_date_time` DATETIME NOT NULL
);
CREATE TABLE `ORDER_DETAILS`(
    `order_details_id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `customer_order_id` BIGINT NOT NULL,
    `shoe_variant_id` BIGINT NOT NULL,
    `quantity` BIGINT NOT NULL
);
CREATE TABLE `CART`(
    `cart_id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT NOT NULL
);
CREATE TABLE `CART_ITEMS`(
    `cart_items_id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `cart_id` BIGINT NOT NULL,
    `shoe_variant_id` BIGINT NOT NULL
);

ALTER TABLE `USER` ADD CONSTRAINT `user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `ROLE`(`role_id`);

ALTER TABLE `CUSTOMER_ORDER` ADD CONSTRAINT `customer_order_status_id_foreign` FOREIGN KEY (`status_id`) REFERENCES `STATUS`(`status_id`);
ALTER TABLE `CUSTOMER_ORDER` ADD CONSTRAINT `customer_order_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `USER`(`user_id`);

ALTER TABLE `CART` ADD CONSTRAINT `cart_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `USER`(`user_id`);

ALTER TABLE `SHOE` ADD CONSTRAINT `shoe_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `CATEGORY`(`category_id`);
ALTER TABLE `SHOE` ADD CONSTRAINT `shoe_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `BRAND`(`brand_id`);
ALTER TABLE `SHOE` ADD CONSTRAINT `shoe_gender_target_id_foreign` FOREIGN KEY (`gender_target_id`) REFERENCES `GENDER_TARGET`(`gender_target_id`);

ALTER TABLE `SHOE_VARIANT` ADD CONSTRAINT `shoe_variant_shoe_id_foreign` FOREIGN KEY (`shoe_id`) REFERENCES `SHOE`(`shoe_id`);
ALTER TABLE `SHOE_VARIANT` ADD CONSTRAINT `shoe_variant_size_id_foreign` FOREIGN KEY (`size_id`) REFERENCES `SIZE`(`size_id`);
ALTER TABLE `SHOE_VARIANT` ADD CONSTRAINT `shoe_variant_color_id_foreign` FOREIGN KEY (`color_id`) REFERENCES `COLOR`(`color_id`);
ALTER TABLE `SHOE_VARIANT` ADD CONSTRAINT `shoe_variant_status_id_foreign` FOREIGN KEY (`status_id`) REFERENCES `STATUS`(`status_id`);

ALTER TABLE `CART_ITEMS` ADD CONSTRAINT `cart_items_cart_id_foreign` FOREIGN KEY (`cart_id`) REFERENCES `CART`(`cart_id`);
ALTER TABLE `CART_ITEMS` ADD CONSTRAINT `cart_items_shoe_variant_id_foreign` FOREIGN KEY (`shoe_variant_id`) REFERENCES `SHOE_VARIANT`(`shoe_variant_id`);

ALTER TABLE `ORDER_DETAILS` ADD CONSTRAINT `order_details_customer_order_id_foreign` FOREIGN KEY (`customer_order_id`) REFERENCES `CUSTOMER_ORDER`(`customer_order_id`);
ALTER TABLE `ORDER_DETAILS` ADD CONSTRAINT `order_details_shoe_variant_id_foreign` FOREIGN KEY (`shoe_variant_id`) REFERENCES `SHOE_VARIANT`(`shoe_variant_id`);