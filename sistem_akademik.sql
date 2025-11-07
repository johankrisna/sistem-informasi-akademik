-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 07, 2025 at 08:06 AM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.1.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sistem_akademik`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `nama`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', '2025-10-07 11:55:37');

-- --------------------------------------------------------

--
-- Table structure for table `jadwal_kuliah`
--

CREATE TABLE `jadwal_kuliah` (
  `id` int(11) NOT NULL,
  `mata_kuliah_id` int(11) NOT NULL,
  `hari` enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `ruangan` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jadwal_kuliah`
--

INSERT INTO `jadwal_kuliah` (`id`, `mata_kuliah_id`, `hari`, `jam_mulai`, `jam_selesai`, `ruangan`) VALUES
(1, 1, 'Senin', '08:00:00', '10:30:00', 'R.101'),
(2, 2, 'Selasa', '10:00:00', '12:30:00', 'R.102'),
(3, 3, 'Rabu', '13:00:00', '15:30:00', 'R.103'),
(4, 4, 'Kamis', '09:00:00', '11:30:00', 'R.201'),
(5, 5, 'Jumat', '13:00:00', '15:30:00', 'Lab. Komputer'),
(6, 6, 'Sabtu', '08:00:00', '10:30:00', 'R.105'),
(7, 7, 'Senin', '14:00:00', '16:30:00', 'R.104'),
(8, 8, 'Selasa', '08:00:00', '10:30:00', 'R.202'),
(9, 9, 'Rabu', '10:00:00', '12:30:00', 'Lab. Programming');

-- --------------------------------------------------------

--
-- Table structure for table `mahasiswa`
--

CREATE TABLE `mahasiswa` (
  `id` int(11) NOT NULL,
  `npm` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `jurusan` varchar(50) NOT NULL,
  `angkatan` year(4) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `no_hp` varchar(15) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mahasiswa`
--

INSERT INTO `mahasiswa` (`id`, `npm`, `password`, `nama`, `jurusan`, `angkatan`, `email`, `no_hp`, `created_at`) VALUES
(1, '202101001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Budi Santoso', 'Teknik Informatika', 2021, 'budi@email.com', '081234567890', '2025-10-07 11:55:37'),
(2, '202101002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Siti Rahayu', 'Sistem Informasi', 2021, 'siti@email.com', '081234567891', '2025-10-07 11:55:37'),
(3, '202101003', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ahmad Wijaya', 'Teknik Informatika', 2021, 'ahmad@email.com', '081234567892', '2025-10-07 11:55:37'),
(4, '202101004', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dewi Lestari', 'Sistem Informasi', 2021, 'dewi@email.com', '081234567893', '2025-10-07 11:55:37'),
(5, '202101005', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rina Melati', 'Teknik Informatika', 2021, 'rina@email.com', '081234567894', '2025-10-07 11:55:37'),
(6, '202102001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Fajar Setiawan', 'Teknik Komputer', 2022, 'fajar@email.com', '081234567895', '2025-10-07 11:55:37'),
(7, '202102002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maya Sari', 'Manajemen Informatika', 2022, 'maya@email.com', '081234567896', '2025-10-07 11:55:37'),
(8, '202102003', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Hendra Pratama', 'Teknik Informatika', 2022, 'hendra@email.com', '081234567897', '2025-10-07 11:55:37'),
(9, '202102004', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Linda Kusuma', 'Sistem Informasi', 2022, 'linda@email.com', '081234567898', '2025-10-07 11:55:37'),
(10, '202103001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rizki Ramadhan', 'Teknik Komputer', 2023, 'rizki@email.com', '081234567899', '2025-10-07 11:55:37'),
(11, '202103002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sari Indah', 'Manajemen Informatika', 2023, 'sari@email.com', '081234567900', '2025-10-07 11:55:37'),
(12, '202103003', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dimas Prayoga', 'Teknik Informatika', 2023, 'dimas@email.com', '081234567901', '2025-10-07 11:55:37'),
(13, '202103004', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nina Marlina', 'Sistem Informasi', 2023, 'nina@email.com', '081234567902', '2025-10-07 11:55:37'),
(14, '202103005', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Eko Prasetyo', 'Teknik Informatika', 2023, 'eko@email.com', '081234567903', '2025-10-07 11:55:37'),
(15, '202103006', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Fitri Anggraini', 'Manajemen Informatika', 2023, 'fitri@email.com', '081234567904', '2025-10-07 11:55:37'),
(17, '215314215', '$2y$10$Sj7RWGIAG8Y3w7HoyIkOG.AxLRzE2Grs1UaJexcFGT/t7GmIhjkrG', 'Johannes Krisnawan', 'Teknik Informatika', 2021, 'johan@email.com', '08813749677', '2025-10-07 12:14:27');

-- --------------------------------------------------------

--
-- Table structure for table `mata_kuliah`
--

CREATE TABLE `mata_kuliah` (
  `id` int(11) NOT NULL,
  `kode_mk` varchar(10) NOT NULL,
  `nama_mk` varchar(100) NOT NULL,
  `sks` int(11) NOT NULL,
  `semester` int(11) NOT NULL,
  `dosen_pengampu` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mata_kuliah`
--

INSERT INTO `mata_kuliah` (`id`, `kode_mk`, `nama_mk`, `sks`, `semester`, `dosen_pengampu`) VALUES
(1, 'MK001', 'Pemrograman Web', 3, 3, 'Dr. Ahmad, M.Kom'),
(2, 'MK002', 'Basis Data', 3, 2, 'Dr. Stefany Puspita Sari, M.T.'),
(3, 'MK003', 'Algoritma dan Pemrograman', 4, 1, 'Prof. Joko Widodo, M.Sc.'),
(4, 'MK004', 'Jaringan Komputer', 3, 4, 'Dr. Rudi, M.T.'),
(5, 'MK005', 'Pemrograman Mobile', 3, 5, 'Dr. Maya, M.Kom.'),
(6, 'MK006', 'Kecerdasan Buatan', 3, 6, 'Prof. Hendra, M.Sc.'),
(7, 'MK007', 'Sistem Operasi', 3, 3, 'Dr. Bambang, M.T.'),
(8, 'MK008', 'Struktur Data', 4, 2, 'Dr. Ani, M.Kom.'),
(9, 'MK009', 'Pemrograman Berorientasi Objek', 3, 3, 'Prof. Surya, M.Sc.');

-- --------------------------------------------------------

--
-- Table structure for table `nilai`
--

CREATE TABLE `nilai` (
  `id` int(11) NOT NULL,
  `mahasiswa_id` int(11) NOT NULL,
  `mata_kuliah_id` int(11) NOT NULL,
  `nilai_huruf` enum('A','B','C','D','E') NOT NULL,
  `nilai_angka` decimal(4,2) NOT NULL,
  `semester` int(11) NOT NULL,
  `tahun_akademik` varchar(9) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nilai`
--

INSERT INTO `nilai` (`id`, `mahasiswa_id`, `mata_kuliah_id`, `nilai_huruf`, `nilai_angka`, `semester`, `tahun_akademik`) VALUES
(1, 1, 3, 'A', '85.50', 1, '2021/2022'),
(2, 2, 3, 'B', '78.00', 1, '2021/2022'),
(3, 3, 3, 'A', '90.00', 1, '2021/2022'),
(4, 4, 3, 'B', '82.50', 1, '2021/2022'),
(5, 5, 3, 'C', '72.00', 1, '2021/2022'),
(6, 1, 2, 'B', '80.00', 2, '2021/2022'),
(7, 1, 8, 'A', '88.00', 2, '2021/2022'),
(8, 2, 2, 'A', '92.00', 2, '2021/2022'),
(9, 3, 2, 'B', '79.50', 2, '2021/2022'),
(10, 4, 8, 'C', '71.00', 2, '2021/2022'),
(11, 1, 1, 'A', '87.00', 3, '2022/2023'),
(12, 1, 7, 'B', '83.50', 3, '2022/2023'),
(13, 1, 9, 'A', '91.00', 3, '2022/2023'),
(14, 2, 1, 'A', '89.00', 3, '2022/2023'),
(15, 3, 1, 'B', '81.00', 3, '2022/2023'),
(16, 4, 7, 'A', '86.50', 3, '2022/2023'),
(17, 5, 1, 'C', '69.00', 3, '2022/2023'),
(18, 6, 3, 'B', '84.00', 1, '2022/2023'),
(19, 7, 3, 'A', '93.00', 1, '2022/2023'),
(20, 8, 3, 'B', '77.50', 1, '2022/2023'),
(21, 9, 3, 'A', '88.00', 1, '2022/2023'),
(22, 10, 3, 'B', '79.00', 1, '2023/2024'),
(23, 11, 3, 'A', '94.00', 1, '2023/2024'),
(24, 12, 3, 'C', '68.50', 1, '2023/2024'),
(25, 13, 3, 'B', '82.00', 1, '2023/2024'),
(26, 2, 7, 'A', '90.50', 3, '2022/2023'),
(27, 3, 9, 'B', '80.00', 3, '2022/2023'),
(28, 6, 2, 'A', '87.50', 2, '2022/2023'),
(29, 7, 8, 'B', '83.00', 2, '2022/2023'),
(30, 8, 1, 'C', '70.50', 3, '2023/2024'),
(31, 9, 7, 'A', '89.00', 3, '2023/2024'),
(35, 17, 2, 'A', '88.00', 3, '2025/2026'),
(36, 17, 3, 'A', '90.00', 1, '2025/2026'),
(37, 17, 4, 'B', '84.00', 2, '2025/2026'),
(38, 17, 6, 'A', '88.00', 3, '2025/2026'),
(39, 17, 9, 'B', '78.00', 4, '2025/2026'),
(40, 17, 5, 'B', '80.00', 5, '2025/2026'),
(41, 17, 1, 'A', '86.00', 6, '2025/2026'),
(42, 17, 7, 'B', '77.00', 7, '2025/2026'),
(43, 17, 8, 'C', '74.00', 8, '2025/2026');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `jadwal_kuliah`
--
ALTER TABLE `jadwal_kuliah`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mata_kuliah_id` (`mata_kuliah_id`);

--
-- Indexes for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `npm` (`npm`);

--
-- Indexes for table `mata_kuliah`
--
ALTER TABLE `mata_kuliah`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_mk` (`kode_mk`);

--
-- Indexes for table `nilai`
--
ALTER TABLE `nilai`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_nilai` (`mahasiswa_id`,`mata_kuliah_id`,`semester`,`tahun_akademik`),
  ADD KEY `mata_kuliah_id` (`mata_kuliah_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `jadwal_kuliah`
--
ALTER TABLE `jadwal_kuliah`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `mata_kuliah`
--
ALTER TABLE `mata_kuliah`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `nilai`
--
ALTER TABLE `nilai`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `jadwal_kuliah`
--
ALTER TABLE `jadwal_kuliah`
  ADD CONSTRAINT `jadwal_kuliah_ibfk_1` FOREIGN KEY (`mata_kuliah_id`) REFERENCES `mata_kuliah` (`id`);

--
-- Constraints for table `nilai`
--
ALTER TABLE `nilai`
  ADD CONSTRAINT `nilai_ibfk_1` FOREIGN KEY (`mahasiswa_id`) REFERENCES `mahasiswa` (`id`),
  ADD CONSTRAINT `nilai_ibfk_2` FOREIGN KEY (`mata_kuliah_id`) REFERENCES `mata_kuliah` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
