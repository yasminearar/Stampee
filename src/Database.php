<?php
namespace App;

/**
 * Classe pour gérer les connexions à la base de données
 * Utilise le pattern Singleton pour avoir une seule instance de connexion
 */
class Database {
    private static $instance = null;
    private $pdo;
    
    /**
     * Constructeur privé pour éviter l'instanciation directe
     */
    private function __construct() {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $this->pdo = new \PDO($dsn, DB_USER, DB_PASS, $options);
    }
    
    /**
     * Récupère l'instance unique de la base de données
     * 
     * @return \PDO Instance de connexion à la base de données
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance->pdo;
    }
    
    /**
     * Empêche le clonage de l'objet
     */
    private function __clone() {}
    
    /**
     * Empêche la désérialisation de l'objet
     */
    public function __wakeup() {}
}
