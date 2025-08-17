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
});
