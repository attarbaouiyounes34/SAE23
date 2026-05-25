<?php
// =============================================
// R&T Coffee Break — Fonctions d'authentification
// SAE23 — Requêtes préparées PDO (anti injection SQL)
// =============================================

/**
 * Vérifie les identifiants d'un utilisateur.
 * 
 * SÉCURITÉ :
 * - Requête préparée (prepare/execute) pour éviter l'injection SQL
 *   Voir TD01 §15-16 : "admin' --" ne fonctionne plus avec prepare()
 * - Mot de passe comparé en SHA1 (non réversible, TD01 §6)
 * - VARBINARY pour la casse du login (TD01 §11)
 * - deleted_at IS NULL : on ignore les comptes supprimés logiquement
 *
 * @param PDO    $pdo   Connexion PDO active
 * @param string $user  Nom d'utilisateur saisi
 * @param string $pass  Mot de passe en clair saisi
 * @return array|false  Données utilisateur si OK, false sinon
 */
function auth($pdo, $user, $pass) {
    // Requête préparée avec paramètres nommés :u et :p
    // SHA1(:p) calcule le hash côté MySQL pour comparer avec passwd
    $sql = "SELECT id, user, mail, role, points_fidelite 
            FROM users 
            WHERE user = CAST(:u AS BINARY) 
              AND passwd = SHA1(:p) 
              AND deleted_at IS NULL";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['u' => $user, 'p' => $pass]);
    $row = $stmt->fetch();
    $stmt->closeCursor();
    
    // Si on a trouvé une ligne, l'authentification est réussie
    if ($row) {
        return $row;
    }
    return false;
}

/**
 * Démarre ou reprend la session de manière sécurisée.
 * 
 * SÉCURITÉ (TD03 2) :
 * - session_name() personnalisé pour éviter le partage de cookie
 *   entre applications sur le même serveur r207.borelly.net
 * - session_regenerate_id() après login pour éviter le fixation
 */
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Nom unique pour notre appli (TD03 2.3)
        session_name('COFFEEBREAK_SID');
        session_start();
    }
}

/**
 * Connecte un utilisateur en stockant ses infos en session.
 * 
 * @param array $userData Données retournées par auth()
 */
function loginUser($userData) {
    // Régénérer l'ID de session pour éviter le session fixation
    session_regenerate_id(true);
    
    $_SESSION['user_id']   = $userData['id'];
    $_SESSION['user_name'] = $userData['user'];
    $_SESSION['user_role'] = $userData['role'];
    $_SESSION['user_mail'] = $userData['mail'];
    $_SESSION['user_pts']  = $userData['points_fidelite'];
}

/**
 * Déconnecte l'utilisateur et détruit la session.
 */
function logoutUser() {
    $_SESSION = [];
    
    // Supprimer le cookie de session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Vérifie si un utilisateur est connecté.
 * 
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Vérifie si l'utilisateur connecté est admin.
 * 
 * @return bool
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Redirige vers login si non connecté.
 * Appelée en haut de chaque page protégée.
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php?action=loginForm');
        exit;
    }
}

/**
 * Redirige si l'utilisateur n'est pas admin.
 * Protection contre l'accès direct par URL (TD03 §1).
 * 
 * Exemple d'attaque : un utilisateur 'alice' tape directement
 * index.php?action=deleteUser&id=3 dans la barre d'adresse.
 * Sans cette vérification, la suppression serait exécutée !
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        echo '<p class="alert alert-error">Accès interdit : droits administrateur requis.</p>';
        exit;
    }
}

/**
 * Vérifie que l'utilisateur peut modifier un compte donné.
 * L'admin peut tout modifier, les autres seulement leur propre compte.
 * (TD02 §7-8)
 * 
 * @param int $targetUserId ID de l'utilisateur à modifier
 * @return bool
 */
function canEditUser($targetUserId) {
    if (isAdmin()) return true;
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] == $targetUserId;
}
?>
