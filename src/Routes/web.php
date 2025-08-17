<?php
/**
 * Fichier de configuration des routes
 * Ce fichier définit toutes les routes de l'application
 */

use App\Routes\Route;
use App\Controllers\UtilisateurController;
use App\Controllers\HomeController;
use App\Controllers\TimbreController;

// Route principale - Page d'accueil
Route::get('/', [HomeController::class, 'index']);

// Routes d'authentification
Route::get('/register', [UtilisateurController::class, 'register']);
Route::post('/register', [UtilisateurController::class, 'store']);
Route::get('/login', [UtilisateurController::class, 'login']);
Route::post('/login', [UtilisateurController::class, 'authenticate']);
Route::get('/logout', [UtilisateurController::class, 'logout']);

// Routes pour les timbres
Route::get('/timbres', [TimbreController::class, 'index']);
Route::get('/timbres/create', [TimbreController::class, 'create']);
Route::post('/timbres/store', [TimbreController::class, 'store']);
Route::get('/timbres/{id}', [TimbreController::class, 'show']);
Route::get('/timbres/{id}/edit', [TimbreController::class, 'edit']);
Route::post('/timbres/{id}/update', [TimbreController::class, 'update']);
Route::post('/timbres/{id}/delete', [TimbreController::class, 'delete']);
Route::post('/timbres/{timbre_id}/images/{image_id}/delete', [TimbreController::class, 'deleteImage']);
