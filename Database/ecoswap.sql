-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 12 Feb 2026 pada 22.40
-- Versi server: 8.0.30
-- Versi PHP: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ecoswap`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `addresses`
--

CREATE TABLE `addresses` (
  `address_id` int NOT NULL,
  `user_id` int NOT NULL,
  `label` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `recipient_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `phone_number` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `full_address` text COLLATE utf8mb4_general_ci NOT NULL,
  `village` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `subdistrict` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `postal_code` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `landmark` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `addresses`
--

INSERT INTO `addresses` (`address_id`, `user_id`, `label`, `recipient_name`, `phone_number`, `full_address`, `village`, `subdistrict`, `city`, `postal_code`, `landmark`, `is_primary`) VALUES
(1, 3, 'Rumah', 'Lionel Messi', '080987654321', 'Dungpring RT 10 RW 10', 'Dungpring', 'Eromoko', 'Wonogiri', '57123', 'Rumah Pinggir Sawah Warna Hijau', 1),
(2, 1, 'Rumah', 'Abid Hanan', '085326513324', 'Pucangsawit RT 02 RW 02', 'Pucangsawit', 'Jebres', 'Surakarta', '57125', 'Rumah Pak RT 02 RW 02', 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `cart`
--

CREATE TABLE `cart` (
  `cart_id` int NOT NULL,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `cart`
--

INSERT INTO `cart` (`cart_id`, `user_id`, `product_id`, `created_at`) VALUES
(2, 3, 2, '2026-02-09 11:14:55'),
(4, 3, 3, '2026-02-12 03:27:20');

-- --------------------------------------------------------

--
-- Struktur dari tabel `chats`
--

CREATE TABLE `chats` (
  `chat_id` int NOT NULL,
  `sender_id` int NOT NULL,
  `receiver_id` int NOT NULL,
  `message` text COLLATE utf8mb4_general_ci,
  `image_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `is_deleted` tinyint(1) DEFAULT '0',
  `is_reported` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `attachment` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `chats`
--

INSERT INTO `chats` (`chat_id`, `sender_id`, `receiver_id`, `message`, `image_path`, `is_read`, `is_deleted`, `is_reported`, `created_at`, `attachment`) VALUES
(2, 3, 1, 'jaket pria kulit hitam ready kan kak??', NULL, 0, 0, 0, '2026-02-09 10:57:00', NULL),
(3, 1, 3, 'ready kakk silahkan diorder...', NULL, 0, 0, 0, '2026-02-09 10:57:59', NULL),
(5, 3, 1, '', '../../../Assets/img/chats/1770866801_e8f9219693.png', 0, 0, 0, '2026-02-12 03:26:41', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `chat_reports`
--

CREATE TABLE `chat_reports` (
  `chat_report_id` int NOT NULL,
  `chat_id` int NOT NULL,
  `reporter_id` int NOT NULL,
  `reported_user_id` int NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','resolved','rejected') DEFAULT 'pending',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `notifications`
--

CREATE TABLE `notifications` (
  `notif_id` int NOT NULL,
  `user_id` int NOT NULL,
  `title` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_general_ci,
  `link` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `notifications`
--

INSERT INTO `notifications` (`notif_id`, `user_id`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 1, 'Pesanan Baru', 'Pesanan baru #INV/20260209/432DEE masuk.', NULL, 0, '2026-02-09 10:53:36'),
(2, 3, 'Pesanan Diproses', 'Pesanan #INV/20260209/432DEE sedang disiapkan oleh penjual.', NULL, 0, '2026-02-09 10:58:24'),
(3, 3, 'Pesanan Dikirim', 'Pesanan #INV/20260209/432DEE telah dikirim. No Resi: 0987654321', NULL, 0, '2026-02-09 10:58:34'),
(4, 3, 'Paket Sampai', 'Paket untuk pesanan #INV/20260209/432DEE telah tiba. Mohon konfirmasi penerimaan.', NULL, 1, '2026-02-09 10:58:48'),
(14, 1, 'Pesanan Selesai', 'Pesanan #INV/20260209/432DEE telah diterima pembeli. Dana masuk ke saldo Anda.', NULL, 0, '2026-02-11 16:31:36'),
(15, 2, 'Laporan Chat', 'LAPORAN CHAT\n- Pelapor (buyer) ID: 3\n- Terlapor ID: 1 (Abid Hanan)\n- Chat ID: 3\n- Waktu: 2026-02-09 17:57:59\n- Isi: ready kakk silahkan diorder...\n- Alasan: sok asik', NULL, 0, '2026-02-12 03:26:58');

-- --------------------------------------------------------

--
-- Struktur dari tabel `orders`
--

CREATE TABLE `orders` (
  `order_id` int NOT NULL,
  `invoice_code` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `buyer_id` int NOT NULL,
  `address_id` int NOT NULL,
  `shop_id` int NOT NULL,
  `product_id` int NOT NULL,
  `total_price` decimal(15,2) NOT NULL,
  `shipping_method` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `shipping_address` text COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('pending','processed','shipping','delivered','completed','cancelled','reviewed') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `tracking_number` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `orders`
--

INSERT INTO `orders` (`order_id`, `invoice_code`, `buyer_id`, `address_id`, `shop_id`, `product_id`, `total_price`, `shipping_method`, `shipping_address`, `status`, `tracking_number`, `created_at`) VALUES
(1, 'INV/20260209/432DEE', 3, 1, 1, 1, 75000.00, 'AnterAja (Rp 20.000) | Transfer Bank (BRI)', 'Dungpring RT 10 RW 10, Dungpring, Eromoko, Wonogiri 57123 (Lionel Messi - 080987654321)', 'reviewed', '0987654321', '2026-02-09 10:53:36');

-- --------------------------------------------------------

--
-- Struktur dari tabel `products`
--

CREATE TABLE `products` (
  `product_id` int NOT NULL,
  `shop_id` int NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `condition` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `image` text COLLATE utf8mb4_general_ci,
  `views` int DEFAULT '0',
  `status` enum('active','sold','deleted','review') COLLATE utf8mb4_general_ci DEFAULT 'review',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `category` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `products`
--

INSERT INTO `products` (`product_id`, `shop_id`, `name`, `price`, `description`, `condition`, `image`, `views`, `status`, `created_at`, `category`) VALUES
(1, 1, 'Jaket Kulit Pria Hitam', 60000.00, 'Ukuran XL', 'Bekas - Seperti Baru', '../../../Assets/img/products/1770632602_oem_jaket-pria-jaket-motor-pria-jaket-kulit-pria-hitam-coklat_full07.webp', 1, 'sold', '2026-02-09 10:23:22', 'Fashion Pria'),
(2, 1, 'Sweater Crop Wanita Biru', 40000.00, 'Ukuran M', 'Bekas - Seperti Baru', '../../../Assets/img/products/1770632700_id-11134207-7r98y-llkakoon558c8d.jpg', 4, 'active', '2026-02-09 10:25:00', 'Fashion Wanita'),
(3, 1, 'Sepatu Kulit Pria Hitam', 75000.00, 'Ukuran 42', 'Bekas - Baik', '../../../Assets/img/products/1770633937_6c59e1da297eb1e91ac9262a848eed6d.jpg', 10, 'active', '2026-02-09 10:45:37', 'Fashion Pria'),
(4, 1, 'Sandal Wanita Dewasa Coklat', 50000.00, 'Ukuran 38', 'Bekas - Baik', '../../../Assets/img/products/1770634058_S2fcc22c93ea642a3b402deebbf12a0efN.webp', 0, 'review', '2026-02-09 10:47:38', 'Fashion Wanita');

-- --------------------------------------------------------

--
-- Struktur dari tabel `reports`
--

CREATE TABLE `reports` (
  `report_id` int NOT NULL,
  `shop_id` int NOT NULL,
  `user_id` int NOT NULL,
  `order_id` int NOT NULL,
  `reason` text COLLATE utf8mb4_general_ci NOT NULL,
  `proof_image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('pending','resolved','blocked') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `reports`
--

INSERT INTO `reports` (`report_id`, `shop_id`, `user_id`, `order_id`, `reason`, `proof_image`, `status`, `created_at`) VALUES
(1, 1, 3, 1, 'Ukuranya kekecilan min, tapi yaudah gapapa dimaafin aja...', '../../../Assets/img/reports/1770827562_rep_oem_jaket-pria-jaket-motor-pria-jaket-kulit-pria-hitam-coklat_full07.webp', 'pending', '2026-02-11 16:32:42');

-- --------------------------------------------------------

--
-- Struktur dari tabel `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int NOT NULL,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `user_id` int NOT NULL,
  `rating` int NOT NULL,
  `comment` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `photo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `reviews`
--

INSERT INTO `reviews` (`review_id`, `order_id`, `product_id`, `user_id`, `rating`, `comment`, `created_at`, `photo`) VALUES
(1, 1, 1, 3, 5, 'Bagus masih kelihatan seperti baru!', '2026-02-11 16:33:20', '../../../Assets/img/reviews/1770827600_oem_jaket-pria-jaket-motor-pria-jaket-kulit-pria-hitam-coklat_full07.webp');

-- --------------------------------------------------------

--
-- Struktur dari tabel `shops`
--

CREATE TABLE `shops` (
  `shop_id` int NOT NULL,
  `user_id` int NOT NULL,
  `shop_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `shop_description` text COLLATE utf8mb4_general_ci,
  `shop_street` text COLLATE utf8mb4_general_ci,
  `shop_village` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `shop_subdistrict` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `shop_postcode` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `shop_address` text COLLATE utf8mb4_general_ci,
  `shop_phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `shop_image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT 'default_shop.jpg',
  `balance` decimal(15,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `address_id` int DEFAULT NULL,
  `shipping_options` json DEFAULT NULL,
  `shipping_costs` json DEFAULT NULL,
  `payment_methods` json DEFAULT NULL,
  `shop_kelurahan` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `shop_kecamatan` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `shop_city` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `shops`
--

INSERT INTO `shops` (`shop_id`, `user_id`, `shop_name`, `shop_description`, `shop_street`, `shop_village`, `shop_subdistrict`, `shop_postcode`, `shop_address`, `shop_phone`, `shop_image`, `balance`, `created_at`, `address_id`, `shipping_options`, `shipping_costs`, `payment_methods`, `shop_kelurahan`, `shop_kecamatan`, `shop_city`) VALUES
(1, 1, 'EcoSwap Official', 'Fashion Pria dan Fashion Wanita', 'Pucangsawit RT 02 RW 02', 'Pucangsawit', 'Jebres', '57125', NULL, '085326513324', '../../../Assets/img/shops/1770631910_logo.png', 0.00, '2026-02-09 10:11:50', NULL, '[\"JNE Reguler\", \"J&T Express\", \"SiCepat\", \"AnterAja\"]', '{\"JNE\": \"15000\", \"JNT\": \"16000\", \"Grab\": \"19000\", \"GoSend\": \"18000\", \"SiCepat\": \"17000\", \"AnterAja\": \"20000\"}', '[\"Transfer Bank (BCA)\", \"Transfer Bank (BRI)\", \"E-Wallet (GoPay)\", \"E-Wallet (Dana)\"]', NULL, NULL, 'Surakarta');

-- --------------------------------------------------------

--
-- Struktur dari tabel `shop_followers`
--

CREATE TABLE `shop_followers` (
  `id` int NOT NULL,
  `shop_id` int NOT NULL,
  `user_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `shop_followers`
--

INSERT INTO `shop_followers` (`id`, `shop_id`, `user_id`, `created_at`) VALUES
(3, 1, 3, '2026-02-12 03:26:14');

-- --------------------------------------------------------

--
-- Struktur dari tabel `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_key` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `setting_value` varchar(255) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `system_settings`
--

INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('admin_balance', '1000'),
('admin_fee', '1000');

-- --------------------------------------------------------

--
-- Struktur dari tabel `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int NOT NULL,
  `shop_id` int DEFAULT NULL,
  `type` enum('in','out') COLLATE utf8mb4_general_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `transactions`
--

INSERT INTO `transactions` (`transaction_id`, `shop_id`, `type`, `amount`, `description`, `created_at`) VALUES
(19, 1, 'in', 95000.00, 'Pendapatan pesanan #INV/20260209/432DEE', '2026-02-11 16:31:36'),
(20, NULL, 'in', 1000.00, 'Fee dari pesanan #INV/20260209/432DEE', '2026-02-11 16:31:36'),
(21, 1, 'out', 95000.00, 'Penarikan ke Dana (085326513324)', '2026-02-12 03:32:13');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `email` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(225) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `phone_number` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('admin','buyer','seller') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'buyer',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `profile_picture` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('active','banned') COLLATE utf8mb4_general_ci DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`user_id`, `email`, `name`, `password`, `phone_number`, `address`, `role`, `created_at`, `updated_at`, `profile_picture`, `status`) VALUES
(1, 'abid@email.com', 'Abid Hanan', '$2y$10$wx1FREF4xMCyAqAG5w.OWefbE0Sl38qJzhQ53040PzALWcyp7YfRi', '085326513324', 'Pucangsawit', 'seller', '2026-02-03 13:36:39', '2026-02-09 10:34:54', '../../../Assets/img/profiles/1770633294_FotoProfil .png', 'active'),
(2, 'ahawi@email.com', 'Ahawi Channel', '$2y$10$q5iEU25xf1PstciQ393Bte5LlsiskfYzXpKntSeMKf/xmAHlcbRwG', '081234567890', 'Grogol', 'admin', '2026-02-09 10:28:24', '2026-02-09 10:32:06', '../../../Assets/img/profiles/1770633126_WhatsApp Image 2026-01-09 at 11.33.20.jpeg', 'active'),
(3, 'messi@email.com', 'Lionel Messi', '$2y$10$NyY2pdv6/KDa7KB4Gkos5.yHfMPq6a9/qunX1tZ0JHKm2JIkDJRBy', '080987654321', 'Dungpring', 'buyer', '2026-02-09 10:37:17', '2026-02-12 03:28:38', '../../../Assets/img/profiles/1770866918_Lionel-Messi-x.com-x.com-@ID_Albiceleste.jpg', 'active');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`address_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indeks untuk tabel `chats`
--
ALTER TABLE `chats`
  ADD PRIMARY KEY (`chat_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indeks untuk tabel `chat_reports`
--
ALTER TABLE `chat_reports`
  ADD PRIMARY KEY (`chat_report_id`),
  ADD KEY `chat_id` (`chat_id`),
  ADD KEY `reporter_id` (`reporter_id`),
  ADD KEY `reported_user_id` (`reported_user_id`);

--
-- Indeks untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notif_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `buyer_id` (`buyer_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `address_id` (`address_id`),
  ADD KEY `shop_id` (`shop_id`);

--
-- Indeks untuk tabel `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `shop_id` (`shop_id`);

--
-- Indeks untuk tabel `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `shop_id` (`shop_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indeks untuk tabel `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `shops`
--
ALTER TABLE `shops`
  ADD PRIMARY KEY (`shop_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `address_id` (`address_id`);

--
-- Indeks untuk tabel `shop_followers`
--
ALTER TABLE `shop_followers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shop_id` (`shop_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indeks untuk tabel `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `shop_id` (`shop_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `addresses`
--
ALTER TABLE `addresses`
  MODIFY `address_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `chats`
--
ALTER TABLE `chats`
  MODIFY `chat_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `chat_reports`
--
ALTER TABLE `chat_reports`
  MODIFY `chat_report_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notif_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT untuk tabel `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `shops`
--
ALTER TABLE `shops`
  MODIFY `shop_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `shop_followers`
--
ALTER TABLE `shop_followers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `chats`
--
ALTER TABLE `chats`
  ADD CONSTRAINT `chats_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `chats_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`);

--
-- Ketidakleluasaan untuk tabel `chat_reports`
--
ALTER TABLE `chat_reports`
  ADD CONSTRAINT `chat_reports_ibfk_1` FOREIGN KEY (`chat_id`) REFERENCES `chats` (`chat_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_reports_ibfk_2` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_reports_ibfk_3` FOREIGN KEY (`reported_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Ketidakleluasaan untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`address_id`),
  ADD CONSTRAINT `orders_ibfk_4` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`shop_id`);

--
-- Ketidakleluasaan untuk tabel `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`shop_id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`shop_id`),
  ADD CONSTRAINT `reports_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);

--
-- Ketidakleluasaan untuk tabel `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Ketidakleluasaan untuk tabel `shops`
--
ALTER TABLE `shops`
  ADD CONSTRAINT `shops_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shops_ibfk_2` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`address_id`);

--
-- Ketidakleluasaan untuk tabel `shop_followers`
--
ALTER TABLE `shop_followers`
  ADD CONSTRAINT `shop_followers_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`shop_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shop_followers_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`shop_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
