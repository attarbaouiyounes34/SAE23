<?php
header('Content-Type: application/json; charset=utf-8');
require 'config-db.php';
require 'funcs-auth.php';
require 'security.php';
initSession();

if (!isAdmin()) {
    logSecurity("Accès admin bloqué : user #" . ($_SESSION['user_id'] ?? 'inconnu'));
    http_response_code(403);
    echo json_encode(['erreur' => 'Accès interdit']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

try {
    switch ($action) {
        case 'valider_commande':
            $stmt = $pdo->prepare("UPDATE orders SET statut='validee' WHERE id=:id AND statut='en_attente'");
            $stmt->execute(['id' => intval($data['id'])]);
            logSecurity("Admin : commande #" . $data['id'] . " validée");
            echo json_encode(['succes' => true]);
            break;

        case 'servir_commande':
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE orders SET statut='servie' WHERE id=:id AND statut='validee'");
            $stmt->execute(['id' => intval($data['id'])]);
            $stmt = $pdo->prepare("UPDATE users SET points_fidelite = points_fidelite + 1 WHERE id = (SELECT user_id FROM orders WHERE id=:id)");
            $stmt->execute(['id' => intval($data['id'])]);
            $pdo->commit();
            logSecurity("Admin : commande #" . $data['id'] . " servie + 1 point fidélité");
            echo json_encode(['succes' => true]);
            break;

        case 'supprimer_commande':
            $stmt = $pdo->prepare("UPDATE orders SET deleted_at=NOW() WHERE id=:id");
            $stmt->execute(['id' => intval($data['id'])]);
            logSecurity("Admin : commande #" . $data['id'] . " supprimée (logique)");
            echo json_encode(['succes' => true]);
            break;

        case 'ajouter_produit':
            $nom = clean($data['nom'] ?? '');
            $desc = clean($data['description'] ?? '');
            $prix = floatval($data['prix'] ?? 0);
            $cat = clean($data['categorie'] ?? '');
            $img = $data['image'] ?? '';
            $stock = intval($data['stock'] ?? 0);

            if (empty($nom) || $prix <= 0) {
                echo json_encode(['erreur' => 'Nom et prix obligatoires']);
                break;
            }
            if (!empty($img) && !isValidURL($img)) {
                echo json_encode(['erreur' => 'URL image invalide']);
                break;
            }

            $stmt = $pdo->prepare("INSERT INTO products (nom, description, prix, categorie, image, stock, disponible) VALUES (:nom, :desc, :prix, :cat, :img, :stock, 1)");
            $stmt->execute(['nom' => $nom, 'desc' => $desc, 'prix' => $prix, 'cat' => $cat, 'img' => $img, 'stock' => $stock]);
            logSecurity("Admin : produit '$nom' ajouté");
            echo json_encode(['succes' => true, 'id' => $pdo->lastInsertId()]);
            break;

        case 'modifier_produit':
            $id = intval($data['id']);
            $nom = clean($data['nom'] ?? '');
            $desc = clean($data['description'] ?? '');
            $prix = floatval($data['prix'] ?? 0);
            $cat = clean($data['categorie'] ?? '');
            $img = $data['image'] ?? '';
            $stock = intval($data['stock'] ?? 0);

            if (empty($nom) || $prix <= 0) {
                echo json_encode(['erreur' => 'Nom et prix obligatoires']);
                break;
            }

            $stmt = $pdo->prepare("UPDATE products SET nom=:nom, description=:desc, prix=:prix, categorie=:cat, image=:img, stock=:stock WHERE id=:id");
            $stmt->execute(['id' => $id, 'nom' => $nom, 'desc' => $desc, 'prix' => $prix, 'cat' => $cat, 'img' => $img, 'stock' => $stock]);
            logSecurity("Admin : produit #$id modifié");
            echo json_encode(['succes' => true]);
            break;

        case 'supprimer_produit':
            $stmt = $pdo->prepare("UPDATE products SET deleted_at=NOW() WHERE id=:id");
            $stmt->execute(['id' => intval($data['id'])]);
            logSecurity("Admin : produit #" . $data['id'] . " supprimé (logique)");
            echo json_encode(['succes' => true]);
            break;

        case 'supprimer_user':
            $id = intval($data['id']);
            if ($id == $_SESSION['user_id']) {
                echo json_encode(['erreur' => 'Impossible de supprimer votre propre compte']);
                break;
            }
            $stmt = $pdo->prepare("UPDATE users SET deleted_at=NOW() WHERE id=:id");
            $stmt->execute(['id' => $id]);
            logSecurity("Admin : utilisateur #$id supprimé (logique)");
            echo json_encode(['succes' => true]);
            break;

        default:
            logSecurity("Admin : action inconnue '$action'");
            echo json_encode(['erreur' => 'Action inconnue']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erreur' => 'Erreur serveur']);
}
?>