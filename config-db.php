<?php
// =============================================
// R&T Coffee Break — Configuration base de données
// SAE23 — Connexion PDO MySQL
// =============================================

// --- PARAMÈTRES DE CONNEXION ---l
$DB_HOST = 'localhost';
$DB_NAME = 'db_ATTARBAOUI';       // base sur phpMyAdmin
$DB_USER = '22507826';          // identifiant MySQL
$DB_PASS = '752076';  // mot de passe MySQL

// --- CONNEXION PDO ---
// PDO = PHP Data Objects, couche d'abstraction pour accéder aux BDD
// On utilise PDO car il supporte les requêtes préparées (anti injection SQL)
try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            // Mode erreur : PDO lance des exceptions en cas de problème
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // Fetch par défaut : tableau associatif (clé = nom de colonne)
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Désactiver l'émulation des requêtes préparées
            // (utilise les vraies requêtes préparées du serveur MySQL)
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // En production, ne jamais afficher le message d'erreur complet
    // (il peut contenir le mot de passe ou des infos sensibles)
    die("Erreur de connexion à la base de données.");
    // Pour le debug uniquement, décommenter la ligne suivante :
    // die("Erreur PDO : " . $e->getMessage());
}

// --- ALTERNATIVE SQLITE (pour développement local) ---
// Décommenter les lignes ci-dessous et commenter le bloc MySQL ci-dessus
// Placer le fichier .sqlite EN DEHORS de public_html pour la sécurité !
/*
$DB_PATH = __DIR__ . '/../data/coffee_break.sqlite';
try {
    $pdo = new PDO("sqlite:$DB_PATH");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("PRAGMA foreign_keys = ON;");
} catch (PDOException $e) {
    die("Erreur de connexion SQLite.");
}
*/
?>
