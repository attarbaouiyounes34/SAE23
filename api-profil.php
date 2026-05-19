<?php
header('Content-Type: application/json; charset=utf-8');
require 'config-db.php';
require 'funcs-auth.php';
initSession();

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['erreur' => 'Non connecté']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {

    // ===== GET : récupérer le profil =====
    if ($method === 'GET') {
        $stmt = $pdo->prepare("SELECT id, user, mail, role, points_fidelite, photo_url, created_at FROM users WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $user = $stmt->fetch();

        // Protection XSS sur les sorties
        $user['user'] = htmlspecialchars($user['user'], ENT_QUOTES, 'UTF-8');
        $user['mail'] = htmlspecialchars($user['mail'], ENT_QUOTES, 'UTF-8');
        $user['photo_url'] = htmlspecialchars($user['photo_url'] ?? '', ENT_QUOTES, 'UTF-8');

        // Nombre total de commandes
        $stmt2 = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = :id AND deleted_at IS NULL");
        $stmt2->execute(['id' => $_SESSION['user_id']]);
        $stats = $stmt2->fetch();
        $user['total_commandes'] = intval($stats['total']);

        echo json_encode($user, JSON_UNESCAPED_UNICODE);
    }

    // ===== POST : modifier le profil =====
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';

        // --- Modifier nom et email ---
        if ($action === 'update_info') {
            $user = trim($data['user'] ?? '');
            $mail = trim($data['mail'] ?? '');

            if (empty($user) || empty($mail)) {
                echo json_encode(['erreur' => 'Champs obligatoires']);
                exit;
            }

            // Vérifier doublon username
            $stmt = $pdo->prepare("SELECT id FROM users WHERE user = CAST(:u AS BINARY) AND id != :id AND deleted_at IS NULL");
            $stmt->execute(['u' => $user, 'id' => $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                echo json_encode(['erreur' => 'Ce nom d\'utilisateur existe déjà']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE users SET user = CAST(:u AS BINARY), mail = :m WHERE id = :id");
            $stmt->execute(['u' => $user, 'm' => $mail, 'id' => $_SESSION['user_id']]);

            $_SESSION['user_name'] = $user;
            setcookie('cb_user', $user, time() + 86400, '/');
            echo json_encode(['succes' => true]);

        // --- Modifier le mot de passe ---
        } elseif ($action === 'update_password') {
            $old = $data['old_pass'] ?? '';
            $new = $data['new_pass'] ?? '';

            if (empty($old) || empty($new)) {
                echo json_encode(['erreur' => 'Champs obligatoires']);
                exit;
            }

            // Vérifier ancien mot de passe
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = :id AND passwd = SHA1(:old)");
            $stmt->execute(['id' => $_SESSION['user_id'], 'old' => $old]);
            if (!$stmt->fetch()) {
                echo json_encode(['erreur' => 'Ancien mot de passe incorrect']);
                exit;
            }

            if (strlen($new) < 6) {
                echo json_encode(['erreur' => 'Le nouveau mot de passe doit faire au moins 6 caractères']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE users SET passwd = SHA1(:p) WHERE id = :id");
            $stmt->execute(['p' => $new, 'id' => $_SESSION['user_id']]);
            echo json_encode(['succes' => true]);

        // --- Modifier la photo de profil (par URL) ---
        } elseif ($action === 'update_photo') {
            $url = htmlspecialchars($data['photo_url'] ?? '', ENT_QUOTES, 'UTF-8');
            $stmt = $pdo->prepare("UPDATE users SET photo_url = :url WHERE id = :id");
            $stmt->execute(['url' => $url, 'id' => $_SESSION['user_id']]);
            echo json_encode(['succes' => true]);

        // --- Supprimer le compte (suppression logique) ---
        } elseif ($action === 'delete_account') {
            $stmt = $pdo->prepare("UPDATE users SET deleted_at = NOW() WHERE id = :id");
            $stmt->execute(['id' => $_SESSION['user_id']]);
            echo json_encode(['succes' => true]);

        } else {
            echo json_encode(['erreur' => 'Action inconnue']);
        }
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erreur' => 'Erreur serveur']);
}
?>