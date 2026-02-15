-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 15-02-2026 a las 18:00:11
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
  `precio` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `nombre`, `descripcion`, `cantidad`, `precio`) VALUES
(1, 'Resma de papel carta', 'Paquete de 500 hojas', 20, 6.50),
(2, 'Cuaderno universitario', 'Cuaderno de 100 hojas', 30, 2.25),
(3, 'Lapicero azul', 'Lapicero tinta azul', 100, 0.35),
(4, 'Lapicero negro', 'Lapicero tinta negra', 80, 0.35),
(5, 'Lápiz', 'Lápiz HB', 120, 0.25),
(6, 'Borrador', 'Borrador blanco', 60, 0.30),
(7, 'Marcador permanente', 'Marcador negro', 40, 1.10),
(8, 'Grapadora', 'Grapadora metálica', 10, 4.50),
(9, 'Impresora', 'Impresora multifuncional', 2, 180.00);

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
(1, 'jose', 'jose@gmail.com', '$2y$10$e0NRX0m1yC4b1x8nF2q9YeQ5yZq5Vx5k9ZC8vZ6FJ7R7d9p6D7K8K', 'admin'),
(2, 'alexis', 'alexis@gmail.com', '$2y$10$e0NRX0m1yC4b1x8nF2q9YeQ5yZq5Vx5k9ZC8vZ6FJ7R7d9p6D7K8K', 'usuario'),
(3, 'jonathan', 'jonathan@gmail.com', '$2y$10$e0NRX0m1yC4b1x8nF2q9YeQ5yZq5Vx5k9ZC8vZ6FJ7R7d9p6D7K8K', 'usuario'),
(5, 'josep', 'jose9pp@gmail.com', '$2y$10$Bgc8gY/ewTT9OWLnlRJ7R.R7CAib7PLJCpoNLcuYDYxfwbTlsnTa.', 'usuario'),
(7, 'manu', 'jmanu@gmail.com', '$2y$10$fnf8fS4j8J91bAuo2AIvlOezGcwZJvvAHl7kGKCwN6prkYcCmsrOO', 'usuario'),
(8, 'manuel', 'manuel@gmail.com', '$2y$10$ZBvkl5FnweHRgAb8jwqiDuhtnaV4dWVXQN6e4mIIfTfrjq7COr77O', 'usuario'),
(9, 'jona', 'jona20@gmail.com', '$2y$10$ed2LEJ73eoMRGHe0WXJREeji9VK11FIL0t94yOuXacdtIrc0PB0gK', 'usuario');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
