-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Vært: mariadb:3306
-- Genereringstid: 15. 10 2022 kl. 12:00:30
-- Serverversion: 10.5.17-MariaDB-1:10.5.17+maria~ubu2004
-- PHP-version: 8.0.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `webhost_db`
--

-- --------------------------------------------------------

--
-- Struktur-dump for tabellen `authorized_keys`
--

CREATE TABLE `authorized_keys` (
  `id` int(24) UNSIGNED NOT NULL,
  `keyid` varchar(55) NOT NULL,
  `username` varchar(155) NOT NULL,
  `description` mediumtext NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktur-dump for tabellen `locales`
--

CREATE TABLE `locales` (
  `id` int(10) UNSIGNED NOT NULL,
  `label` varchar(55) NOT NULL,
  `code` varchar(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Data dump for tabellen `locales`
--

INSERT INTO `locales` (`id`, `label`, `code`) VALUES
(1, 'English (US)', 'en_US'),
(2, 'Danish (DK)', 'da_DK');

-- --------------------------------------------------------

--
-- Struktur-dump for tabellen `php_fastcgi_port_counter`
--

CREATE TABLE `php_fastcgi_port_counter` (
  `current` int(48) NOT NULL DEFAULT 7999
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Data dump for tabellen `php_fastcgi_port_counter`
--

INSERT INTO `php_fastcgi_port_counter` (`current`) VALUES
(7999);

-- --------------------------------------------------------

--
-- Struktur-dump for tabellen `ssh_keys`
--

CREATE TABLE `ssh_keys` (
  `id` int(24) UNSIGNED NOT NULL,
  `keyid` varchar(155) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `username` varchar(155) NOT NULL,
  `description` mediumtext NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktur-dump for tabellen `ssh_users`
--

CREATE TABLE `ssh_users` (
  `id` int(24) UNSIGNED NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktur-dump for tabellen `vhosts`
--

CREATE TABLE `vhosts` (
  `id` int(24) UNSIGNED NOT NULL,
  `username` varchar(255) NOT NULL,
  `userid` int(24) NOT NULL,
  `www_root` varchar(255) NOT NULL,
  `php_version` varchar(5) NOT NULL DEFAULT '8.1',
  `domains_json` mediumtext NOT NULL,
  `www_suffix_domains_json` mediumtext NOT NULL,
  `ssl_domains_json` mediumtext NOT NULL,
  `nginx_config_path` varchar(255) NOT NULL,
  `fpm_config_path` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Begrænsninger for dumpede tabeller
--

--
-- Indeks for tabel `authorized_keys`
--
ALTER TABLE `authorized_keys`
  ADD PRIMARY KEY (`id`);

--
-- Indeks for tabel `locales`
--
ALTER TABLE `locales`
  ADD PRIMARY KEY (`id`);

--
-- Indeks for tabel `ssh_keys`
--
ALTER TABLE `ssh_keys`
  ADD PRIMARY KEY (`id`);

--
-- Indeks for tabel `ssh_users`
--
ALTER TABLE `ssh_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks for tabel `vhosts`
--
ALTER TABLE `vhosts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Brug ikke AUTO_INCREMENT for slettede tabeller
--

--
-- Tilføj AUTO_INCREMENT i tabel `authorized_keys`
--
ALTER TABLE `authorized_keys`
  MODIFY `id` int(24) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tilføj AUTO_INCREMENT i tabel `locales`
--
ALTER TABLE `locales`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tilføj AUTO_INCREMENT i tabel `ssh_keys`
--
ALTER TABLE `ssh_keys`
  MODIFY `id` int(24) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tilføj AUTO_INCREMENT i tabel `ssh_users`
--
ALTER TABLE `ssh_users`
  MODIFY `id` int(24) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tilføj AUTO_INCREMENT i tabel `vhosts`
--
ALTER TABLE `vhosts`
  MODIFY `id` int(24) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;
