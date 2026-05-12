-- 1. Création de la base de données
CREATE DATABASE IF NOT EXISTS formasat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE formasat;

-- 2. Table des étudiants
CREATE TABLE etudiant (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE, -- UNIQUE empêche deux inscriptions avec le même mail
    mot_de_passe VARCHAR(255) NOT NULL, -- 255 car le hash PHP est long
    date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. Table des modules
CREATE TABLE module (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    formateur VARCHAR(100) NOT NULL,
    description TEXT
) ENGINE=InnoDB;

-- 4. Table des administrateurs
CREATE TABLE administrateur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifiant VARCHAR(50) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- 5. Table des évaluations (La table centrale)
CREATE TABLE evaluation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etudiant_id INT NOT NULL,
    module_id INT NOT NULL,
    note_contenu TINYINT NOT NULL, -- On peut ajouter CHECK (note_contenu BETWEEN 1 AND 5)
    note_pedagogie TINYINT NOT NULL,
    note_support TINYINT NOT NULL,
    note_rythme TINYINT NOT NULL,
    note_applicabilite TINYINT NOT NULL,
    commentaire TEXT,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Clés étrangères pour l'intégrité des données
    FOREIGN KEY (etudiant_id) REFERENCES etudiant(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES module(id) ON DELETE CASCADE,
    
    -- Q5 : Contrainte d'unicité pour empêcher un étudiant de noter 2 fois le même module
    UNIQUE KEY unique_evaluation_etudiant_module (etudiant_id, module_id)
) ENGINE=InnoDB;

-- 6. Données de test
INSERT INTO module (nom, formateur) VALUES 
('Développement Web PHP', 'M. Bourguiba'),
('Cybersécurité', 'M. Bernard'),
('Réseaux TCP/IP', 'Dylan'),
('Gestion de projet Agile', 'Mme Manga'),
('Bureautique avancée', 'M. Leroy');

-- L'identifiant est 'admin' et le mot de passe est 'admin123' (haché ici pour l'exemple)
INSERT INTO administrateur (identifiant, mot_de_passe) VALUES 
('admin', '$2y$10$UnL.fI99/XUaX9.e2D6fauFp8CgY9iYmK2/i/V1H7oO4uB7Q.hF9W');