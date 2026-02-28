-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 28-02-2026 a las 00:12:21
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `inventario_login`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `proveedores` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `nombre`, `descripcion`, `cantidad`, `precio`, `proveedores`) VALUES
(1, 'Resma de papel carta', 'Paquete de 500 hojas', 20, 6.50, 'Walmart'),
(2, 'Cuaderno universitario', 'Cuaderno de 100 hojas', 30, 2.25, 'Walmart'),
(3, 'Lapicero azul', 'Lapicero tinta azul', 100, 0.35, 'Walmart'),
(4, 'Lapicero negro', 'Lapicero tinta negra', 80, 0.35, 'Walmart'),
(5, 'Lapiz', 'Lapiz HB', 120, 0.25, 'Walmart'),
(6, 'Borrador', 'Borrador blanco', 60, 0.30, 'Walmart'),
(7, 'Marcador permanente', 'Marcador negro', 40, 1.10, 'Walmart'),
(8, 'Grapadora', 'Grapadora metalica', 10, 4.50, 'Walmart'),
(9, 'Tijeras', 'Tijeras de oficina', 25, 1.75, 'Walmart'),
(10, 'Cinta adhesiva', 'Rollo de cinta transparente', 50, 0.80, 'Walmart'),
(11, 'Silla ejecutiva', 'Silla ergonomica con ruedas', 5, 120.00, 'Siman'),
(12, 'Escritorio de madera', 'Escritorio 120x60 cm', 3, 250.00, 'Siman'),
(13, 'Estanteria metalica', 'Estante 5 niveles', 4, 85.00, 'Siman'),
(14, 'Archivador', 'Archivador 3 gavetas', 6, 95.00, 'Siman'),
(15, 'Mesa de reuniones', 'Mesa ovalada para 8 personas', 1, 480.00, 'Siman'),
(16, 'HP Laptop 15', 'Intel Core i5 8GB RAM 256GB SSD', 4, 650.00, 'HP'),
(17, 'HP EliteBook 840', 'Intel Core i7 16GB RAM 512GB SSD', 3, 1100.00, 'HP'),
(18, 'HP Desktop ProDesk', 'Torre Intel i5 8GB RAM 1TB HDD', 2, 750.00, 'HP'),
(19, 'HP LaserJet Pro', 'Impresora laser monocromatica', 2, 320.00, 'HP'),
(20, 'HP Monitor 24', 'Monitor FHD IPS 24 pulgadas', 5, 190.00, 'HP'),
(21, 'Samsung Galaxy A54', 'Smartphone 6.4 128GB', 6, 380.00, 'Samsung'),
(22, 'Samsung Galaxy Tab S6', 'Tablet 10.5 128GB WiFi', 4, 420.00, 'Samsung'),
(23, 'Samsung Monitor 27', 'Monitor 4K UHD 27 pulgadas', 3, 310.00, 'Samsung'),
(24, 'Samsung SSD 1TB', 'Disco SSD externo USB-C', 8, 95.00, 'Samsung'),
(25, 'Samsung Galaxy S23', 'Smartphone 256GB 5G', 5, 750.00, 'Samsung'),
(26, 'Lenovo ThinkPad E15', 'Intel Core i5 16GB RAM 512GB SSD', 3, 890.00, 'Lenovo'),
(27, 'Lenovo IdeaPad 3', 'Intel Core i3 8GB RAM 256GB SSD', 5, 520.00, 'Lenovo'),
(28, 'Lenovo ThinkCentre', 'PC de escritorio empresarial i7', 2, 980.00, 'Lenovo'),
(29, 'Lenovo Tab P11', 'Tablet 11 128GB Android', 4, 290.00, 'Lenovo'),
(30, 'Lenovo Monitor 23.8', 'Monitor FHD IPS para oficina', 4, 175.00, 'Lenovo'),
(31, 'iPhone 14', 'Smartphone 128GB iOS', 4, 799.00, 'Apple'),
(32, 'iPhone 15 Pro', 'Smartphone 256GB chip A17', 3, 1099.00, 'Apple'),
(33, 'MacBook Air M2', 'Laptop 13 Apple Silicon 8GB 256GB', 2, 1199.00, 'Apple'),
(34, 'iPad Air', 'Tablet 10.9 64GB WiFi', 4, 599.00, 'Apple'),
(35, 'Teclado inalambrico', 'Teclado Bluetooth compacto', 10, 35.00, 'Amazon'),
(36, 'Mouse optico', 'Mouse USB 1600 DPI', 15, 18.00, 'Amazon'),
(37, 'Webcam HD 1080p', 'Camara web con microfono', 6, 55.00, 'Amazon'),
(38, 'Hub USB-C 7 en 1', 'Concentrador USB-C multipuerto', 8, 42.00, 'Amazon'),
(39, 'Audifonos con micro', 'Headset para videollamadas', 10, 38.00, 'Amazon');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `usuario` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `rol` enum('admin','usuario') DEFAULT 'usuario'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `usuario`, `email`, `password`, `rol`) VALUES
(1, 'jose', 'jose@gmail.com', '$2y$10$q24buFmWJDSp6DFCQeauP.GTGlL8UvAKkqOvtm.I9bP4OWkh4wM1a', 'admin'),
(2, 'alexis', 'alexis@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'usuario'),
(3, 'jonathan', 'jonathan@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'usuario'),
(4, 'manuel', 'manuel@gmail.com', '$2y$10$rN.GROosHEwn1jU/muT.CesSsBx.ZYWaTMetOYABveRIzac42RTuO', 'usuario');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
