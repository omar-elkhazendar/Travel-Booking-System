-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 22, 2025 at 01:35 PM
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
-- Database: `travel_booking`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password`, `created_at`) VALUES
(3, 'admin', 'admin@gmail.com', '$2y$10$wYzDf172lnFy9rJwAwP8F.344bx6yLQc.5fawLUhScFQ9YX4e5iVu', '2025-05-19 17:08:56');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `travel_offer_id` int(11) NOT NULL,
  `booking_date` datetime DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'pending',
  `visa_number` varchar(32) DEFAULT NULL,
  `visa_expiry` varchar(10) DEFAULT NULL,
  `visa_name` varchar(100) DEFAULT NULL,
  `delete_request` tinyint(1) DEFAULT 0,
  `delete_request_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `travel_offer_id`, `booking_date`, `status`, `visa_number`, `visa_expiry`, `visa_name`, `delete_request`, `delete_request_date`) VALUES
(36, 6, 9, '2025-05-22 14:34:57', 'confirmed', '$2y$10$GeRdC02mTL/O/7oVfMuar.NUO', '1127', 'Omar', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

CREATE TABLE `cities` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `page_id` varchar(100) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `subject`, `message`, `created_at`) VALUES
(1, 'kareem', 'kareemhelmii77@gmail.com', '???????????', 'This is the worst company ever.', '2025-05-13 20:07:50'),
(2, 'kareem', 'kareem.helmii@gmail.com', '???????????', '////////////', '2025-05-19 16:43:44');

-- --------------------------------------------------------

--
-- Table structure for table `travel_offers`
--

CREATE TABLE `travel_offers` (
  `id` int(11) NOT NULL,
  `destination` varchar(100) NOT NULL,
  `departure_date` date NOT NULL,
  `return_date` date NOT NULL,
  `price` decimal(12,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `city_id` int(11) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `available` tinyint(1) DEFAULT 1,
  `max_passengers` int(11) NOT NULL DEFAULT 100,
  `trip_type` varchar(20) NOT NULL DEFAULT 'Round-trip',
  `outbound_time` varchar(10) DEFAULT NULL,
  `return_time` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `travel_offers`
--

INSERT INTO `travel_offers` (`id`, `destination`, `departure_date`, `return_date`, `price`, `description`, `created_at`, `city_id`, `image_path`, `available`, `max_passengers`, `trip_type`, `outbound_time`, `return_time`) VALUES
(1, 'Cairo', '2025-05-17', '2025-05-29', 1199.97, 'Cairo is the bustling capital of Egypt, famous for its ancient history, including the nearby Pyramids of Giza and the Sphinx. It\'s a vibrant city that combines historic landmarks with a lively urban atmosphere.', '2025-05-04 15:41:11', NULL, 'uploads/6817adfc1e629_download (2).jpeg', 1, 100, 'Round-trip', '22:20', '23:20'),
(2, 'kuwait', '2025-05-15', '2025-05-29', 4999.99, 'Kuwait is a wealthy Gulf nation known for its oil reserves, modern architecture, and cultural heritage. Its capital, Kuwait City, blends tradition with development, offering museums, souks, and a scenic coastline.', '2025-05-04 15:42:05', NULL, 'uploads/6817ae19f1d16_bbcce1e6-ctry-133-16cd86277ef.jpg', 1, 100, 'One-way', '07:07', '07:07'),
(7, 'Thailand', '2025-02-04', '2025-02-04', 6000.00, 'Thailand is a Southeast Asian country known for its rich culture, stunning beaches, ornate temples, and delicious cuisine. Its capital, Bangkok, is a vibrant city blending tradition and modernity, while places like Chiang Mai and Phuket offer natural beauty and cultural experiences.', '2025-05-19 15:48:01', NULL, 'uploads/682b288187ae3_Thailand.jpeg', 1, 20, 'One-way', '15:40', ''),
(9, 'Qatar', '2025-03-04', '2025-03-04', 3999.98, 'Qatar is a wealthy Middle Eastern country on the Arabian Peninsula, known for its modern skyline, desert landscapes, and rich cultural heritage. Its capital, Doha, is a hub for business, luxury, and innovation, blending traditional Islamic architecture with futuristic design.', '2025-05-19 15:52:23', NULL, 'uploads/682b29877a413_download.jpeg', 1, 60, 'One-way', '06:06', ''),
(12, 'USA-California', '2025-03-05', '2025-03-05', 49999.99, 'California is a diverse U.S. state on the West Coast, known for its beaches, tech innovation, entertainment industry, and natural wonders. From Hollywood in Los Angeles to Silicon Valley and the scenic beauty of Yosemite and the Pacific Coast, it offers a unique mix of culture, nature, and opportunity.', '2025-05-19 15:59:26', NULL, 'uploads/682b2b2e5e3b3_america.jpeg', 1, 30, 'One-way', '02:23', ''),
(14, 'Canada', '2025-10-09', '2025-10-09', 30000.00, 'Canada is a vast North American country known for its natural beauty, multicultural cities, and high quality of life. From the Rocky Mountains to vibrant cities like Toronto and Vancouver, it offers a blend of wilderness, modern living, and a welcoming, diverse society.', '2025-05-19 16:03:09', NULL, 'uploads/682b2c0d7d11d_canada.jpeg', 1, 80, 'One-way', '16:52', NULL),
(15, 'Mexico', '2027-02-01', '2027-02-28', 6000.00, 'Prepare for a trip filled with sun-soaked beaches, mouthwatering tacos, and centuries of culture. Mexico is a land of contrast—where ancient Mayan ruins meet vibrant cities, and mariachi music fills the air as you sip on fresh horchata or margaritas. Whether you\'re diving into cenotes, exploring colonial towns, or lounging in Cancún, you\'re in for a warm, colorful, and spirited experience. Your Mexican adventure starts now—¡Vámonos!', '2025-05-22 14:33:26', NULL, 'uploads/682f0b86df55f_mexico.jpg', 1, 100, 'Round-trip', '07:00', '09:10');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `github_id` varchar(100) DEFAULT NULL,
  `github_username` varchar(100) DEFAULT NULL,
  `github_avatar` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `oauth_provider` varchar(20) DEFAULT NULL,
  `oauth_id` varchar(50) DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `github_id`, `github_username`, `github_avatar`, `created_at`, `oauth_provider`, `oauth_id`, `is_admin`) VALUES
(6, 'omarmagdyyy14', 'omarmagdyyy14@gmail.com', '$2y$10$i9KIckd/pchIQSOmJVJm8O0AUvg8gOHizZghIbF6WJY3rX4YGlNjS', NULL, NULL, NULL, '2025-05-22 14:28:50', NULL, NULL, 0),
(7, 'Omar', 'omar@gmail.com', '$2y$10$aDxd8HkbeAUT5b0jfTzr1O3eBIoXnQ1jLk7yvn.ePtoFcgeV7MK2W', NULL, NULL, NULL, '2025-05-22 14:30:38', NULL, NULL, 1),
(8, 'Omar Magdy', 'omarmagdyyy16@gmail.com', '$2y$10$MHt7EjF/4q1QHIc9KH9ClOuyhUs5.344Emii40aNLNEIvb1UcCc4C', NULL, NULL, NULL, '2025-05-22 14:31:13', NULL, NULL, 0),
(9, 'Omar3443', 'omar.magdy3443728@gmail.com', '', '139278558', 'Omar3443', 'https://avatars.githubusercontent.com/u/139278558?v=4', '2025-05-22 14:34:13', NULL, NULL, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `travel_offer_id` (`travel_offer_id`);

--
-- Indexes for table `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `travel_offers`
--
ALTER TABLE `travel_offers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_city` (`city_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `github_id` (`github_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `cities`
--
ALTER TABLE `cities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `travel_offers`
--
ALTER TABLE `travel_offers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`travel_offer_id`) REFERENCES `travel_offers` (`id`);

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `travel_offers`
--
ALTER TABLE `travel_offers`
  ADD CONSTRAINT `fk_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
