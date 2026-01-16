<?php
require_once __DIR__ . '/../config/Database.php';

class CommentaireDAO {
    private $db;
    private $table = 'Commentaire_Joueur';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /** Récupérer tous les commentaires */
    public function getAll() {
        try {
            $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY date_commentaire DESC");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur DAO getAll Commentaire: " . $e->getMessage());
            return [];
        }
    }

    /** Récupérer un commentaire par ID */
    public function getById($id) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE id_commentaire = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Erreur DAO getById Commentaire: " . $e->getMessage());
            return false;
        }
    }

    /** Récupérer tous les commentaires d’un joueur */
    public function getByJoueur($id_joueur) {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE id_joueur = :id_joueur 
                    ORDER BY date_commentaire DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id_joueur', $id_joueur, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur DAO getByJoueur Commentaire: " . $e->getMessage());
            return [];
        }
    }

    /** Ajouter un commentaire */
    public function create($data) {
        try {
            $sql = "INSERT INTO {$this->table} (Texte, id_joueur)
                    VALUES (:texte, :id_joueur)";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':texte' => $data['texte'],
                ':id_joueur' => $data['id_joueur']
            ]);
        } catch (PDOException $e) {
            error_log("Erreur DAO create Commentaire: " . $e->getMessage());
            return false;
        }
    }

    /** Modifier un commentaire */
    public function update($id, $data) {
        try {
            $sql = "UPDATE {$this->table}
                    SET Texte = :texte
                    WHERE id_commentaire = :id";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $id,
                ':texte' => $data['texte']
            ]);
        } catch (PDOException $e) {
            error_log("Erreur DAO update Commentaire: " . $e->getMessage());
            return false;
        }
    }

    /** Supprimer un commentaire */
    public function delete($id) {
        try {
            $sql = "DELETE FROM {$this->table} WHERE id_commentaire = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur DAO delete Commentaire: " . $e->getMessage());
            return false;
        }
    }
}
