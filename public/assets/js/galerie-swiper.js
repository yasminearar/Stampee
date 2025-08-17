/**
 * Gestionnaire de galeries Swiper pour le catalogue
 * Utilise Swiper.js pour une expérience optimale
 */
class GalerieSwiperCatalogue {
    constructor() {
        this.swipers = new Map();
        this.init();
    }

    init() {
        // Attendre que Swiper soit chargé
        if (typeof Swiper === 'undefined') {
            setTimeout(() => this.init(), 100);
            return;
        }

        this.initAllSwipers();
    }

    initAllSwipers() {
        document.querySelectorAll('.carte-produit__swiper').forEach(swiperElement => {
            this.initSwiper(swiperElement);
        });
    }

    initSwiper(swiperElement) {
        const timbreId = swiperElement.dataset.timbreId;
        
        const swiperConfig = {
            // Configuration de base
            loop: true,
            grabCursor: true,
            centeredSlides: true,
            slidesPerView: 1,
            spaceBetween: 0,

            // Auto-play au survol uniquement
            autoplay: false,

            // Navigation
            navigation: {
                nextEl: swiperElement.querySelector('.swiper-button-next'),
                prevEl: swiperElement.querySelector('.swiper-button-prev'),
            },

            // Pagination avec bullets cliquables
            pagination: {
                el: swiperElement.querySelector('.swiper-pagination'),
                clickable: true,
                dynamicBullets: true,
                dynamicMainBullets: 3,
            },

            // Lazy loading
            lazy: {
                loadPrevNext: true,
                loadPrevNextAmount: 2,
            },

            // Effets et transitions
            effect: 'fade',
            fadeEffect: {
                crossFade: true
            },

            // Performance
            watchSlidesProgress: true,
            watchSlidesVisibility: true,

            // Touch et gestes
            touchRatio: 1,
            touchAngle: 45,
            simulateTouch: true,
            shortSwipes: true,
            longSwipes: true,

            // Accessibilité
            a11y: {
                prevSlideMessage: 'Image précédente',
                nextSlideMessage: 'Image suivante',
                firstSlideMessage: 'Première image',
                lastSlideMessage: 'Dernière image',
                paginationBulletMessage: 'Aller à l\'image {{index}}',
            },

            // Callbacks
            on: {
                init: () => this.onSwiperInit(timbreId),
                slideChange: () => this.onSlideChange(timbreId),
            }
        };

        // Créer l'instance Swiper
        const swiper = new Swiper(swiperElement, swiperConfig);
        this.swipers.set(timbreId, swiper);

        // Gestion de l'auto-play au survol
        this.setupHoverAutoplay(swiperElement, swiper);
    }

    setupHoverAutoplay(swiperElement, swiper) {
        const carteElement = swiperElement.closest('.carte-produit');
        let autoplayTimer;

        carteElement.addEventListener('mouseenter', () => {
            // Démarrer l'auto-play au survol
            autoplayTimer = setInterval(() => {
                if (swiper && !swiper.destroyed) {
                    swiper.slideNext();
                }
            }, 2500); // Change toutes les 2.5 secondes
        });

        carteElement.addEventListener('mouseleave', () => {
            // Arrêter l'auto-play
            if (autoplayTimer) {
                clearInterval(autoplayTimer);
                autoplayTimer = null;
            }
        });

        // Pause auto-play si l'utilisateur interagit
        swiperElement.addEventListener('touchstart', () => {
            if (autoplayTimer) {
                clearInterval(autoplayTimer);
                autoplayTimer = null;
            }
        });

        swiperElement.addEventListener('mousedown', () => {
            if (autoplayTimer) {
                clearInterval(autoplayTimer);
                autoplayTimer = null;
            }
        });
    }

    onSwiperInit(timbreId) {
        // Actions à l'initialisation
        console.log(`Galerie ${timbreId} initialisée`);
    }

    onSlideChange(timbreId) {
        // Actions lors du changement de slide
        const swiper = this.swipers.get(timbreId);
        if (swiper) {
            // Optionnel : analytics ou autres actions
        }
    }

    // Méthode pour réinitialiser après du contenu dynamique
    refreshAll() {
        // Détruire les anciennes instances
        this.swipers.forEach(swiper => {
            if (swiper && !swiper.destroyed) {
                swiper.destroy(true, true);
            }
        });
        this.swipers.clear();

        // Réinitialiser
        this.initAllSwipers();
    }

    // Méthode pour détruire proprement
    destroy() {
        this.swipers.forEach(swiper => {
            if (swiper && !swiper.destroyed) {
                swiper.destroy(true, true);
            }
        });
        this.swipers.clear();
    }
}

// Instance globale
let galerieCatalogue;

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    galerieCatalogue = new GalerieSwiperCatalogue();
});

// Support pour les requêtes AJAX (si applicable)
document.addEventListener('ajaxComplete', () => {
    if (galerieCatalogue) {
        galerieCatalogue.refreshAll();
    }
});

// Nettoyage avant fermeture de page
window.addEventListener('beforeunload', () => {
    if (galerieCatalogue) {
        galerieCatalogue.destroy();
    }
});
