<?php
require_once __DIR__ . '/../config/Database.php';

class JoueurDAO {
    private $db;
    private $table = 'Joueur';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll() {
        try {
            $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY nom, prenom");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur DAO getAll: " . $e->getMessage());
            return [];
        }
    }

    public function getById($id) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE id_joueur = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Erreur DAO getById: " . $e->getMessage());
            return false;
        }
    }

    public function create($data) {
        try {
            $sql = "INSERT INTO {$this->table} (numero_licence, nom, prenom, date_naissance, taille, poids, statut) 
                    VALUES (:numero_licence, :nom, :prenom, :date_naissance, :taille, :poids, :statut)";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':numero_licence' => $data['numero_licence'],
                ':nom' => $data['nom'],
                ':prenom' => $data['prenom'],
                ':date_naissance' => $data['date_naissance'],
                ':taille' => $data['taille'],
                ':poids' => $data['poids'],
                ':statut' => $data['statut']
            ]);
        } catch (PDOException $e) {
            error_log("Erreur DAO create: " . $e->getMessage());
            return false;
        }
    }

    public function update($id, $data) {
        try {
            $sql = "UPDATE {$this->table} 
                    SET numero_licence = :numero_licence, 
                        nom = :nom, 
                        prenom = :prenom, 
                        date_naissance = :date_naissance, 
                        taille = :taille, 
                        poids = :poids, 
                        statut = :statut
                    WHERE id_joueur = :id";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $id,
                ':numero_licence' => $data['numero_licence'],
                ':nom' => $data['nom'],
                ':prenom' => $data['prenom'],
                ':date_naissance' => $data['date_naissance'],
                ':taille' => $data['taille'],
                ':poids' => $data['poids'],
                ':statut' => $data['statut']
            ]);
        } catch (PDOException $e) {
            error_log("Erreur DAO update: " . $e->getMessage());
            return false;
        }
    }

    public function delete($id) {
        try {
            $sql = "DELETE FROM {$this->table} WHERE id_joueur = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur DAO delete: " . $e->getMessage());
            return false;
        }
    }

    public function joueurExists($numero_licence, $exclude_id = null) {
        try {
            $sql = "SELECT id_joueur FROM {$this->table} WHERE numero_licence = :numero_licence";
            
            if ($exclude_id) {
                $sql .= " AND id_joueur != :exclude_id";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':numero_licence', $numero_licence, PDO::PARAM_STR);
            
            if ($exclude_id) {
                $stmt->bindValue(':exclude_id', $exclude_id, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("Erreur DAO joueurExists: " . $e->getMessage());
            return false;
        }
    }

    public function getByStatut($statut) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE statut = :statut ORDER BY nom, prenom";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':statut', $statut, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur DAO getByStatut: " . $e->getMessage());
            return [];
        }
    }

    public function search($term) {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE nom LIKE :term OR prenom LIKE :term OR numero_licence LIKE :term
                    ORDER BY nom, prenom";
            $stmt = $this->db->prepare($sql);
            $searchTerm = "%$term%";
            $stmt->bindValue(':term', $searchTerm, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur DAO search: " . $e->getMessage());
            return [];
        }
    }
}