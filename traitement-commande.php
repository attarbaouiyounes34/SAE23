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
if (!$data || !isset($data['product_id']) || !isset($data['quantite'])) {
    http_response_code(400);
    echo json_encode(['erreur' => 'Données manquantes']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Récupérer le prix du produit
    $stmt = $pdo->prepare("SELECT prix FROM products WHERE id = :id AND deleted_at IS NULL");
    $stmt->execute(['id' => $data['product_id']]);
    $produit = $stmt->fetch();
    if (!$produit) { throw new Exception('Produit introuvable'); }

    $prix_unitaire = floatval($produit['prix']);
    $supplements = floatval($data['supplements'] ?? 0);
    $quantite = intval($data['quantite']);
    $total = ($prix_unitaire + $supplements) * $quantite;

    // Créer la commande
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, total) VALUES (:uid, :total)");
    $stmt->execute(['uid' => $_SESSION['user_id'], 'total' => $total]);
    $order_id = $pdo->lastInsertId();

    // Ajouter la ligne de commande
    $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantite, prix_unitaire, dose_cafe, dose_sucre, dose_lait) VALUES (:oid, :pid, :qty, :prix, :cafe, :sucre, :lait)");
    $stmt->execute([
        'oid'   => $order_id,
        'pid'   => $data['product_id'],
        'qty'   => $quantite,
        'prix'  => $prix_unitaire + $supplements,
        'cafe'  => $data['dose_cafe'] ?? null,
        'sucre' => $data['dose_sucre'] ?? null,
        'lait'  => $data['dose_lait'] ?? null
    ]);
    $item_id = $pdo->lastInsertId();

    // Ajouter les options cochées
    if (!empty($data['options'])) {
        $stmt = $pdo->prepare("SELECT id FROM options WHERE nom = :nom");
        $stmtInsert = $pdo->prepare("INSERT INTO order_item_options (order_item_id, option_id) VALUES (:iid, :oid)");
        foreach ($data['options'] as $optNom) {
            $stmt->execute(['nom' => $optNom]);
            $opt = $stmt->fetch();
            if ($opt) {
                $stmtInsert->execute(['iid' => $item_id, 'oid' => $opt['id']]);
            }
        }
    }

    $pdo->commit();
    echo json_encode(['succes' => true, 'order_id' => $order_id, 'total' => $total]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['erreur' => 'Erreur lors de la commande']);
}
?>