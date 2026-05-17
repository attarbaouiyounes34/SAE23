<?php
require 'config-db.php';
require 'funcs-auth.php';
initSession();

if (!isLoggedIn()) { die('Non connecté'); }

if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    $type = $_FILES['photo']['type'];
    $size = $_FILES['photo']['size'];

    if (!in_array($type, $allowed)) { die('Format non autorisé (JPG, PNG, WEBP uniquement)'); }
    if ($size > 2 * 1024 * 1024) { die('Image trop lourde (max 2 Mo)'); }

    // Créer le dossier uploads si besoin
    $dir = 'uploads/avatars/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    // Nom unique basé sur l'ID utilisateur
    $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $filename = 'avatar_' . $_SESSION['user_id'] . '.' . $ext;
    $path = $dir . $filename;

    if (move_uploaded_file($_FILES['photo']['tmp_name'], $path)) {
        // Sauvegarder le chemin en base
        $stmt = $pdo->prepare("UPDATE users SET photo_url = :url WHERE id = :id");
        $stmt->execute(['url' => $path, 'id' => $_SESSION['user_id']]);
        header('Location: profil.html');
        exit;
    } else {
        die('Erreur lors de l\'upload');
    }
}
?>