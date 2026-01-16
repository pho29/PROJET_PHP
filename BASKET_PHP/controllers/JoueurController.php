<?php
require_once __DIR__ . '/../models/JoueurDAO.php';

class JoueurController {
    private $joueurDAO;
    private $db; // Ajout de la propriété $db

    public function __construct() {
        require_once __DIR__ . '/../config/Database.php';
        $database = Database::getInstance();
        $this->db = $database->getConnection();
        $this->joueurDAO = new JoueurDAO();
    }

    public function getAll() {
        return $this->joueurDAO->getAll();
    }

    public function getById($id) {
        return $this->joueurDAO->getById($id);
    }

    public function creerJoueur($data) {
        $erreurs = $this->validerDonneesJoueur($data);
        if (!empty($erreurs)) {
            throw new Exception(implode(', ', $erreurs));
        }

        return $this->joueurDAO->create($data);
    }

    public function modifierJoueur($id, $data) {
        $erreurs = $this->validerDonneesJoueur($data);
        if (!empty($erreurs)) {
            throw new Exception(implode(', ', $erreurs));
        }

        return $this->joueurDAO->update($id, $data);
    }

    public function delete($id) {
        return $this->joueurDAO->delete($id);
    }

    public function getHistoriqueJoueur($idJoueur) {
        try {
            $sql = "SELECT 
                        COUNT(jm.id_match) as nb_matchs,
                        AVG(jm.evaluation) as moyenne_evaluation,
                        COUNT(jm.evaluation) as nb_evaluations
                    FROM Participer jm
                    JOIN Match_basket m ON jm.id_match = m.id_match
                    WHERE jm.id_joueur = :id_joueur";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id_joueur', $idJoueur, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch();
            
            // Récupérer le commentaire général du joueur
            $sqlCommentaire = "SELECT commentaire_general FROM Joueur WHERE id_joueur = :id_joueur";
            $stmtCommentaire = $this->db->prepare($sqlCommentaire);
            $stmtCommentaire->bindValue(':id_joueur', $idJoueur, PDO::PARAM_INT);
            $stmtCommentaire->execute();
            $commentaire = $stmtCommentaire->fetchColumn();
            
            // Récupérer les 3 derniers matchs avec évaluation
            $sqlDerniersMatchs = "SELECT 
                                    m.date_heure,
                                    m.equipe_adverse,
                                    jm.evaluation,
                                    jm.titulaire,
                                    jm.libelle_poste
                                  FROM Participer jm
                                  JOIN Match_basket m ON jm.id_match = m.id_match
                                  WHERE jm.id_joueur = :id_joueur
                                  AND jm.evaluation IS NOT NULL
                                  ORDER BY m.date_heure DESC
                                  LIMIT 3";
            
            $stmtMatchs = $this->db->prepare($sqlDerniersMatchs);
            $stmtMatchs->bindValue(':id_joueur', $idJoueur, PDO::PARAM_INT);
            $stmtMatchs->execute();
            $derniersMatchs = $stmtMatchs->fetchAll();
            
            return [
                'nb_matchs' => $result['nb_matchs'] ?? 0,
                'moyenne_evaluation' => $result['moyenne_evaluation'] ? round($result['moyenne_evaluation'], 1) : 0.0,
                'nb_evaluations' => $result['nb_evaluations'] ?? 0,
                'commentaire_general' => $commentaire ?: '',
                'derniers_matchs' => $derniersMatchs
            ];
        } catch (PDOException $e) {
            error_log("Erreur getHistoriqueJoueur: " . $e->getMessage());
            return [
                'nb_matchs' => 0,
                'moyenne_evaluation' => 0.0,
                'nb_evaluations' => 0,
                'commentaire_general' => '',
                'derniers_matchs' => []
            ];
        }
    }

    public function ajouterCommentaireJoueur($idJoueur, $commentaire, $date = null) {
        if (!$date) {
            $date = date('Y-m-d H:i:s');
        }
        
        try {
            // Vérifier si un commentaire général existe déjà
            $sqlCheck = "SELECT commentaire_general FROM Joueur WHERE id_joueur = :id_joueur";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->bindValue(':id_joueur', $idJoueur, PDO::PARAM_INT);
            $stmtCheck->execute();
            $commentaireExistant = $stmtCheck->fetchColumn();
            
            // Préparer le nouveau commentaire
            if ($commentaireExistant) {
                // Ajouter le nouveau commentaire avec date
                $nouveauCommentaire = $commentaireExistant . "\n--- " . date('d/m/Y', strtotime($date)) . " ---\n" . $commentaire;
            } else {
                $nouveauCommentaire = "--- " . date('d/m/Y', strtotime($date)) . " ---\n" . $commentaire;
            }
            
            // Mettre à jour la base de données
            $sqlUpdate = "UPDATE Joueur SET commentaire_general = :commentaire WHERE id_joueur = :id_joueur";
            $stmtUpdate = $this->db->prepare($sqlUpdate);
            $stmtUpdate->bindValue(':commentaire', $nouveauCommentaire, PDO::PARAM_STR);
            $stmtUpdate->bindValue(':id_joueur', $idJoueur, PDO::PARAM_INT);
            
            return $stmtUpdate->execute();
        } catch (PDOException $e) {
            error_log("Erreur ajouterCommentaireJoueur: " . $e->getMessage());
            return false;
        }
    }

    public function getCommentairesJoueur($idJoueur) {
        try {
            $sql = "SELECT commentaire_general FROM Joueur WHERE id_joueur = :id_joueur";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id_joueur', $idJoueur, PDO::PARAM_INT);
            $stmt->execute();
            
            $commentaire = $stmt->fetchColumn();
            
            if ($commentaire) {
                // Séparer les commentaires par date
                $lignes = explode("\n", $commentaire);
                $commentaires = [];
                $dateCourante = '';
                
                foreach ($lignes as $ligne) {
                    if (strpos($ligne, '--- ') === 0 && strpos($ligne, ' ---') !== false) {
                        // C'est une ligne de date
                        $dateCourante = trim(str_replace(['---', '---'], '', $ligne));
                    } elseif (!empty($ligne) && !empty($dateCourante)) {
                        // C'est un commentaire pour la date courante
                        if (!isset($commentaires[$dateCourante])) {
                            $commentaires[$dateCourante] = [];
                        }
                        $commentaires[$dateCourante][] = $ligne;
                    }
                }
                
                return $commentaires;
            }
            
            return [];
        } catch (PDOException $e) {
            error_log("Erreur getCommentairesJoueur: " . $e->getMessage());
            return [];
        }
    }

    private function validerDonneesJoueur($data) {
        $erreurs = [];
        
        if (empty(trim($data['nom']))) $erreurs[] = "Le nom est obligatoire";
        if (empty(trim($data['prenom']))) $erreurs[] = "Le prénom est obligatoire";
        if (empty(trim($data['numero_licence']))) $erreurs[] = "Le numéro de licence est obligatoire";
        if (empty($data['date_naissance'])) $erreurs[] = "La date de naissance est obligatoire";
        if (empty($data['taille'])) $erreurs[] = "La taille est obligatoire";
        if (empty($data['poids'])) $erreurs[] = "Le poids est obligatoire";
        
        // Vérifier le numéro de licence unique
        if ($this->joueurDAO->joueurExists($data['numero_licence'])) {
            $erreurs[] = "Ce numéro de licence existe déjà";
        }
        
        return $erreurs;
    }
}