<?php
session_start();
require_once __DIR__ . '/../../controllers/JoueurController.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../views/login.php');
    exit();
}

$joueurController = new JoueurController();
$message = '';
$erreurs = [];

$idJoueur = $_GET['id'] ?? 0;
$joueur = $joueurController->getById($idJoueur);

if (!$joueur) {
    header('Location: liste.php?error=joueur_introuvable');
    exit();
}

$commentairesExistants = $joueurController->getCommentairesJoueur($idJoueur);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $commentaire = trim($_POST['commentaire'] ?? '');
    $dateCommentaire = $_POST['date_commentaire'] ?? '';
    
    if (empty($commentaire)) {
        $erreurs[] = "Le commentaire ne peut pas être vide.";
    }
    
    if (empty($dateCommentaire)) {
        $dateCommentaire = date('Y-m-d');
    }
    
    if (empty($erreurs)) {
        if ($joueurController->ajouterCommentaireJoueur($idJoueur, $commentaire, $dateCommentaire)) {
            $message = "success:Commentaire ajouté avec succès !";
            $commentairesExistants = $joueurController->getCommentairesJoueur($idJoueur);
            $_POST = [];
        } else {
            $message = "error:Erreur lors de l'ajout du commentaire.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commentaires du Joueur</title>
    <link href="../../css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="barre-navigation">
        <div class="conteneur">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <a class="marque-navigation" href="../index.php">Gestion Basket</a>
                <div class="liens-navigation">
                    <a class="lien-navigation" href="../index.php">Accueil</a>
                    <a class="lien-navigation actif" href="liste.php">Joueurs</a>
                    <a class="lien-navigation" href="../matchs/liste.php">Matchs</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="conteneur page">
        <div class="entete-page">
            <h1>Commentaires du Joueur</h1>
            <a href="liste.php" class="bouton bouton-retour">← Retour à la liste</a>
        </div>

        <div class="info-joueur marge-bas">
            <h4><?= htmlspecialchars($joueur['prenom'] . ' ' . $joueur['nom']) ?></h4>
            <p>
                <strong>Numéro de licence:</strong> <?= htmlspecialchars($joueur['numero_licence']) ?> | 
                <strong>Taille:</strong> <?= $joueur['taille'] ?>cm | 
                <strong>Poids:</strong> <?= $joueur['poids'] ?>kg |
                <strong>Statut:</strong> 
                <span class="badge <?= 
                    $joueur['statut'] === 'Actif' ? 'fond-succes' : 
                    ($joueur['statut'] === 'Blessé' ? 'fond-avertissement' : 
                    ($joueur['statut'] === 'Suspendu' ? 'fond-danger' : 'fond-secondaire')) 
                ?>">
                    <?= $joueur['statut'] ?>
                </span>
            </p>
        </div>

        <?php 
        if ($message) {
            $type = strpos($message, 'success:') === 0 ? 'succes' : 'danger';
            $texte = str_replace(['success:', 'error:'], '', $message);
            echo '<div class="alerte alerte-' . $type . ' marge-bas">' . $texte . '</div>';
        }
        
        if (!empty($erreurs)): ?>
            <div class="alerte alerte-danger marge-bas">
                <?php foreach ($erreurs as $erreur): ?>
                    <div><?= $erreur ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="ligne">
            <div class="tablette-demi">
                <div class="carte marge-bas">
                    <div class="entete-carte entete-succes">
                        <h5 class="titre-section">Ajouter un commentaire</h5>
                    </div>
                    <div class="corps-carte">
                        <form method="POST">
                            <div class="marge-bas">
                                <label for="date_commentaire" class="etiquette-formulaire">Date du commentaire</label>
                                <input type="date" class="champ-formulaire" id="date_commentaire" name="date_commentaire" 
                                       value="<?= $_POST['date_commentaire'] ?? date('Y-m-d') ?>">
                                <small class="texte-mute">Laisser vide pour utiliser la date d'aujourd'hui</small>
                            </div>

                            <div class="marge-bas">
                                <label for="commentaire" class="etiquette-formulaire">Commentaire *</label>
                                <textarea class="champ-formulaire" id="commentaire" name="commentaire" 
                                          rows="6" placeholder="Entrez votre commentaire ici..." 
                                          required><?= htmlspecialchars($_POST['commentaire'] ?? '') ?></textarea>
                            </div>

                            <div class="actions-boutons">
                                <button type="submit" class="bouton bouton-succes">Ajouter le commentaire</button>
                                <button type="reset" class="bouton bouton-retour">Effacer</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="tablette-demi">
                <div class="carte marge-bas">
                    <div class="entete-carte entete-info">
                        <h5 class="titre-section">Historique des commentaires</h5>
                    </div>
                    <div class="corps-carte">
                        <?php if (empty($commentairesExistants)): ?>
                            <div class="centrer-texte">
                                <p class="texte-mute">Aucun commentaire pour ce joueur.</p>
                            </div>
                        <?php else: ?>
                            <div class="historique-commentaires">
                                <?php 
                                // Trier les commentaires par date décroissante
                                krsort($commentairesExistants);
                                $nbTotalCommentaires = 0;
                                
                                foreach ($commentairesExistants as $date => $commentairesDuJour): 
                                    $nbTotalCommentaires += count($commentairesDuJour);
                                ?>
                                    <div class="commentaire-date marge-bas-petite">
                                        <strong><?= htmlspecialchars($date) ?></strong>
                                    </div>
                                    <?php foreach ($commentairesDuJour as $commentaire): ?>
                                        <div class="commentaire-texte marge-bas">
                                            <?= nl2br(htmlspecialchars($commentaire)) ?>
                                        </div>
                                    <?php endforeach; ?>
                                    <hr class="separateur-commentaire">
                                <?php endforeach; ?>
                            </div>
                            <div class="texte-mute centrer-texte marge-haut">
                                Total: <?= $nbTotalCommentaires ?> commentaire(s)
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="centrer-texte marge-haut">
            <a href="voir.php?id=<?= $idJoueur ?>" class="bouton bouton-info">Voir les détails</a>
            <a href="modifier.php?id=<?= $idJoueur ?>" class="bouton bouton-modifier">Modifier le joueur</a>
            <a href="supprimer.php?id=<?= $idJoueur ?>" class="bouton bouton-supprimer" 
               onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce joueur ?')">Supprimer le joueur</a>
        </div>
    </div>
</body>
</html>