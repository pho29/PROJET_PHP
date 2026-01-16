<?php
require_once __DIR__ . '/../models/CommentaireDAO.php';

class CommentaireController {
    private $commentaireDAO;

    public function __construct() {
        $this->commentaireDAO = new CommentaireDAO();
    }

    /** Compatibilité vue */
    public function getAll() {
        return $this->commentaireDAO->getAll();
    }

    public function getById($id) {
        return $this->commentaireDAO->getById($id);
    }

    public function getByJoueur($id_joueur) {
        return $this->commentaireDAO->getByJoueur($id_joueur);
    }

    public function create($data) {
        return $this->creerCommentaire($data);
    }

    public function update($id, $data) {
        return $this->modifierCommentaire($id, $data);
    }

    public function delete($id) {
        return $this->supprimerCommentaire($id);
    }


    /* ===============================
       MÉTHODES EN FRANÇAIS (nouveau style)
       =============================== */

    /** Liste complète */
    public function listerCommentaires() {
        return $this->commentaireDAO->getAll();
    }

    /** Un seul commentaire */
    public function getCommentaire($id) {
        return $this->commentaireDAO->getById($id);
    }

    /** Commentaires d’un joueur */
    public function getCommentairesJoueur($id_joueur) {
        return $this->commentaireDAO->getByJoueur($id_joueur);
    }

    /** Ajout */
    public function creerCommentaire($data) {
        $erreurs = $this->validerDonnees($data);
        if (!empty($erreurs)) {
            throw new Exception(implode(', ', $erreurs));
        }

        return $this->commentaireDAO->create($data);
    }

    /** Modification */
    public function modifierCommentaire($id, $data) {
        if (empty(trim($data['texte']))) {
            throw new Exception("Le texte du commentaire ne peut pas être vide");
        }

        return $this->commentaireDAO->update($id, $data);
    }

    /** Suppression */
    public function supprimerCommentaire($id) {
        return $this->commentaireDAO->delete($id);
    }

    /** Validation */
    private function validerDonnees($data) {
        $erreurs = [];

        if (empty(trim($data['texte']))) {
            $erreurs[] = "Le texte du commentaire est obligatoire";
        }

        if (empty($data['id_joueur'])) {
            $erreurs[] = "L'id du joueur est obligatoire";
        }

        return $erreurs;
    }
}
