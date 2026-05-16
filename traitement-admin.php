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

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

try {
    switch ($action) {

        // --- COMMANDES ---
        case 'valider_commande':
            $stmt = $pdo->prepare("UPDATE orders SET statut='validee' WHERE id=:id AND statut='en_attente'");
            $stmt->execute(['id' => $data['id']]);
            echo json_encode(['succes' => true]);
            break;

        case 'servir_commande':
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE orders SET statut='servie' WHERE id=:id AND statut='validee'");
            $stmt->execute(['id' => $data['id']]);
            // Ajouter 1 point fidélité
            $stmt = $pdo->prepare("UPDATE users SET points_fidelite = points_fidelite + 1 WHERE id = (SELECT user_id FROM orders WHERE id=:id)");
            $stmt->execute(['id' => $data['id']]);
            $pdo->commit();
            echo json_encode(['succes' => true]);
            break;

        case 'supprimer_commande':
            $stmt = $pdo->prepare("UPDATE orders SET deleted_at=NOW() WHERE id=:id");
            $stmt->execute(['id' => $data['id']]);
            echo json_encode(['succes' => true]);
            break;

        // --- PRODUITS ---
        case 'ajouter_produit':
            $stmt = $pdo->prepare("INSERT INTO products (nom, description, prix, categorie, image, stock, disponible) VALUES (:nom, :desc, :prix, :cat, :img, :stock, 1)");
            $stmt->execute([
                'nom' => htmlspecialchars($data['nom']),
                'desc' => htmlspecialchars($data['description']),
                'prix' => $data['prix'],
                'cat' => $data['categorie'],
                'img' => $data['image'],
                'stock' => $data['stock']
            ]);
            echo json_encode(['succes' => true, 'id' => $pdo->lastInsertId()]);
            break;

        case 'modifier_produit':
            $stmt = $pdo->prepare("UPDATE products SET nom=:nom, description=:desc, prix=:prix, categorie=:cat, image=:img, stock=:stock WHERE id=:id");
            $stmt->execute([
                'id' => $data['id'],
                'nom' => htmlspecialchars($data['nom']),
                'desc' => htmlspecialchars($data['description']),
                'prix' => $data['prix'],
                'cat' => $data['categorie'],
                'img' => $data['image'],
                'stock' => $data['stock']
            ]);
            echo json_encode(['succes' => true]);
            break;

        case 'supprimer_produit':
            $stmt = $pdo->prepare("UPDATE products SET deleted_at=NOW() WHERE id=:id");
            $stmt->execute(['id' => $data['id']]);
            echo json_encode(['succes' => true]);
            break;

        // --- UTILISATEURS ---
        case 'supprimer_user':
            if ($data['id'] == $_SESSION['user_id']) {
                echo json_encode(['erreur' => 'Impossible de supprimer votre propre compte']);
                break;
            }
            $stmt = $pdo->prepare("UPDATE users SET deleted_at=NOW() WHERE id=:id");
            $stmt->execute(['id' => $data['id']]);
            echo json_encode(['succes' => true]);
            break;

        default:
            echo json_encode(['erreur' => 'Action inconnue']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erreur' => 'Erreur serveur']);
}
?>