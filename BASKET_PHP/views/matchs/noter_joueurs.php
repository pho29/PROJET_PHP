<?php
session_start();
require_once __DIR__ . '/../../controllers/MatchController.php';
require_once __DIR__ . '/../../controllers/JoueurController.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../views/login.php');
    exit();
}

$match_id = $_GET['id'] ?? null;
if (!$match_id) {
    header('Location: liste.php?error=Match non spécifié');
    exit();
}

$matchController = new MatchController();
$joueurController = new JoueurController();

$match = $matchController->getById($match_id);
if (!$match) {
    header('Location: liste.php?error=Match non trouvé');
    exit();
}

// Vérifier si le match a déjà eu lieu
$dateMatch = strtotime($match['date_heure']);
$dateActuelle = time();

// Un match ne peut être noté que s'il a déjà eu lieu
if ($dateMatch > $dateActuelle) {
    header('Location: liste.php?error=Impossible de noter les joueurs pour un match à venir');
    exit();
}

$participants = $matchController->getParticipants($match_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $toutesEvaluationsValides = true;
        
        foreach ($_POST['evaluations'] as $joueur_id => $evaluation) {
            $evaluation = trim($evaluation);
            
            if ($evaluation === '') {
                $error = "Tous les joueurs doivent être notés (1 à 5)";
                $toutesEvaluationsValides = false;
                break;
            }
            
            if (!is_numeric($evaluation) || $evaluation < 1 || $evaluation > 5) {
                $error = "Les notes doivent être comprises entre 1 et 5";
                $toutesEvaluationsValides = false;
                break;
            }
            
            $evaluation = (int)$evaluation;
            $matchController->updateParticipantEvaluation($match_id, $joueur_id, $evaluation);
        }
        
        if ($toutesEvaluationsValides) {
            header('Location: liste.php?success=Évaluations enregistrées avec succès');
            exit();
        }
    } catch (Exception $e) {
        $error = "Erreur lors de l'enregistrement des évaluations: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Noter les Joueurs</title>
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
            <h1>Noter les joueurs</h1>
            <a href="liste.php" class="bouton bouton-info">Retour à la liste</a>
        </div>

        <div class="carte marge-bas">
            <div class="entete-carte">
                <h2><?= htmlspecialchars($match['equipe_adverse']) ?></h2>
                <span class="badge <?= $match['lieu'] === 'Domicile' ? 'fond-succes' : 'fond-info' ?>">
                    <?= $match['lieu'] ?>
                </span>
            </div>
            <div class="corps-carte">
                <p><strong>Date:</strong> <?= date('d/m/Y H:i', strtotime($match['date_heure'])) ?></p>
                <p><strong>Résultat:</strong> 
                    <span class="badge <?= $match['resultat'] === 'Victoire' ? 'fond-succes' : ($match['resultat'] === 'Nul' ? 'fond-avertissement' : 'fond-danger') ?>">
                        <?= $match['resultat'] ?>
                    </span>
                </p>
                <?php if (!empty($match['score_propre']) && !empty($match['score_adverse'])): ?>
                    <p><strong>Score:</strong> <?= $match['score_propre'] ?> - <?= $match['score_adverse'] ?></p>
                <?php endif; ?>
                <?php if ($match['commentaire_match']): ?>
                    <p><strong>Commentaire:</strong> <?= htmlspecialchars($match['commentaire_match']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alerte alerte-danger marge-bas"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($match['resultat'] === 'À venir'): ?>
            <div class="alerte alerte-avertissement marge-bas">
                <strong>Attention:</strong> Ce match est marqué comme "À venir". Veuillez d'abord mettre à jour le résultat avant de noter les joueurs.
                <div class="marge-haut">
                    <a href="modifier.php?id=<?= $match_id ?>" class="bouton bouton-avertissement">Mettre à jour le résultat</a>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="carte">
                <div class="entete-carte">
                    <h3>Évaluations des joueurs</h3>
                    <small class="texte-mute">Sélectionnez une note de 1 à 5 pour chaque joueur</small>
                </div>
                <div class="corps-carte">
                    <?php if (empty($participants)): ?>
                        <p class="texte-mute centrer-texte">Aucun joueur n'a participé à ce match.</p>
                        <div class="centrer-texte marge-haut">
                            <a href="feuille.php?id=<?= $match_id ?>" class="bouton bouton-info">Composer la feuille de match</a>
                        </div>
                    <?php else: ?>
                        <table class="tableau tableau-bande">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Prénom</th>
                                    <th>Licence</th>
                                    <th>Poste</th>
                                    <th>Statut</th>
                                    <th>Note actuelle</th>
                                    <th>Nouvelle note</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($participants as $participant): ?>
                                <tr>
                                    <td><?= htmlspecialchars($participant['nom']) ?></td>
                                    <td><?= htmlspecialchars($participant['prenom']) ?></td>
                                    <td><?= htmlspecialchars($participant['numero_licence']) ?></td>
                                    <td>
                                        <?php if ($participant['libelle_poste']): ?>
                                            <span class="badge fond-info"><?= $participant['libelle_poste'] ?></span>
                                        <?php else: ?>
                                            <span class="texte-mute">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $participant['titulaire'] ? 'fond-succes' : 'fond-info' ?>">
                                            <?= $participant['titulaire'] ? 'Titulaire' : 'Remplaçant' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($participant['evaluation']): ?>
                                            <span class="badge fond-avertissement">
                                                <?= $participant['evaluation'] ?>/5
                                            </span>
                                        <?php else: ?>
                                            <span class="texte-mute">Non noté</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <select class="selection-formulaire" name="evaluations[<?= $participant['id_joueur'] ?>]" required>
                                            <option value="">-- Note --</option>
                                            <option value="1" <?= ($participant['evaluation'] ?? '') == 1 ? 'selected' : '' ?>>1 - Insuffisant</option>
                                            <option value="2" <?= ($participant['evaluation'] ?? '') == 2 ? 'selected' : '' ?>>2 - Passable</option>
                                            <option value="3" <?= ($participant['evaluation'] ?? '') == 3 ? 'selected' : '' ?>>3 - Bien</option>
                                            <option value="4" <?= ($participant['evaluation'] ?? '') == 4 ? 'selected' : '' ?>>4 - Très bien</option>
                                            <option value="5" <?= ($participant['evaluation'] ?? '') == 5 ? 'selected' : '' ?>>5 - Excellent</option>
                                        </select>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div class="marge-haut">
                            <div class="alerte alerte-info">
                                <strong>Guide d'évaluation:</strong>
                                <ul>
                                    <li><strong>1 - Insuffisant:</strong> Performance très en dessous des attentes</li>
                                    <li><strong>2 - Passable:</strong> Performance acceptable mais peut mieux faire</li>
                                    <li><strong>3 - Bien:</strong> Performance conforme aux attentes</li>
                                    <li><strong>4 - Très bien:</strong> Performance au-dessus des attentes</li>
                                    <li><strong>5 - Excellent:</strong> Performance exceptionnelle</li>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($participants)): ?>
                <div class="pied-carte">
                    <div class="ligne" style="justify-content: flex-end; gap: 10px;">
                        <a href="liste.php" class="bouton bouton-info">Annuler</a>
                        <button type="submit" class="bouton bouton-succes">
                            Enregistrer les notes
                        </button>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </form>
    </div>
</body>
</html>