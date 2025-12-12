<?php
session_start();
require_once __DIR__ . '/../../controllers/MatchController.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../views/login.php');
    exit();
}

$matchController = new MatchController();
$message = '';
$erreurs = [];

$idMatch = $_GET['id'] ?? 0;
$match = $matchController->getMatch($idMatch);

if (!$match) {
    header('Location: liste.php?error=match_introuvable');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dateMatch = $_POST['date_match'] ?? '';
    $heureMatch = $_POST['heure_match'] ?? '';
    $equipeAdverse = trim($_POST['equipe_adverse'] ?? '');
    $lieu = $_POST['lieu'] ?? 'Domicile';
    $resultat = $_POST['resultat'] ?? 'À venir';
    $scorePropre = $_POST['score_propre'] ?? '';
    $scoreAdverse = $_POST['score_adverse'] ?? '';
    $commentaireMatch = trim($_POST['commentaire_match'] ?? '');

    try {
        if ($matchController->modifierMatch($idMatch, $_POST)) {
            $message = "Match modifié avec succès!";
            $match = $matchController->getMatch($idMatch);
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
    <title>Modifier un Match</title>
    <link href="../../css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="barre-navigation">
        <div class="conteneur">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <a class="marque-navigation" href="../index.php">Gestion Basket</a>
                <div class="liens-navigation">
                    <a class="lien-navigation" href="../index.php">Accueil</a>
                    <a class="lien-navigation" href="../joueurs/liste.php">Joueurs</a>
                    <a class="lien-navigation" href="liste.php">Matchs</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="conteneur page">
        <div class="ligne centrer">
            <div class="colonne-md-8">
                <div class="carte">
                    <div class="entete-carte entete-primaire">
                        <h4 class="texte-blanc">Modifier le Match</h4>
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
                            <div class="ligne">
                                <div class="colonne-md-6 marge-bas">
                                    <label for="date_match" class="etiquette-formulaire">Date du match *</label>
                                    <input type="date" class="champ-formulaire" id="date_match" name="date_match" 
                                           value="<?= date('Y-m-d', strtotime($match['date_heure'])) ?>" required>
                                </div>
                                <div class="colonne-md-6 marge-bas">
                                    <label for="heure_match" class="etiquette-formulaire">Heure du match *</label>
                                    <input type="time" class="champ-formulaire" id="heure_match" name="heure_match" 
                                           value="<?= date('H:i', strtotime($match['date_heure'])) ?>" required>
                                </div>
                            </div>

                            <div class="marge-bas">
                                <label for="equipe_adverse" class="etiquette-formulaire">Équipe adverse *</label>
                                <input type="text" class="champ-formulaire" id="equipe_adverse" name="equipe_adverse" 
                                       value="<?= $match['equipe_adverse'] ?>" required>
                            </div>

                            <div class="marge-bas">
                                <label for="lieu" class="etiquette-formulaire">Lieu *</label>
                                <select class="selection-formulaire" id="lieu" name="lieu" required>
                                    <option value="Domicile" <?= $match['lieu'] === 'Domicile' ? 'selected' : '' ?>>Domicile</option>
                                    <option value="Extérieur" <?= $match['lieu'] === 'Extérieur' ? 'selected' : '' ?>>Extérieur</option>
                                </select>
                            </div>

                            <div class="marge-bas">
                                <label for="resultat" class="etiquette-formulaire">Résultat *</label>
                                <select class="selection-formulaire" id="resultat" name="resultat" required>
                                    <option value="À venir" <?= $match['resultat'] === 'À venir' ? 'selected' : '' ?>>À venir</option>
                                    <option value="Victoire" <?= $match['resultat'] === 'Victoire' ? 'selected' : '' ?>>Victoire</option>
                                    <option value="Défaite" <?= $match['resultat'] === 'Défaite' ? 'selected' : '' ?>>Défaite</option>
                                    <option value="Nul" <?= $match['resultat'] === 'Nul' ? 'selected' : '' ?>>Nul</option>
                                </select>
                            </div>

                            <div class="ligne">
                                <div class="colonne-md-6 marge-bas">
                                    <label for="score_propre" class="etiquette-formulaire">Score propre</label>
                                    <input type="number" class="champ-formulaire" id="score_propre" name="score_propre" 
                                           value="<?= $match['score_propre'] ?? '' ?>">
                                </div>
                                <div class="colonne-md-6 marge-bas">
                                    <label for="score_adverse" class="etiquette-formulaire">Score adverse</label>
                                    <input type="number" class="champ-formulaire" id="score_adverse" name="score_adverse" 
                                           value="<?= $match['score_adverse'] ?? '' ?>">
                                </div>
                            </div>

                            <div class="marge-bas">
                                <label for="commentaire_match" class="etiquette-formulaire">Commentaire</label>
                                <textarea class="champ-formulaire" id="commentaire_match" name="commentaire_match" 
                                          rows="3"><?= $match['commentaire_match'] ?? '' ?></textarea>
                            </div>

                            <div class="actions-boutons">
                                <a href="liste.php" class="bouton bouton-secondaire">Retour</a>
                                <button type="submit" class="bouton bouton-primaire">Modifier le match</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>