/**
 * Ce fichier contient le JavaScript pour la gestion des fonctionnalités de l'interface
 * utilisateur telles que les messages flash et le popover du compte utilisateur.
 */

document.addEventListener('DOMContentLoaded', function() {
  // ===== Gestion des messages flash =====
  const flashMessages = document.querySelectorAll('.flash-message');
  
  // Fonction pour fermer un message flash avec animation
  const closeFlashMessage = (message) => {
    if (message) {
      // Animation de fermeture
      message.style.opacity = '0';
      message.style.transform = 'translateY(-20px)';
      setTimeout(() => {
        if (message.parentNode) {
          message.remove();
          // Si c'était le dernier message, enlève le conteneur
          if (document.querySelectorAll('.flash-message').length === 0) {
            const flashMessagesContainer = document.querySelector('.flash-messages');
            if (flashMessagesContainer) {
              flashMessagesContainer.remove();
            }
          }
        }
      }, 180);
    }
  };
  
  // Ajoute des listeners sur les boutons de fermeture
  const closeButtons = document.querySelectorAll('.flash-message__close');
  closeButtons.forEach(button => {
    button.addEventListener('click', function() {
      const flashMessage = this.parentNode;
      closeFlashMessage(flashMessage);
    });
  });
  
  // Fermeture automatique après quelques secondes
  if (flashMessages.length > 0) {
    flashMessages.forEach((message, index) => {
      // Ajout d'un léger délai progressif pour les messages multiples
      setTimeout(() => {
        closeFlashMessage(message);
      }, 4000 + (index * 500)); // 4 secondes + décalage pour messages multiples
    });
  }
  
  // Note: La gestion du popover du compte utilisateur se trouve ci-dessous
  
  // ===== Gestion du popover de compte utilisateur =====
  const compteBtn = document.getElementById('compte-btn');
  const comptePopover = document.getElementById('compte-popover');
  
  if (compteBtn && comptePopover) {
    // Fonction pour vérifier si le popover est ouvert
    function isPopoverOpen() {
      return comptePopover.hasAttribute('open');
    }
    
    // Fonction pour ouvrir le popover
    function openPopover() {
      comptePopover.show();
    }
    
    // Fonction pour fermer le popover
    function closePopover() {
      comptePopover.close();
    }
    
    // S'assurer que l'élément parent a position relative
    const parentElement = compteBtn.closest('.entete__profil');
    if (parentElement) {
      parentElement.style.position = 'relative';
    }
    
    // S'assurer que le popover est fermé initialement
    closePopover();
    
    // Gestionnaire d'événements pour le bouton compte
    compteBtn.addEventListener('click', function(event) {
      event.preventDefault();
      event.stopPropagation();
      
      if (isPopoverOpen()) {
        closePopover();
      } else {
        openPopover();
      }
    });
    
    // Fermer le dialogue lors d'un clic ailleurs
    document.addEventListener('click', function(event) {
      if (isPopoverOpen() && 
          event.target !== comptePopover && 
          !comptePopover.contains(event.target) && 
          event.target !== compteBtn && 
          !compteBtn.contains(event.target)) {
        closePopover();
      }
    });
    
    // Fermer avec la touche Escape
    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape' && isPopoverOpen()) {
        closePopover();
      }
    });
  }
  
  // ===== Validation du formulaire de connexion =====
  const loginForm = document.querySelector('form[action*="/login"]');
  
  if (loginForm) {
    loginForm.addEventListener('submit', function(event) {
      const username = document.getElementById('username');
      const password = document.getElementById('password');
      
      if (!username.value.trim()) {
        event.preventDefault();
        alert('Veuillez saisir votre nom d\'utilisateur.');
        username.focus();
        return false;
      }
      
      if (!password.value) {
        event.preventDefault();
        alert('Veuillez saisir votre mot de passe.');
        password.focus();
        return false;
      }
    });
  }

  // ===================================
  // VALIDATION FORMULAIRE D'INSCRIPTION
  // ===================================
  const registerForm = document.querySelector('form[action*="/register"]');
  if (registerForm) {
    const password = document.getElementById('mot_de_passe');
    const confirmPassword = document.getElementById('confirmer-mot-de-passe');
    
    registerForm.addEventListener('submit', function(event) {
      // Vérification que les mots de passe correspondent
      if (password && confirmPassword && password.value !== confirmPassword.value) {
        event.preventDefault();
        alert('Les mots de passe ne correspondent pas.');
        confirmPassword.focus();
        return false;
      }
      
      // Validation du format du mot de passe
      if (password) {
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/;
        if (!passwordRegex.test(password.value)) {
          event.preventDefault();
          alert('Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre.');
          password.focus();
          return false;
        }
      }
    });
  }

  // ===================================
  // VALIDATION FORMULAIRE D'AJOUT DE TIMBRE
  // ===================================
  const addTimbreForm = document.querySelector('form[action*="/timbres/store"]');
  if (addTimbreForm) {
    const nomInput = document.getElementById('nom');
    const tirageInput = document.getElementById('tirage');
    const dimensionsInput = document.getElementById('dimensions');
    const imageInput = document.getElementById('image');
    
    addTimbreForm.addEventListener('submit', function(event) {
      // Validation du nom (obligatoire)
      if (!nomInput.value.trim()) {
        event.preventDefault();
        alert('Le nom du timbre est obligatoire.');
        nomInput.focus();
        return false;
      }
      
      // Validation du tirage (nombre positif si fourni)
      if (tirageInput.value && (isNaN(tirageInput.value) || parseInt(tirageInput.value) < 1)) {
        event.preventDefault();
        alert('Le tirage doit être un nombre positif.');
        tirageInput.focus();
        return false;
      }
      
      // Validation des dimensions (format basique si fourni)
      if (dimensionsInput.value) {
        const dimensionsPattern = /^\d+\s*[x×]\s*\d+/;
        if (!dimensionsPattern.test(dimensionsInput.value)) {
          event.preventDefault();
          alert('Format attendu pour les dimensions : largeur x hauteur (ex: 25 x 30).');
          dimensionsInput.focus();
          return false;
        }
      }
      
      // Validation de l'image (taille et type si fourni)
      if (imageInput.files.length > 0) {
        const file = imageInput.files[0];
        const maxSize = 2 * 1024 * 1024; // 2MB
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        
        if (file.size > maxSize) {
          event.preventDefault();
          alert('L\'image ne doit pas dépasser 2MB.');
          return false;
        }
        
        if (!allowedTypes.includes(file.type)) {
          event.preventDefault();
          alert('Formats d\'image acceptés : JPEG, PNG, WebP.');
          return false;
        }
      }
    });
  }

  // ===================================
  // GALERIE D'IMAGES TIMBRE
  // ===================================
  // Fonction globale pour la galerie d'images dans la page de détails
  window.changeMainImage = function(newSrc, thumbnail) {
    const mainImage = document.getElementById('mainImage');
    const thumbnails = document.querySelectorAll('.timbre-gallery__thumb');
    
    if (mainImage) {
      // Changer l'image principale
      mainImage.src = newSrc;
      
      // Mettre à jour les classes actives des miniatures
      thumbnails.forEach(thumb => thumb.classList.remove('timbre-gallery__thumb--active'));
      if (thumbnail) {
        thumbnail.classList.add('timbre-gallery__thumb--active');
      }
    }
  };
});
