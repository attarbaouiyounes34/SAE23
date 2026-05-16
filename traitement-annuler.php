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

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['order_id'])) {
    http_response_code(400);
    echo json_encode(['erreur' => 'ID manquant']);
    exit;
}

try {
    // Vérifier que la commande appartient à l'utilisateur et est en attente
    $stmt = $pdo->prepare("SELECT id, user_id, statut FROM orders WHERE id = :id AND deleted_at IS NULL");
    $stmt->execute(['id' => $data['order_id']]);
    $cmd = $stmt->fetch();

    if (!$cmd || $cmd['user_id'] != $_SESSION['user_id']) {
        echo json_encode(['erreur' => 'Commande introuvable']);
        exit;
    }
    if ($cmd['statut'] !== 'en_attente') {
        echo json_encode(['erreur' => 'Impossible d\'annuler une commande déjà traitée']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE orders SET statut = 'annulee' WHERE id = :id");
    $stmt->execute(['id' => $data['order_id']]);

    echo json_encode(['succes' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erreur' => 'Erreur serveur']);
}
?>