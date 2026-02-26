<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plataforma de Cursos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Fuente moderna y minimalista -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif; /* Fuente limpia */
            background-color: #f9fafb; /* Gris muy claro */
            color: #1e293b; /* Texto gris oscuro para buena legibilidad */
        }
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .wrapper {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .content {
            flex: 1 0 auto;
            padding: 20px;
            padding-bottom: 180px; /* AGREGADO: Espacio para el footer */
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php require_once 'navbar.php'; ?>
        <div class="content">
