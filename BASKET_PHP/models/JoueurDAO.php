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

    public function getStatsParJoueur() {
        $sql = "
        SELECT 
            j.id_joueur,
            j.nom,
            j.prenom,
            j.statut,

            COUNT(p.id_match) AS matchs_joues,
            SUM(p.titulaire = 1) AS titularisations,
            SUM(p.titulaire = 0) AS remplacements,
            ROUND(AVG(p.evaluation), 2) AS moyenne_evaluation,

            SUM(CASE WHEN m.resultat = 'Victoire' AND p.id_match IS NOT NULL THEN 1 ELSE 0 END) AS victoires_jouees,

            (
                SELECT libelle_poste
                FROM Participer p2
                WHERE p2.id_joueur = j.id_joueur
                GROUP BY libelle_poste
                ORDER BY COUNT(*) DESC
                LIMIT 1
            ) AS poste_prefere

        FROM Joueur j
        LEFT JOIN Participer p ON j.id_joueur = p.id_joueur
        LEFT JOIN Match_basket m ON p.id_match = m.id_match
        GROUP BY j.id_joueur
        ORDER BY j.nom, j.prenom
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getSelectionsConsecutives($id_joueur) {
        $sql = "
            SELECT m.id_match
            FROM Match_basket m
            LEFT JOIN Participer p 
                ON m.id_match = p.id_match 
                AND p.id_joueur = :id
            WHERE m.resultat != 'À venir'
            ORDER BY m.date_heure DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id_joueur, PDO::PARAM_INT);
        $stmt->execute();

        $count = 0;
        foreach ($stmt->fetchAll() as $row) {
            if ($row['id_match'] === null) {
                break; // rupture de la série
            }
            $count++;
        }
        return $count;
    }


}