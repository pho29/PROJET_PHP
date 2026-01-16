<?php
session_start();
require_once __DIR__ . '/../../controllers/CommentaireController.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../views/login.php');
    exit();
}

$commentaireController = new CommentaireController();
$idCommentaire = $_GET['id'] ?? 0;

if (!$idCommentaire) {
    header('Location: commentaires.php?error=id_manquant');
    exit();
}

$commentaire = $commentaireController->getById($idCommentaire);

if (!$commentaire) {
    header('Location: commentaires.php?error=commentaire_introuvable');
    exit();
}

$confirmation = isset($_GET['confirm']) && $_GET['confirm'] === 'true';

if ($confirmation) {
    if ($commentaireController->supprimerCommentaire($idCommentaire)) {
        header('Location: commentaires.php?id=' . $commentaire['id_joueur'] . '&success=commentaire_supprime');
        exit();
    } else {
        header('Location: commentaires.php?id=' . $commentaire['id_joueur'] . '&error=erreur_suppression');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supprimer un Commentaire</title>
    <link href="../../css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="barre-navigation">
        <div class="conteneur">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <a class="marque-navigation" href="../index.php">Gestion Basket</a>
                <div class="liens-navigation">
                    <a class="lien-navigation" href="../index.php">Accueil</a>
                    <a class="lien-navigation actif" href="../joueurs/liste.php">Joueurs</a>
                    <a class="lien-navigation" href="../statistiques/stats.php">Statistiques</a>
                    <a class="lien-navigation" href="../matchs/liste.php">Matchs</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="conteneur page">
        <div class="ligne centrer">
            <div class="tablette-un-tiers">
                <div class="carte">
                    <div class="entete-carte entete-danger">
                        <h4 class="texte-blanc">Confirmation de suppression</h4>
                    </div>
                    <div class="corps-carte">
                        <div class="alerte alerte-avertissement">
                            <strong>Attention !</strong> Vous êtes sur le point de supprimer définitivement ce commentaire.
                        </div>

                        <div class="marge-bas">
                            <h5>Commentaire à supprimer :</h5>
                            <p><?= nl2br(htmlspecialchars($commentaire['Texte'])) ?></p>
                            <p><strong>Date :</strong> <?= $commentaire['date_commentaire'] ?></p>
                        </div>

                        <div class="alerte alerte-danger">
                            <strong>Conséquences :</strong>
                            <ul>
                                <li>Le commentaire sera définitivement supprimé</li>
                                <li>Cette action est irréversible</li>
                            </ul>
                        </div>

                        <div class="actions-boutons">
                            <a href="commentaires.php?id=<?= $commentaire['id_joueur'] ?>" class="bouton bouton-retour">Annuler</a>
                            <a href="supprimer.php?id=<?= $idCommentaire ?>&confirm=true" 
                               class="bouton bouton-supprimer" 
                               onclick="return confirm('Êtes-vous ABSOLUMENT SÛR de supprimer ce commentaire ?')">
                                Confirmer la suppression
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
