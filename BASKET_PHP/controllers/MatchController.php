<?php
require_once __DIR__ . '/../models/MatchDAO.php';

class MatchController {
    private $matchDAO;

    public function __construct() {
        $this->matchDAO = new MatchDAO();
    }

    // Méthode getAll() pour compatibilité
    public function getAll() {
        return $this->matchDAO->getAll();
    }

    // Alias pour getById()
    public function getById($id) {
        return $this->getMatch($id);
    }

    public function getMatch($id) {
        return $this->matchDAO->getById($id);
    }

    public function listerMatchs() {
        return $this->matchDAO->getAll();
    }

    public function creerMatch($data) {
        $erreurs = $this->validerDonneesMatch($data);
        if (!empty($erreurs)) {
            throw new Exception(implode(', ', $erreurs));
        }

        $data['date_heure'] = $this->combinerDateHeure($data['date_match'], $data['heure_match']);
        
        $cleanData = [
            'date_heure' => $data['date_heure'],
            'equipe_adverse' => trim($data['equipe_adverse']),
            'lieu' => $data['lieu'],
            'resultat' => $data['resultat'],
            'score_propre' => !empty($data['score_propre']) ? (int)$data['score_propre'] : null,
            'score_adverse' => !empty($data['score_adverse']) ? (int)$data['score_adverse'] : null,
            'commentaire_match' => trim($data['commentaire_match'] ?? '')
        ];

        return $this->matchDAO->create($cleanData);
    }

    public function modifierMatch($id, $data) {
        $erreurs = $this->validerDonneesMatch($data);
        if (!empty($erreurs)) {
            throw new Exception(implode(', ', $erreurs));
        }

        $data['date_heure'] = $this->combinerDateHeure($data['date_match'], $data['heure_match']);
        
        $cleanData = [
            'date_heure' => $data['date_heure'],
            'equipe_adverse' => trim($data['equipe_adverse']),
            'lieu' => $data['lieu'],
            'resultat' => $data['resultat'],
            'score_propre' => !empty($data['score_propre']) ? (int)$data['score_propre'] : null,
            'score_adverse' => !empty($data['score_adverse']) ? (int)$data['score_adverse'] : null,
            'commentaire_match' => trim($data['commentaire_match'] ?? '')
        ];

        return $this->matchDAO->update($id, $cleanData);
    }

    public function supprimerMatch($id) {
        return $this->matchDAO->delete($id);
    }

    public function getProchainsMatchs() {
        return $this->matchDAO->getProchains();
    }

    public function getMatchsTermines() {
        return $this->matchDAO->getTermines();
    }

    public function getParticipants($match_id) {
        return $this->matchDAO->getParticipants($match_id);
    }

    public function setParticipants($match_id, $participants) {
        try {
            return $this->matchDAO->setParticipants($match_id, $participants);
        } catch (Exception $e) {
            error_log("Erreur setParticipants: " . $e->getMessage());
            throw $e;
        }
    }

    private function validerDonneesMatch($data) {
        $erreurs = [];
        
        if (empty($data['date_match'])) $erreurs[] = "La date est obligatoire";
        if (empty($data['heure_match'])) $erreurs[] = "L'heure est obligatoire";
        if (empty(trim($data['equipe_adverse']))) $erreurs[] = "L'équipe adverse est obligatoire";
        
        return $erreurs;
    }

    private function combinerDateHeure($date, $heure) {
        if (empty($date) || empty($heure)) {
            throw new InvalidArgumentException("La date et l'heure sont obligatoires");
        }
        return $date . ' ' . $heure . ':00';
    }
}