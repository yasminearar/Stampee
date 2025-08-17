<?php

namespace App\Controllers;

use App\Models\Timbre;

class TimbreController extends Controller
{
    private $timbreModel;

    public function __construct()
    {
        parent::__construct();
        $this->timbreModel = new Timbre();
    }

    /**
     * Afficher le formulaire d'ajout de timbre
     */
    public function create()
    {
        // Vérifier l'authentification
        $this->requireAuth();
        
        // Récupérer les données nécessaires pour les listes déroulantes
        $conditions = $this->timbreModel->getConditions();
        $pays = $this->timbreModel->getPays();
        $couleurs = $this->timbreModel->getCouleurs();

        // Passer les données à la vue
        $this->render('timbres/create.twig', [
            'conditions' => $conditions,
            'pays' => $pays,
            'couleurs' => $couleurs,
            'title' => 'Ajouter un timbre'
        ]);
    }

    /**
     * Traiter la soumission du formulaire d'ajout
     */
    public function store()
    {
        // Vérifier l'authentification
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/Stampee/timbres/create');
            return;
        }

        // Validation des données
        $errors = $this->validateTimbreData($_POST);
        
        if (!empty($errors)) {
            // Récupérer à nouveau les données pour les listes
            $conditions = $this->timbreModel->getConditions();
            $pays = $this->timbreModel->getPays();
            $couleurs = $this->timbreModel->getCouleurs();

            $this->render('timbres/create.twig', [
                'conditions' => $conditions,
                'pays' => $pays,
                'couleurs' => $couleurs,
                'errors' => $errors,
                'old' => $_POST,
                'title' => 'Ajouter un timbre'
            ]);
            return;
        }

        // Préparer les données pour l'insertion
        $timbreData = [
            'nom' => trim($_POST['nom']),
            'date_creation' => date('Y-m-d H:i:s'),
            'condition_id' => !empty($_POST['condition_id']) ? $_POST['condition_id'] : null,
            'tirage' => !empty($_POST['tirage']) ? (int)$_POST['tirage'] : null,
            'certifie' => isset($_POST['certifie']),
            'dimensions' => !empty($_POST['dimensions']) ? trim($_POST['dimensions']) : null,
            'description' => !empty($_POST['description']) ? trim($_POST['description']) : null,
            'pays_id' => !empty($_POST['pays_id']) ? $_POST['pays_id'] : null,
            'couleur_id' => !empty($_POST['couleur_id']) ? $_POST['couleur_id'] : null,
            'utilisateur_id' => $_SESSION['user_id'] ?? 1 // TODO: Récupérer l'ID de l'utilisateur connecté
        ];

        // Créer le timbre
        if ($this->timbreModel->create($timbreData)) {
            $timbreId = $this->timbreModel->getLastInsertId();
            
            // Gérer l'upload de plusieurs images si présentes
            $uploadedImages = 0;
            if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
                $uploadedImages = $this->handleMultipleImageUpload($_FILES['images'], $timbreId);
            }

            $message = 'Timbre ajouté avec succès !';
            if ($uploadedImages > 0) {
                $message .= " {$uploadedImages} image(s) téléchargée(s).";
            }
            
            $this->addFlashMessage($message, 'success');
            $this->redirect('/Stampee/timbres'); // Rediriger vers le catalogue
        } else {
            $this->addFlashMessage('Erreur lors de l\'ajout du timbre.', 'error');
            $this->redirect('/Stampee/timbres/create');
        }
    }

    /**
     * Valider les données du timbre
     */
    private function validateTimbreData($data)
    {
        $errors = [];

        // Nom obligatoire
        if (empty(trim($data['nom']))) {
            $errors['nom'] = 'Le nom du timbre est obligatoire.';
        }

        // Tirage doit être un nombre positif si fourni
        if (!empty($data['tirage']) && (!is_numeric($data['tirage']) || $data['tirage'] < 0)) {
            $errors['tirage'] = 'Le tirage doit être un nombre positif.';
        }

        // Dimensions format basique si fourni
        if (!empty($data['dimensions']) && !preg_match('/^\d+\s*[x×]\s*\d+/', $data['dimensions'])) {
            $errors['dimensions'] = 'Format attendu pour les dimensions : largeur x hauteur (ex: 25 x 30).';
        }

        return $errors;
    }

    /**
     * Gérer l'upload d'image
     */
    private function handleImageUpload($file, $timbreId)
    {
        $uploadDir = __DIR__ . '/../../public/assets/img/timbres/';
        
        // Créer le répertoire s'il n'existe pas
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            return false;
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'timbre_' . $timbreId . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Enregistrer l'image en base
            $urlImage = '/assets/img/timbres/' . $filename;
            return $this->timbreModel->addImage($timbreId, $urlImage, true); // Image principale
        }

        return false;
    }

    /**
     * Gérer l'upload de plusieurs images avec compression intelligente
     */
    private function handleMultipleImageUpload($files, $timbreId)
    {
        $uploadDir = __DIR__ . '/../../public/assets/img/timbres/';
        
        // Créer le répertoire s'il n'existe pas
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $uploadedCount = 0;
        $isFirstImage = true;
        $totalOriginalSize = 0;
        $totalCompressedSize = 0;

        // Parcourir chaque fichier
        for ($i = 0; $i < count($files['name']); $i++) {
            // Vérifier s'il y a une erreur d'upload
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            // Vérifier le type de fichier
            if (!in_array($files['type'][$i], $allowedTypes)) {
                continue;
            }

            // Vérifier que le fichier n'est pas vide
            if ($files['size'][$i] <= 0) {
                continue;
            }

            $originalSize = $files['size'][$i];
            $totalOriginalSize += $originalSize;

            // Optimiser l'image
            $optimizedPath = $this->optimizeAndSaveImage(
                $files['tmp_name'][$i], 
                $files['type'][$i], 
                $timbreId, 
                $i + 1
            );

            if ($optimizedPath) {
                $compressedSize = filesize(__DIR__ . '/../../public' . $optimizedPath);
                $totalCompressedSize += $compressedSize;

                // Enregistrer l'image en base
                $isPrincipal = $isFirstImage; // La première image uploadée devient l'image principale
                
                if ($this->timbreModel->addImage($timbreId, $optimizedPath, $isPrincipal)) {
                    $uploadedCount++;
                    $isFirstImage = false; // Seule la première image est principale
                }
            }
        }

        // Log des statistiques de compression
        if ($totalOriginalSize > 0) {
            $compressionRatio = (($totalOriginalSize - $totalCompressedSize) / $totalOriginalSize) * 100;
            error_log(sprintf(
                "Compression stats - Original: %s, Compressed: %s, Ratio: %.1f%%",
                $this->formatFileSize($totalOriginalSize),
                $this->formatFileSize($totalCompressedSize),
                $compressionRatio
            ));
        }

        return $uploadedCount;
    }

    /**
     * Optimiser et sauvegarder une image avec compression intelligente
     */
    private function optimizeAndSaveImage($tmpPath, $mimeType, $timbreId, $imageNumber)
    {
        // Lire l'image source
        $sourceImage = $this->createImageFromFile($tmpPath, $mimeType);
        if (!$sourceImage) {
            return false;
        }

        // Obtenir les dimensions originales
        list($originalWidth, $originalHeight) = getimagesize($tmpPath);
        
        // Calculer les nouvelles dimensions (max 1200px pour la plus grande dimension)
        $maxDimension = 1200;
        $ratio = min($maxDimension / $originalWidth, $maxDimension / $originalHeight, 1);
        $newWidth = (int)($originalWidth * $ratio);
        $newHeight = (int)($originalHeight * $ratio);

        // Créer l'image redimensionnée
        $optimizedImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Préserver la transparence pour PNG
        if ($mimeType === 'image/png') {
            imagealphablending($optimizedImage, false);
            imagesavealpha($optimizedImage, true);
            $transparent = imagecolorallocatealpha($optimizedImage, 255, 255, 255, 127);
            imagefill($optimizedImage, 0, 0, $transparent);
        } else {
            // Fond blanc pour JPEG/WebP
            $white = imagecolorallocate($optimizedImage, 255, 255, 255);
            imagefill($optimizedImage, 0, 0, $white);
        }

        // Redimensionner avec antialiasing
        imagecopyresampled(
            $optimizedImage, $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $originalWidth, $originalHeight
        );

        // Déterminer le format de sortie et le nom de fichier
        $outputFormat = $this->determineOptimalFormat($mimeType, $optimizedImage);
        $extension = $this->getExtensionFromMimeType($outputFormat);
        $filename = 'timbre_' . $timbreId . '_' . time() . '_' . $imageNumber . '.' . $extension;
        $filepath = __DIR__ . '/../../public/assets/img/timbres/' . $filename;

        // Sauvegarder avec compression optimale
        $saved = $this->saveOptimizedImage($optimizedImage, $filepath, $outputFormat);

        // Nettoyer la mémoire
        imagedestroy($sourceImage);
        imagedestroy($optimizedImage);

        if ($saved) {
            return '/assets/img/timbres/' . $filename;
        }

        return false;
    }

    /**
     * Créer une ressource image à partir d'un fichier
     */
    private function createImageFromFile($filepath, $mimeType)
    {
        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                return imagecreatefromjpeg($filepath);
            case 'image/png':
                return imagecreatefrompng($filepath);
            case 'image/webp':
                return imagecreatefromwebp($filepath);
            default:
                return false;
        }
    }

    /**
     * Déterminer le format optimal pour la compression
     */
    private function determineOptimalFormat($originalMimeType, $image)
    {
        // Pour PNG, vérifier s'il y a de la transparence
        if ($originalMimeType === 'image/png') {
            if ($this->hasTransparency($image)) {
                return 'image/png'; // Garder PNG si transparence
            } else {
                return 'image/jpeg'; // Convertir en JPEG si pas de transparence
            }
        }

        // WebP reste WebP si supporté, sinon JPEG
        if ($originalMimeType === 'image/webp') {
            return function_exists('imagewebp') ? 'image/webp' : 'image/jpeg';
        }

        // JPEG reste JPEG
        return 'image/jpeg';
    }

    /**
     * Vérifier si une image a de la transparence
     */
    private function hasTransparency($image)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Échantillonner quelques pixels pour la performance
        $step = max(1, min($width, $height) / 50);
        
        for ($x = 0; $x < $width; $x += $step) {
            for ($y = 0; $y < $height; $y += $step) {
                $rgba = imagecolorat($image, (int)$x, (int)$y);
                $alpha = ($rgba & 0x7F000000) >> 24;
                if ($alpha > 0) {
                    return true; // Transparence détectée
                }
            }
        }
        
        return false;
    }

    /**
     * Sauvegarder l'image avec compression optimale
     */
    private function saveOptimizedImage($image, $filepath, $mimeType)
    {
        switch ($mimeType) {
            case 'image/jpeg':
                return imagejpeg($image, $filepath, 88); // 88% qualité
            case 'image/png':
                // PNG: compression 6 (bon compromis taille/qualité)
                return imagepng($image, $filepath, 6);
            case 'image/webp':
                return function_exists('imagewebp') ? 
                    imagewebp($image, $filepath, 85) : // 85% qualité pour WebP
                    imagejpeg($image, $filepath, 88);   // Fallback JPEG
            default:
                return false;
        }
    }

    /**
     * Obtenir l'extension à partir du type MIME
     */
    private function getExtensionFromMimeType($mimeType)
    {
        switch ($mimeType) {
            case 'image/jpeg':
                return 'jpg';
            case 'image/png':
                return 'png';
            case 'image/webp':
                return 'webp';
            default:
                return 'jpg';
        }
    }

    /**
     * Formater la taille de fichier
     */
    private function formatFileSize($bytes)
    {
        if ($bytes === 0) return '0 B';
        $k = 1024;
        $sizes = ['B', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));
        return round($bytes / pow($k, $i), 1) . ' ' . $sizes[$i];
    }

    /**
     * Afficher la liste des timbres (catalogue)
     */
    public function index()
    {
        // Vérifier l'authentification
        $this->requireAuth();
        
        // Pagination
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 12; // Nombre de timbres par page
        $offset = ($page - 1) * $perPage;

        // Filtres
        $filters = [
            'search' => $_GET['search'] ?? '',
            'pays_id' => $_GET['pays_id'] ?? '',
            'couleur_id' => $_GET['couleur_id'] ?? '',
            'condition_id' => $_GET['condition_id'] ?? '',
            'certifie' => isset($_GET['certifie']),
            'sort' => $_GET['sort'] ?? 'date_desc'
        ];

        // Récupérer les timbres avec filtres et leurs images
        $timbres = $this->timbreModel->getAllTimbresWithImagesAndFilters($filters, $perPage, $offset);
        $totalTimbres = $this->timbreModel->countTimbresWithFilters($filters);
        $totalPages = ceil($totalTimbres / $perPage);

        // Récupérer les données pour les filtres
        $conditions = $this->timbreModel->getConditions();
        $pays = $this->timbreModel->getPays();
        $couleurs = $this->timbreModel->getCouleurs();

        // Passer les données à la vue
        $this->render('timbres/index.twig', [
            'timbres' => $timbres,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalTimbres' => $totalTimbres,
            'conditions' => $conditions,
            'pays' => $pays,
            'couleurs' => $couleurs,
            'filters' => $filters,
            'title' => 'Catalogue des Timbres'
        ]);
    }

    /**
     * Afficher les détails d'un timbre
     */
    public function show($id)
    {
        // Vérifier l'authentification
        $this->requireAuth();
        
        $timbre = $this->timbreModel->getById($id);
        
        if (!$timbre) {
            $this->addFlashMessage('Timbre non trouvé.', 'error');
            $this->redirect('/Stampee/timbres');
            return;
        }

        // Récupérer les images du timbre
        $images = $this->timbreModel->getTimbreImages($id);

        $this->render('timbres/show.twig', [
            'timbre' => $timbre,
            'images' => $images,
            'title' => $timbre['nom']
        ]);
    }

    /**
     * Afficher le formulaire d'édition d'un timbre
     */
    public function edit($id)
    {
        // Vérifier l'authentification
        $this->requireAuth();
        
        $timbre = $this->timbreModel->getById($id);
        
        if (!$timbre) {
            $this->addFlashMessage('Timbre non trouvé.', 'error');
            $this->redirect('/Stampee/timbres');
            return;
        }

        // Vérifier que le timbre appartient à l'utilisateur connecté
        if (!$this->timbreModel->belongsToUser($id, $_SESSION['user_id'])) {
            $this->addFlashMessage('Vous n\'avez pas l\'autorisation de modifier ce timbre.', 'error');
            $this->redirect('/Stampee/timbres');
            return;
        }

        // Récupérer les images du timbre
        $images = $this->timbreModel->getTimbreImages($id);

        // Récupérer les données pour les listes déroulantes
        $conditions = $this->timbreModel->getConditions();
        $pays = $this->timbreModel->getPays();
        $couleurs = $this->timbreModel->getCouleurs();

        $this->render('timbres/edit.twig', [
            'timbre' => $timbre,
            'images' => $images,
            'conditions' => $conditions,
            'pays' => $pays,
            'couleurs' => $couleurs,
            'title' => 'Modifier ' . $timbre['nom']
        ]);
    }

    /**
     * Traiter la mise à jour d'un timbre
     */
    public function update($id)
    {
        // Vérifier l'authentification
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/Stampee/timbres/' . $id . '/edit');
            return;
        }

        $timbre = $this->timbreModel->getById($id);
        
        if (!$timbre) {
            $this->addFlashMessage('Timbre non trouvé.', 'error');
            $this->redirect('/Stampee/timbres');
            return;
        }

        // Vérifier que le timbre appartient à l'utilisateur connecté
        if (!$this->timbreModel->belongsToUser($id, $_SESSION['user_id'])) {
            $this->addFlashMessage('Vous n\'avez pas l\'autorisation de modifier ce timbre.', 'error');
            $this->redirect('/Stampee/timbres');
            return;
        }

        // Validation des données
        $errors = $this->validateTimbreData($_POST);
        
        if (!empty($errors)) {
            // Récupérer les données pour les listes et images
            $images = $this->timbreModel->getTimbreImages($id);
            $conditions = $this->timbreModel->getConditions();
            $pays = $this->timbreModel->getPays();
            $couleurs = $this->timbreModel->getCouleurs();

            $this->render('timbres/edit.twig', [
                'timbre' => $timbre,
                'images' => $images,
                'conditions' => $conditions,
                'pays' => $pays,
                'couleurs' => $couleurs,
                'errors' => $errors,
                'old' => $_POST,
                'title' => 'Modifier ' . $timbre['nom']
            ]);
            return;
        }

        // Préparer les données pour la mise à jour
        $timbreData = [
            'nom' => trim($_POST['nom']),
            'condition_id' => !empty($_POST['condition_id']) ? $_POST['condition_id'] : null,
            'tirage' => !empty($_POST['tirage']) ? (int)$_POST['tirage'] : null,
            'certifie' => isset($_POST['certifie']),
            'dimensions' => !empty($_POST['dimensions']) ? trim($_POST['dimensions']) : null,
            'description' => !empty($_POST['description']) ? trim($_POST['description']) : null,
            'pays_id' => !empty($_POST['pays_id']) ? $_POST['pays_id'] : null,
            'couleur_id' => !empty($_POST['couleur_id']) ? $_POST['couleur_id'] : null
        ];

        // Mettre à jour le timbre
        if ($this->timbreModel->update($id, $timbreData)) {
            // Gérer l'upload de nouvelles images si présentes
            $uploadedImages = 0;
            if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
                $uploadedImages = $this->handleMultipleImageUpload($_FILES['images'], $id);
            }

            $message = 'Timbre modifié avec succès !';
            if ($uploadedImages > 0) {
                $message .= " {$uploadedImages} nouvelle(s) image(s) ajoutée(s).";
            }
            
            $this->addFlashMessage($message, 'success');
            $this->redirect('/Stampee/timbres/' . $id);
        } else {
            $this->addFlashMessage('Erreur lors de la modification du timbre.', 'error');
            $this->redirect('/Stampee/timbres/' . $id . '/edit');
        }
    }

    /**
     * Supprimer un timbre
     */
    public function delete($id)
    {
        // Vérifier l'authentification
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/Stampee/timbres');
            return;
        }

        $timbre = $this->timbreModel->getById($id);
        
        if (!$timbre) {
            $this->addFlashMessage('Timbre non trouvé.', 'error');
            $this->redirect('/Stampee/timbres');
            return;
        }

        // Vérifier que le timbre appartient à l'utilisateur connecté
        if (!$this->timbreModel->belongsToUser($id, $_SESSION['user_id'])) {
            $this->addFlashMessage('Vous n\'avez pas l\'autorisation de supprimer ce timbre.', 'error');
            $this->redirect('/Stampee/timbres');
            return;
        }

        // Supprimer le timbre
        if ($this->timbreModel->delete($id)) {
            $this->addFlashMessage('Timbre "' . $timbre['nom'] . '" supprimé avec succès.', 'success');
        } else {
            $this->addFlashMessage('Erreur lors de la suppression du timbre.', 'error');
        }
        
        $this->redirect('/Stampee/timbres');
    }

    /**
     * Supprimer une image spécifique d'un timbre (AJAX)
     */
    public function deleteImage($timbreId, $imageId)
    {
        // Vérifier l'authentification
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }

        // Vérifier que le timbre appartient à l'utilisateur connecté
        if (!$this->timbreModel->belongsToUser($timbreId, $_SESSION['user_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Non autorisé']);
            return;
        }

        // Supprimer l'image
        if ($this->timbreModel->deleteImage($imageId, $timbreId)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Image supprimée avec succès']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
        }
    }
}
