<?php
session_start();
require_once __DIR__ . '/../../controllers/MatchController.php';
require_once __DIR__ . '/../../controllers/JoueurController.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../views/login.php');
    exit();
}

$matchController = new MatchController();
$joueurController = new JoueurController();

$idMatch = $_GET['id'] ?? 0;
$match = $matchController->getMatch($idMatch);

if (!$match) {
    header('Location: liste.php?error=match_introuvable');
    exit();
}

// Déterminer si le match est à venir ou passé
$dateMatch = strtotime($match['date_heure']);
$dateActuelle = time();
$matchAVenir = $dateMatch > $dateActuelle;
$matchPasse = !$matchAVenir && $match['resultat'] !== 'À venir';
$resultatManquant = !$matchAVenir && $match['resultat'] === 'À venir';

// Charger tous les joueurs avec leurs historiques
$tousJoueurs = $joueurController->getAll();
$joueursActifs = [];

foreach ($tousJoueurs as $joueur) {
    if ($joueur['statut'] === 'Actif') {
        $historique = $joueurController->getHistoriqueJoueur($joueur['id_joueur']);
        $joueursActifs[] = array_merge($joueur, [
            'nb_matchs' => $historique['nb_matchs'] ?? 0,
            'commentaire_general' => $historique['commentaire_general'] ?? '',
            'derniers_matchs' => $historique['derniers_matchs'] ?? []
        ]);
    }
}

// POSTES
$postes = ['Meneur', 'Arrière', 'Ailier', 'Ailier_fort', 'Pivot'];

// Charger les participants
$participantsExistants = $matchController->getParticipants($idMatch);
$titulaires = [];
$remplacants = [];

foreach ($participantsExistants as $participant) {
    if ($participant['titulaire']) {
        $titulaires[$participant['libelle_poste']] = $participant;
    } else {
        $remplacants[] = $participant;
    }
}

$message = '';

// Seulement traiter le POST si le match est à venir
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $matchAVenir) {
    $participants = [];
    $nombreTitulaires = 0;
    $erreurs = [];
    
    foreach ($postes as $poste) {
        $idJoueur = $_POST['titulaire_' . $poste] ?? 0;
        $idJoueur = trim($idJoueur);
        
        if (!empty($idJoueur) && $idJoueur !== '' && $idJoueur !== '0' && is_numeric($idJoueur)) {
            $participants[] = [
                'id_joueur' => (int)$idJoueur,
                'titulaire' => true,
                'evaluation' => null,
                'libelle_poste' => $poste
            ];
            $nombreTitulaires++;
        } else {
            $erreurs[] = "Le poste " . str_replace('_', ' ', $poste) . " doit être attribué";
        }
    }
    
    $nombreRemplacants = $_POST['remplacants_count'] ?? 5;
    $nombreRemplacantsValides = 0;
    
    for ($i = 0; $i < $nombreRemplacants; $i++) {
        $idJoueur = $_POST['remplacant_joueur_' . $i] ?? 0;
        $poste = $_POST['remplacant_poste_' . $i] ?? '';
        
        $idJoueur = trim($idJoueur);
        $poste = trim($poste);
        
        if (!empty($idJoueur) && $idJoueur !== '' && $idJoueur !== '0' && !empty($poste)) {
            $participants[] = [
                'id_joueur' => (int)$idJoueur,
                'titulaire' => false,
                'evaluation' => null,
                'libelle_poste' => $poste
            ];
            $nombreRemplacantsValides++;
        }
    }
    
    if ($nombreTitulaires < 5) {
        $message = "error:Erreur : 5 titulaires requis (trouvés : $nombreTitulaires)";
        if (!empty($erreurs)) {
            $message .= " - " . implode(', ', $erreurs);
        }
    } elseif ($nombreRemplacantsValides < 3) {
        $message = "error:Erreur : 3 remplaçants minimum requis (trouvés : $nombreRemplacantsValides)";
    } else {
        if ($matchController->setParticipants($idMatch, $participants)) {
            $message = "success:Feuille de match enregistrée avec succès !";
            
            $participantsExistants = $matchController->getParticipants($idMatch);
            $titulaires = [];
            $remplacants = [];
            
            foreach ($participantsExistants as $participant) {
                if ($participant['titulaire']) {
                    $titulaires[$participant['libelle_poste']] = $participant;
                } else {
                    $remplacants[] = $participant;
                }
            }
            
        } else {
            $message = "error:Erreur lors de l'enregistrement dans la base de données";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feuille de Match</title>
    <link href="../../css/style.css" rel="stylesheet">
    <style>
        .info-joueur-details {
            font-size: 0.85em;
            color: #666;
            margin-top: 5px;
            padding: 10px;
            border-left: 3px solid #27ae60;
            background-color: #f8f9fa;
            border-radius: 4px;
            display: none;
        }
        .historique-matchs {
            max-height: 120px;
            overflow-y: auto;
            margin-top: 5px;
            padding: 5px;
            background: #fff;
            border-radius: 3px;
            font-size: 0.8em;
            border: 1px solid #dee2e6;
        }
        .match-historique {
            padding: 3px 0;
            border-bottom: 1px solid #eee;
        }
        .match-historique:last-child {
            border-bottom: none;
        }
        .match-date {
            color: #6c757d;
            font-size: 0.85em;
        }
        .match-info {
            margin-left: 10px;
        }
        .commentaire-court {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
            display: inline-block;
            vertical-align: bottom;
        }
        .voir-plus {
            color: #3498db;
            cursor: pointer;
            font-size: 0.9em;
            margin-left: 5px;
            text-decoration: underline;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
    </style>
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
            <h1>Feuille de Match 
                <?php if ($matchPasse): ?>
                    <span class="badge fond-info">Match passé</span>
                <?php elseif ($resultatManquant): ?>
                    <span class="badge fond-danger">Match passé - Résultat manquant</span>
                <?php elseif ($matchAVenir): ?>
                    <span class="badge fond-avertissement">Match à venir</span>
                <?php endif; ?>
            </h1>
            <a href="liste.php" class="bouton bouton-retour">← Retour à la liste</a>
        </div>

        <div class="carte marge-bas">
            <div class="corps-carte">
                <div class="info-match-section">
                    <div class="info-match-item">
                        <h6>Match</h6>
                        <p><strong>Notre Équipe</strong> vs <strong><?= htmlspecialchars($match['equipe_adverse']) ?></strong></p>
                    </div>
                    <div class="info-match-item">
                        <h6>Date & Heure</h6>
                        <p><?= date('d/m/Y H:i', strtotime($match['date_heure'])) ?></p>
                    </div>
                    <div class="info-match-item">
                        <h6>Lieu</h6>
                        <p>
                            <span class="badge <?= $match['lieu'] === 'Domicile' ? 'fond-succes' : 'fond-info' ?>">
                                <?= $match['lieu'] ?>
                            </span>
                        </p>
                    </div>
                    <div class="info-match-item">
                        <h6>Résultat</h6>
                        <p>
                            <?php if ($match['resultat'] !== 'À venir'): ?>
                                <span class="badge <?= $match['resultat'] === 'Victoire' ? 'fond-succes' : ($match['resultat'] === 'Nul' ? 'fond-avertissement' : 'fond-danger') ?>">
                                    <?= $match['resultat'] ?>
                                    <?php if (!empty($match['score_propre']) && !empty($match['score_adverse'])): ?>
                                        (<?= $match['score_propre'] ?>-<?= $match['score_adverse'] ?>)
                                    <?php endif; ?>
                                </span>
                            <?php else: ?>
                                <span class="badge fond-secondaire">À venir</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                
                <?php if ($matchPasse): ?>
                    <div class="alerte alerte-info">
                        <strong>Mode consultation :</strong> Ce match étant passé, la feuille de match est en lecture seule.
                    </div>
                <?php elseif ($resultatManquant): ?>
                    <div class="alerte alerte-danger">
                        <strong>Match terminé sans résultat :</strong> Ce match est passé mais n'a pas encore de résultat. 
                        <a href="modifier.php?id=<?= $idMatch ?>" class="lien-alerte">Mettre à jour le résultat</a>
                    </div>
                <?php elseif ($matchAVenir): ?>
                    <div class="alerte alerte-avertissement">
                        <strong>Mode édition :</strong> Vous pouvez composer l'équipe pour ce match à venir.
                        <div class="compteur-titulaires">
                            Titulaires : <?= count($titulaires) ?>/5
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php 
        if ($message) {
            $type = strpos($message, 'success:') === 0 ? 'succes' : 'danger';
            $texte = str_replace(['success:', 'error:'], '', $message);
            echo '<div class="alerte alerte-' . $type . '">' . $texte . '</div>';
        }
        ?>

        <?php if ($matchAVenir): ?>
            <form method="POST" id="formulaireFeuille">
        <?php else: ?>
            <div id="feuilleLectureSeule">
        <?php endif; ?>

            <!-- Section Titulaires -->
            <div class="carte marge-bas">
                <div class="entete-carte entete-succes">
                    <div class="section-header">
                        <h5 class="titre-section">
                            Titulaires 
                            <small class="texte-blanc">(<?= count($titulaires) ?>/5 sélectionnés)</small>
                        </h5>
                    </div>
                </div>
                <div class="corps-carte">
                    <div class="ligne">
                        <?php foreach ($postes as $poste): 
                            $actuel = $titulaires[$poste] ?? null;
                            $selectedJoueurId = null;
                            
                            if ($matchAVenir && $_SERVER['REQUEST_METHOD'] === 'POST') {
                                $selectedJoueurId = $_POST['titulaire_' . $poste] ?? 0;
                            } elseif ($actuel) {
                                $selectedJoueurId = $actuel['id_joueur'];
                            }
                        ?>
                        <div class="tablette-demi marge-bas">
                            <div class="poste-display">
                                <?= str_replace('_', ' ', $poste) ?>
                                <span>(Poste fixe)</span>
                            </div>
                            
                            <?php if ($matchAVenir): ?>
                                <select class="selection-formulaire selection-titulaire" 
                                        name="titulaire_<?= $poste ?>" 
                                        required 
                                        onchange="afficherDetailsTitulaire(this, <?= array_search($poste, $postes) ?>)"
                                        data-poste="<?= $poste ?>">
                                    <option value="">-- Sélectionner un joueur --</option>
                                    <?php foreach ($joueursActifs as $joueur): 
                                        $selectionne = $selectedJoueurId && $selectedJoueurId == $joueur['id_joueur'] ? 'selected' : '';
                                        $commentaireCourt = substr($joueur['commentaire_general'], 0, 100);
                                        $derniersMatchsJson = htmlspecialchars(json_encode($joueur['derniers_matchs']), ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <option value="<?= $joueur['id_joueur'] ?>" <?= $selectionne ?>
                                            data-taille="<?= $joueur['taille'] ?? 'N/A' ?>"
                                            data-poids="<?= $joueur['poids'] ?? 'N/A' ?>"
                                            data-nbmatchs="<?= $joueur['nb_matchs'] ?? 0 ?>"
                                            data-commentaire="<?= htmlspecialchars($commentaireCourt) ?>"
                                            data-commentairecomplet="<?= htmlspecialchars($joueur['commentaire_general']) ?>"
                                            data-prenom="<?= htmlspecialchars($joueur['prenom']) ?>"
                                            data-nom="<?= htmlspecialchars($joueur['nom']) ?>"
                                            data-derniersmatchs='<?= $derniersMatchsJson ?>'>
                                        #<?= $joueur['numero_licence'] ?> - <?= $joueur['prenom'] . ' ' . $joueur['nom'] ?>
                                        (<?= $joueur['taille'] ?? '?' ?>cm/<?= $joueur['poids'] ?? '?' ?>kg)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="info-joueur-details" id="details_<?= $poste ?>">
                                    <!-- Les détails seront remplis par JavaScript -->
                                </div>
                                
                            <?php else: ?>
                                <div class="zone-lecture-seule">
                                    <?php if ($actuel): 
                                        $joueurInfo = null;
                                        foreach ($joueursActifs as $j) {
                                            if ($j['id_joueur'] == $actuel['id_joueur']) {
                                                $joueurInfo = $j;
                                                break;
                                            }
                                        }
                                    ?>
                                        <div>
                                            <strong>#<?= $actuel['numero_licence'] ?> <?= $actuel['prenom'] . ' ' . $actuel['nom'] ?></strong>
                                            <div class="info-joueur-details" style="display: block;">
                                                <strong>Informations :</strong><br>
                                                Taille: <?= $joueurInfo['taille'] ?? 'N/A' ?>cm | Poids: <?= $joueurInfo['poids'] ?? 'N/A' ?>kg<br>
                                                Matchs joués: <?= $joueurInfo['nb_matchs'] ?? 0 ?><br>
                                                
                                                <?php if (!empty($joueurInfo['commentaire_general'])): 
                                                    $commentaireCourt = substr($joueurInfo['commentaire_general'], 0, 100);
                                                ?>
                                                    <strong>Commentaire :</strong> 
                                                    <?php if (strlen($joueurInfo['commentaire_general']) > 100): ?>
                                                        <span class="commentaire-court"><?= htmlspecialchars($commentaireCourt) ?>...</span>
                                                        <span class="voir-plus" onclick="afficherModalCommentaire('<?= htmlspecialchars($joueurInfo['prenom'] . ' ' . $joueurInfo['nom']) ?>', '<?= htmlspecialchars(addslashes($joueurInfo['commentaire_general'])) ?>')">Voir plus</span><br>
                                                    <?php else: ?>
                                                        <?= htmlspecialchars($joueurInfo['commentaire_general']) ?><br>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($joueurInfo['derniers_matchs']) && count($joueurInfo['derniers_matchs']) > 0): ?>
                                                    <strong>3 derniers matchs :</strong><br>
                                                    <div class="historique-matchs">
                                                        <?php foreach ($joueurInfo['derniers_matchs'] as $matchJoueur): 
                                                            $date = date('d/m/Y', strtotime($matchJoueur['date_heure']));
                                                            $posteMatch = !empty($matchJoueur['libelle_poste']) ? ' (' . str_replace('_', ' ', $matchJoueur['libelle_poste']) . ')' : '';
                                                            $titulaire = $matchJoueur['titulaire'] ? 'Titulaire' : 'Remplaçant';
                                                        ?>
                                                        <div class="match-historique">
                                                            <span class="match-date"><?= $date ?></span>
                                                            <span class="match-info"><?= htmlspecialchars($matchJoueur['equipe_adverse']) ?><?= $posteMatch ?><br><small><?= $titulaire ?></small></span>
                                                        </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="texte-mute">Poste non attribué</div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Section Remplaçants -->
            <div class="carte marge-bas">
                <div class="entete-carte entete-avertissement">
                    <div class="section-header">
                        <h5 class="titre-section">
                            Remplaçants 
                            <small class="texte-blanc">(<?= count($remplacants) ?> sélectionnés)</small>
                        </h5>
                    </div>
                </div>
                <div class="corps-carte">
                    <?php for ($i = 0; $i < 5; $i++): 
                        $actuel = $remplacants[$i] ?? null;
                        $selectedJoueurId = null;
                        $selectedPoste = null;
                        
                        if ($matchAVenir && $_SERVER['REQUEST_METHOD'] === 'POST') {
                            $selectedJoueurId = $_POST['remplacant_joueur_' . $i] ?? 0;
                            $selectedPoste = $_POST['remplacant_poste_' . $i] ?? '';
                        } elseif ($actuel) {
                            $selectedJoueurId = $actuel['id_joueur'];
                            $selectedPoste = $actuel['libelle_poste'];
                        }
                    ?>
                    <div class="ligne marge-bas">
                        <div class="tablette-demi">
                            <div class="poste-display">
                                Remplaçant <?= $i + 1 ?>
                                <span>(Choix libre)</span>
                            </div>
                            <?php if ($matchAVenir): ?>
                                <select class="selection-formulaire selection-remplacant" 
                                        name="remplacant_joueur_<?= $i ?>"
                                        onchange="afficherDetailsRemplacant(<?= $i ?>)"
                                        data-index="<?= $i ?>">
                                    <option value="">-- Sélectionner un joueur --</option>
                                    <?php foreach ($joueursActifs as $joueur): 
                                        $selectionne = $selectedJoueurId && $selectedJoueurId == $joueur['id_joueur'] ? 'selected' : '';
                                        $commentaireCourt = substr($joueur['commentaire_general'], 0, 100);
                                        $derniersMatchsJson = htmlspecialchars(json_encode($joueur['derniers_matchs']), ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <option value="<?= $joueur['id_joueur'] ?>" <?= $selectionne ?>
                                            data-taille="<?= $joueur['taille'] ?? 'N/A' ?>"
                                            data-poids="<?= $joueur['poids'] ?? 'N/A' ?>"
                                            data-nbmatchs="<?= $joueur['nb_matchs'] ?? 0 ?>"
                                            data-commentaire="<?= htmlspecialchars($commentaireCourt) ?>"
                                            data-commentairecomplet="<?= htmlspecialchars($joueur['commentaire_general']) ?>"
                                            data-prenom="<?= htmlspecialchars($joueur['prenom']) ?>"
                                            data-nom="<?= htmlspecialchars($joueur['nom']) ?>"
                                            data-derniersmatchs='<?= $derniersMatchsJson ?>'>
                                        #<?= $joueur['numero_licence'] ?> - <?= $joueur['prenom'] . ' ' . $joueur['nom'] ?>
                                        (<?= $joueur['taille'] ?? '?' ?>cm/<?= $joueur['poids'] ?? '?' ?>kg)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="info-joueur-details" id="details_remplacant_<?= $i ?>">
                                    <!-- Les détails seront remplis par JavaScript -->
                                </div>
                                
                            <?php else: ?>
                                <div class="zone-lecture-seule">
                                    <?php if ($actuel): 
                                        $joueurInfo = null;
                                        foreach ($joueursActifs as $j) {
                                            if ($j['id_joueur'] == $actuel['id_joueur']) {
                                                $joueurInfo = $j;
                                                break;
                                            }
                                        }
                                    ?>
                                        <div>
                                            <strong>#<?= $actuel['numero_licence'] ?> <?= $actuel['prenom'] . ' ' . $actuel['nom'] ?></strong>
                                            <div class="info-joueur-details" style="display: block;">
                                                <strong>Informations :</strong><br>
                                                Taille: <?= $joueurInfo['taille'] ?? 'N/A' ?>cm | Poids: <?= $joueurInfo['poids'] ?? 'N/A' ?>kg<br>
                                                Matchs joués: <?= $joueurInfo['nb_matchs'] ?? 0 ?><br>
                                                
                                                <?php if (!empty($joueurInfo['commentaire_general'])): 
                                                    $commentaireCourt = substr($joueurInfo['commentaire_general'], 0, 100);
                                                ?>
                                                    <strong>Commentaire :</strong> 
                                                    <?php if (strlen($joueurInfo['commentaire_general']) > 100): ?>
                                                        <span class="commentaire-court"><?= htmlspecialchars($commentaireCourt) ?>...</span>
                                                        <span class="voir-plus" onclick="afficherModalCommentaire('<?= htmlspecialchars($joueurInfo['prenom'] . ' ' . $joueurInfo['nom']) ?>', '<?= htmlspecialchars(addslashes($joueurInfo['commentaire_general'])) ?>')">Voir plus</span><br>
                                                    <?php else: ?>
                                                        <?= htmlspecialchars($joueurInfo['commentaire_general']) ?><br>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($joueurInfo['derniers_matchs']) && count($joueurInfo['derniers_matchs']) > 0): ?>
                                                    <strong>3 derniers matchs :</strong><br>
                                                    <div class="historique-matchs">
                                                        <?php foreach ($joueurInfo['derniers_matchs'] as $matchJoueur): 
                                                            $date = date('d/m/Y', strtotime($matchJoueur['date_heure']));
                                                            $posteMatch = !empty($matchJoueur['libelle_poste']) ? ' (' . str_replace('_', ' ', $matchJoueur['libelle_poste']) . ')' : '';
                                                            $titulaire = $matchJoueur['titulaire'] ? 'Titulaire' : 'Remplaçant';
                                                        ?>
                                                        <div class="match-historique">
                                                            <span class="match-date"><?= $date ?></span>
                                                            <span class="match-info"><?= htmlspecialchars($matchJoueur['equipe_adverse']) ?><?= $posteMatch ?><br><small><?= $titulaire ?></small></span>
                                                        </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="texte-mute">Non attribué</div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="tablette-demi">
                            <div class="poste-display">
                                Poste principal
                                <span>(Pour ce match)</span>
                            </div>
                            <?php if ($matchAVenir): ?>
                                <select class="selection-formulaire" name="remplacant_poste_<?= $i ?>">
                                    <option value="">-- Choisir un poste --</option>
                                    <?php foreach ($postes as $poste): 
                                        $selectionne = $selectedPoste && $selectedPoste == $poste ? 'selected' : '';
                                    ?>
                                    <option value="<?= $poste ?>" <?= $selectionne ?>>
                                        <?= str_replace('_', ' ', $poste) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <div class="zone-lecture-seule">
                                    <?php if ($actuel): ?>
                                        <?= str_replace('_', ' ', $actuel['libelle_poste']) ?>
                                    <?php else: ?>
                                        <span class="texte-mute">Non spécifié</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endfor; ?>
                    <?php if ($matchAVenir): ?>
                        <input type="hidden" name="remplacants_count" value="5">
                    <?php endif; ?>
                    
                    <div class="marge-haut">
                        <small class="texte-mute">
                            <strong>Validation :</strong> 
                            <ul>
                                <li>5 titulaires obligatoires (1 par poste)</li>
                                <li>Minimum 3 remplaçants pour un match officiel</li>
                                <li>Tous les postes des titulaires doivent être attribués</li>
                            </ul>
                        </small>
                    </div>
                </div>
            </div>

            <?php if ($matchAVenir): ?>
                <div class="centrer-texte">
                    <button type="submit" class="bouton bouton-succes bouton-grand">
                        Enregistrer la feuille de match
                    </button>
                    <a href="liste.php" class="bouton bouton-retour bouton-grand">
                        Annuler
                    </a>
                </div>
            </form>
            <?php else: ?>
                <div class="centrer-texte">
                    <a href="liste.php" class="bouton bouton-retour bouton-grand">
                        Retour aux matchs
                    </a>
                    <?php if ($resultatManquant): ?>
                        <a href="modifier.php?id=<?= $idMatch ?>" class="bouton bouton-danger bouton-grand">
                            Mettre à jour le résultat
                        </a>
                    <?php endif; ?>
                    <?php if ($matchPasse): ?>
                        <a href="noter_joueurs.php?id=<?= $idMatch ?>" class="bouton bouton-info bouton-grand">
                            Noter les joueurs
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        <?php if ($matchAVenir): ?>
            </form>
        <?php endif; ?>
    </div>

    <!-- Modal pour afficher les commentaires complets -->
    <div id="modalCommentaire" class="modal">
        <div class="modal-content">
            <h4>Commentaire complet</h4>
            <div id="modalContent" style="margin: 15px 0; white-space: pre-line; max-height: 400px; overflow-y: auto;"></div>
            <div class="centrer-texte">
                <button onclick="fermerModal()" class="bouton bouton-retour">Fermer</button>
            </div>
        </div>
    </div>

    <script>
        function afficherDetailsTitulaire(select, index) {
            const postes = ['Meneur', 'Arrière', 'Ailier', 'Ailier_fort', 'Pivot'];
            const poste = postes[index];
            const detailsDiv = document.getElementById(`details_${poste}`);
            
            if (select.selectedIndex > 0) {
                const option = select.options[select.selectedIndex];
                const taille = option.getAttribute('data-taille');
                const poids = option.getAttribute('data-poids');
                const nbMatchs = option.getAttribute('data-nbmatchs');
                const commentaire = option.getAttribute('data-commentaire');
                const commentaireComplet = option.getAttribute('data-commentairecomplet');
                const derniersMatchs = JSON.parse(option.getAttribute('data-derniersmatchs'));
                
                let html = `<strong>Informations :</strong><br>`;
                html += `Taille: ${taille}cm | Poids: ${poids}kg<br>`;
                html += `Matchs joués: ${nbMatchs}<br>`;
                
                if (commentaireComplet && commentaireComplet !== '') {
                    if (commentaireComplet.length > 100) {
                        html += `<strong>Commentaire :</strong> `;
                        html += `<span class="commentaire-court">${commentaire}...</span> `;
                        html += `<span class="voir-plus" onclick="afficherModalCommentaire('${option.getAttribute('data-prenom')} ${option.getAttribute('data-nom')}', '${commentaireComplet.replace(/'/g, "\\'")}')">Voir plus</span><br>`;
                    } else {
                        html += `<strong>Commentaire :</strong> ${commentaireComplet}<br>`;
                    }
                }
                
                if (derniersMatchs && derniersMatchs.length > 0) {
                    html += `<strong>3 derniers matchs :</strong><br>`;
                    html += `<div class="historique-matchs">`;
                    derniersMatchs.forEach(match => {
                        const date = new Date(match.date_heure);
                        const dateStr = date.toLocaleDateString('fr-FR');
                        const posteMatch = match.libelle_poste ? ` (${match.libelle_poste.replace('_', ' ')})` : '';
                        const titulaire = match.titulaire ? 'Titulaire' : 'Remplaçant';
                        
                        html += `<div class="match-historique">
                                    <span class="match-date">${dateStr}</span>
                                    <span class="match-info">${match.equipe_adverse} ${posteMatch}<br><small>${titulaire}</small></span>
                                </div>`;
                    });
                    html += `</div>`;
                }
                
                detailsDiv.innerHTML = html;
                detailsDiv.style.display = 'block';
            } else {
                detailsDiv.style.display = 'none';
            }
        }

        function cacherDetailsTitulaire(index) {
            const postes = ['Meneur', 'Arrière', 'Ailier', 'Ailier_fort', 'Pivot'];
            const poste = postes[index];
            const detailsDiv = document.getElementById(`details_${poste}`);
            detailsDiv.style.display = 'none';
        }

        function afficherDetailsRemplacant(index) {
            const select = document.querySelector(`select[name="remplacant_joueur_${index}"]`);
            const detailsDiv = document.getElementById(`details_remplacant_${index}`);
            
            if (select.selectedIndex > 0) {
                const option = select.options[select.selectedIndex];
                const taille = option.getAttribute('data-taille');
                const poids = option.getAttribute('data-poids');
                const nbMatchs = option.getAttribute('data-nbmatchs');
                const commentaire = option.getAttribute('data-commentaire');
                const commentaireComplet = option.getAttribute('data-commentairecomplet');
                const derniersMatchs = JSON.parse(option.getAttribute('data-derniersmatchs'));
                
                let html = `<strong>Informations :</strong><br>`;
                html += `Taille: ${taille}cm | Poids: ${poids}kg<br>`;
                html += `Matchs joués: ${nbMatchs}<br>`;
                
                if (commentaireComplet && commentaireComplet !== '') {
                    if (commentaireComplet.length > 100) {
                        html += `<strong>Commentaire :</strong> `;
                        html += `<span class="commentaire-court">${commentaire}...</span> `;
                        html += `<span class="voir-plus" onclick="afficherModalCommentaire('${option.getAttribute('data-prenom')} ${option.getAttribute('data-nom')}', '${commentaireComplet.replace(/'/g, "\\'")}')">Voir plus</span><br>`;
                    } else {
                        html += `<strong>Commentaire :</strong> ${commentaireComplet}<br>`;
                    }
                }
                
                if (derniersMatchs && derniersMatchs.length > 0) {
                    html += `<strong>3 derniers matchs :</strong><br>`;
                    html += `<div class="historique-matchs">`;
                    derniersMatchs.forEach(match => {
                        const date = new Date(match.date_heure);
                        const dateStr = date.toLocaleDateString('fr-FR');
                        const posteMatch = match.libelle_poste ? ` (${match.libelle_poste.replace('_', ' ')})` : '';
                        const titulaire = match.titulaire ? 'Titulaire' : 'Remplaçant';
                        
                        html += `<div class="match-historique">
                                    <span class="match-date">${dateStr}</span>
                                    <span class="match-info">${match.equipe_adverse} ${posteMatch}<br><small>${titulaire}</small></span>
                                </div>`;
                    });
                    html += `</div>`;
                }
                
                detailsDiv.innerHTML = html;
                detailsDiv.style.display = 'block';
            } else {
                detailsDiv.style.display = 'none';
            }
        }

        function afficherModalCommentaire(nomJoueur, commentaire) {
            const modal = document.getElementById('modalCommentaire');
            const content = document.getElementById('modalContent');
            
            document.getElementById('modalContent').innerHTML = `
                <h5>${nomJoueur}</h5>
                <div style="margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 4px;">
                    ${commentaire.replace(/\n/g, '<br>')}
                </div>
            `;
            
            modal.style.display = 'flex';
        }

        function fermerModal() {
            document.getElementById('modalCommentaire').style.display = 'none';
        }

        // Initialiser les détails au chargement
        document.addEventListener('DOMContentLoaded', function() {
            // Afficher les détails des titulaires existants
            const postes = ['Meneur', 'Arrière', 'Ailier', 'Ailier_fort', 'Pivot'];
            postes.forEach((poste, index) => {
                const select = document.querySelector(`select[name="titulaire_${poste}"]`);
                if (select && select.value) {
                    afficherDetailsTitulaire(select, index);
                }
            });
            
            // Afficher les détails des remplaçants existants
            for (let i = 0; i < 5; i++) {
                const select = document.querySelector(`select[name="remplacant_joueur_${i}"]`);
                if (select && select.value) {
                    afficherDetailsRemplacant(i);
                }
            }
            
            // Fermer la modal en cliquant en dehors
            document.getElementById('modalCommentaire').addEventListener('click', function(e) {
                if (e.target === this) {
                    fermerModal();
                }
            });
            
            // Fermer la modal avec la touche Échap
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    fermerModal();
                }
            });
        });
    </script>
</body>
</html>