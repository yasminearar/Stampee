/**
 * Gestionnaire de galeries d'images pour le catalogue
 */
class GalerieCatalogue {
    constructor() {
        this.galleries = new Map();
        this.init();
    }

    init() {
        // Initialiser toutes les galeries sur la page
        document.querySelectorAll('.carte-produit__gallery').forEach(gallery => {
            this.initGallery(gallery);
        });
    }

    initGallery(galleryElement) {
        const timbreId = galleryElement.dataset.timbreId;
        const images = galleryElement.querySelectorAll('.carte-produit__gallery-image');
        
        if (images.length <= 1) {
            return; // Pas besoin de navigation pour une seule image
        }

        const galleryData = {
            element: galleryElement,
            images: images,
            currentIndex: 0,
            total: images.length
        };

        this.galleries.set(timbreId, galleryData);
        this.setupNavigation(timbreId, galleryData);
        this.setupDots(timbreId, galleryData);
        this.setupAutoPlay(timbreId, galleryData);
    }

    setupNavigation(timbreId, galleryData) {
        const container = galleryData.element.parentElement;
        
        // Bouton précédent
        const prevBtn = document.createElement('button');
        prevBtn.className = 'carte-produit__gallery-nav carte-produit__gallery-nav--prev';
        prevBtn.innerHTML = '‹';
        prevBtn.setAttribute('aria-label', 'Image précédente');
        prevBtn.onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.showPrevious(timbreId);
        };

        // Bouton suivant
        const nextBtn = document.createElement('button');
        nextBtn.className = 'carte-produit__gallery-nav carte-produit__gallery-nav--next';
        nextBtn.innerHTML = '›';
        nextBtn.setAttribute('aria-label', 'Image suivante');
        nextBtn.onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.showNext(timbreId);
        };

        container.appendChild(prevBtn);
        container.appendChild(nextBtn);
    }

    setupDots(timbreId, galleryData) {
        const container = galleryData.element.parentElement;
        const dotsContainer = document.createElement('div');
        dotsContainer.className = 'carte-produit__gallery-dots';

        for (let i = 0; i < galleryData.total; i++) {
            const dot = document.createElement('button');
            dot.className = 'carte-produit__gallery-dot';
            if (i === 0) dot.classList.add('carte-produit__gallery-dot--active');
            dot.setAttribute('aria-label', `Aller à l'image ${i + 1}`);
            dot.onclick = (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.goToImage(timbreId, i);
            };
            dotsContainer.appendChild(dot);
        }

        container.appendChild(dotsContainer);
        galleryData.dotsContainer = dotsContainer;
    }

    setupAutoPlay(timbreId, galleryData) {
        const container = galleryData.element.parentElement.parentElement;
        let autoPlayInterval;

        // Démarrer l'auto-play au survol
        container.addEventListener('mouseenter', () => {
            autoPlayInterval = setInterval(() => {
                this.showNext(timbreId);
            }, 3000); // Changer d'image toutes les 3 secondes
        });

        // Arrêter l'auto-play quand on quitte le survol
        container.addEventListener('mouseleave', () => {
            if (autoPlayInterval) {
                clearInterval(autoPlayInterval);
                autoPlayInterval = null;
            }
        });

        galleryData.autoPlayInterval = autoPlayInterval;
    }

    showNext(timbreId) {
        const galleryData = this.galleries.get(timbreId);
        if (!galleryData) return;

        galleryData.currentIndex = (galleryData.currentIndex + 1) % galleryData.total;
        this.updateGallery(timbreId, galleryData);
    }

    showPrevious(timbreId) {
        const galleryData = this.galleries.get(timbreId);
        if (!galleryData) return;

        galleryData.currentIndex = galleryData.currentIndex === 0 
            ? galleryData.total - 1 
            : galleryData.currentIndex - 1;
        this.updateGallery(timbreId, galleryData);
    }

    goToImage(timbreId, index) {
        const galleryData = this.galleries.get(timbreId);
        if (!galleryData) return;

        galleryData.currentIndex = index;
        this.updateGallery(timbreId, galleryData);
    }

    updateGallery(timbreId, galleryData) {
        // Mettre à jour la position de la galerie
        const translateX = -galleryData.currentIndex * 100;
        galleryData.element.style.transform = `translateX(${translateX}%)`;

        // Mettre à jour les dots
        if (galleryData.dotsContainer) {
            const dots = galleryData.dotsContainer.querySelectorAll('.carte-produit__gallery-dot');
            dots.forEach((dot, index) => {
                dot.classList.toggle('carte-produit__gallery-dot--active', index === galleryData.currentIndex);
            });
        }
    }
}

// Initialiser les galeries quand le DOM est chargé
document.addEventListener('DOMContentLoaded', () => {
    new GalerieCatalogue();
});

// Réinitialiser après les requêtes AJAX (si applicable)
document.addEventListener('ajaxComplete', () => {
    new GalerieCatalogue();
});
