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
    // Commandes de l'utilisateur
    $stmt = $pdo->prepare("
        SELECT o.id, o.date_commande, o.statut, o.total
        FROM orders o
        WHERE o.user_id = :uid AND o.deleted_at IS NULL
        ORDER BY o.date_commande DESC
    ");
    $stmt->execute(['uid' => $_SESSION['user_id']]);
    $commandes = $stmt->fetchAll();

    // Pour chaque commande, récupérer les items
    $stmtItems = $pdo->prepare("
        SELECT oi.quantite, oi.prix_unitaire, p.nom
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = :oid
    ");

    foreach ($commandes as &$cmd) {
        $cmd['total'] = floatval($cmd['total']);
        $stmtItems->execute(['oid' => $cmd['id']]);
        $cmd['items'] = $stmtItems->fetchAll();
    }

    echo json_encode(['commandes' => $commandes], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erreur' => 'Erreur de base de données']);
}
?>