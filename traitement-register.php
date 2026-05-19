<?php
require 'config-db.php';
require 'funcs-auth.php';
require 'security.php';
initSession();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header('Location: register.html');
    exit;
}

$user = trim($_POST['reg-user'] ?? '');
$mail = trim($_POST['reg-mail'] ?? '');
$pass = $_POST['reg-pass'] ?? '';

// Validation côté serveur
if (empty($user) || empty($mail) || empty($pass)) {
    logSecurity("Inscription échouée : champs vides - user=$user");
    header('Location: register.html?error=champs');
    exit;
}

if (strlen($user) < 2 || strlen($user) > 30) {
    header('Location: register.html?error=champs');
    exit;
}

if (!isValidEmail($mail)) {
    header('Location: register.html?error=champs');
    exit;
}

if (strlen($pass) < 6) {
    header('Location: register.html?error=mdp');
    exit;
}

// Protection caractères dangereux dans le username
if (preg_match('/[<>"\'%;()&]/', $user)) {
    logSecurity("Inscription bloquée : caractères suspects dans username - $user");
    header('Location: register.html?error=champs');
    exit;
}

$sql = "INSERT INTO users (user, mail, passwd, role, points_fidelite)
        VALUES (CAST(:u AS BINARY), :m, SHA1(:p), 'user', 0)";
$stmt = $pdo->prepare($sql);

try {
    $stmt->execute(['u' => $user, 'm' => $mail, 'p' => $pass]);
    logSecurity("Inscription réussie : $user");
    header('Location: login.html?success=1');
    exit;
} catch (PDOException $e) {
    logSecurity("Inscription échouée : doublon - $user");
    header('Location: register.html?error=doublon');
    exit;
}
?>