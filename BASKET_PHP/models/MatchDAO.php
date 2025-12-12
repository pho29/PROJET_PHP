<?php
require_once __DIR__ . '/../config/Database.php';

class MatchDAO {
    private $db;
    private $table_name = "Match_basket";

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll() {
        try {
            $query = "SELECT * FROM " . $this->table_name . " ORDER BY date_heure DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur DAO getAll: " . $e->getMessage());
            return [];
        }
    }

    public function getById($id) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE id_match = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Erreur DAO getById: " . $e->getMessage());
            return null;
        }
    }

    public function create($data) {
        try {
            $query = "INSERT INTO " . $this->table_name . " 
                     (date_heure, equipe_adverse, lieu, resultat, score_propre, score_adverse, commentaire_match) 
                     VALUES (:date_heure, :equipe_adverse, :lieu, :resultat, :score_propre, :score_adverse, :commentaire_match)";
            
            $stmt = $this->db->prepare($query);
            
            $stmt->bindValue(":date_heure", $data['date_heure']);
            $stmt->bindValue(":equipe_adverse", trim($data['equipe_adverse']), PDO::PARAM_STR);
            $stmt->bindValue(":lieu", $data['lieu'], PDO::PARAM_STR);
            $stmt->bindValue(":resultat", $data['resultat'], PDO::PARAM_STR);
            $stmt->bindValue(":score_propre", $data['score_propre'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(":score_adverse", $data['score_adverse'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(":commentaire_match", trim($data['commentaire_match'] ?? ''), PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur DAO create: " . $e->getMessage());
            return false;
        }
    }

    public function update($id, $data) {
        try {
            $query = "UPDATE " . $this->table_name . " 
                     SET date_heure = :date_heure, equipe_adverse = :equipe_adverse, lieu = :lieu, 
                         resultat = :resultat, score_propre = :score_propre, score_adverse = :score_adverse, 
                         commentaire_match = :commentaire_match
                     WHERE id_match = :id";
            
            $stmt = $this->db->prepare($query);
            
            $stmt->bindValue(":id", $id, PDO::PARAM_INT);
            $stmt->bindValue(":date_heure", $data['date_heure']);
            $stmt->bindValue(":equipe_adverse", trim($data['equipe_adverse']), PDO::PARAM_STR);
            $stmt->bindValue(":lieu", $data['lieu'], PDO::PARAM_STR);
            $stmt->bindValue(":resultat", $data['resultat'], PDO::PARAM_STR);
            $stmt->bindValue(":score_propre", $data['score_propre'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(":score_adverse", $data['score_adverse'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(":commentaire_match", trim($data['commentaire_match'] ?? ''), PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur DAO update: " . $e->getMessage());
            return false;
        }
    }

    public function delete($id) {
        try {
            $this->db->beginTransaction();
            
            // Supprimer les participations d'abord
            $this->deleteParticipants($id);
            
            // Supprimer le match
            $query = "DELETE FROM " . $this->table_name . " WHERE id_match = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(":id", $id, PDO::PARAM_INT);
            $success = $stmt->execute();
            
            $this->db->commit();
            return $success;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur DAO delete: " . $e->getMessage());
            return false;
        }
    }

    public function getProchains() {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE date_heure > NOW() ORDER BY date_heure ASC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur DAO getProchains: " . $e->getMessage());
            return [];
        }
    }

    public function getTermines() {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE resultat != 'À venir' ORDER BY date_heure DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur DAO getTermines: " . $e->getMessage());
            return [];
        }
    }

    public function getParticipants($match_id) {
        try {
            $query = "SELECT p.*, j.nom, j.prenom, j.numero_licence 
                    FROM Participer p 
                    JOIN Joueur j ON p.id_joueur = j.id_joueur 
                    WHERE p.id_match = :match_id
                    ORDER BY p.titulaire DESC, j.nom ASC, j.prenom ASC";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(":match_id", $match_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur DAO getParticipants: " . $e->getMessage());
            return [];
        }
    }

    public function setParticipants($match_id, $participants) {
        try {
            $this->db->beginTransaction();

            // Supprimer les anciennes participations
            $this->deleteParticipants($match_id);

            // Insérer les nouvelles participations
            $query = "INSERT INTO Participer (id_joueur, id_match, titulaire, evaluation, libelle_poste) 
                     VALUES (:id_joueur, :id_match, :titulaire, :evaluation, :libelle_poste)";
            $stmt = $this->db->prepare($query);

            foreach ($participants as $participant) {
                $stmt->bindValue(":id_joueur", $participant['id_joueur'], PDO::PARAM_INT);
                $stmt->bindValue(":id_match", $match_id, PDO::PARAM_INT);
                $stmt->bindValue(":titulaire", $participant['titulaire'], PDO::PARAM_BOOL);
                $stmt->bindValue(":evaluation", $participant['evaluation'] ?? null, 
                               $participant['evaluation'] ? PDO::PARAM_INT : PDO::PARAM_NULL);
                $stmt->bindValue(":libelle_poste", $participant['libelle_poste'] ?? null, 
                               $participant['libelle_poste'] ? PDO::PARAM_STR : PDO::PARAM_NULL);
                
                $stmt->execute();
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Erreur DAO setParticipants: " . $e->getMessage());
            return false;
        }
    }

    private function deleteParticipants($match_id) {
        try {
            $query = "DELETE FROM Participer WHERE id_match = :match_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(":match_id", $match_id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur DAO deleteParticipants: " . $e->getMessage());
            throw $e;
        }
    }

    public function getStatistiques() {
        try {
            $query = "SELECT 
                        COUNT(*) as total_matchs,
                        SUM(CASE WHEN resultat = 'Victoire' THEN 1 ELSE 0 END) as victoires,
                        SUM(CASE WHEN resultat = 'Défaite' THEN 1 ELSE 0 END) as defaites,
                        SUM(CASE WHEN resultat = 'Nul' THEN 1 ELSE 0 END) as nuls
                     FROM " . $this->table_name . " 
                     WHERE resultat != 'À venir'";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            $result = $stmt->fetch();
            return $result ?: [
                'total_matchs' => 0,
                'victoires' => 0,
                'defaites' => 0,
                'nuls' => 0
            ];
        } catch (PDOException $e) {
            error_log("Erreur DAO getStatistiques: " . $e->getMessage());
            return [
                'total_matchs' => 0,
                'victoires' => 0,
                'defaites' => 0,
                'nuls' => 0
            ];
        }
    }
}