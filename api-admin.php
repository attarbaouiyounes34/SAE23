<?php
// Indique au navigateur/client que la réponse est au format JSON et encodée en UTF-8
header('Content-Type: application/json; charset=utf-8');

// Inclusion de la configuration de la base de données et des fonctions d'authentification
require 'config-db.php';
require 'funcs-auth.php';

// Initialisation de la session pour l'utilisateur actuel
initSession();

// Vérifie si l'utilisateur actuel possède les droits d'administrateur
if (!isAdmin()) {
    // Si non, retourne un code d'erreur HTTP 403 (Accès refusé)
    http_response_code(403);
    // Renvoie un message d'erreur en JSON
    echo json_encode(['erreur' => 'Accès interdit']);
    // Arrête l'exécution du script immédiatement
    exit;
}

// Récupère le paramètre 'action' depuis l'URL (méthode GET). S'il n'existe pas, on lui assigne une chaîne vide.
$action = $_GET['action'] ?? '';

try {
    // Exécute un bloc de code différent selon la valeur de '$action'
    switch ($action) {

        case 'commandes':
            // Action "commandes" : Récupère la liste de toutes les commandes actives
            // Prépare une requête SQL avec une jointure pour récupérer le nom de l'utilisateur associé à la commande
            $stmt = $pdo->prepare("
                SELECT o.id, o.date_commande, o.statut, o.total, u.user AS username
                FROM orders o JOIN users u ON o.user_id = u.id
                WHERE o.deleted_at IS NULL
                ORDER BY o.date_commande DESC
            ");
            $stmt->execute();
            // Récupère toutes les commandes sous forme de tableau associatif
            $commandes = $stmt->fetchAll();
            
            // Prépare une seconde requête pour récupérer les articles (produits) de chaque commande
            $stmtItems = $pdo->prepare("SELECT oi.quantite, p.nom FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = :oid");
            
            // Parcourt chaque commande (le '&' permet de modifier le tableau d'origine directement)
            foreach ($commandes as &$c) {
                // S'assure que le total est bien formaté en nombre décimal (float)
                $c['total'] = floatval($c['total']);
                // Exécute la requête des articles pour l'ID de la commande en cours
                $stmtItems->execute(['oid' => $c['id']]);
                // Ajoute le détail des articles directement dans le tableau de la commande
                $c['items'] = $stmtItems->fetchAll();
            }
            // Renvoie le tableau final des commandes en JSON (en gardant les accents intacts avec JSON_UNESCAPED_UNICODE)
            echo json_encode(['commandes' => $commandes], JSON_UNESCAPED_UNICODE);
            break;

        case 'produits':
            // Action "produits" : Récupère le catalogue des produits
            // Prépare et exécute la requête pour lister tous les produits non supprimés, triés par catégorie et par nom
            $stmt = $pdo->prepare("SELECT * FROM products WHERE deleted_at IS NULL ORDER BY categorie, nom");
            $stmt->execute();
            $produits = $stmt->fetchAll();
            
            // Parcourt les produits pour convertir les prix en format décimal
            foreach ($produits as &$p) { $p['prix'] = floatval($p['prix']); }
            // Renvoie la liste des produits en JSON
            echo json_encode(['produits' => $produits], JSON_UNESCAPED_UNICODE);
            break;

        case 'users':
            // Action "users" : Récupère la liste des utilisateurs
            // Prépare et exécute la requête pour obtenir les données principales des utilisateurs non supprimés
            $stmt = $pdo->prepare("SELECT id, user, mail, role, points_fidelite FROM users WHERE deleted_at IS NULL ORDER BY id");
            $stmt->execute();
            // Renvoie directement le résultat de la requête en JSON
            echo json_encode(['users' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
            break;

        case 'stats':
            // Action "stats" : Récupère des données statistiques (pour un tableau de bord par exemple)
            $stats = [];
            
            // Compte le nombre de commandes avec le statut "en_attente"
            $stmt = $pdo->query("SELECT COUNT(*) as n FROM orders WHERE statut='en_attente' AND deleted_at IS NULL");
            $stats['en_attente'] = $stmt->fetch()['n'];
            
            // Compte le nombre de commandes avec le statut "validee"
            $stmt = $pdo->query("SELECT COUNT(*) as n FROM orders WHERE statut='validee' AND deleted_at IS NULL");
            $stats['validees'] = $stmt->fetch()['n'];
            
            // Compte le nombre de commandes avec le statut "servie"
            $stmt = $pdo->query("SELECT COUNT(*) as n FROM orders WHERE statut='servie' AND deleted_at IS NULL");
            $stats['servies'] = $stmt->fetch()['n'];
            
            // Calcule la somme totale générée aujourd'hui (commandes du jour, non annulées, non supprimées)
            $stmt = $pdo->query("SELECT COALESCE(SUM(total),0) as t FROM orders WHERE DATE(date_commande)=CURDATE() AND deleted_at IS NULL AND statut!='annulee'");
            // Stocke le chiffre d'affaires du jour en le convertissant en décimal
            $stats['chiffre_jour'] = floatval($stmt->fetch()['t']);
            
            // Renvoie toutes les statistiques en JSON
            echo json_encode($stats);
            break;

        default:
            // Si le paramètre 'action' ne correspond à aucun des cas ci-dessus
            echo json_encode(['erreur' => 'Action inconnue']);
    }
} catch (PDOException $e) {
    // Si une erreur liée à la base de données survient (ex: table manquante, mauvaise requête)
    // Renvoie un code d'erreur HTTP 500 (Erreur interne du serveur)
    http_response_code(500);
    // Renvoie un message d'erreur générique en JSON pour ne pas exposer les détails techniques
    echo json_encode(['erreur' => 'Erreur serveur']);
}
?>