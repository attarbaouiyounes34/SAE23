<?php
// traitement-register.php
require 'config-db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = trim($_POST['reg-user']);
    $mail = trim($_POST['reg-mail']);
    $pass = $_POST['reg-pass'];

    // Validations côté serveur (ne jamais faire confiance au JS seul)
    if (empty($user) || empty($mail) || empty($pass)) {
        header('Location: register.html?error=champs');
        exit;
    }
    if (strlen($pass) < 6) {
        header('Location: register.html?error=mdp');
        exit;
    }

    $sql = "INSERT INTO users (user, mail, passwd, role, points_fidelite)
    VALUES (CAST(:u AS BINARY), :m, SHA1(:p), 'user', 0)";
    $stmt = $pdo->prepare($sql);

    try {
        $stmt->execute(['u' => $user, 'm' => $mail, 'p' => $pass]);
        // Succès : on redirige vers login avec un paramètre dans l'URL
        header('Location: login.html?success=1');
        exit; // TOUJOURS mettre exit après header()
    } catch (PDOException $e) {
        header('Location: register.html?error=doublon');
        exit;
    }
}
?>
