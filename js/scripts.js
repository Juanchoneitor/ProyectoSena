document.addEventListener('DOMContentLoaded', function() {
    // Validación de formularios
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
            let valid = true;
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    valid = false;
                    input.classList.add('is-invalid');
                } else {
                    input.classList.remove('is-invalid');
                }
            });
            if (!valid) {
                e.preventDefault();
                alert('Por favor, completa todos los campos obligatorios.');
            }
        });
    });

    // Confirmación para eliminar
    const deleteButtons = document.querySelectorAll('.btn-danger');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('¿Seguro que deseas eliminar este elemento?')) {
                e.preventDefault();
            }
        });
    });

    // AJAX para eliminar cursos
    const deleteCourseLinks = document.querySelectorAll('a[href*="courseController.php?action=delete"]');
    deleteCourseLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('¿Seguro que deseas eliminar este curso?')) {
                fetch(this.href)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert('Error: ' + data.error);
                        }
                    })
                    .catch(error => alert('Error: ' + error));
            }
        });
    });
});