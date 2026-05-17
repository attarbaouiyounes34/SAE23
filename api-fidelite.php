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

try {
    // Points de l'utilisateur
    $stmt = $pdo->prepare("SELECT user, points_fidelite FROM users WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();

    // Historique : commandes servies = +1 point
    $stmt = $pdo->prepare("
        SELECT o.id, o.date_commande, o.statut
        FROM orders o
        WHERE o.user_id = :id AND o.deleted_at IS NULL AND o.statut IN ('servie','annulee')
        ORDER BY o.date_commande DESC
        LIMIT 20
    ");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $historique = $stmt->fetchAll();

    echo json_encode([
        'user' => $user['user'],
        'points' => intval($user['points_fidelite']),
        'historique' => $historique
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erreur' => 'Erreur serveur']);
}
?>