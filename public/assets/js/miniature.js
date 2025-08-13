// Gestion des miniatures avec transition fluide
document.addEventListener("DOMContentLoaded", () => {
  const miniatures = document.querySelectorAll('.fiche-timbre__miniature');
  const imagePrincipale = document.getElementById('image-principale');
  
  // Fonction pour changer l'image principale
  function changerImage(miniature) {
    // Si déjà sélectionnée, ne rien faire
    if (miniature.classList.contains('fiche-timbre__miniature--active')) {
      return;
    }
    
    // Mettre à jour les classes actives
    miniatures.forEach(m => m.classList.remove('fiche-timbre__miniature--active'));
    miniature.classList.add('fiche-timbre__miniature--active');
    
    // Appliquer l'effet de transition
    imagePrincipale.style.opacity = '0.5';
    
    // Changer l'image
    const imageSrc = miniature.getAttribute('data-image');
    setTimeout(() => {
      imagePrincipale.src = imageSrc;
      imagePrincipale.onload = () => imagePrincipale.style.opacity = '1';
      
      // Fallback au cas où onload ne se déclenche pas
      setTimeout(() => imagePrincipale.style.opacity = '1', 300);
    }, 100);
  }
  
  // Ajouter les écouteurs d'événements
  miniatures.forEach(miniature => {
    miniature.addEventListener('click', () => changerImage(miniature));
  });
  
  // Précharger les images
  miniatures.forEach(miniature => {
    const img = new Image();
    img.src = miniature.getAttribute('data-image');
  });
});
