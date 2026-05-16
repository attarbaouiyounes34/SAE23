<?php
require 'config-db.php';
require 'funcs-auth.php';

initSession();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = trim($_POST['user']);
    $pass = $_POST['pass'];

    if (empty($user) || empty($pass)) {
        header('Location: login.html?error=champs');
        exit;
    }

    $userData = auth($pdo, $user, $pass);

    if ($userData) {
        loginUser($userData);
        // Redirection selon le rôle
        if ($_SESSION['user_role'] === 'admin') {
            header('Location: admin.html');
        } else {
            header('Location: mes-commandes.html');
        }
        exit;
    } else {
        header('Location: login.html?error=auth');
        exit;
    }
}
?>
