<?php
// traitement-register.php
require 'config-db.php'; // On charge la connexion PDO

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupération des données du formulaire HTML
    $user = $_POST['reg-user'];
    $mail = $_POST['reg-mail'];
    $pass = $_POST['reg-pass'];

    // Préparation de la requête d'insertion
    $sql = "INSERT INTO users (user, mail, passwd, role, points_fidelite)
    VALUES (CAST(:u AS BINARY), :m, SHA1(:p), 'user', 0)";

    $stmt = $pdo->prepare($sql);

    try {
        $stmt->execute([
            'u' => $user,
            'm' => $mail,
            'p' => $pass
        ]);
        echo "Inscription réussie ! Vous pouvez maintenant vous connecter.";
        // header('Location: login.html'); // Décommenter pour rediriger
    } catch (PDOException $e) {
        echo "Erreur lors de l'inscription : Ce nom d'utilisateur existe peut-être déjà.";
    }
}
?>
