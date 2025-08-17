<?php
/**
 * Fichier de configuration des routes
 * Ce fichier définit toutes les routes de l'application
 */

use App\Routes\Route;
use App\Controllers\UtilisateurController;
use App\Controllers\HomeController;

// Route principale - Page d'accueil
Route::get('/', [HomeController::class, 'index']);

// Routes d'authentification
Route::get('/register', [UtilisateurController::class, 'register']);
Route::post('/register', [UtilisateurController::class, 'store']);
Route::get('/login', [UtilisateurController::class, 'login']);
Route::post('/login', [UtilisateurController::class, 'authenticate']);
Route::get('/logout', [UtilisateurController::class, 'logout']);
