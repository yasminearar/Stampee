<?php
namespace App\Controllers;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Contrôleur de base
 */
class Controller {
    protected $twig;
    
    public function __construct() {
        // Configuration de Twig
        $loader = new FilesystemLoader('src/Views');
        $this->twig = new Environment($loader, [
            'cache' => false, // Désactiver le cache pour le développement
        ]);
        
        // Ajout de variables globales pour Twig
        $this->twig->addGlobal('BASE', defined('BASE') ? BASE : '');
        $this->twig->addGlobal('ASSET', defined('ASSET') ? ASSET : '');
        
        // Ajouter les données de session de manière plus explicite
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->twig->addGlobal('session', $_SESSION);
        $this->twig->addGlobal('user_id', $_SESSION['user_id'] ?? null);
    }
    
    /**
     * Vérifie si l'utilisateur est connecté
     * 
     * @return bool True si l'utilisateur est connecté
     */
    protected function isAuthenticated(): bool {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Vérifie si l'utilisateur est connecté et redirige vers la page de connexion si nécessaire
     * 
     * @param string $redirectUrl URL de redirection après connexion
     */
    protected function requireAuth(string $redirectUrl = null): void {
        if (!$this->isAuthenticated()) {
            $this->addFlashMessage('Vous devez être connecté pour accéder à cette page.', 'warning');
            
            // Construire l'URL de redirection avec le paramètre de retour
            $loginUrl = BASE . '/login';
            if ($redirectUrl) {
                $loginUrl .= '?redirect=' . urlencode($redirectUrl);
            } else {
                // Utiliser l'URL actuelle si aucune redirection spécifiée
                $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
                if ($currentUrl && $currentUrl !== '/login') {
                    $loginUrl .= '?redirect=' . urlencode($currentUrl);
                }
            }
            
            $this->redirect($loginUrl);
        }
    }
    
    /**
     * Récupère l'ID de l'utilisateur connecté
     * 
     * @return int|null ID de l'utilisateur ou null si non connecté
     */
    protected function getCurrentUserId(): ?int {
        return $this->isAuthenticated() ? (int)$_SESSION['user_id'] : null;
    }
    
    /**
     * Vérifie si la requête est de type POST
     * 
     * @return bool True si la requête est de type POST
     */
    protected function isPost(): bool {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
    
    /**
     * Récupère un paramètre POST
     * 
     * @param string $key Clé du paramètre
     * @param int $filter Filtre à appliquer (constantes FILTER_*)
     * @return mixed Valeur du paramètre
     */
    protected function postParam(string $key, int $filter = FILTER_DEFAULT) {
        return filter_input(INPUT_POST, $key, $filter) ?? '';
    }
    
    /**
     * Ajoute un message flash
     * 
     * @param string $message Message à afficher
     * @param string $type Type de message (success, error, warning, info)
     */
    protected function addFlashMessage(string $message, string $type = 'info'): void {
        if (!isset($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = [];
        }
        
        $_SESSION['flash_messages'][] = [
            'message' => $message,
            'type' => $type
        ];
    }
    
    /**
     * Récupère les messages flash
     * 
     * @return array Messages flash
     */
    protected function getFlashMessages(): array {
        $messages = $_SESSION['flash_messages'] ?? [];
        unset($_SESSION['flash_messages']);
        return $messages;
    }
    
    /**
     * Redirige vers une URL
     * 
     * @param string $url URL de destination
     */
    protected function redirect(string $url): void {
        header("Location: $url");
        exit();
    }
    
    /**
     * Affiche une vue avec Twig
     * 
     * @param string $template Nom du template Twig
     * @param array $data Données à passer au template
     */
    protected function render(string $template, array $data = []): void {
        // Démarrer la session si elle n'est pas déjà active
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Ajouter les données de session aux variables de template
        $data['session'] = $_SESSION;
        
        // Ajout des messages flash aux données
        $data['flash_messages'] = $this->getFlashMessages();
        
        // Rendu du template
        echo $this->twig->render($template, $data);
    }
}
