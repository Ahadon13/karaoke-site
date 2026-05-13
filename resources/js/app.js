import Alpine from 'alpinejs';

const initScrollReveal = () => {
    const revealItems = document.querySelectorAll('[data-reveal]');

    if (!revealItems.length) {
        return;
    }

    if (!('IntersectionObserver' in window)) {
        revealItems.forEach((item) => item.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        },
        {
            threshold: 0.16,
            rootMargin: '0px 0px -8% 0px',
        },
    );

    revealItems.forEach((item) => observer.observe(item));
};

document.addEventListener('DOMContentLoaded', initScrollReveal);

window.Alpine = Alpine;

Alpine.start();
