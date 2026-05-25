<?php
error_reporting(E_ALL); ini_set("display_errors", 0);
header('Content-Type: application/json; charset=utf-8');
require 'config-db.php';
require 'funcs-auth.php';
require 'security.php';
initSession();

if (!isLoggedIn()) {
    logSecurity("Commande bloquée : non connecté");
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

// Validation des types
$product_id = intval($data['product_id']);
$quantite = intval($data['quantite']);
$supplements = floatval($data['supplements'] ?? 0);

if ($product_id <= 0 || $quantite <= 0 || $quantite > 10) {
    logSecurity("Commande bloquée : valeurs invalides pid=$product_id qty=$quantite");
    http_response_code(400);
    echo json_encode(['erreur' => 'Valeurs invalides']);
    exit;
}

if ($supplements < 0 || $supplements > 5) {
    http_response_code(400);
    echo json_encode(['erreur' => 'Supplément invalide']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT prix, stock, disponible FROM products WHERE id = :id AND deleted_at IS NULL");
    $stmt->execute(['id' => $product_id]);
    $produit = $stmt->fetch();

    if (!$produit) { throw new Exception('Produit introuvable'); }
    if (!$produit['disponible']) { throw new Exception('Produit indisponible'); }
    if ($produit['stock'] < $quantite) { throw new Exception('Stock insuffisant'); }

    $prix_unitaire = floatval($produit['prix']);
    $total = ($prix_unitaire + $supplements) * $quantite;

    // Décrémenter le stock
    $stmt = $pdo->prepare("UPDATE products SET stock = stock - :qty WHERE id = :id");
    $stmt->execute(['qty' => $quantite, 'id' => $product_id]);

    $stmt = $pdo->prepare("INSERT INTO orders (user_id, total) VALUES (:uid, :total)");
    $stmt->execute(['uid' => $_SESSION['user_id'], 'total' => $total]);
    $order_id = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantite, prix_unitaire, dose_cafe, dose_sucre, dose_lait) VALUES (:oid, :pid, :qty, :prix, :cafe, :sucre, :lait)");
    $stmt->execute([
        'oid' => $order_id,
        'pid' => $product_id,
        'qty' => $quantite,
        'prix' => $prix_unitaire + $supplements,
        'cafe' => isset($data['dose_cafe']) ? intval($data['dose_cafe']) : null,
        'sucre' => isset($data['dose_sucre']) ? intval($data['dose_sucre']) : null,
        'lait' => isset($data['dose_lait']) ? intval($data['dose_lait']) : null
    ]);
    $item_id = $pdo->lastInsertId();

    if (!empty($data['options']) && is_array($data['options'])) {
        $stmtOpt = $pdo->prepare("SELECT id FROM options WHERE nom = :nom");
        $stmtIns = $pdo->prepare("INSERT INTO order_item_options (order_item_id, option_id) VALUES (:iid, :oid)");
        foreach ($data['options'] as $optNom) {
            $optNom = clean($optNom);
            $stmtOpt->execute(['nom' => $optNom]);
            $opt = $stmtOpt->fetch();
            if ($opt) $stmtIns->execute(['iid' => $item_id, 'oid' => $opt['id']]);
        }
    }

    $pdo->commit();
    logSecurity("Commande #$order_id créée par user #" . $_SESSION['user_id'] . " - total $total€");
    echo json_encode(['succes' => true, 'order_id' => $order_id, 'total' => $total]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['erreur' => $e->getMessage()]);
}
?>