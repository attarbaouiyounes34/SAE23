<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

require 'config-db.php';

try {
    $stmt = $pdo->prepare("
        SELECT id, nom, description, prix, categorie, image, stock, disponible
        FROM products
        WHERE deleted_at IS NULL
        ORDER BY categorie, nom
    ");
    $stmt->execute();
    $produits = $stmt->fetchAll();
    $stmt->closeCursor();

    foreach ($produits as &$p) {
        $p['prix'] = floatval($p['prix']);
        $p['stock'] = intval($p['stock']);
        $p['disponible'] = $p['disponible'] == 1;

        $cat = $p['categorie'];
        if ($cat === 'Boissons chaudes') {
            if (strpos($p['nom'], 'Café') !== false || 
                strpos($p['nom'], 'Cappuccino') !== false || 
                strpos($p['nom'], 'Expresso') !== false) {
                $p['options'] = ['sucre', 'lait', 'sirop_vanille', 'double_dose', 'dose_cafe'];
            } elseif (strpos($p['nom'], 'Chocolat') !== false) {
                $p['options'] = ['sucre', 'lait', 'chantilly'];
            } else {
                $p['options'] = ['sucre', 'lait'];
            }
        } elseif ($cat === 'Boissons fraîches') {
            if (strpos($p['nom'], 'Café') !== false) {
                $p['options'] = ['sucre', 'lait', 'sirop_vanille', 'dose_cafe'];
            } else {
                $p['options'] = [];
            }
        } else {
            $p['options'] = [];
        }
    }

    echo json_encode(['produits' => $produits], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erreur' => 'Erreur de base de données']);
}
?>