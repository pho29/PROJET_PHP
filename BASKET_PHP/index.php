<?php
require_once 'config/auth.php';
require_once 'config/database.php';

Auth::requireAuth();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Basket</title>
</head>
<body>
    <header>
        <div class="header-content">
            <h1>Gestion d'Équipe Basket</h1>
            <div class="user-info">
                <span>Bonjour, <strong><?php echo htmlspecialchars(Auth::getUsername()); ?></strong></span>
                <a href="logout.php" class="logout-btn">Déconnexion</a>
            </div>
        </div>
        <nav class="header-content">
            <a href="index.php">Accueil</a>
            <a href="views/joueurs/liste.php">Joueurs</a>
            <a href="views/matchs/liste.php"> Matchs</a>
            <a href="views/statistiques/dashboard.php">Statistiques</a>
        </nav>
    </header>
    
    <main>
        <div class="welcome-section">
            <h2>Tableau de Bord</h2>
            <p>Bienvenue dans votre application de gestion d'équipe de basket</p>
        </div>
        
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <div class="card-icon"></div>
                <h3>Gestion des Joueurs</h3>
                <p>Ajouter, modifier et gérer les joueurs de votre équipe</p>
                <a href="views/joueurs/liste.php">Accéder aux Joueurs</a>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon"></div>
                <h3>Gestion des Matchs</h3>
                <p>Planifier, modifier et suivre les matchs de l'équipe</p>
                <a href="views/matchs/liste.php">Accéder aux Matchs</a>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon"></div>
                <h3>Statistiques</h3>
                <p>Analyser les performances et statistiques de l'équipe</p>
                <a href="views/statistiques/dashboard.php">Voir les Statistiques</a>
            </div>
        </div>
    </main>
</body>
</html>