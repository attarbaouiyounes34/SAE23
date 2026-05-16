<?php
header('Content-Type: application/json; charset=utf-8');
require 'config-db.php';
require 'funcs-auth.php';
initSession();

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['erreur' => 'Accès interdit']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {

        case 'commandes':
            $stmt = $pdo->prepare("
                SELECT o.id, o.date_commande, o.statut, o.total, u.user AS username
                FROM orders o JOIN users u ON o.user_id = u.id
                WHERE o.deleted_at IS NULL
                ORDER BY o.date_commande DESC
            ");
            $stmt->execute();
            $commandes = $stmt->fetchAll();
            $stmtItems = $pdo->prepare("SELECT oi.quantite, p.nom FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = :oid");
            foreach ($commandes as &$c) {
                $c['total'] = floatval($c['total']);
                $stmtItems->execute(['oid' => $c['id']]);
                $c['items'] = $stmtItems->fetchAll();
            }
            echo json_encode(['commandes' => $commandes], JSON_UNESCAPED_UNICODE);
            break;

        case 'produits':
            $stmt = $pdo->prepare("SELECT * FROM products WHERE deleted_at IS NULL ORDER BY categorie, nom");
            $stmt->execute();
            $produits = $stmt->fetchAll();
            foreach ($produits as &$p) { $p['prix'] = floatval($p['prix']); }
            echo json_encode(['produits' => $produits], JSON_UNESCAPED_UNICODE);
            break;

        case 'users':
            $stmt = $pdo->prepare("SELECT id, user, mail, role, points_fidelite FROM users WHERE deleted_at IS NULL ORDER BY id");
            $stmt->execute();
            echo json_encode(['users' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
            break;

        case 'stats':
            $stats = [];
            $stmt = $pdo->query("SELECT COUNT(*) as n FROM orders WHERE statut='en_attente' AND deleted_at IS NULL");
            $stats['en_attente'] = $stmt->fetch()['n'];
            $stmt = $pdo->query("SELECT COUNT(*) as n FROM orders WHERE statut='validee' AND deleted_at IS NULL");
            $stats['validees'] = $stmt->fetch()['n'];
            $stmt = $pdo->query("SELECT COUNT(*) as n FROM orders WHERE statut='servie' AND deleted_at IS NULL");
            $stats['servies'] = $stmt->fetch()['n'];
            $stmt = $pdo->query("SELECT COALESCE(SUM(total),0) as t FROM orders WHERE DATE(date_commande)=CURDATE() AND deleted_at IS NULL AND statut!='annulee'");
            $stats['chiffre_jour'] = floatval($stmt->fetch()['t']);
            echo json_encode($stats);
            break;

        default:
            echo json_encode(['erreur' => 'Action inconnue']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erreur' => 'Erreur serveur']);
}
?>