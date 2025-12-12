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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $numeroLicence = trim($_POST['numero_licence'] ?? '');
    $dateNaissance = $_POST['date_naissance'] ?? '';
    $taille = $_POST['taille'] ?? '';
    $poids = $_POST['poids'] ?? '';
    $statut = $_POST['statut'] ?? 'Actif';

    try {
        $donneesJoueur = [
            'nom' => $nom,
            'prenom' => $prenom,
            'numero_licence' => $numeroLicence,
            'date_naissance' => $dateNaissance,
            'taille' => (float)$taille,
            'poids' => (float)$poids,
            'statut' => $statut
        ];

        if ($joueurController->modifierJoueur($idJoueur, $donneesJoueur)) {
            $message = "Joueur modifié avec succès!";
            $joueur = $joueurController->getById($idJoueur);
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
    <title>Modifier un Joueur</title>
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
        <div class="ligne centrer">
            <div class="tablette-deux-tiers">
                <div class="carte">
                    <div class="entete-carte entete-avertissement">
                        <h4 class="texte-blanc">Modifier le Joueur</h4>
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
                                <div class="tablette-demi marge-bas">
                                    <label for="nom" class="etiquette-formulaire">Nom *</label>
                                    <input type="text" class="champ-formulaire" id="nom" name="nom" 
                                           value="<?= htmlspecialchars($joueur['nom']) ?>" required>
                                </div>
                                <div class="tablette-demi marge-bas">
                                    <label for="prenom" class="etiquette-formulaire">Prénom *</label>
                                    <input type="text" class="champ-formulaire" id="prenom" name="prenom" 
                                           value="<?= htmlspecialchars($joueur['prenom']) ?>" required>
                                </div>
                            </div>

                            <div class="marge-bas">
                                <label for="numero_licence" class="etiquette-formulaire">Numéro de licence *</label>
                                <input type="text" class="champ-formulaire" id="numero_licence" name="numero_licence" 
                                       value="<?= htmlspecialchars($joueur['numero_licence']) ?>" required>
                            </div>

                            <div class="marge-bas">
                                <label for="date_naissance" class="etiquette-formulaire">Date de naissance *</label>
                                <input type="date" class="champ-formulaire" id="date_naissance" name="date_naissance" 
                                       value="<?= $joueur['date_naissance'] ?>" required>
                            </div>

                            <div class="ligne">
                                <div class="tablette-demi marge-bas">
                                    <label for="taille" class="etiquette-formulaire">Taille (cm) *</label>
                                    <input type="number" step="0.1" class="champ-formulaire" id="taille" name="taille" 
                                           value="<?= $joueur['taille'] ?>" required>
                                </div>
                                <div class="tablette-demi marge-bas">
                                    <label for="poids" class="etiquette-formulaire">Poids (kg) *</label>
                                    <input type="number" step="0.1" class="champ-formulaire" id="poids" name="poids" 
                                           value="<?= $joueur['poids'] ?>" required>
                                </div>
                            </div>

                            <div class="marge-bas">
                                <label for="statut" class="etiquette-formulaire">Statut *</label>
                                <select class="selection-formulaire" id="statut" name="statut" required>
                                    <option value="Actif" <?= $joueur['statut'] === 'Actif' ? 'selected' : '' ?>>Actif</option>
                                    <option value="Blessé" <?= $joueur['statut'] === 'Blessé' ? 'selected' : '' ?>>Blessé</option>
                                    <option value="Suspendu" <?= $joueur['statut'] === 'Suspendu' ? 'selected' : '' ?>>Suspendu</option>
                                    <option value="Absent" <?= $joueur['statut'] === 'Absent' ? 'selected' : '' ?>>Absent</option>
                                </select>
                            </div>

                            <div class="actions-boutons">
                                <a href="liste.php" class="bouton bouton-retour">Retour</a>
                                <button type="submit" class="bouton bouton-modifier">Modifier le joueur</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>