-- Utilisation de la base de données existante
USE e2496039;

-- Table: Conditions_Timbres (Conditions des timbres)
CREATE TABLE IF NOT EXISTS Conditions_Timbres (
    condition_id INT AUTO_INCREMENT PRIMARY KEY,
    nom_condition VARCHAR(255) NOT NULL
);

-- Table: Pays_Timbres (Pays d'origine des timbres)
CREATE TABLE IF NOT EXISTS Pays_Timbres (
    pays_id INT AUTO_INCREMENT PRIMARY KEY,
    nom_pays VARCHAR(255) NOT NULL
);

-- Table: Couleurs_Timbres (Couleurs des timbres)
CREATE TABLE IF NOT EXISTS Couleurs_Timbres (
    couleur_id INT AUTO_INCREMENT PRIMARY KEY,
    nom_couleur VARCHAR(255) NOT NULL
);

-- Table: Privileges
CREATE TABLE IF NOT EXISTS Privileges (
    privilege_id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('admin', 'utilisateur') NOT NULL,
    description TEXT NOT NULL
);

-- Insertion des privilèges de base
INSERT INTO Privileges (role, description) VALUES 
    ('admin', 'Administrateur avec tous les droits'),
    ('utilisateur', 'Utilisateur standard');

-- Table: Utilisateurs 
CREATE TABLE IF NOT EXISTS Utilisateurs (
    utilisateur_id INT AUTO_INCREMENT PRIMARY KEY,
    prenom VARCHAR(255) NOT NULL,
    nom_utilisateur VARCHAR(50) UNIQUE NOT NULL,  
    email VARCHAR(255) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    date_inscription DATETIME NOT NULL,
    privilege_id INT NOT NULL DEFAULT 2,  
    FOREIGN KEY (privilege_id) REFERENCES Privileges(privilege_id) ON DELETE RESTRICT,
    INDEX idx_email (email),
    INDEX idx_nom_utilisateur (nom_utilisateur), 
    INDEX idx_privilege (privilege_id)
);

-- Table: Timbres 
CREATE TABLE IF NOT EXISTS Timbres (
    timbre_id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    date_creation DATETIME NOT NULL,
    condition_id INT,
    tirage INT,
    certifié BOOLEAN NOT NULL,
    dimensions VARCHAR(50),
    description TEXT,  
    pays_id INT,  
    couleur_id INT,  
    utilisateur_id INT,  
    FOREIGN KEY (condition_id) REFERENCES Conditions_Timbres(condition_id) ON DELETE SET NULL,
    FOREIGN KEY (pays_id) REFERENCES Pays_Timbres(pays_id) ON DELETE SET NULL,
    FOREIGN KEY (couleur_id) REFERENCES Couleurs_Timbres(couleur_id) ON DELETE SET NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES Utilisateurs(utilisateur_id) ON DELETE SET NULL
);

-- Table: Images_Timbres (Images des timbres)
CREATE TABLE IF NOT EXISTS Images_Timbres (
    image_id INT AUTO_INCREMENT PRIMARY KEY,
    timbre_id INT NOT NULL,
    url_image VARCHAR(255) NOT NULL,
    image_principale BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (timbre_id) REFERENCES Timbres(timbre_id) ON DELETE CASCADE
);

-- Table: Encheres 
CREATE TABLE IF NOT EXISTS Encheres (
    enchere_id INT AUTO_INCREMENT PRIMARY KEY,
    timbre_id INT NOT NULL,
    date_debut DATETIME NOT NULL,
    date_fin DATETIME NOT NULL,
    coups_de_coeur BOOLEAN NOT NULL DEFAULT FALSE,  
    FOREIGN KEY (timbre_id) REFERENCES Timbres(timbre_id) ON DELETE CASCADE
);

-- Table: Offres
CREATE TABLE IF NOT EXISTS Offres (
    offre_id INT AUTO_INCREMENT PRIMARY KEY,
    enchere_id INT NOT NULL,
    utilisateur_id INT NOT NULL,
    montant_offre DECIMAL(10, 2) NOT NULL,
    date_offre DATETIME NOT NULL,
    FOREIGN KEY (enchere_id) REFERENCES Encheres(enchere_id) ON DELETE CASCADE,
    FOREIGN KEY (utilisateur_id) REFERENCES Utilisateurs(utilisateur_id) ON DELETE CASCADE
);

-- Table: Commentaires (Commentaires sur les encheres archivees)
CREATE TABLE IF NOT EXISTS Commentaires (
    commentaire_id INT AUTO_INCREMENT PRIMARY KEY,
    enchere_id INT NOT NULL,
    utilisateur_id INT NOT NULL,
    commentaire TEXT NOT NULL,
    date_commentaire DATETIME NOT NULL,
    FOREIGN KEY (enchere_id) REFERENCES Encheres(enchere_id) ON DELETE CASCADE,
    FOREIGN KEY (utilisateur_id) REFERENCES Utilisateurs(utilisateur_id) ON DELETE CASCADE
);
