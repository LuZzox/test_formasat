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

-- Nouvelle table : promo
CREATE TABLE promo (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    libelle  VARCHAR(80)  NOT NULL UNIQUE,   -- ex : 'SLAM 2025A'
    annee    YEAR         NOT NULL,
    actif    TINYINT(1)   DEFAULT 1, -- 1 for active, 0 for inactive (soft delete)
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Nouvelle table : prof
CREATE TABLE prof (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nom         VARCHAR(100) NOT NULL,
    prenom      VARCHAR(100) NOT NULL,
    email       VARCHAR(150) UNIQUE, -- Email for contact, potentially for login later
    specialite  VARCHAR(255),
    date_embauche DATE,
    actif       TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

-- 3. Table des modules
CREATE TABLE module (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    formateur VARCHAR(100) NOT NULL, -- Ajouté pour simuler la v1 et permettre le DROP lors de l'évolution
    description TEXT 
) ENGINE=InnoDB;

-- Modification de la table etudiant
ALTER TABLE etudiant
    ADD COLUMN promo_id INT NOT NULL, 
    ADD CONSTRAINT fk_etu_promo
        FOREIGN KEY (promo_id) REFERENCES promo(id) ON DELETE RESTRICT; -- Prevent deleting a promo if students are still assigned

-- Modification de la table module
ALTER TABLE module
    DROP COLUMN formateur, -- Remove old formateur column
    ADD COLUMN prof_id   INT NOT NULL,
    ADD COLUMN promo_id  INT NOT NULL,
    ADD COLUMN date_debut DATE,
    ADD COLUMN date_fin   DATE,
    ADD CONSTRAINT fk_mod_prof  FOREIGN KEY (prof_id)  REFERENCES prof(id) ON DELETE RESTRICT, -- Prevent deleting a prof if modules are still assigned
    ADD CONSTRAINT fk_mod_promo FOREIGN KEY (promo_id) REFERENCES promo(id) ON DELETE RESTRICT; -- Prevent deleting a promo if modules are still assigned


-- Nouvelle table : stats_module (cache automatisé)
CREATE TABLE stats_module (
    module_id       INT PRIMARY KEY,
    nb_evaluations  INT     DEFAULT 0,
    moy_contenu     DECIMAL(3,2) DEFAULT 0,
    moy_pedagogie   DECIMAL(3,2) DEFAULT 0,
    moy_support     DECIMAL(3,2) DEFAULT 0,
    moy_rythme      DECIMAL(3,2) DEFAULT 0,
    moy_applicab    DECIMAL(3,2) DEFAULT 0,
    moy_globale     DECIMAL(3,2) DEFAULT 0,
    mise_a_jour     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_stats_mod FOREIGN KEY (module_id) REFERENCES module(id) ON DELETE CASCADE
);

-- Nouvelle table : log_suppression_etudiant
CREATE TABLE log_suppression_etudiant (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    etudiant_id    INT,
    nom_etudiant   VARCHAR(100),
    email_etudiant VARCHAR(150),
    supprime_le    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

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

-- ==========================================================
-- TRIGGERS
-- ==========================================================
DELIMITER //

-- ⚡ Trigger 1 : Mise à jour automatique des statistiques
CREATE TRIGGER trg_maj_stats
    AFTER INSERT ON evaluation
    FOR EACH ROW
BEGIN
    INSERT INTO stats_module (module_id, nb_evaluations, moy_contenu, moy_pedagogie, moy_support, moy_rythme, moy_applicab, moy_globale)
    SELECT 
        NEW.module_id,
        COUNT(*),
        ROUND(AVG(note_contenu), 2),
        ROUND(AVG(note_pedagogie), 2),
        ROUND(AVG(note_support), 2),
        ROUND(AVG(note_rythme), 2),
        ROUND(AVG(note_applicabilite), 2),
        ROUND(AVG((note_contenu + note_pedagogie + note_support + note_rythme + note_applicabilite) / 5.0), 2)
    FROM evaluation
    WHERE module_id = NEW.module_id
    ON DUPLICATE KEY UPDATE
        nb_evaluations = VALUES(nb_evaluations),
        moy_contenu    = VALUES(moy_contenu),
        moy_pedagogie  = VALUES(moy_pedagogie),
        moy_support    = VALUES(moy_support),
        moy_rythme     = VALUES(moy_rythme),
        moy_applicab   = VALUES(moy_applicab),
        moy_globale    = VALUES(moy_globale);
END //

-- ⚡ Trigger 2 : Journalisation des suppressions
CREATE TRIGGER trg_log_suppr_etudiant
    BEFORE DELETE ON etudiant
    FOR EACH ROW
BEGIN
    INSERT INTO log_suppression_etudiant (etudiant_id, nom_etudiant, email_etudiant)
    VALUES (OLD.id, OLD.nom, OLD.email);
END //

-- ⚡ Trigger 3 : Vérification de cohérence des dates
CREATE TRIGGER trg_check_dates_module
    BEFORE INSERT ON module
    FOR EACH ROW
BEGIN
    IF NEW.date_fin <= NEW.date_debut THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Erreur : date_fin doit être postérieure à date_debut';
    END IF;
END //

-- ⚡ Trigger 4 : Initialisation automatique de stats_module
CREATE TRIGGER trg_init_stats_module
    AFTER INSERT ON module
    FOR EACH ROW
BEGIN
    INSERT INTO stats_module (module_id) VALUES (NEW.id);
END //

DELIMITER ;

-- ==========================================================
-- PROCÉDURES STOCKÉES
-- ==========================================================
DELIMITER //

-- 📦 Procédure 1 : Inscription étudiant (Transactionnelle)
CREATE PROCEDURE sp_inscrire_etudiant(
    IN  p_nom       VARCHAR(100),
    IN  p_prenom    VARCHAR(100),
    IN  p_email     VARCHAR(150),
    IN  p_mdp_hash  VARCHAR(255),
    IN  p_promo_id  INT,
    OUT p_succes    TINYINT,
    OUT p_message   VARCHAR(200)
)
BEGIN
    DECLARE v_email_existe INT DEFAULT 0;
    DECLARE v_promo_active INT DEFAULT 0;
    
    SELECT COUNT(*) INTO v_email_existe FROM etudiant WHERE email = p_email;
    
    IF v_email_existe > 0 THEN
        SET p_succes = 0;
        SET p_message = 'Cet email est déjà utilisé';
    ELSE -- L'email est unique, on procède à la vérification de la promo et à l'insertion
        SELECT COUNT(*) INTO v_promo_active FROM promo WHERE id = p_promo_id AND actif = 1;
        
        IF v_promo_active = 0 THEN
            SET p_succes = 0;
            SET p_message = 'La promotion sélectionnée n\'existe pas ou n\'est pas active.';
        ELSE -- La promo est valide, on procède à l'insertion
            START TRANSACTION;
                INSERT INTO etudiant (nom, prenom, email, mot_de_passe, promo_id)
                VALUES (p_nom, p_prenom, p_email, p_mdp_hash, p_promo_id);
            COMMIT;
            SET p_succes = 1;
            SET p_message = 'Étudiant inscrit avec succès';
        END IF;
    END IF;
END //

-- 📦 Procédure 2 : Rapport satisfaction par promo
CREATE PROCEDURE sp_rapport_satisfaction_promo(IN p_promo_id INT)
BEGIN
    SELECT 
        m.nom AS module,
        CONCAT(pr.prenom, ' ', pr.nom) AS formateur,
        s.nb_evaluations,
        s.moy_globale
    FROM module m
    JOIN prof pr ON m.prof_id = pr.id
    LEFT JOIN stats_module s ON s.module_id = m.id
    WHERE m.promo_id = p_promo_id
    ORDER BY s.moy_globale DESC;
END //

DELIMITER ;

-- 6. Données de test v2
INSERT INTO promo (libelle, annee) VALUES ('SLAM 2025', 2025), ('SISR 2025', 2025);

INSERT INTO prof (nom, prenom, email, specialite) VALUES 
('Bourguiba', 'M.', 'm.bourguiba@formasat.fr', 'Développement'),
('Bernard', 'M.', 'm.bernard@formasat.fr', 'Sécurité'),
('Leroy', 'M.', 'm.leroy@formasat.fr', 'Bureautique');

-- Les inserts de modules utilisent maintenant les IDs des profs et promos
INSERT INTO module (nom, description, prof_id, promo_id, date_debut, date_fin) VALUES 
('Développement Web PHP', 'Cours PHP', 1, 1, '2025-09-01', '2025-09-30'),
('Cybersécurité', 'Cours Sécu', 2, 1, '2025-10-01', '2025-10-31');

-- L'identifiant est 'admin' et le mot de passe est 'admin123' (haché ici pour l'exemple)
INSERT INTO administrateur (identifiant, mot_de_passe) VALUES 
('admin', '$2y$10$UnL.fI99/XUaX9.e2D6fauFp8CgY9iYmK2/i/V1H7oO4uB7Q.hF9W');