/**
 * Gestion des interactions des filtres
 * Améliore la spécificité CSS en utilisant des classes plutôt que des sélecteurs complexes
 */

document.addEventListener('DOMContentLoaded', function() {
  // Sélectionner tous les éléments details dans les filtres
  const filterSections = document.querySelectorAll('.filtres__section');
  
  filterSections.forEach(section => {
    const summary = section.querySelector('.filtres__section-titre');
    
    // Fonction pour mettre à jour la classe CSS
    function updateSectionState() {
      if (section.open) {
        summary.classList.add('filtres__section-titre--active');
      } else {
        summary.classList.remove('filtres__section-titre--active');
      }
    }
    
    // Écouter les changements d'état
    section.addEventListener('toggle', updateSectionState);
    
    // Initialiser l'état au chargement
    updateSectionState();
  });
});
