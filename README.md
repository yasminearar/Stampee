# Projet Stampee

Plateforme d'enchères de timbres rares et d'exception.

## Installation

1. Cloner le dépôt
2. Installer les dépendances avec Composer : `composer install`
3. Configurer la base de données dans `config.php`
4. Importer la base de données : `database.sql`
5. Configurer le serveur web pour pointer vers le dossier du projet

## Structure du projet

Le projet suit une architecture MVC (Modèle-Vue-Contrôleur) avec Twig comme moteur de template:

- `src/Controllers/` : Contrôleurs de l'application
- `src/Models/` : Modèles de données
- `src/Views/` : Templates Twig
- `src/Routes/` : Gestion des routes
- `public/` : Fichiers publics (CSS, JS, images)

## Fonctionnalités implémentées

- Inscription d'utilisateur
- Connexion/Déconnexion
- Gestion des timbres
- Gestion des enchères
