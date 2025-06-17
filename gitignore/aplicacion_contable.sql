-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 02-04-2025 a las 15:42:19
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Base de datos: `aplicacion_contable`

-- --------------------------------------------------------

-- Estructura de tabla para la tabla `acreedores`
CREATE TABLE `acreedores` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `saldo` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acreedores_nombre_index` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Estructura de tabla para la tabla `compradores`
CREATE TABLE `compradores` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `direccion` varchar(255) NOT NULL,
  `telefono` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `dni` VARCHAR(20) NOT NULL,
  `lote_comprado_id` BIGINT(20) UNSIGNED DEFAULT NULL,
  `financiacion_id` bigint(20) UNSIGNED DEFAULT NULL,
  `judicializado` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `compradores_email_index` (`email`),
  KEY `fk_compradores_financiacion` (`financiacion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Estructura de tabla para la tabla `comprador_acreedor`
CREATE TABLE `comprador_acreedor` (
  `comprador_id` bigint(20) UNSIGNED NOT NULL,
  `acreedor_id` bigint(20) UNSIGNED NOT NULL,
  `porcentaje_cuota` decimal(5,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`comprador_id`, `acreedor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Estructura de tabla para la tabla `cuotas`
CREATE TABLE `cuotas` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `financiacion_id` bigint(20) UNSIGNED NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha_de_vencimiento` date NOT NULL,
  `estado` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Estructura de tabla para la tabla `failed_jobs`
CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Estructura de tabla para la tabla `financiaciones`
CREATE TABLE `financiaciones` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `comprador_id` bigint(20) UNSIGNED NOT NULL UNIQUE,
  `monto_a_financiar` decimal(10,2) NOT NULL,
  `cantidad_de_cuotas` int(11) NOT NULL,
  `monto_de_las_cuotas` decimal(10,2) NOT NULL,
  `fecha_de_vencimiento` date NOT NULL,
  `mes_de_inicio` date NOT NULL,
  `mes_de_finalizacion` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Estructura de tabla para la tabla `financiacion_acreedor`
CREATE TABLE `financiacion_acreedor` (
  `financiacion_id` bigint(20) UNSIGNED NOT NULL,
  `acreedor_id` bigint(20) UNSIGNED NOT NULL,
  `porcentaje` decimal(5,2) NOT NULL,
  PRIMARY KEY (`financiacion_id`, `acreedor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Estructura de tabla para la tabla `lotes`
CREATE TABLE `lotes` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `estado` varchar(255) NOT NULL,
  `comprador_id` bigint(20) UNSIGNED UNIQUE,
  `loteo` varchar(255) NOT NULL,
  `manzana` varchar(255) NOT NULL,
  `lote` varchar(255) NOT NULL,
  `mts_cuadrados` decimal(8,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Estructura de tabla para la tabla `migrations`
CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Estructura de tabla para la tabla `pagos`
CREATE TABLE `pagos` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `cuota_id` bigint(20) UNSIGNED NOT NULL,
  `acreedor_id` bigint(20) UNSIGNED NOT NULL,
  `monto_pagado` decimal(10,2) NOT NULL,
  `comprobante` varchar(255) DEFAULT NULL,
  `sin_comprobante` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_de_pago` date NOT NULL,
  `pago_divisa` TINYINT(1) NOT NULL DEFAULT 0,
  `monto_usd` DECIMAL(10,2) DEFAULT NULL,
  `tipo_cambio` DECIMAL(10,4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Estructura de tabla para la tabla `password_resets`
CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Estructura de tabla para la tabla `personal_access_tokens`
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Estructura de tabla para la tabla `users`
CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices para tablas volcadas

-- Indices de la tabla `compradores`
ALTER TABLE `compradores`
  ADD CONSTRAINT `compradores_lote_comprado_id_foreign` FOREIGN KEY (`lote_comprado_id`) REFERENCES `lotes` (`id`),
  ADD CONSTRAINT `compradores_financiacion_id_foreign` FOREIGN KEY (`financiacion_id`) REFERENCES `financiaciones` (`id`);

-- Indices de la tabla `comprador_acreedor`
ALTER TABLE `comprador_acreedor`
  ADD CONSTRAINT `comprador_acreedor_comprador_id_foreign` FOREIGN KEY (`comprador_id`) REFERENCES `compradores` (`id`),
  ADD CONSTRAINT `comprador_acreedor_acreedor_id_foreign` FOREIGN KEY (`acreedor_id`) REFERENCES `acreedores` (`id`);

-- Indices de la tabla `cuotas`
ALTER TABLE `cuotas`
  ADD CONSTRAINT `cuotas_financiacion_id_foreign` FOREIGN KEY (`financiacion_id`) REFERENCES `financiaciones` (`id`);

-- Indices de la tabla `financiaciones`
ALTER TABLE `financiaciones`
  ADD CONSTRAINT `financiaciones_comprador_id_foreign` FOREIGN KEY (`comprador_id`) REFERENCES `compradores` (`id`);

-- Indices de la tabla `financiacion_acreedor`
ALTER TABLE `financiacion_acreedor`
  ADD CONSTRAINT `financiacion_acreedor_financiacion_id_foreign` FOREIGN KEY (`financiacion_id`) REFERENCES `financiaciones` (`id`),
  ADD CONSTRAINT `financiacion_acreedor_acreedor_id_foreign` FOREIGN KEY (`acreedor_id`) REFERENCES `acreedores` (`id`);

-- Indices de la tabla `lotes`
ALTER TABLE `lotes`
  ADD CONSTRAINT `lotes_comprador_id_foreign` FOREIGN KEY (`comprador_id`) REFERENCES `compradores` (`id`);

-- Indices de la tabla `pagos`
ALTER TABLE `pagos`
  ADD CONSTRAINT `pagos_cuota_id_foreign` FOREIGN KEY (`cuota_id`) REFERENCES `cuotas` (`id`),
  ADD CONSTRAINT `pagos_acreedor_id_foreign` FOREIGN KEY (`acreedor_id`) REFERENCES `acreedores` (`id`);

COMMIT;