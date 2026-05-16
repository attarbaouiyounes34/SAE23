<?php
// On charge la configuration de la BDD et les fonctions d'authentification
require 'config-db.php';
require 'funcs-auth.php';

// On initialise la session
initSession();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['user'];
    $pass = $_POST['pass'];

    // On utilise la fonction auth()
    $userData = auth($pdo, $user, $pass);

    if ($userData) {
        // Si ça marche, on connecte l'utilisateur
        loginUser($userData);

        // Redirection vers l'espace utilisateur et arrêt du script
        header('Location: mes-commandes.html');
        exit;
    } else {
        echo "Identifiant ou mot de passe incorrect.";
    }
}
?>
