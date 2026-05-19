<?php
require 'config-db.php';
require 'funcs-auth.php';
require 'security.php';
initSession();

if (!isLoggedIn()) {
    header('Location: login.html');
    exit;
}

if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== 0) {
    header('Location: profil.html');
    exit;
}

$allowed = ['image/jpeg', 'image/png', 'image/webp'];
$type = $_FILES['photo']['type'];
$size = $_FILES['photo']['size'];

// Vérifier le type MIME
if (!in_array($type, $allowed)) {
    logSecurity("Upload bloqué : type MIME interdit ($type) par user #" . $_SESSION['user_id']);
    die('Format non autorisé (JPG, PNG, WEBP uniquement)');
}

// Vérifier la taille (max 2 Mo)
if ($size > 2 * 1024 * 1024) {
    die('Image trop lourde (max 2 Mo)');
}

// Vérifier que c'est vraiment une image
$check = getimagesize($_FILES['photo']['tmp_name']);
if ($check === false) {
    logSecurity("Upload bloqué : faux fichier image par user #" . $_SESSION['user_id']);
    die('Ce fichier n\'est pas une image valide');
}

// Extension sécurisée
$ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
    die('Extension non autorisée');
}

$dir = __DIR__ . '/uploads/avatars/';
if (!is_dir($dir)) mkdir($dir, 0755, true);

// Nom basé uniquement sur l'ID utilisateur (pas sur le nom du fichier uploadé)
$filename = 'avatar_' . $_SESSION['user_id'] . '.' . $ext;
$fullpath = $dir . $filename;
$webpath = 'uploads/avatars/' . $filename;

if (move_uploaded_file($_FILES['photo']['tmp_name'], $fullpath)) {
    $stmt = $pdo->prepare("UPDATE users SET photo_url = :url WHERE id = :id");
    $stmt->execute(['url' => $webpath, 'id' => $_SESSION['user_id']]);
    logSecurity("Photo uploadée par user #" . $_SESSION['user_id']);
    header('Location: profil.html');
    exit;
} else {
    logSecurity("Upload échoué pour user #" . $_SESSION['user_id']);
    die('Erreur lors de l\'upload');
}
?>