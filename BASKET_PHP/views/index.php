<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - Gestion Basket</title>
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="barre-navigation">
        <div class="conteneur">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <a class="marque-navigation" href="index.php">Gestion Basket</a>
                <div class="liens-navigation">
                    <span class="texte-blanc marge-droite">
                        Bonjour, <strong><?= $_SESSION['username'] ?? 'Utilisateur' ?></strong>
                    </span>
                    <a href="logout.php" class="bouton bouton-retour bouton-petit">Déconnexion</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="conteneur page">
        <!-- En-tête -->
        <div class="ligne marge-bas-grande">
            <div class="largeur-totale centrer-texte">
                <h1>Gestionnaire d'Équipe de Basket</h1>
                <p class="texte-mute">Bienvenue dans votre espace d'entraîneur</p>
            </div>
        </div>

        <!-- Cartes principales -->
        <div class="ligne marge-bas-grande">
            <div class="tablette-demi marge-bas">
                <div class="carte hauteur-totale">
                    <div class="entete-carte entete-primaire">
                        <h3 class="texte-blanc">Gestion des Joueurs</h3>
                    </div>
                    <div class="corps-carte centrer-texte">
                        <p>
                            Gérez votre effectif : ajoutez, modifiez et suivez l'état de vos joueurs.
                            Consultez leurs statistiques et leur disponibilité.
                        </p>
                        <div class="marge-haut">
                            <a href="joueurs/liste.php" class="bouton bouton-composer bouton-grand">
                                Voir les joueurs
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="tablette-demi marge-bas">
                <div class="carte hauteur-totale">
                    <div class="entete-carte entete-succes">
                        <h3 class="texte-blanc">Gestion des Matchs</h3>
                    </div>
                    <div class="corps-carte centrer-texte">
                        <p>
                            Planifiez les rencontres, enregistrez les résultats et analysez les performances
                            de votre équipe.
                        </p>
                        <div class="marge-haut">
                            <a href="matchs/liste.php" class="bouton bouton-succes bouton-grand">
                                Voir les matchs
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        

        <div class="ligne marge-bas-grande">
            <div class="largeur-totale marge-bas">
                <div class="carte hauteur-totale">
                    <div class="entete-carte" style="background-color: #9b59b6; color: white;">
                        <h3 class="texte-blanc">Statistiques</h3>
                    </div>
                    <div class="corps-carte centrer-texte">
                        <p>
                            Analysez les performances et statistiques de l'équipe. 
                            Consultez les données individuelles et collectives pour optimiser vos stratégies.
                        </p>
                        <div class="marge-haut">
                            <a href="statistiques/stats.php" class="bouton" 
                               style="background-color: #9b59b6; color: white; padding: 0.75rem 1.5rem;">
                                Voir les Statistiques
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="pied-page">
        <div class="conteneur centrer-texte">
            <p class="marge-bas-0">
                Système de gestion basket - © <?= date('Y') ?>
            </p>
        </div>
    </footer>
</body>
</html>