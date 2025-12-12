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

// Charger les joueurs actifs
$tousJoueurs = $joueurController->getAll();
$joueursActifs = array_filter($tousJoueurs, function($joueur) {
    return $joueur['statut'] === 'Actif';
});

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
    
    // Récupérer les titulaires
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
        }
    }
    
    // Récupérer les remplaçants
    $nombreRemplacants = $_POST['remplacants_count'] ?? 5;
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
        }
    }
    
    // Validation
    if ($nombreTitulaires < 5) {
        $message = "error:Erreur : 5 titulaires requis (trouvés : $nombreTitulaires)";
    } elseif (count($participants) < 8) {
        $message = "error:Erreur : 8 joueurs minimum requis (actuellement " . count($participants) . ")";
    } else {
        if ($matchController->setParticipants($idMatch, $participants)) {
            $message = "success:Feuille de match enregistrée avec succès !";
            
            // Recharger toutes les données
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
            <h1>Feuille de Match 
                <?php if ($matchPasse): ?>
                    <span class="badge fond-info">Match passé</span>
                <?php elseif ($matchAVenir): ?>
                    <span class="badge fond-avertissement">Match à venir</span>
                <?php endif; ?>
            </h1>
            <a href="liste.php" class="bouton bouton-retour">← Retour à la liste</a>
        </div>

        <!-- Informations du match -->
        <div class="carte marge-bas">
            <div class="corps-carte">
                <h4 class="titre-carte"><?= htmlspecialchars($match['equipe_adverse']) ?> vs Notre Équipe</h4>
                <p class="texte-carte">
                    <strong>Date:</strong> <?= date('d/m/Y H:i', strtotime($match['date_heure'])) ?> | 
                    <strong>Lieu:</strong> 
                    <span class="badge <?= $match['lieu'] === 'Domicile' ? 'fond-succes' : 'fond-info' ?>">
                        <?= $match['lieu'] ?>
                    </span>
                    <?php if ($match['resultat'] !== 'À venir'): ?>
                        | <strong>Résultat:</strong>
                        <span class="badge <?= $match['resultat'] === 'Victoire' ? 'fond-succes' : ($match['resultat'] === 'Nul' ? 'fond-avertissement' : 'fond-danger') ?>">
                            <?= $match['resultat'] ?>
                            <?php if (!empty($match['score_propre']) && !empty($match['score_adverse'])): ?>
                                (<?= $match['score_propre'] ?>-<?= $match['score_adverse'] ?>)
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                </p>
                <?php if ($matchPasse): ?>
                    <div class="alerte alerte-info">
                        <strong>Mode consultation :</strong> Ce match étant passé, la feuille de match est en lecture seule.
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
                    <h5 class="titre-section">Titulaires 
                        <small class="texte-blanc">(<?= count($titulaires) ?>/5 sélectionnés)</small>
                    </h5>
                </div>
                <div class="corps-carte">
                    <div class="ligne">
                        <?php foreach ($postes as $poste): 
                            $actuel = $titulaires[$poste] ?? null;
                        ?>
                        <div class="tablette-demi marge-bas">
                            <label class="etiquette-formulaire">
                                <strong><?= $poste ?></strong>
                            </label>
                            <?php if ($matchAVenir): ?>
                                <select class="selection-formulaire selection-titulaire" 
                                        name="titulaire_<?= $poste ?>" 
                                        required 
                                        onchange="mettreAJourCompteurTitulaires()">
                                    <option value="">-- Choisir un joueur --</option>
                                    <?php foreach ($joueursActifs as $joueur): 
                                        $selectionne = $actuel && $actuel['id_joueur'] == $joueur['id_joueur'] ? 'selected' : '';
                                    ?>
                                    <option value="<?= $joueur['id_joueur'] ?>" <?= $selectionne ?>>
                                        #<?= $joueur['numero_licence'] ?> - 
                                        <?= $joueur['prenom'] . ' ' . $joueur['nom'] ?>
                                        (<?= $joueur['taille'] ?>cm)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <div class="zone-lecture-seule">
                                    <?php if ($actuel): ?>
                                        <div class="joueur-selectionne">
                                            <strong>#<?= $actuel['numero_licence'] ?></strong> - 
                                            <?= $actuel['prenom'] . ' ' . $actuel['nom'] ?>
                                            <small class="texte-mute">(<?= $joueursActifs[$actuel['id_joueur']]['taille'] ?? '?' ?>cm)</small>
                                        </div>
                                    <?php else: ?>
                                        <div class="texte-mute">Poste non attribué</div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($matchAVenir): ?>
                    <div class="marge-haut">
                        <small class="texte-mute" id="compteurTitulaires">
                            Titulaires sélectionnés: <?= count($titulaires) ?>/5
                        </small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Section Remplaçants -->
            <div class="carte marge-bas">
                <div class="entete-carte entete-avertissement">
                    <h5 class="titre-section">Remplaçants 
                        <small class="texte-blanc">(<?= count($remplacants) ?> enregistrés)</small>
                    </h5>
                </div>
                <div class="corps-carte">
                    <?php for ($i = 0; $i < 5; $i++): 
                        $actuel = $remplacants[$i] ?? null;
                    ?>
                    <div class="ligne marge-bas">
                        <div class="tablette-demi">
                            <label class="etiquette-formulaire">Remplaçant <?= $i + 1 ?></label>
                            <?php if ($matchAVenir): ?>
                                <select class="selection-formulaire" name="remplacant_joueur_<?= $i ?>">
                                    <option value="">-- Choisir un joueur --</option>
                                    <?php foreach ($joueursActifs as $joueur): 
                                        $selectionne = $actuel && $actuel['id_joueur'] == $joueur['id_joueur'] ? 'selected' : '';
                                    ?>
                                    <option value="<?= $joueur['id_joueur'] ?>" <?= $selectionne ?>>
                                        #<?= $joueur['numero_licence'] ?> - 
                                        <?= $joueur['prenom'] . ' ' . $joueur['nom'] ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <div class="zone-lecture-seule">
                                    <?php if ($actuel): ?>
                                        <div class="joueur-selectionne">
                                            <strong>#<?= $actuel['numero_licence'] ?></strong> - 
                                            <?= $actuel['prenom'] . ' ' . $actuel['nom'] ?>
                                            <small class="texte-mute">(Poste: <?= $actuel['libelle_poste'] ?>)</small>
                                        </div>
                                    <?php else: ?>
                                        <div class="texte-mute">Non attribué</div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="tablette-demi">
                            <label class="etiquette-formulaire">Poste principal</label>
                            <?php if ($matchAVenir): ?>
                                <select class="selection-formulaire" name="remplacant_poste_<?= $i ?>">
                                    <option value="">-- Choisir un poste --</option>
                                    <?php foreach ($postes as $poste): 
                                        $selectionne = $actuel && $actuel['libelle_poste'] == $poste ? 'selected' : '';
                                    ?>
                                    <option value="<?= $poste ?>" <?= $selectionne ?>>
                                        <?= $poste ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <div class="zone-lecture-seule">
                                    <?php if ($actuel): ?>
                                        <?= $actuel['libelle_poste'] ?>
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
                </div>
            </div>

            <?php if ($matchAVenir): ?>
                <div class="centrer-texte">
                    <button type="submit" class="bouton bouton-succes bouton-grand">Enregistrer la feuille de match</button>
                    <button type="button" class="bouton bouton-retour bouton-grand" onclick="previsualiserFeuille()">Prévisualiser</button>
                </div>
            </form>
            <?php else: ?>
                <div class="centrer-texte">
                    <a href="liste.php" class="bouton bouton-retour bouton-grand">Retour aux matchs</a>
                    <?php if ($match['resultat'] === 'À venir'): ?>
                        <a href="modifier.php?id=<?= $idMatch ?>" class="bouton bouton-modifier bouton-grand">Modifier le match</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        <?php if ($matchAVenir): ?>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($matchAVenir): ?>
    <script>
        function mettreAJourCompteurTitulaires() {
            const selections = document.querySelectorAll('.selection-titulaire');
            let compte = 0;
            
            selections.forEach(selection => {
                if (selection.value && selection.value !== '') {
                    compte++;
                }
            });
            
            document.getElementById('compteurTitulaires').textContent = `Titulaires sélectionnés: ${compte}/5`;
            
            const elementCompte = document.getElementById('compteurTitulaires');
            if (compte === 5) {
                elementCompte.className = 'texte-succes';
            } else if (compte >= 3) {
                elementCompte.className = 'texte-avertissement';
            } else {
                elementCompte.className = 'texte-danger';
            }
        }

        function previsualiserFeuille() {
            const selections = document.querySelectorAll('.selection-titulaire');
            let compte = 0;
            
            selections.forEach(selection => {
                if (selection.value && selection.value !== '') {
                    compte++;
                }
            });
            
            if (compte === 5) {
                alert('Composition valide ! 5/5 titulaires sélectionnés.');
            } else {
                alert(`Problème : ${compte}/5 titulaires sélectionnés.`);
            }
        }

        // Initialiser le compteur au chargement
        document.addEventListener('DOMContentLoaded', function() {
            mettreAJourCompteurTitulaires();
        });
    </script>
    <?php endif; ?>
</body>
</html>