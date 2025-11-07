-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 07, 2025 at 01:54 PM
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
(1, 'admin', 'password', 'Administrator', '2025-10-05 13:07:21');

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
(6, 6, 'Sabtu', '08:00:00', '10:30:00', 'R.105');

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
(1, '202101001', 'password', 'Budi Santoso', 'Teknik Informatika', 2021, 'budi@email.com', '081234567890', '2025-10-05 13:07:21'),
(2, '202101002', 'password', 'Siti Rahayu', 'Sistem Informasi', 2021, 'siti@email.com', '081234567891', '2025-10-05 13:07:21'),
(3, '202102001', 'password', 'Ahmad Wijaya', 'Teknik Informatika', 2022, 'ahmad@email.com', '081234567892', '2025-10-07 10:59:18'),
(4, '202103001', 'password', 'Dewi Lestari', 'Sistem Informasi', 2023, 'dewi@email.com', '081234567893', '2025-10-07 10:59:18'),
(5, '202101003', 'password', 'Rina Melati', 'Teknik Informatika', 2021, 'rina@email.com', '081234567894', '2025-10-07 11:09:40'),
(6, '202102002', 'password', 'Fajar Setiawan', 'Sistem Informasi', 2022, 'fajar@email.com', '081234567895', '2025-10-07 11:09:40'),
(7, '202103002', 'password', 'Maya Sari', 'Teknik Informatika', 2023, 'maya@email.com', '081234567896', '2025-10-07 11:09:40');

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
(2, 'MK002', 'Basis Data', 3, 2, 'Dr. Sari, M.T.'),
(3, 'MK003', 'Algoritma dan Pemrograman', 4, 1, 'Prof. Joko, M.Sc.'),
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
(1, 1, 1, 'A', '85.50', 3, '2023/2024'),
(2, 1, 2, 'B', '78.00', 2, '2022/2023'),
(3, 2, 1, 'A', '90.00', 3, '2023/2024'),
(4, 2, 3, 'B', '82.50', 1, '2021/2022'),
(5, 1, 2, 'A', '89.00', 5, '2023');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `mata_kuliah`
--
ALTER TABLE `mata_kuliah`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `nilai`
--
ALTER TABLE `nilai`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

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
