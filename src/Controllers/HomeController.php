<?php
namespace App\Controllers;

/**
 * Contrôleur pour la page d'accueil
 */
class HomeController extends Controller {
    
    /**
     * Affiche la page d'accueil
     */
    public function index() {
        // Ici, vous pourriez récupérer les timbres depuis votre modèle
        // $timbres = (new \App\Models\Timbre())->getAll();
        
        // Pour l'instant, on affiche simplement la page avec les données statiques
        $this->render('home/index.twig', [
            'pageTitle' => 'Catalogue des enchères'
            // Vous pourriez ajouter les timbres récupérés de la base de données
            // 'timbres' => $timbres
        ]);
    }
}
