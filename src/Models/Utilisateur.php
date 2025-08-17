<?php
namespace App\Models;

use App\Database;

/**
 * Modèle pour la table 'Utilisateurs'
 */
class Utilisateur {
    /**
     * Table associée au modèle
     */
    protected $table = 'Utilisateurs';
    protected $primaryKey = 'utilisateur_id';
    protected $pdo;
    protected $fillable = ['prenom', 'nom_utilisateur', 'email', 'mot_de_passe', 'date_inscription', 'privilege_id'];
    
    /**
     * Constructeur
     */
    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    /**
     * Authentifie un utilisateur
     * 
     * @param string $username Nom d'utilisateur ou email
     * @param string $password Mot de passe en clair
     * @return array|false Utilisateur authentifié ou false
     */
    public function authenticate(string $username, string $password) {
        $sql = "SELECT u.*, p.role as privilege 
                FROM {$this->table} u 
                LEFT JOIN Privileges p ON u.privilege_id = p.privilege_id 
                WHERE u.nom_utilisateur = :username_param OR u.email = :email_param";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'username_param' => $username,
            'email_param' => $username
        ]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['mot_de_passe'])) {
            unset($user['mot_de_passe']);
            return $user;
        }
        
        return false;
    }
    
    /**
     * Vérifie si un nom d'utilisateur existe
     * 
     * @param string $username Nom d'utilisateur
     * @return bool True si existe
     */
    public function usernameExists(string $username): bool {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE nom_utilisateur = :username";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['username' => $username]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Vérifie si un email existe
     * 
     * @param string $email Email
     * @return bool True si existe
     */
    public function emailExists(string $email): bool {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Crée un nouvel utilisateur
     * 
     * @param array $data Données utilisateur
     * @return bool|int ID de l'utilisateur créé ou false
     */
    public function createUser(array $data) {
        // Hasher le mot de passe
        if (isset($data['mot_de_passe'])) {
            $data['mot_de_passe'] = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);
        }
        
        return $this->insert($data);
    }
    
    /**
     * Insère un nouvel enregistrement dans la table
     * 
     * @param array $data Données à insérer
     * @return int|false ID de l'enregistrement inséré ou false en cas d'erreur
     */
    /**
     * Vérifie si la table existe dans la base de données
     * 
     * @return bool True si la table existe
     */
    private function tableExists() {
        try {
            $sql = "SHOW TABLES LIKE '{$this->table}'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchColumn();
            
            error_log("Vérification de l'existence de la table {$this->table}: " . ($result ? "Existe" : "N'existe pas"));
            return $result !== false;
        } catch (\Exception $e) {
            error_log("Erreur lors de la vérification de l'existence de la table: " . $e->getMessage());
            return false;
        }
    }

    public function insert($data) {
        // Vérifier si la table existe
        if (!$this->tableExists()) {
            error_log("ERREUR CRITIQUE: La table {$this->table} n'existe pas!");
            
            // Afficher la liste des tables disponibles
            try {
                $sql = "SHOW TABLES";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute();
                $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
                error_log("Tables disponibles dans la base de données: " . implode(", ", $tables));
            } catch (\Exception $e) {
                error_log("Impossible de lister les tables: " . $e->getMessage());
            }
            
            return false;
        }
        
        // Vérifier la structure de la table
        try {
            $sql = "DESCRIBE {$this->table}";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $columns = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            error_log("Colonnes de la table {$this->table}: " . implode(", ", $columns));
        } catch (\Exception $e) {
            error_log("Impossible de décrire la table: " . $e->getMessage());
        }

        // Filtrer les données pour ne garder que celles correspondant aux champs fillable
        $data_filtered = array_intersect_key($data, array_flip($this->fillable));
        
        $columns = implode(', ', array_keys($data_filtered));
        $placeholders = ':' . implode(', :', array_keys($data_filtered));
        
        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";

        try {
            // Vérifier la connexion à la base de données
            $dbName = $this->pdo->query("SELECT DATABASE()")->fetchColumn();
            error_log("Base de données actuelle: $dbName");
            
            // Afficher les détails SQL pour le débogage (temporaire)
            error_log("SQL à exécuter: $sql");
            error_log("Données à insérer: " . print_r($data_filtered, true));
            
            $stmt = $this->pdo->prepare($sql);
            
            // Utiliser bindValue pour chaque paramètre comme dans le projet Tp1-boutique-fleurs
            foreach($data_filtered as $key => $value) {
                $stmt->bindValue(":$key", $value);
                error_log("Binding: :$key => " . (is_string($value) ? $value : gettype($value)));
            }
            
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $success = $stmt->execute();
            
            if ($success) {
                $newId = $this->pdo->lastInsertId();
                error_log("Insertion réussie, ID: $newId");
                return $newId;
            }
            
            error_log("Échec de l'insertion sans exception");
            return false;
        } catch (\PDOException $e) {
            // Log l'erreur avec plus de détails
            error_log("Erreur PDO lors de l'insertion: " . $e->getMessage());
            error_log("Code d'erreur: " . $e->getCode());
            error_log("SQL: $sql");
            error_log("Données: " . print_r($data_filtered, true));
            
            // Informations sur les erreurs spécifiques
            if ($e->getCode() == 23000) {
                error_log("Erreur de clé dupliquée détectée");
                throw new \Exception("Cette adresse email ou ce nom d'utilisateur est déjà utilisé.");
            } else if ($e->getCode() == 1146) {
                error_log("La table n'existe pas");
                throw new \Exception("Table non trouvée. Veuillez contacter l'administrateur.");
            } else if ($e->getCode() == 1045) {
                error_log("Erreur d'authentification à la base de données");
                throw new \Exception("Problème d'accès à la base de données. Veuillez contacter l'administrateur.");
            }
            
            throw $e;
        } catch (\Exception $e) {
            // Log toute autre erreur
            error_log("Exception générale: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
            throw $e;
        }
    }
    
    /**
     * Récupère un utilisateur par son ID
     * 
     * @param int $id ID de l'utilisateur
     * @return array|false Utilisateur ou false
     */
    public function find($id) {
        $sql = "SELECT u.*, p.role as privilege 
                FROM {$this->table} u 
                LEFT JOIN Privileges p ON u.privilege_id = p.privilege_id 
                WHERE u.{$this->primaryKey} = :id";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['id' => $id]);
            $user = $stmt->fetch();
            
            if ($user) {
                unset($user['mot_de_passe']);
            }
            
            return $user;
        } catch (\PDOException $e) {
            error_log("Erreur lors de la recherche de l'utilisateur: " . $e->getMessage());
            return false;
        }
    }
    
    // Les méthodes liées au "se souvenir de moi" ont été supprimées car cette fonctionnalité n'est pas requise dans le devis
    
    /**
     * Test de connectivité à la base de données et de la structure de la table
     *
     * @return array Résultats du test
     */
    public function testDatabaseConnection() {
        $results = [
            'connection' => false,
            'table_exists' => false,
            'structure' => [],
            'errors' => []
        ];
        
        try {
            // Tester la connexion à la base de données
            $dbName = $this->pdo->query("SELECT DATABASE()")->fetchColumn();
            $results['connection'] = true;
            $results['database'] = $dbName;
            
            // Vérifier si la table existe
            $tableExists = $this->tableExists();
            $results['table_exists'] = $tableExists;
            
            if ($tableExists) {
                // Vérifier la structure de la table
                $sql = "DESCRIBE {$this->table}";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute();
                $columns = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $results['structure'] = $columns;
            }
        } catch (\Exception $e) {
            $results['errors'][] = $e->getMessage();
        }
        
        return $results;
    }
}
