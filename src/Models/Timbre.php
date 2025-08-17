<?php

namespace App\Models;

use App\Database;
use PDO;
use Exception;

class Timbre
{
    protected $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    /**
     * Créer un nouveau timbre
     */
    public function create($data)
    {
        $sql = "INSERT INTO Timbres (nom, date_creation, condition_id, tirage, certifié, dimensions, description, pays_id, couleur_id, utilisateur_id) 
                VALUES (:nom, :date_creation, :condition_id, :tirage, :certifie, :dimensions, :description, :pays_id, :couleur_id, :utilisateur_id)";
        
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute([
            ':nom' => $data['nom'],
            ':date_creation' => $data['date_creation'],
            ':condition_id' => $data['condition_id'] ?? null,
            ':tirage' => $data['tirage'] ?? null,
            ':certifie' => isset($data['certifie']) ? 1 : 0,
            ':dimensions' => $data['dimensions'] ?? null,
            ':description' => $data['description'] ?? null,
            ':pays_id' => $data['pays_id'] ?? null,
            ':couleur_id' => $data['couleur_id'] ?? null,
            ':utilisateur_id' => $data['utilisateur_id']
        ]);
    }

    /**
     * Obtenir le dernier ID inséré
     */
    public function getLastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Récupérer toutes les conditions disponibles
     */
    public function getConditions()
    {
        $sql = "SELECT condition_id, nom_condition FROM Conditions_Timbres ORDER BY nom_condition";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer tous les pays disponibles
     */
    public function getPays()
    {
        $sql = "SELECT pays_id, nom_pays FROM Pays_Timbres ORDER BY nom_pays";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer toutes les couleurs disponibles
     */
    public function getCouleurs()
    {
        $sql = "SELECT couleur_id, nom_couleur FROM Couleurs_Timbres ORDER BY nom_couleur";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ajouter une image pour un timbre
     */
    public function addImage($timbreId, $urlImage, $isPrincipal = false)
    {
        $sql = "INSERT INTO Images_Timbres (timbre_id, url_image, image_principale) VALUES (:timbre_id, :url_image, :image_principale)";
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute([
            ':timbre_id' => $timbreId,
            ':url_image' => $urlImage,
            ':image_principale' => $isPrincipal ? 1 : 0
        ]);
    }

    /**
     * Récupérer un timbre par ID
     */
    public function getById($id)
    {
        $sql = "SELECT t.*, c.nom_condition, p.nom_pays, co.nom_couleur, u.nom_utilisateur 
                FROM Timbres t
                LEFT JOIN Conditions_Timbres c ON t.condition_id = c.condition_id
                LEFT JOIN Pays_Timbres p ON t.pays_id = p.pays_id
                LEFT JOIN Couleurs_Timbres co ON t.couleur_id = co.couleur_id
                LEFT JOIN Utilisateurs u ON t.utilisateur_id = u.utilisateur_id
                WHERE t.timbre_id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer tous les timbres avec leurs informations complètes
     */
    public function getAllTimbres($limit = null, $offset = 0)
    {
        $sql = "SELECT t.*, c.nom_condition, p.nom_pays, co.nom_couleur, u.nom_utilisateur,
                       (SELECT url_image FROM Images_Timbres it WHERE it.timbre_id = t.timbre_id AND it.image_principale = 1 LIMIT 1) as image_principale
                FROM Timbres t
                LEFT JOIN Conditions_Timbres c ON t.condition_id = c.condition_id
                LEFT JOIN Pays_Timbres p ON t.pays_id = p.pays_id
                LEFT JOIN Couleurs_Timbres co ON t.couleur_id = co.couleur_id
                LEFT JOIN Utilisateurs u ON t.utilisateur_id = u.utilisateur_id
                ORDER BY t.date_creation DESC";
        
        if ($limit) {
            $sql .= " LIMIT :limit OFFSET :offset";
        }
        
        $stmt = $this->pdo->prepare($sql);
        
        if ($limit) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Compter le nombre total de timbres
     */
    public function countTimbres()
    {
        $sql = "SELECT COUNT(*) FROM Timbres";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchColumn();
    }

    /**
     * Récupérer les images d'un timbre
     */
    public function getTimbreImages($timbreId)
    {
        $sql = "SELECT * FROM Images_Timbres WHERE timbre_id = :timbre_id ORDER BY image_principale DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':timbre_id' => $timbreId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer tous les timbres avec filtres
     */
    public function getAllTimbresWithFilters($filters, $limit = null, $offset = 0)
    {
        $sql = "SELECT t.*, c.nom_condition, p.nom_pays, co.nom_couleur, u.nom_utilisateur,
                       (SELECT url_image FROM Images_Timbres it WHERE it.timbre_id = t.timbre_id AND it.image_principale = 1 LIMIT 1) as image_principale
                FROM Timbres t
                LEFT JOIN Conditions_Timbres c ON t.condition_id = c.condition_id
                LEFT JOIN Pays_Timbres p ON t.pays_id = p.pays_id
                LEFT JOIN Couleurs_Timbres co ON t.couleur_id = co.couleur_id
                LEFT JOIN Utilisateurs u ON t.utilisateur_id = u.utilisateur_id";
        
        $where = [];
        $params = [];
        
        // Filtrage par recherche de nom
        if (!empty($filters['search'])) {
            $where[] = "t.nom LIKE :search";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        // Filtrage par pays
        if (!empty($filters['pays_id'])) {
            $where[] = "t.pays_id = :pays_id";
            $params[':pays_id'] = $filters['pays_id'];
        }
        
        // Filtrage par couleur
        if (!empty($filters['couleur_id'])) {
            $where[] = "t.couleur_id = :couleur_id";
            $params[':couleur_id'] = $filters['couleur_id'];
        }
        
        // Filtrage par condition
        if (!empty($filters['condition_id'])) {
            $where[] = "t.condition_id = :condition_id";
            $params[':condition_id'] = $filters['condition_id'];
        }
        
        // Filtrage par certification
        if ($filters['certifie']) {
            $where[] = "t.certifié = 1";
        }
        
        // Ajouter les conditions WHERE
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        // Tri
        switch ($filters['sort']) {
            case 'date_asc':
                $sql .= " ORDER BY t.date_creation ASC";
                break;
            case 'nom_asc':
                $sql .= " ORDER BY t.nom ASC";
                break;
            case 'nom_desc':
                $sql .= " ORDER BY t.nom DESC";
                break;
            default: // date_desc
                $sql .= " ORDER BY t.date_creation DESC";
                break;
        }
        
        if ($limit) {
            $sql .= " LIMIT :limit OFFSET :offset";
            $params[':limit'] = $limit;
            $params[':offset'] = $offset;
        }
        
        $stmt = $this->pdo->prepare($sql);
        
        // Bind des paramètres avec types appropriés
        foreach ($params as $key => $value) {
            if ($key === ':limit' || $key === ':offset') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Compter le nombre de timbres avec filtres
     */
    public function countTimbresWithFilters($filters)
    {
        $sql = "SELECT COUNT(*) FROM Timbres t
                LEFT JOIN Conditions_Timbres c ON t.condition_id = c.condition_id
                LEFT JOIN Pays_Timbres p ON t.pays_id = p.pays_id
                LEFT JOIN Couleurs_Timbres co ON t.couleur_id = co.couleur_id";
        
        $where = [];
        $params = [];
        
        // Mêmes filtres que getAllTimbresWithFilters
        if (!empty($filters['search'])) {
            $where[] = "t.nom LIKE :search";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['pays_id'])) {
            $where[] = "t.pays_id = :pays_id";
            $params[':pays_id'] = $filters['pays_id'];
        }
        
        if (!empty($filters['couleur_id'])) {
            $where[] = "t.couleur_id = :couleur_id";
            $params[':couleur_id'] = $filters['couleur_id'];
        }
        
        if (!empty($filters['condition_id'])) {
            $where[] = "t.condition_id = :condition_id";
            $params[':condition_id'] = $filters['condition_id'];
        }
        
        if ($filters['certifie']) {
            $where[] = "t.certifié = 1";
        }
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /**
     * Récupérer tous les timbres avec filtres et leurs images
     */
    public function getAllTimbresWithImagesAndFilters($filters, $limit = null, $offset = 0)
    {
        // D'abord récupérer les timbres avec filtres
        $timbres = $this->getAllTimbresWithFilters($filters, $limit, $offset);
        
        // Ensuite récupérer toutes les images pour chaque timbre
        foreach ($timbres as &$timbre) {
            $timbre['images'] = $this->getTimbreImages($timbre['timbre_id']);
        }
        
        return $timbres;
    }
    
    /**
     * Mettre à jour un timbre
     */
    public function update($id, $data)
    {
        $sql = "UPDATE Timbres 
                SET nom = :nom, 
                    condition_id = :condition_id, 
                    tirage = :tirage, 
                    certifié = :certifie, 
                    dimensions = :dimensions, 
                    description = :description, 
                    pays_id = :pays_id, 
                    couleur_id = :couleur_id
                WHERE timbre_id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute([
            ':id' => $id,
            ':nom' => $data['nom'],
            ':condition_id' => $data['condition_id'] ?? null,
            ':tirage' => $data['tirage'] ?? null,
            ':certifie' => isset($data['certifie']) ? 1 : 0,
            ':dimensions' => $data['dimensions'] ?? null,
            ':description' => $data['description'] ?? null,
            ':pays_id' => $data['pays_id'] ?? null,
            ':couleur_id' => $data['couleur_id'] ?? null
        ]);
    }
    
    /**
     * Supprimer un timbre et ses images
     */
    public function delete($id)
    {
        try {
            $this->pdo->beginTransaction();
            
            // Récupérer les images avant suppression pour les supprimer du disque
            $images = $this->getTimbreImages($id);
            
            // Supprimer les images de la base de données
            $sql = "DELETE FROM Images_Timbres WHERE timbre_id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            // Supprimer le timbre
            $sql = "DELETE FROM Timbres WHERE timbre_id = :id";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([':id' => $id]);
            
            $this->pdo->commit();
            
            // Supprimer les fichiers images du disque
            foreach ($images as $image) {
                $imagePath = __DIR__ . '/../../public' . $image['url_image'];
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            
            return $result;
        } catch (Exception $e) {
            $this->pdo->rollback();
            return false;
        }
    }
    
    /**
     * Vérifier si un timbre appartient à un utilisateur
     */
    public function belongsToUser($timbreId, $userId)
    {
        $sql = "SELECT COUNT(*) FROM Timbres WHERE timbre_id = :timbre_id AND utilisateur_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':timbre_id' => $timbreId,
            ':user_id' => $userId
        ]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Supprimer une image spécifique d'un timbre
     */
    public function deleteImage($imageId, $timbreId)
    {
        // Récupérer l'URL de l'image avant suppression
        $sql = "SELECT url_image FROM Images_Timbres WHERE image_id = :image_id AND timbre_id = :timbre_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':image_id' => $imageId, ':timbre_id' => $timbreId]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($image) {
            // Supprimer de la base de données
            $sql = "DELETE FROM Images_Timbres WHERE image_id = :image_id AND timbre_id = :timbre_id";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([':image_id' => $imageId, ':timbre_id' => $timbreId]);
            
            // Supprimer le fichier du disque
            if ($result) {
                $imagePath = __DIR__ . '/../../public' . $image['url_image'];
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            
            return $result;
        }
        
        return false;
    }
}
