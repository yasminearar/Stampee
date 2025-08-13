<?php
/**
 * Fichier de configuration des routes
 * Ce fichier définit toutes les routes de l'application
 */

use App\Routes\Route;
use App\Controllers\UtilisateurController;

// Route principale (temporaire, redirige vers inscription)
Route::get('/', function() {
    header('Location: ' . BASE . '/register');
    exit;
});

// Routes d'authentification
Route::get('/register', [UtilisateurController::class, 'register']);
Route::post('/register', [UtilisateurController::class, 'store']);
Route::get('/login', [UtilisateurController::class, 'login']);
Route::post('/login', [UtilisateurController::class, 'authenticate']);
