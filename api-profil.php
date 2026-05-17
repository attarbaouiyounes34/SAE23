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

if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT id, user, mail, role, points_fidelite, created_at FROM users WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();
    $stmt2 = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = :id AND deleted_at IS NULL");
    $stmt2->execute(['id' => $_SESSION['user_id']]);
    $stats = $stmt2->fetch();
    $user['total_commandes'] = intval($stats['total']);
    echo json_encode($user, JSON_UNESCAPED_UNICODE);
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';

    try {
        if ($action === 'update_info') {
            // Vérifier doublon username
            $stmt = $pdo->prepare("SELECT id FROM users WHERE user = CAST(:u AS BINARY) AND id != :id AND deleted_at IS NULL");
            $stmt->execute(['u' => $data['user'], 'id' => $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                echo json_encode(['erreur' => 'Ce nom d\'utilisateur existe déjà']);
                exit;
            }
            $stmt = $pdo->prepare("UPDATE users SET user = CAST(:u AS BINARY), mail = :m WHERE id = :id");
            $stmt->execute(['u' => $data['user'], 'm' => $data['mail'], 'id' => $_SESSION['user_id']]);
            // Mettre à jour la session et le cookie
            $_SESSION['user_name'] = $data['user'];
            setcookie('cb_user', $data['user'], time() + 86400, '/');
            echo json_encode(['succes' => true]);

        } elseif ($action === 'update_password') {
            // Vérifier ancien mot de passe
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = :id AND passwd = SHA1(:old)");
            $stmt->execute(['id' => $_SESSION['user_id'], 'old' => $data['old_pass']]);
            if (!$stmt->fetch()) {
                echo json_encode(['erreur' => 'Ancien mot de passe incorrect']);
                exit;
            }
            if (strlen($data['new_pass']) < 6) {
                echo json_encode(['erreur' => 'Le nouveau mot de passe doit faire au moins 6 caractères']);
                exit;
            }
            $stmt = $pdo->prepare("UPDATE users SET passwd = SHA1(:p) WHERE id = :id");
            $stmt->execute(['p' => $data['new_pass'], 'id' => $_SESSION['user_id']]);
            echo json_encode(['succes' => true]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['erreur' => 'Erreur serveur']);
    }
}
?>