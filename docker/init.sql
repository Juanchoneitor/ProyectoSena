-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 26-02-2026 a las 05:00:52
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `cursos_app`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(7, 'Programación Web', 'Cursos relacionados con desarrollo web frontend y backend'),
(8, 'Bases de Datos', 'Cursos sobre diseño y administración de bases de datos'),
(9, 'Inteligencia Artificial', 'Cursos relacionados con IA y Machine Learning'),
(10, 'Redes y Seguridad', 'Cursos de redes informáticas y ciberseguridad'),
(11, 'Ingeniería de Software', 'Metodologías, arquitectura y gestión de proyectos'),
(12, 'Sistemas Operativos', 'Administración y fundamentos de sistemas operativos'),
(13, 'Desarrollo Móvil', 'Creación de aplicaciones Android e iOS'),
(14, 'Electrónica e Instrumentación', 'Cursos técnicos industriales y automatización'),
(15, 'Ofimática', 'Herramientas como Excel, Word y PowerPoint'),
(16, 'General', 'Categoría general para cursos variados');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `courses`
--

INSERT INTO `courses` (`id`, `title`, `description`, `category_id`, `teacher_id`, `created_at`) VALUES
(20, 'Introducción a la programación', 'En este curso veremos las bases de la programación ', 7, 20, '2026-02-24 03:06:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `enrollments`
--

INSERT INTO `enrollments` (`id`, `student_id`, `course_id`, `enrolled_at`) VALUES
(11, 19, 20, '2026-02-24 03:10:01');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `guides`
--

CREATE TABLE `guides` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materials`
--

CREATE TABLE `materials` (
  `id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `content_type` varchar(20) DEFAULT 'file',
  `content_url` text DEFAULT NULL,
  `content_text` longtext DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `materials`
--

INSERT INTO `materials` (`id`, `course_id`, `title`, `description`, `content_type`, `content_url`, `content_text`, `file_path`, `uploaded_at`) VALUES
(4, 20, 'Que es la programacion', 'Material de estudio', 'text', NULL, 'La programación es el proceso de crear instrucciones que una computadora puede entender y ejecutar. A través de lenguajes de programación, los desarrolladores diseñan soluciones para resolver problemas, automatizar tareas y construir aplicaciones o sistemas. En esencia, programar es transformar ideas en código que da vida a herramientas digitales.', '', '2026-02-24 03:09:24');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `material_progress`
--

CREATE TABLE `material_progress` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `submissions`
--

CREATE TABLE `submissions` (
  `id` int(11) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `text_submission` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `grade` decimal(4,2) DEFAULT NULL,
  `feedback` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tasks`
--

INSERT INTO `tasks` (`id`, `course_id`, `title`, `description`, `due_date`, `created_at`) VALUES
(12, 20, 'Que es la programacion', 'Da un breve resumen sobre que es la programacion', '2026-02-28', '2026-02-24 03:07:47');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `profile_image`) VALUES
(19, 'Thomas Quintero', 'thomas@gmail.com', '$2y$10$rfndo517SSzlaqMNizjLPuORj0NRREfDB80hcaBl0Z0Xv/M6MyDkm', 'student', '2026-02-24 02:16:18', NULL),
(20, 'Martin Quintero', 'martin@gmail.com', '$2y$10$EIfkC6OtF5oqw2BoSlgLBuzXchZxIFUNZ9bwvsTvIbVN4H8dE6yce', 'teacher', '2026-02-24 02:17:11', NULL),
(22, 'Juan David', 'juan@gmail.com', '$2y$10$/g5nfn2fo9Hc6r4PsNNCou396wLSHp8EVjvhyznxL2tVxV2g.31ty', 'admin', '2026-02-24 02:20:19', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `videos`
--

CREATE TABLE `videos` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indices de la tabla `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_enrollment` (`student_id`,`course_id`),
  ADD KEY `fk_course` (`course_id`);

--
-- Indices de la tabla `guides`
--
ALTER TABLE `guides`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indices de la tabla `materials`
--
ALTER TABLE `materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indices de la tabla `material_progress`
--
ALTER TABLE `material_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_progress` (`student_id`,`material_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `material_id` (`material_id`);

--
-- Indices de la tabla `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indices de la tabla `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `guides`
--
ALTER TABLE `guides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `materials`
--
ALTER TABLE `materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `material_progress`
--
ALTER TABLE `material_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `submissions`
--
ALTER TABLE `submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `videos`
--
ALTER TABLE `videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `courses_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `guides`
--
ALTER TABLE `guides`
  ADD CONSTRAINT `guides_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `materials`
--
ALTER TABLE `materials`
  ADD CONSTRAINT `materials_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`);

--
-- Filtros para la tabla `material_progress`
--
ALTER TABLE `material_progress`
  ADD CONSTRAINT `material_progress_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `material_progress_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`),
  ADD CONSTRAINT `material_progress_ibfk_3` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`);

--
-- Filtros para la tabla `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`),
  ADD CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`);

--
-- Filtros para la tabla `videos`
--
ALTER TABLE `videos`
  ADD CONSTRAINT `videos_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;