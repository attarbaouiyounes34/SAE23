<?php
require 'config-db.php';
require 'funcs-auth.php';
require 'security.php';
initSession();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header('Location: login.html');
    exit;
}

$user = trim($_POST['user'] ?? '');
$pass = $_POST['pass'] ?? '';

if (empty($user) || empty($pass)) {
    logSecurity("Login échoué : champs vides");
    header('Location: login.html?error=champs');
    exit;
}

// Protection brute force simple : limiter les tentatives
$attempts_key = 'login_attempts_' . session_id();
if (!isset($_SESSION[$attempts_key])) $_SESSION[$attempts_key] = 0;
if (!isset($_SESSION['login_last_attempt'])) $_SESSION['login_last_attempt'] = 0;

// Reset après 15 minutes
if (time() - $_SESSION['login_last_attempt'] > 900) {
    $_SESSION[$attempts_key] = 0;
}

if ($_SESSION[$attempts_key] >= 5) {
    logSecurity("Login bloqué : trop de tentatives pour $user");
    header('Location: login.html?error=bloque');
    exit;
}

$userData = auth($pdo, $user, $pass);

if ($userData) {
    $_SESSION[$attempts_key] = 0;
    loginUser($userData);
    setcookie('cb_user', $userData['user'], time() + 86400, '/');
    setcookie('cb_role', $userData['role'], time() + 86400, '/');
    logSecurity("Login réussi : " . $userData['user']);

    if ($_SESSION['user_role'] === 'admin') {
        header('Location: admin.html');
    } else {
        header('Location: mes-commandes.html');
    }
    exit;
} else {
    $_SESSION[$attempts_key]++;
    $_SESSION['login_last_attempt'] = time();
    logSecurity("Login échoué : mauvais identifiants pour $user (tentative " . $_SESSION[$attempts_key] . ")");
    header('Location: login.html?error=auth');
    exit;
}
?>