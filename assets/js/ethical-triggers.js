/**
 * WM Ethical Conversion Triggers: fade-in of the notice, skipped with prefers-reduced-motion.
 */
document.addEventListener('DOMContentLoaded', function() {
    const containers = document.querySelectorAll('.wm-ect-container');
    
    if (containers.length === 0) return;

    // WCAG 2.2: Respetar prefers-reduced-motion
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    containers.forEach(function(container) {
        // Ejemplo de animación sutil solo si el usuario no tiene reducida la moción
        if (!prefersReducedMotion) {
            container.style.opacity = '0';
            container.style.transform = 'translateY(10px)';
            container.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            
            // Trigger reflow
            void container.offsetWidth;
            
            container.style.opacity = '1';
            container.style.transform = 'translateY(0)';
        }
    });
});
