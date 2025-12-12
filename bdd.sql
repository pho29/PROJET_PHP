use php_basket;
-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'entraineur',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insérer un utilisateur admin par défaut (mot de passe: admin123)
INSERT IGNORE INTO users (username, password, role) 
VALUES ('admin', 'admin123', 'entraineur');

-- Vos tables existantes...
CREATE TABLE IF NOT EXISTS Joueur(
   id_joueur INT AUTO_INCREMENT PRIMARY KEY,
   numero_licence VARCHAR(50) UNIQUE,
   nom VARCHAR(100) NOT NULL,
   prenom VARCHAR(100) NOT NULL,
   date_naissance DATE,
   taille DECIMAL(5,2),
   poids DECIMAL(5,2),
   statut VARCHAR(15) DEFAULT 'Actif'
);

CREATE TABLE IF NOT EXISTS Match_basket(
   id_match INT AUTO_INCREMENT PRIMARY KEY,
   date_heure DATETIME,
   equipe_adverse VARCHAR(200),
   lieu VARCHAR(200),
   resultat VARCHAR(20),
   score_propre SMALLINT,
   score_adverse SMALLINT,
   commentaire_match TEXT
);

CREATE TABLE IF NOT EXISTS Commentaire_Joueur(
   id_commentaire INT AUTO_INCREMENT PRIMARY KEY,
   Texte TEXT,
   date_commentaire DATETIME DEFAULT CURRENT_TIMESTAMP,
   id_joueur INT NOT NULL,
   FOREIGN KEY(id_joueur) REFERENCES Joueur(id_joueur) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Participer(
   id_joueur INT,
   id_match INT,
   titulaire BOOLEAN,
   evaluation SMALLINT CHECK (evaluation BETWEEN 1 AND 5),
   libelle_poste VARCHAR(100),
   PRIMARY KEY(id_joueur, id_match),
   FOREIGN KEY(id_joueur) REFERENCES Joueur(id_joueur) ON DELETE CASCADE,
   FOREIGN KEY(id_match) REFERENCES Match_basket(id_match) ON DELETE CASCADE
);