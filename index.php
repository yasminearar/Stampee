<?php
// Supprimer l'affichage des avertissements de dépréciation qui perturbent les en-têtes HTTP
error_reporting(E_ALL & ~E_DEPRECATED);

require_once 'vendor/autoload.php';
require_once 'config.php';

use App\Routes\Route;

// Vérification initiale de la base de données
try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    // Vérifie si la table 'Utilisateurs' existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'Utilisateurs'");
    $tableExists = $stmt->fetchColumn();
    
    // Si la table n'existe pas, exécuter le script d'initialisation
    if (!$tableExists) {
        error_log("La table Utilisateurs n'existe pas. Exécution du script d'initialisation.");
        include 'src/dbinit.php';
    }
} catch (\PDOException $e) {
    error_log("Erreur lors de la vérification de la structure de la base de données: " . $e->getMessage());
}

// La session est déjà démarrée dans config.php, pas besoin de la démarrer à nouveau

// Définition des URLs de base pour la cohérence
define('BASE_URL', BASE);
define('ASSETS_URL', ASSET);

// Chargement des routes
require_once 'src/Routes/web.php';

// Résolution de la route
Route::dispatch();
