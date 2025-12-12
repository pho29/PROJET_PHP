<?php
require_once __DIR__ . '/../models/JoueurDAO.php';

class JoueurController {
    private $joueurDAO;

    public function __construct() {
        $this->joueurDAO = new JoueurDAO();
    }

    // Méthodes pour compatibilité avec les vues existantes
    public function getAll() {
        return $this->joueurDAO->getAll();
    }

    public function getById($id) {
        return $this->joueurDAO->getById($id);
    }

    public function create($data) {
        return $this->creerJoueur($data);
    }

    public function update($id, $data) {
        return $this->modifierJoueur($id, $data);
    }

    public function delete($id) {
        return $this->supprimerJoueur($id);
    }

    public function joueurExists($numero_licence, $exclude_id = null) {
        return $this->joueurDAO->joueurExists($numero_licence, $exclude_id);
    }

    public function getActifs() {
        return $this->getJoueursActifs();
    }

    // Nouvelles méthodes (nomenclature française)
    public function listerJoueurs() {
        return $this->joueurDAO->getAll();
    }

    public function getJoueur($id) {
        return $this->joueurDAO->getById($id);
    }

    public function creerJoueur($data) {
        // Validation des données
        $erreurs = $this->validerDonneesJoueur($data);
        if (!empty($erreurs)) {
            throw new Exception(implode(', ', $erreurs));
        }

        if ($this->joueurDAO->joueurExists($data['numero_licence'])) {
            throw new Exception("Ce numéro de licence existe déjà");
        }

        return $this->joueurDAO->create($data);
    }

    public function modifierJoueur($id, $data) {
        // Validation des données
        $erreurs = $this->validerDonneesJoueur($data);
        if (!empty($erreurs)) {
            throw new Exception(implode(', ', $erreurs));
        }

        if ($this->joueurDAO->joueurExists($data['numero_licence'], $id)) {
            throw new Exception("Ce numéro de licence existe déjà");
        }

        return $this->joueurDAO->update($id, $data);
    }

    public function supprimerJoueur($id) {
        return $this->joueurDAO->delete($id);
    }

    public function getJoueursActifs() {
        return array_filter($this->listerJoueurs(), function($joueur) {
            return $joueur['statut'] === 'Actif';
        });
    }

    public function getJoueursBlesses() {
        return $this->joueurDAO->getByStatut('Blessé');
    }

    public function rechercherJoueurs($term) {
        return $this->joueurDAO->search($term);
    }

    private function validerDonneesJoueur($data) {
        $erreurs = [];
        
        if (empty(trim($data['nom']))) $erreurs[] = "Le nom est obligatoire";
        if (empty(trim($data['prenom']))) $erreurs[] = "Le prénom est obligatoire";
        if (empty(trim($data['numero_licence']))) $erreurs[] = "Le numéro de licence est obligatoire";
        if (empty($data['date_naissance'])) $erreurs[] = "La date de naissance est obligatoire";
        if (empty($data['taille'])) $erreurs[] = "La taille est obligatoire";
        if (empty($data['poids'])) $erreurs[] = "Le poids est obligatoire";
        
        return $erreurs;
    }
}