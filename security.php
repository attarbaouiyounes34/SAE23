<?php
// =============================================
// R&T Coffee Break — Fonctions de sécurité
// Protection XSS, CSRF, validation des entrées
// =============================================

/**
 * Nettoie une chaîne contre les attaques XSS
 * À utiliser sur TOUT ce qui est affiché venant de la BDD ou d'un formulaire
 */
function clean($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Génère un token CSRF et le stocke en session
 */
function generateCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie le token CSRF envoyé
 */
function checkCSRF($token) {
    if (empty($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        http_response_code(403);
        die(json_encode(['erreur' => 'Token CSRF invalide']));
    }
}

/**
 * Valide une adresse email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valide une URL (pour les images)
 */
function isValidURL($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false || empty($url);
}

/**
 * Log les tentatives suspectes
 */
function logSecurity($message) {
    $log = date('Y-m-d H:i:s') . ' | ' . $_SERVER['REMOTE_ADDR'] . ' | ' . $message . "\n";
    file_put_contents(__DIR__ . '/logs/security.log', $log, FILE_APPEND | LOCK_EX);
}
?>