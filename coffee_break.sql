-- =============================================
-- =============================================
-- R&T Coffee Break — Base de données MySQL
-- SAE23 — IUT de Béziers, Département R&T
-- Younes ATTARBAOUI & Maxime MAURA
-- =============================================
-- Importation : phpMyAdmin sur r207.borelly.net
-- Base : db_ATTARBAOUI
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================
-- 1. TABLE USERS
-- VARBINARY pour sensibilité à la casse (TD01 §11)
-- passwd = SHA1 du mot de passe (non réversible)
-- deleted_at = suppression logique (NULL = actif)
-- =============================================
DROP TABLE IF EXISTS `order_item_options`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `options`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
    `id`               INT AUTO_INCREMENT PRIMARY KEY,
    `user`             VARBINARY(50) NOT NULL UNIQUE,
    `passwd`           VARCHAR(50)   NOT NULL COMMENT 'SHA1 du mot de passe',
    `mail`             VARCHAR(100)  NOT NULL,
    `role`             ENUM('admin','user') NOT NULL DEFAULT 'user',
    `points_fidelite`  INT NOT NULL DEFAULT 0,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`       DATETIME DEFAULT NULL COMMENT 'NULL=actif, date=supprimé'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Mots de passe :
--   admin   -> mdpadmin   -> SHA1 = a84c4d891bdf1468edaa0a61ed8668edd4e6c45d
--   alice   -> abcd1234   -> SHA1 = 7ce0359f12857f2a90c7de465f40a95f01cb5da9
--   bob     -> pass5678   -> SHA1 = 1d7f7abc38f8d1d3b3c3b6e7437e6a28a9fc1df5
-- Vérification : echo -n "mdpadmin" | sha1sum

INSERT INTO `users` (`user`, `passwd`, `mail`, `role`, `points_fidelite`) VALUES
(CAST('admin' AS BINARY), SHA1('mdpadmin'), 'admin@rt-coffee.fr', 'admin', 0),
(CAST('alice' AS BINARY), SHA1('abcd1234'), 'alice@etudiant.fr', 'user', 7),
(CAST('bob'   AS BINARY), SHA1('pass5678'), 'bob@etudiant.fr',   'user', 3);


-- =============================================
-- 2. TABLE PRODUCTS
-- Les 30 articles du catalogue
-- =============================================
CREATE TABLE `products` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `nom`         VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) DEFAULT '',
    `prix`        DECIMAL(5,2) NOT NULL,
    `categorie`   VARCHAR(50)  NOT NULL,
    `image`       VARCHAR(255) DEFAULT '',
    `stock`       INT NOT NULL DEFAULT 0,
    `disponible`  TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`  DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `products` (`nom`, `description`, `prix`, `categorie`, `image`, `stock`, `disponible`) VALUES
-- Boissons chaudes
('Café Expresso',        'Un café court et corsé, parfait pour bien démarrer la journée.', 0.50, 'Boissons chaudes', 'https://images.unsplash.com/photo-1510707577719-ae7c14805e3a?w=400', 50, 1),
('Café Allongé',         'Un café doux et léger, idéal pour les pauses prolongées.',       0.60, 'Boissons chaudes', 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=400', 50, 1),
('Cappuccino',           'Espresso couronné d''une mousse de lait onctueuse.',             0.80, 'Boissons chaudes', 'https://images.unsplash.com/photo-1572442388796-11668a67e53d?w=400', 40, 1),
('Chocolat Chaud',       'Chocolat fondant et crémeux, réconfortant à souhait.',           0.80, 'Boissons chaudes', 'https://images.unsplash.com/photo-1517578239113-b03992dcdd25?w=400', 35, 1),
('Thé Vert',             'Thé vert nature, léger et rafraîchissant.',                      0.50, 'Boissons chaudes', 'https://images.unsplash.com/photo-1576092768241-dec231879fc3?w=400', 30, 1),
('Thé Noir',             'Thé noir corsé, idéal avec un nuage de lait.',                   0.50, 'Boissons chaudes', 'https://images.unsplash.com/photo-1594631252845-29fc4cc8cde9?w=400', 30, 1),
-- Boissons fraîches
('Café Glacé',           'Café froid sur glace, parfait pour les beaux jours.',             0.90, 'Boissons fraîches', 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?w=400', 25, 1),
('Jus d''Orange',        'Jus d''orange 100% pur jus, 25cl.',                              0.80, 'Boissons fraîches', 'https://images.unsplash.com/photo-1600271886742-f049cd451bba?w=400', 35, 1),
('Jus de Pomme',         'Jus de pomme trouble, doux et fruité, 25cl.',                    0.80, 'Boissons fraîches', 'https://images.unsplash.com/photo-1576673442511-7e39b6545c87?w=400', 30, 1),
('Eau Plate',            'Bouteille d''eau minérale 50cl.',                                0.40, 'Boissons fraîches', 'https://images.unsplash.com/photo-1560023907-5f339617ea30?w=400', 80, 1),
('Ice Tea Pêche',        'Thé glacé à la pêche, canette 33cl.',                            0.70, 'Boissons fraîches', 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=400', 40, 1),
('Coca-Cola',            'Canette 33cl bien fraîche.',                                     0.70, 'Boissons fraîches', 'https://images.unsplash.com/photo-1554866585-cd94860890b7?w=400', 50, 1),
-- Viennoiseries
('Pain au Chocolat',     'Viennoiserie feuilletée garnie de deux barres de chocolat.',      0.60, 'Viennoiseries', 'https://images.unsplash.com/photo-1623334044303-241021148842?w=400', 25, 1),
('Croissant',            'Croissant pur beurre, doré et croustillant.',                     0.50, 'Viennoiseries', 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?w=400', 30, 1),
('Croissant aux Amandes','Croissant garni de crème d''amandes et amandes effilées.',        0.80, 'Viennoiseries', 'https://images.unsplash.com/photo-1612240498936-65f5101365d2?w=400', 20, 1),
('Pain aux Raisins',     'Viennoiserie spirale à la crème pâtissière et aux raisins.',      0.60, 'Viennoiseries', 'https://images.unsplash.com/photo-1509365390695-33aee754301f?w=400', 20, 1),
('Pain Suisse',          'Brioche feuilletée, crème pâtissière et pépites de chocolat.',    0.70, 'Viennoiseries', 'https://images.unsplash.com/photo-1608198093002-ad4e005484ec?w=400', 18, 1),
('Chausson aux Pommes',  'Pâte feuilletée garnie de compote de pommes.',                    0.60, 'Viennoiseries', 'https://images.unsplash.com/photo-1509365465985-25d11c17e812?w=400', 20, 1),
('Briochette Nature',    'Petite brioche moelleuse au beurre.',                             0.40, 'Viennoiseries', 'https://images.unsplash.com/photo-1558401391-d59e141613e4?w=400', 25, 1),
('Briochette Pépites Chocolat', 'Petite brioche moelleuse aux pépites de chocolat.',        0.50, 'Viennoiseries', 'https://images.unsplash.com/photo-1509365390695-33aee754301f?w=400', 25, 1),
('Mini Beignet Nature',  'Beignet léger et moelleux, saupoudré de sucre.',                  0.30, 'Viennoiseries', 'https://images.unsplash.com/photo-1551024601-bec78aea704b?w=400', 30, 1),
('Chouquettes',          'Sachet de chouquettes au sucre perlé (x6).',                     0.50, 'Viennoiseries', 'https://images.unsplash.com/photo-1587536849024-daaa4a417b16?w=400', 20, 1),
-- Pâtisseries
('Tarte aux Fraises',    'Tartelette pâtissière aux fraises fraîches.',                     1.00, 'Pâtisseries', 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=400', 12, 1),
('Tarte au Citron',      'Tartelette au citron meringuée, acidulée et fondante.',           1.00, 'Pâtisseries', 'https://images.unsplash.com/photo-1519915028121-7d3463d20b13?w=400', 10, 1),
('Tarte aux Framboises', 'Tartelette crème pâtissière et framboises fraîches.',             1.00, 'Pâtisseries', 'https://images.unsplash.com/photo-1464305795204-6f5bbfc7fb81?w=400', 10, 1),
('Cookie Chocolat',      'Cookie moelleux aux pépites de chocolat noir.',                   0.50, 'Pâtisseries', 'https://images.unsplash.com/photo-1499636136210-6f4ee915583e?w=400', 40, 1),
('Muffin Myrtille',      'Muffin moelleux aux myrtilles fraîches.',                         0.80, 'Pâtisseries', 'https://images.unsplash.com/photo-1607958996333-41aef7caefaa?w=400', 15, 1),
('Muffin Chocolat',      'Muffin fondant au chocolat noir intense.',                        0.80, 'Pâtisseries', 'https://images.unsplash.com/photo-1604882406195-d94d0eef406b?w=400', 15, 1),
-- Salé
('Sandwich Jambon-Beurre','Demi-baguette fraîche, jambon supérieur, beurre doux.',          1.50, 'Salé', 'https://images.unsplash.com/photo-1528735602780-2552fd46c7af?w=400', 15, 1),
('Panini Mozzarella-Tomate','Panini chaud à la mozzarella, tomate et basilic.',             1.80, 'Salé', 'https://images.unsplash.com/photo-1626700051175-6818013e1d4f?w=400', 12, 1);


-- =============================================
-- 3. TABLE OPTIONS
-- Options de personnalisation pour les boissons
-- =============================================
CREATE TABLE `options` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `nom`             VARCHAR(50) NOT NULL,
    `supplement_prix` DECIMAL(4,2) NOT NULL DEFAULT 0.00,
    `type`            ENUM('checkbox','slider') NOT NULL DEFAULT 'checkbox',
    `categorie_cible` VARCHAR(100) DEFAULT NULL COMMENT 'NULL=toutes, sinon catégories autorisées séparées par virgule'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- categorie_cible détermine sur quels produits l'option est affichée
INSERT INTO `options` (`nom`, `supplement_prix`, `type`, `categorie_cible`) VALUES
('Sucre',         0.00, 'checkbox', 'Boissons chaudes,Boissons fraîches'),
('Lait',          0.10, 'checkbox', 'Boissons chaudes,Boissons fraîches'),
('Chantilly',     0.20, 'checkbox', 'Boissons chaudes'),
('Sirop vanille', 0.20, 'checkbox', 'Boissons chaudes,Boissons fraîches'),
('Double dose',   0.30, 'checkbox', 'Boissons chaudes');

-- Les sliders (dose café, sucre, lait) sont gérés côté JS
-- Ils ne coûtent pas de supplément


-- =============================================
-- 4. TABLE ORDERS (commandes)
-- =============================================
CREATE TABLE `orders` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`         INT NOT NULL,
    `date_commande`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `statut`          ENUM('en_attente','validee','servie','annulee') NOT NULL DEFAULT 'en_attente',
    `total`           DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    `deleted_at`      DATETIME DEFAULT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================
-- 5. TABLE ORDER_ITEMS (lignes de commande)
-- =============================================
CREATE TABLE `order_items` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `order_id`      INT NOT NULL,
    `product_id`    INT NOT NULL,
    `quantite`      INT NOT NULL DEFAULT 1,
    `prix_unitaire` DECIMAL(5,2) NOT NULL,
    `dose_cafe`     TINYINT DEFAULT NULL COMMENT '1=léger à 5=très fort, NULL=non applicable',
    `dose_sucre`    TINYINT DEFAULT NULL COMMENT '0=sans à 4=très sucré',
    `dose_lait`     TINYINT DEFAULT NULL COMMENT '1=nuage à 3=beaucoup',
    FOREIGN KEY (`order_id`)   REFERENCES `orders`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================
-- 6. TABLE ORDER_ITEM_OPTIONS (options choisies)
-- Table de liaison N:N entre order_items et options
-- =============================================
CREATE TABLE `order_item_options` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `order_item_id` INT NOT NULL,
    `option_id`     INT NOT NULL,
    FOREIGN KEY (`order_item_id`) REFERENCES `order_items`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`option_id`)     REFERENCES `options`(`id`)     ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================
-- 7. DONNÉES D'EXEMPLE — Commandes
-- =============================================
INSERT INTO `orders` (`user_id`, `date_commande`, `statut`, `total`) VALUES
(2, '2026-05-09 09:15:00', 'servie',     1.50),
(3, '2026-05-10 14:20:00', 'validee',    1.00),
(2, '2026-05-11 10:35:00', 'en_attente', 1.30);

-- Commande #1 (alice) : Thé vert + Cookie x2
INSERT INTO `order_items` (`order_id`, `product_id`, `quantite`, `prix_unitaire`) VALUES
(1, 5,  1, 0.50),
(1, 26, 2, 0.50);

-- Commande #2 (bob) : Expresso x2 avec double dose
INSERT INTO `order_items` (`order_id`, `product_id`, `quantite`, `prix_unitaire`, `dose_cafe`) VALUES
(2, 1, 2, 0.50, 5);
INSERT INTO `order_item_options` (`order_item_id`, `option_id`) VALUES (3, 5);

-- Commande #3 (alice) : Cappuccino + Croissant
INSERT INTO `order_items` (`order_id`, `product_id`, `quantite`, `prix_unitaire`, `dose_cafe`, `dose_sucre`, `dose_lait`) VALUES
(3, 3,  1, 0.80, 3, 1, 2),
(3, 14, 1, 0.50, NULL, NULL, NULL);
INSERT INTO `order_item_options` (`order_item_id`, `option_id`) VALUES
(4, 1),
(4, 2);


SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- FIN DU SCRIPT
-- Pour vérifier : SELECT * FROM users;
--                 SELECT * FROM products WHERE deleted_at IS NULL;
--                 SELECT * FROM orders;
-- =============================================
