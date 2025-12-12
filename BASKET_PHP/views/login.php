<?php
session_start();
require_once __DIR__ . '/../controllers/AuthController.php';

// Si l'utilisateur est déjà connecté, rediriger vers l'accueil
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$authController = new AuthController();
$erreur = '';

// Initialiser l'admin si nécessaire
$authController->initializeAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($authController->login($username, $password)) {
        header('Location: index.php');
        exit();
    } else {
        $erreur = "Identifiants incorrects";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Gestion Basket</title>
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
    <div class="conteneur page">
        <div class="ligne centrer">
            <div class="colonne-md-6 colonne-lg-4">
                <div class="carte">
                    <div class="entete-carte entete-primaire">
                        <h4 class="texte-blanc centrer-texte">Connexion</h4>
                    </div>
                    <div class="corps-carte">
                        <?php if ($erreur): ?>
                            <div class="alerte alerte-danger"><?= $erreur ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="marge-bas">
                                <label for="username" class="etiquette-formulaire">Nom d'utilisateur</label>
                                <input type="text" class="champ-formulaire" id="username" name="username" 
                                       value="<?= $_POST['username'] ?? 'entraineur' ?>" required>
                            </div>
                            
                            <div class="marge-bas">
                                <label for="password" class="etiquette-formulaire">Mot de passe</label>
                                <input type="password" class="champ-formulaire" id="password" name="password" 
                                       value="<?= $_POST['password'] ?? 'basket123' ?>" required>
                            </div>
                            
                            <div class="marge-bas">
                                <button type="submit" class="bouton bouton-primaire largeur-totale">Se connecter</button>
                            </div>
                            
                            <div class="centrer-texte">
                                <small class="texte-mute">
                                    Identifiants par défaut :<br>
                                    <strong>entraineur</strong> / <strong>basket123</strong>
                                </small>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>