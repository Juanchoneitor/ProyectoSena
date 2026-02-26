<footer class="footer mt-auto">
    <div class="container text-center py-3">
        <p class="mb-1">&copy; <?php echo date('Y'); ?> Plataforma de Cursos. Todos los derechos reservados.</p>
        <p class="mb-0">
            <a href="/views/contact.php" class="footer-link">Contacto</a> | 
            <a href="/views/about.php" class="footer-link">Acerca de</a>
        </p>
    </div>
</footer>

<style>
    /* Agregar padding al body para que el contenido no quede tapado /
    body {
        padding-bottom: 100px; / Espacio para el footer fijo /
        min-height: 100vh;
    }

    .footer {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        background-color: #1a1a1a;
        color: #e5e7eb;
        border-top: 1px solid #333;
        z-index: 1000;
    }

    .footer-link {
        color: #fbbf24;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.2s ease;
    }

    .footer-link:hover {
        color: #facc15;
    }

    / Responsive - Ajustar padding en móviles */
    @media (max-width: 768px) {
        body {
            padding-bottom: 120px;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>