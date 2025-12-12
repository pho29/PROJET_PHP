<?php
session_start();
require_once __DIR__ . '/../../controllers/MatchController.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../views/login.php');
    exit();
}

$matchController = new MatchController();
$matchs = $matchController->getAll();

// Séparer les matchs à venir et terminés
$matchsAVenir = [];
$matchsTermines = [];

foreach ($matchs as $match) {
    $dateMatch = strtotime($match['date_heure']);
    $dateActuelle = time();
    $matchAVenir = $dateMatch > $dateActuelle;
    
    if ($matchAVenir || $match['resultat'] === 'À venir') {
        $matchsAVenir[] = $match;
    } else {
        $matchsTermines[] = $match;
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
            <div class="entete-page">
                <h2>Matchs à Venir</h2>
                <span class="badge fond-avertissement"><?= count($matchsAVenir) ?> match(s)</span>
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
                            <th>Résultat</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matchsAVenir as $match): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($match['date_heure'])) ?></td>
                            <td><?= htmlspecialchars($match['equipe_adverse']) ?></td>
                            <td>
                                <span class="badge <?= $match['lieu'] === 'Domicile' ? 'fond-succes' : 'fond-info' ?>">
                                    <?= $match['lieu'] ?>
                                </span>
                            </td>
                            <td>
                                <span class="texte-mute">À venir</span>
                            </td>
                            <td>
                                <div class="ligne" style="gap: 5px; flex-wrap: nowrap;">
                                    <a href="feuille.php?id=<?= $match['id_match'] ?>" 
                                       class="bouton bouton-petit bouton-composer"
                                       title="Composer la feuille de match">
                                        Composer
                                    </a>
                                    <a href="modifier.php?id=<?= $match['id_match'] ?>" 
                                       class="bouton bouton-petit bouton-modifier"
                                       title="Modifier le match">
                                        Modifier
                                    </a>
                                    <a href="supprimer.php?id=<?= $match['id_match'] ?>" 
                                       class="bouton bouton-petit bouton-supprimer"
                                       title="Supprimer le match"
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce match ?');">
                                        Supprimer
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Section des Matchs Terminés -->
        <div>
            <div class="entete-page">
                <h2>Matchs Terminés</h2>
                <span class="badge fond-info"><?= count($matchsTermines) ?> match(s)</span>
            </div>

            <?php if (empty($matchsTermines)): ?>
                <div class="carte">
                    <div class="corps-carte centrer-texte">
                        <p class="texte-mute">Aucun match terminé.</p>
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
                        <?php foreach ($matchsTermines as $match): ?>
                        <tr>
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
                                    <a href="supprimer.php?id=<?= $match['id_match'] ?>" 
                                       class="bouton bouton-petit bouton-supprimer"
                                       title="Supprimer le match"
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce match ?');">
                                        Supprimer
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

    <script>
        // Ajouter un effet de surbrillance sur les matchs récents (moins de 7 jours)
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('tbody tr');
            const aujourdhui = new Date();
            
            rows.forEach(row => {
                const dateCell = row.querySelector('td:first-child');
                if (dateCell) {
                    const dateText = dateCell.textContent.trim();
                    const dateMatch = parseDate(dateText);
                    
                    if (dateMatch) {
                        const diffTime = aujourdhui - dateMatch;
                        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                        
                        // Surbrillance pour les matchs terminés dans les 7 derniers jours
                        if (diffDays <= 7 && diffDays >= 0) {
                            row.style.backgroundColor = '#e8f5e9';
                        }
                    }
                }
            });
            
            function parseDate(dateString) {
                const parts = dateString.split(' ');
                if (parts.length < 2) return null;
                
                const dateParts = parts[0].split('/');
                if (dateParts.length !== 3) return null;
                
                const day = parseInt(dateParts[0]);
                const month = parseInt(dateParts[1]) - 1;
                const year = parseInt(dateParts[2]);
                
                return new Date(year, month, day);
            }
        });
    </script>
</body>
</html>