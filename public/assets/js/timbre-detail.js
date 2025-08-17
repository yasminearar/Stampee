// Gestion de la galerie d'images pour la page de détail du timbre

document.addEventListener('DOMContentLoaded', function() {
    // Initialiser la galerie d'images
    initTimbreGallery();
});

function initTimbreGallery() {
    const miniatures = document.querySelectorAll('.fiche-timbre__miniature');
    const imagePrincipale = document.querySelector('#image-principale');
    
    if (!miniatures.length || !imagePrincipale) {
        return;
    }
    
    miniatures.forEach(miniature => {
        miniature.addEventListener('click', function() {
            // Récupérer l'URL de l'image depuis data-image
            const nouvelleImage = this.getAttribute('data-image');
            
            if (nouvelleImage) {
                // Mettre à jour l'image principale
                imagePrincipale.src = nouvelleImage;
                
                // Retirer la classe active de toutes les miniatures
                miniatures.forEach(mini => {
                    mini.classList.remove('fiche-timbre__miniature--active');
                });
                
                // Ajouter la classe active à la miniature cliquée
                this.classList.add('fiche-timbre__miniature--active');
            }
        });
    });
}

// Fonctions pour la compatibilité avec l'ancien système (si nécessaire)
function changeMainImage(src, element) {
    const imagePrincipale = document.querySelector('#mainImage, #image-principale');
    if (imagePrincipale) {
        imagePrincipale.src = src;
    }
    
    // Gestion des classes actives
    const miniatures = document.querySelectorAll('.timbre-gallery__thumb, .fiche-timbre__miniature');
    miniatures.forEach(mini => {
        mini.classList.remove('timbre-gallery__thumb--active', 'fiche-timbre__miniature--active');
    });
    
    if (element) {
        element.classList.add('timbre-gallery__thumb--active', 'fiche-timbre__miniature--active');
    }
}
