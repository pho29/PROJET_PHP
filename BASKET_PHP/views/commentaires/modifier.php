<?php
session_start();
require_once __DIR__ . '/../../controllers/CommentaireController.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../views/login.php');
    exit();
}

$commentaireController = new CommentaireController();
$message = '';
$erreurs = [];

$idCommentaire = $_GET['id'] ?? 0;
$commentaire = $commentaireController->getById($idCommentaire);

if (!$commentaire) {
    header('Location: commentaires.php?error=commentaire_introuvable&id=' . ($commentaire['id_joueur'] ?? 0));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $texte = trim($_POST['texte'] ?? '');

    try {
        $donneesCommentaire = [
            'texte' => $texte
        ];

        if ($commentaireController->modifierCommentaire($idCommentaire, $donneesCommentaire)) {
            $message = "Commentaire modifié avec succès !";
            $commentaire = $commentaireController->getById($idCommentaire);
        }
    } catch (Exception $e) {
        $erreurs[] = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un Commentaire</title>
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
            <div class="tablette-deux-tiers">
                <div class="carte">
                    <div class="entete-carte entete-avertissement">
                        <h4 class="texte-blanc">Modifier le Commentaire</h4>
                    </div>
                    <div class="corps-carte">
                        <?php if ($message): ?>
                            <div class="alerte alerte-succes"><?= $message ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($erreurs)): ?>
                            <div class="alerte alerte-danger">
                                <?php foreach ($erreurs as $erreur): ?>
                                    <div><?= $erreur ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="marge-bas">
                                <label for="texte" class="etiquette-formulaire">Commentaire *</label>
                                <textarea class="champ-formulaire" id="texte" name="texte" rows="5" required><?= htmlspecialchars($commentaire['Texte']) ?></textarea>
                            </div>

                            <div class="actions-boutons">
                                <a href="commentaires.php?id=<?= $commentaire['id_joueur'] ?>" class="bouton bouton-retour">Retour</a>
                                <button type="submit" class="bouton bouton-modifier">Modifier le commentaire</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
