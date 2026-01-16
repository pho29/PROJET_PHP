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
        
        // Vérifier que la date n'est pas dans le passé
        $dateMatch = strtotime($data['date_heure']);
        $dateActuelle = time();
        if ($dateMatch <= $dateActuelle) {
            throw new Exception("La date du match ne peut pas être dans le passé");
        }
        
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
        // Récupérer le match existant
        $matchExistant = $this->getMatch($id);
        if (!$matchExistant) {
            throw new Exception("Match non trouvé");
        }
        
        // Vérifier si le match n'a pas encore eu lieu
        $dateMatchExistant = strtotime($matchExistant['date_heure']);
        $dateActuelle = time();
        if ($dateMatchExistant <= $dateActuelle) {
            throw new Exception("Impossible de modifier un match qui a déjà eu lieu");
        }
        
        // Validation des données
        $erreurs = $this->validerDonneesMatch($data);
        if (!empty($erreurs)) {
            throw new Exception(implode(', ', $erreurs));
        }
        
        // Vérifier que la nouvelle date n'est pas dans le passé
        $nouvelleDateHeure = $this->combinerDateHeure($data['date_match'], $data['heure_match']);
        $nouvelleDate = strtotime($nouvelleDateHeure);
        if ($nouvelleDate <= $dateActuelle) {
            throw new Exception("La nouvelle date du match ne peut pas être dans le passé");
        }
        
        $cleanData = [
            'date_heure' => $nouvelleDateHeure,
            'equipe_adverse' => trim($data['equipe_adverse']),
            'lieu' => $data['lieu'],
            'resultat' => $data['resultat'],
            'score_propre' => !empty($data['score_propre']) ? (int)$data['score_propre'] : null,
            'score_adverse' => !empty($data['score_adverse']) ? (int)$data['score_adverse'] : null,
            'commentaire_match' => trim($data['commentaire_match'] ?? '')
        ];
        
        // Si le match est à venir, le résultat doit être "À venir"
        if ($cleanData['date_heure'] > date('Y-m-d H:i:s')) {
            $cleanData['resultat'] = 'À venir';
            $cleanData['score_propre'] = null;
            $cleanData['score_adverse'] = null;
        }
        
        return $this->matchDAO->update($id, $cleanData);
    }

    // Méthode pour vérifier si un match peut être supprimé
    public function peutSupprimerMatch($id) {
        $match = $this->getMatch($id);
        if (!$match) {
            return false;
        }
        
        $dateMatch = strtotime($match['date_heure']);
        $dateActuelle = time();
        
        // Un match ne peut être supprimé que s'il n'a pas encore eu lieu
        return $dateMatch > $dateActuelle;
    }

    public function supprimerMatch($id) {
        // Vérifier si le match peut être supprimé
        if (!$this->peutSupprimerMatch($id)) {
            throw new Exception("Impossible de supprimer un match qui a déjà eu lieu");
        }
        
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
        
        // Ajouter la validation des scores
        $erreursScores = $this->validerScores($data);
        $erreurs = array_merge($erreurs, $erreursScores);
        
        return $erreurs;
    }

    public function updateParticipantEvaluation($match_id, $joueur_id, $evaluation) {
        return $this->matchDAO->updateParticipantEvaluation($match_id, $joueur_id, $evaluation);
    }

    private function combinerDateHeure($date, $heure) {
        if (empty($date) || empty($heure)) {
            throw new InvalidArgumentException("La date et l'heure sont obligatoires");
        }
        return $date . ' ' . $heure . ':00';
    }

    private function validerScores($data) {
        $erreurs = [];
        
        // Si le résultat n'est pas "À venir", les scores sont obligatoires
        if ($data['resultat'] !== 'À venir') {
            if (empty($data['score_propre'])) {
                $erreurs[] = "Le score propre est obligatoire pour un match terminé";
            }
            if (empty($data['score_adverse'])) {
                $erreurs[] = "Le score adverse est obligatoire pour un match terminé";
            }
            
            // Si les deux scores sont présents, valider la logique
            if (!empty($data['score_propre']) && !empty($data['score_adverse'])) {
                $scorePropre = (int)$data['score_propre'];
                $scoreAdverse = (int)$data['score_adverse'];
                
                switch ($data['resultat']) {
                    case 'Victoire':
                        if ($scorePropre <= $scoreAdverse) {
                            $erreurs[] = "En cas de victoire, notre score doit être supérieur au score adverse";
                        }
                        break;
                    case 'Défaite':
                        if ($scorePropre >= $scoreAdverse) {
                            $erreurs[] = "En cas de défaite, notre score doit être inférieur au score adverse";
                        }
                        break;
                    case 'Nul':
                        if ($scorePropre !== $scoreAdverse) {
                            $erreurs[] = "En cas de match nul, les scores doivent être égaux";
                        }
                        break;
                }
            }
        }
        
        return $erreurs;
    }

}
?>