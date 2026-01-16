<?php
session_start();
require_once __DIR__ . '/../../controllers/MatchController.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../views/login.php');
    exit();
}

$matchController = new MatchController();

// Récupérer tous les matchs
$tousMatchs = $matchController->getAll();

// Séparer les matchs
$matchsAVenir = [];
$matchsTerminesAvecResultat = [];
$matchsTerminesSansResultat = [];

$dateActuelle = time();

foreach ($tousMatchs as $match) {
    $dateMatch = strtotime($match['date_heure']);
    
    if ($dateMatch > $dateActuelle) {
        $matchsAVenir[] = $match;
    } else {
        if ($match['resultat'] === 'À venir') {
            $matchsTerminesSansResultat[] = $match;
        } else {
            $matchsTerminesAvecResultat[] = $match;
        }
    }
}

$message = '';
if (isset($_GET['success'])) {
    $message = "success:" . htmlspecialchars($_GET['success']);
}
if (isset($_GET['error'])) {
    $message = "error:" . htmlspecialchars($_GET['error']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Matchs</title>
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
                    <a class="lien-navigation" href="../statistiques/stats.php">Statistiques</a>
                    <a class="lien-navigation actif" href="liste.php">Matchs</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="conteneur page">
        <div class="entete-page">
            <h1>Liste des Matchs</h1>
            <a href="ajouter.php" class="bouton bouton-succes">+ Nouveau Match</a>
        </div>

        <?php 
        if ($message) {
            $type = strpos($message, 'success:') === 0 ? 'succes' : 'danger';
            $texte = str_replace(['success:', 'error:'], '', $message);
            echo '<div class="alerte alerte-' . $type . ' marge-bas">' . $texte . '</div>';
        }
        ?>

        <!-- Section des Matchs à Venir -->
        <div class="marge-bas-grande">
            <div class="section-header">
                <div class="section-title">
                    <h2>Matchs à Venir</h2>
                    <span class="badge fond-avertissement"><?= count($matchsAVenir) ?> match(s)</span>
                </div>
            </div>

            <?php if (empty($matchsAVenir)): ?>
                <div class="carte">
                    <div class="corps-carte centrer-texte">
                        <p class="texte-mute">Aucun match à venir.</p>
                        <a href="ajouter.php" class="bouton bouton-succes marge-haut">Planifier un match</a>
                    </div>
                </div>
            <?php else: ?>
                <table class="tableau tableau-bande tableau-survol">
                    <thead class="entete-tableau-sombre">
                        <tr>
                            <th>Date</th>
                            <th>Équipe Adverse</th>
                            <th>Lieu</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matchsAVenir as $match): 
                            $dateMatch = strtotime($match['date_heure']);
                            $peutModifier = $dateMatch > $dateActuelle;
                            $peutSupprimer = $dateMatch > $dateActuelle;
                        ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($match['date_heure'])) ?></td>
                            <td><?= htmlspecialchars($match['equipe_adverse']) ?></td>
                            <td>
                                <span class="badge <?= $match['lieu'] === 'Domicile' ? 'fond-succes' : 'fond-info' ?>">
                                    <?= $match['lieu'] ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge-a-venir">À venir</span>
                            </td>
                            <td>
                                <div class="ligne" style="gap: 5px; flex-wrap: nowrap;">
                                    <a href="feuille.php?id=<?= $match['id_match'] ?>" 
                                       class="bouton bouton-petit bouton-composer"
                                       title="Composer la feuille de match">
                                        Composer
                                    </a>
                                    <?php if ($peutModifier): ?>
                                    <a href="modifier.php?id=<?= $match['id_match'] ?>" 
                                       class="bouton bouton-petit bouton-modifier"
                                       title="Modifier le match">
                                        Modifier
                                    </a>
                                    <?php endif; ?>
                                    <?php if ($peutSupprimer): ?>
                                    <a href="supprimer.php?id=<?= $match['id_match'] ?>" 
                                       class="bouton bouton-petit bouton-supprimer"
                                       title="Supprimer le match"
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce match ?');">
                                        Supprimer
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Section des Matchs Terminés SANS résultat -->
        <?php if (!empty($matchsTerminesSansResultat)): ?>
        <div class="marge-bas-grande">
            <div class="section-header">
                <div class="section-title">
                    <h2>Matchs Terminés (sans résultat)</h2>
                    <span class="badge fond-danger"><?= count($matchsTerminesSansResultat) ?> match(s)</span>
                </div>
                <small class="texte-mute">Ces matchs sont passés mais n'ont pas encore de résultat</small>
            </div>

            <table class="tableau tableau-bande tableau-survol">
                <thead class="entete-tableau-sombre">
                    <tr>
                        <th>Date</th>
                        <th>Équipe Adverse</th>
                        <th>Lieu</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($matchsTerminesSansResultat as $match): 
                        $dateMatch = strtotime($match['date_heure']);
                        $peutModifier = false; // Les matchs passés ne peuvent plus être modifiés
                    ?>
                    <tr class="match-passe">
                        <td><?= date('d/m/Y H:i', strtotime($match['date_heure'])) ?></td>
                        <td><?= htmlspecialchars($match['equipe_adverse']) ?></td>
                        <td>
                            <span class="badge <?= $match['lieu'] === 'Domicile' ? 'fond-succes' : 'fond-info' ?>">
                                <?= $match['lieu'] ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge-sans-resultat">
                                Résultat manquant
                            </span>
                        </td>
                        <td>
                            <div class="ligne" style="gap: 5px; flex-wrap: nowrap;">
                                <a href="feuille.php?id=<?= $match['id_match'] ?>" 
                                   class="bouton bouton-petit bouton-composer"
                                   title="Voir la feuille de match">
                                    Voir
                                </a>
                                <a href="noter_joueurs.php?id=<?= $match['id_match'] ?>" 
                                   class="bouton bouton-petit bouton-info"
                                   title="Noter les joueurs">
                                    Noter
                                </a>
                                <a href="modifier.php?id=<?= $match['id_match'] ?>" 
                                   class="bouton bouton-petit bouton-modifier <?= $peutModifier ? '' : 'bouton-disabled' ?>"
                                   title="Mettre à jour le résultat"
                                   <?= !$peutModifier ? 'onclick="alert(\'Impossible de modifier un match qui a déjà eu lieu\'); return false;"' : '' ?>>
                                    Mettre résultat
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Section des Matchs Terminés AVEC résultat -->
        <div>
            <div class="section-header">
                <div class="section-title">
                    <h2>Matchs Terminés (avec résultat)</h2>
                    <span class="badge fond-info"><?= count($matchsTerminesAvecResultat) ?> match(s)</span>
                </div>
            </div>

            <?php if (empty($matchsTerminesAvecResultat)): ?>
                <div class="carte">
                    <div class="corps-carte centrer-texte">
                        <p class="texte-mute">Aucun match terminé avec résultat.</p>
                    </div>
                </div>
            <?php else: ?>
                <table class="tableau tableau-bande tableau-survol">
                    <thead class="entete-tableau-sombre">
                        <tr>
                            <th>Date</th>
                            <th>Équipe Adverse</th>
                            <th>Lieu</th>
                            <th>Résultat</th>
                            <th>Score</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matchsTerminesAvecResultat as $match): 
                            $dateMatch = strtotime($match['date_heure']);
                            $peutModifier = false; // Les matchs passés ne peuvent plus être modifiés
                        ?>
                        <tr class="match-passe">
                            <td><?= date('d/m/Y H:i', strtotime($match['date_heure'])) ?></td>
                            <td><?= htmlspecialchars($match['equipe_adverse']) ?></td>
                            <td>
                                <span class="badge <?= $match['lieu'] === 'Domicile' ? 'fond-succes' : 'fond-info' ?>">
                                    <?= $match['lieu'] ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $match['resultat'] === 'Victoire' ? 'fond-succes' : ($match['resultat'] === 'Nul' ? 'fond-avertissement' : 'fond-danger') ?>">
                                    <?= $match['resultat'] ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($match['score_propre']) && !empty($match['score_adverse'])): ?>
                                    <?= $match['score_propre'] ?> - <?= $match['score_adverse'] ?>
                                <?php else: ?>
                                    <span class="texte-mute">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="ligne" style="gap: 5px; flex-wrap: nowrap;">
                                    <a href="feuille.php?id=<?= $match['id_match'] ?>" 
                                       class="bouton bouton-petit bouton-composer"
                                       title="Voir la feuille de match">
                                        Voir
                                    </a>
                                    <a href="noter_joueurs.php?id=<?= $match['id_match'] ?>" 
                                       class="bouton bouton-petit bouton-info"
                                       title="Noter les joueurs">
                                        Noter
                                    </a>
                                    <a href="modifier.php?id=<?= $match['id_match'] ?>" 
                                       class="bouton bouton-petit bouton-modifier <?= $peutModifier ? '' : 'bouton-disabled' ?>"
                                       title="Modifier le match"
                                       <?= !$peutModifier ? 'onclick="alert(\'Impossible de modifier un match qui a déjà eu lieu\'); return false;"' : '' ?>>
                                        Modifier
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>