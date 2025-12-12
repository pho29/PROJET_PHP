<?php
session_start();
require_once __DIR__ . '/../../controllers/JoueurController.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../views/login.php');
    exit();
}

$joueurController = new JoueurController();
$listeJoueurs = $joueurController->getAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Joueurs</title>
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
            <h1>Liste des Joueurs</h1>
            <a href="ajouter.php" class="bouton bouton-succes">Ajouter un joueur</a>
        </div>

        <?php if (empty($listeJoueurs)): ?>
            <div class="alerte alerte-info">Aucun joueur trouvé.</div>
        <?php else: ?>
            <table class="tableau tableau-bande tableau-survol">
                <thead class="entete-tableau-sombre">
                    <tr>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Licence</th>
                        <th>Date naissance</th>
                        <th>Taille</th>
                        <th>Poids</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listeJoueurs as $joueur): ?>
                    <tr>
                        <td><?= $joueur['nom'] ?></td>
                        <td><?= $joueur['prenom'] ?></td>
                        <td><?= $joueur['numero_licence'] ?></td>
                        <td><?= date('d/m/Y', strtotime($joueur['date_naissance'])) ?></td>
                        <td><?= $joueur['taille'] ?> cm</td>
                        <td><?= $joueur['poids'] ?> kg</td>
                        <td>
                            <?php
                            $classesBadge = [
                                'Actif' => 'fond-succes',
                                'Blessé' => 'fond-avertissement',
                                'Suspendu' => 'fond-danger',
                                'Absent' => 'fond-secondaire'
                            ];
                            $statut = $joueur['statut'];
                            ?>
                            <span class="badge <?= $classesBadge[$statut] ?>"><?= $statut ?></span>
                        </td>
                        <td>
                            <a href="modifier.php?id=<?= $joueur['id_joueur'] ?>" class="bouton bouton-petit bouton-modifier">Modifier</a>
                            <a href="supprimer.php?id=<?= $joueur['id_joueur'] ?>" class="bouton bouton-petit bouton-supprimer" onclick="return confirm('Supprimer ce joueur ?')">Supprimer</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>