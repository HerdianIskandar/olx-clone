-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 06, 2026 at 06:05 AM
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
-- Database: `db_olx_clone`
--

-- --------------------------------------------------------

--
-- Table structure for table `ads`
--

CREATE TABLE `ads` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(15,2) NOT NULL,
  `location` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ad_images`
--

CREATE TABLE `ad_images` (
  `id` int(11) NOT NULL,
  `ad_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `icon` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ads`
--
ALTER TABLE `ads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `ad_images`
--
ALTER TABLE `ad_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ad_id` (`ad_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ads`
--
ALTER TABLE `ads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ad_images`
--
ALTER TABLE `ad_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ads`
--
ALTER TABLE `ads`
  ADD CONSTRAINT `ads_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `ads_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `ad_images`
--
ALTER TABLE `ad_images`
  ADD CONSTRAINT `ad_images_ibfk_1` FOREIGN KEY (`ad_id`) REFERENCES `ads` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- Insert sample categories
INSERT INTO categories (name, icon) VALUES
('Mobil', 'bi bi-car-front'),
('Motor', 'bi bi-bicycle'),
('Properti', 'bi bi-house'),
('Elektronik', 'bi bi-phone'),
('Komputer', 'bi bi-laptop'),
('Fashion', 'bi bi-bag'),
('Kesehatan & Kecantikan', 'bi bi-heart'),
('Hobi & Olahraga', 'bi bi-trophy'),
('Rumah Tangga', 'bi bi-house-door'),
('Jasa', 'bi bi-briefcase');

-- Insert sample users (password: password123)
INSERT INTO users (name, email, password) VALUES
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Jane Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Ahmad Rizki', 'ahmad@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Siti Nurhaliza', 'siti@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert sample ads
INSERT INTO ads (user_id, category_id, title, description, price, location) VALUES
(1, 2, 'Honda Vario 125 2021', 'Honda Vario 125 tahun 2021, kondisi mulus, kilometer rendah, service rutin Honda', 15500000, 'Jakarta Selatan'),
(2, 4, 'iPhone 13 Pro Max', 'iPhone 13 Pro Max 256GB, warna Sierra Blue, kondisi like new, garansi resmi', 12000000, 'Bandung'),
(3, 5, 'MacBook Air M1 2020', 'MacBook Air M1 2020 8GB RAM 256GB SSD, kondisi prima, charger included', 8500000, 'Surabaya'),
(4, 1, 'Toyota Avanza 2019', 'Toyota Avanza 1.3 G 2019, manual, warna hitam, pajak panjang', 145000000, 'Depok'),
(1, 4, 'Samsung Galaxy S21', 'Samsung Galaxy S21 5G 128GB, warna Phantom Gray, good condition', 6500000, 'Tangerang'),
(2, 8, 'PlayStation 5', 'PS5 Standard Edition, 2 controller, 5 games, kondisi excellent', 7200000, 'Bekasi'),
(3, 9, 'IKEA Sofa 3 Seater', 'IKEA KIVIK sofa 3 seater, warna beige, kondisi 90%', 2800000, 'Jakarta Pusat'),
(4, 2, 'Yamaha NMAX 2022', 'Yamaha NMAX ABS 2022, kilometer 5.000, service rutin Yamaha', 28500000, 'Bogor');

-- Insert sample ad images
INSERT INTO ad_images (ad_id, image_path) VALUES
(1, 'uploads/honda_vario_125_2021_1.jpg'),
(1, 'uploads/honda_vario_125_2021_2.jpg'),
(2, 'uploads/iphone_13_pro_max_1.jpg'),
(2, 'uploads/iphone_13_pro_max_2.jpg'),
(3, 'uploads/macbook_air_m1_1.jpg'),
(4, 'uploads/toyota_avanza_2019_1.jpg'),
(4, 'uploads/toyota_avanza_2019_2.jpg'),
(5, 'uploads/samsung_galaxy_s21_1.jpg'),
(6, 'uploads/playstation_5_1.jpg'),
(7, 'uploads/ikea_sofa_1.jpg'),
(8, 'uploads/yamaha_nmax_2022_1.jpg'),
(8, 'uploads/yamaha_nmax_2022_2.jpg');
