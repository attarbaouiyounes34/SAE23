# ☕ R&T Coffee Break

**SAE23 — Application WEB | IUT de Béziers, Département R&T**

> Application web de commande de boissons et snacks pour le département R&T de l'IUT de Béziers.

🌐 **Démo en ligne** : [r207.borelly.net/~u22507826/SAE23/](https://r207.borelly.net/~u22507826/SAE23/)

---

## 👥 Équipe

| Nom | Rôle |
|-----|------|
| **Younes ATTARBAOUI** | Développeur principal |
| **Maxime MAURA** | Développeur |

**Encadrant** : Christophe BORELLY

---

## 📋 Fonctionnalités

### 🔐 Authentification
- Inscription avec captcha mathématique
- Connexion sécurisée (SHA1 + requêtes préparées PDO)
- Sessions PHP + cookies
- Déconnexion propre

### 🛒 Catalogue & Commandes
- 30+ produits chargés dynamiquement depuis MySQL
- Filtres par catégorie + barre de recherche
- Options personnalisables (sucre, lait, sirop vanille, double dose)
- Curseurs de dose (intensité café, sucre, lait) pour les boissons
- Panier avec calcul du total en temps réel
- Système de commande complet (passer, annuler)

### 👤 Espace Utilisateur
- Page profil (modifier nom, email, mot de passe)
- Upload de photo de profil
- Mes commandes en temps réel (en attente → validée → servie)
- Programme fidélité dynamique (1 commande servie = 1 point, 10 points = 1 boisson offerte)

### ⚙️ Administration
- Dashboard avec statistiques (commandes en attente, validées, servies, chiffre du jour)
- CRUD Produits (ajouter, modifier, supprimer logiquement)
- Gestion des commandes (valider, servir, supprimer)
- Gestion des utilisateurs (consulter, supprimer)

### 🎨 Interface
- Design responsive (desktop + mobile)
- Mode sombre / clair avec sauvegarde de la préférence
- Police Open Sans
- Animations fluides (cartes, modals, toasts)

---

## 🛠️ Technologies

| Technologie | Utilisation |
|-------------|-------------|
| **PHP** | Backend, API REST, authentification, sessions |
| **MySQL** | Base de données relationnelle |
| **HTML5** | Structure des pages |
| **CSS3** | Design, responsive, dark mode (variables CSS) |
| **JavaScript** | Interactions, fetch API, DOM dynamique |
| **PDO** | Requêtes préparées (anti injection SQL) |

> ⚠️ **Aucun framework PHP** utilisé (conformément au cahier des charges)
