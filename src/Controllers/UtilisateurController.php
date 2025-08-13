<?php
namespace App\Controllers;

use App\Models\Utilisateur;

/**
 * Contrôleur pour la gestion des utilisateurs
 */
class UtilisateurController extends Controller {
    
    /**
     * Affiche le formulaire de connexion
     */
    public function login() {
        $this->render('utilisateurs/login.twig', [
            'pageTitle' => 'Connexion',
            'BASE' => BASE,
            'ASSET' => ASSET
        ]);
    }
    
    /**
     * Traite la connexion
     */
    public function authenticate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE . '/login');
            return;
        }
        
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $password = filter_input(INPUT_POST, 'password');
        
        if (empty($username) || empty($password)) {
            $this->addFlashMessage('Veuillez remplir tous les champs', 'error');
            $this->redirect(BASE . '/login');
            return;
        }
        
        $user = new \App\Models\Utilisateur();
        $authenticatedUser = $user->authenticate($username, $password);
        
        if ($authenticatedUser) {
            session_regenerate_id(true);
            
            $_SESSION['user'] = $authenticatedUser;
            $_SESSION['user_id'] = $authenticatedUser['utilisateur_id'];
            $_SESSION['user_name'] = $authenticatedUser['prenom'];
            $_SESSION['username'] = $authenticatedUser['nom_utilisateur'];
            $_SESSION['privilege_id'] = $authenticatedUser['privilege_id'];
            $_SESSION['privilege'] = $authenticatedUser['privilege'];
            $_SESSION['fingerPrint'] = md5($_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);
            $_SESSION['LAST_ACTIVITY'] = time();
            
            $this->addFlashMessage('Connexion réussie! Bienvenue ' . $authenticatedUser['prenom'], 'success');
            
            $this->redirect(BASE . '/');
        } else {
            $this->addFlashMessage('Nom d\'utilisateur ou mot de passe incorrect', 'error');
            $this->redirect(BASE . '/login');
        }
    }
    
    /**
     * Affiche le formulaire d'inscription
     */
    public function register() {
        $this->render('utilisateurs/register.twig', [
            'pageTitle' => 'Inscription',
            'BASE' => BASE,
            'ASSET' => ASSET
        ]);
    }
    
    /**
     * Traite l'inscription
     */
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE . '/register');
            return;
        }
        
        $prenom = filter_input(INPUT_POST, 'prenom', FILTER_SANITIZE_STRING);
        $username = filter_input(INPUT_POST, 'nom_utilisateur', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = filter_input(INPUT_POST, 'mot_de_passe');
        $passwordConfirm = filter_input(INPUT_POST, 'confirmer-mot-de-passe');

        $errors = [];
        
        if (empty($prenom)) {
            $errors[] = 'Le prénom est requis';
        }
        
        if (empty($username)) {
            $errors[] = 'Le nom d\'utilisateur est requis';
        }
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Un email valide est requis';
        }
        
        if (empty($password)) {
            $errors[] = 'Le mot de passe est requis';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Le mot de passe doit contenir au moins 8 caractères';
        }
        
        if ($password !== $passwordConfirm) {
            $errors[] = 'Les mots de passe ne correspondent pas';
        }

        $user = new Utilisateur();
        
        if ($user->usernameExists($username)) {
            $errors[] = 'Ce nom d\'utilisateur est déjà utilisé';
        }
        
        if ($user->emailExists($email)) {
            $errors[] = 'Cet email est déjà utilisé';
        }
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addFlashMessage($error, 'error');
            }
            $this->redirect(BASE . '/register');
            return;
        }

        // Journaliser les données avant l'insertion
        error_log("Tentative d'inscription pour: $username ($email)");
        
        $userData = [
            'prenom' => $prenom,
            'nom_utilisateur' => $username,
            'email' => $email,
            'mot_de_passe' => $password,
            'date_inscription' => date('Y-m-d H:i:s'),
            'privilege_id' => 2  // Utilisateur standard
        ];
        
        try {
            $userId = $user->createUser($userData);
            
            if ($userId) {
                error_log("Inscription réussie pour $username avec ID: $userId");
                $this->addFlashMessage('Inscription réussie! Vous pouvez maintenant vous connecter.', 'success');
                $this->redirect(BASE . '/login');
            } else {
                error_log("Échec de l'inscription pour $username - Pas d'ID retourné");
                $this->addFlashMessage('Erreur lors de l\'inscription. Veuillez réessayer plus tard ou contacter l\'administrateur.', 'error');
                $this->redirect(BASE . '/register');
            }
        } catch (\Exception $e) {
            error_log("Exception lors de l'inscription pour $username: " . $e->getMessage());
            $this->addFlashMessage('Une erreur s\'est produite lors de l\'inscription: ' . $e->getMessage(), 'error');
            $this->redirect(BASE . '/register');
        }
    }
}
