/* =============================================
   R&T Coffee Break — Script principal
   SAE23 — JavaScript pur (pas de framework)
   ============================================= */

// --- MODE SOMBRE / CLAIR ---
function toggleTheme() {
    var current = document.documentElement.getAttribute('data-theme');
    var next = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
    updateThemeIcon(next);
}

function updateThemeIcon(theme) {
    var btn = document.getElementById('themeToggle');
    if (btn) btn.textContent = theme === 'dark' ? '☀️' : '🌙';
}

// Charger le thème sauvegardé dès que possible
(function () {
    var saved = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', saved);
})();

// --- NAVBAR SCROLL ---
window.addEventListener('scroll', function () {
    var nav = document.querySelector('.navbar');
    if (nav) nav.classList.toggle('scrolled', window.scrollY > 20);
});

// --- MENU BURGER ---
function toggleMenu() {
    var links = document.querySelector('.nav-links');
    if (links) links.classList.toggle('open');
}

// --- VARIABLES GLOBALES ---
var tousLesProduits = [];
var categorieActive = 'Tous';
var currentProduct = null;
var currentQty = 1;

// --- DOMContentLoaded ---
document.addEventListener('DOMContentLoaded', function () {
    // Mettre à jour l'icône du thème
    var saved = localStorage.getItem('theme') || 'light';
    updateThemeIcon(saved);
    // --- AFFICHER LE NOM DE L'UTILISATEUR CONNECTÉ ---
    var cookies = document.cookie.split(';');
    var username = '';
    var userRole = '';
    cookies.forEach(function (c) {
        var parts = c.trim().split('=');
        if (parts[0] === 'cb_user') username = decodeURIComponent(parts[1]);
        if (parts[0] === 'cb_role') userRole = decodeURIComponent(parts[1]);
    });

    if (username) {
        // Remplacer le bouton "Se connecter" par le badge utilisateur
        var loginBtn = document.querySelector('.btn-login');
        if (loginBtn) {
            var badge = document.createElement('div');
            badge.className = 'user-badge';
            badge.innerHTML = '<span class="user-avatar">' + username.charAt(0).toUpperCase() + '</span>' +
                '<span class="user-name">' + username + '</span>' +
                '<a href="logout.php" class="btn-logout" title="Déconnexion">&#x2715;</a>';
            loginBtn.replaceWith(badge);
        }
        // Mettre à jour les badges existants (mes-commandes, fidelite)
        document.querySelectorAll('.user-name').forEach(function (el) { el.textContent = username; });
        document.querySelectorAll('.user-avatar').forEach(function (el) {
            if (!el.classList.contains('admin-avatar')) el.textContent = username.charAt(0).toUpperCase();
        });
    }
    // Alertes depuis l'URL
    var params = new URLSearchParams(window.location.search);
    if (params.get('success') === '1') showPageAlert('Compte créé avec succès ! Connectez-vous.', 'success');
    if (params.get('error') === 'auth') showPageAlert('Identifiant ou mot de passe incorrect.', 'error');
    if (params.get('error') === 'champs') showPageAlert('Veuillez remplir tous les champs.', 'error');
    if (params.get('error') === 'doublon') showPageAlert("Ce nom d'utilisateur existe déjà.", 'error');
    if (params.get('error') === 'mdp') showPageAlert('Le mot de passe doit faire au moins 6 caractères.', 'error');

    // Chargement des produits
    var grille = document.getElementById('grille');
    if (!grille) return;

    fetch('api-produits.php')
        .then(function (r) {
            if (!r.ok) throw new Error('Impossible de charger les produits');
            return r.json();
        })
        .then(function (data) {
            tousLesProduits = data.produits;
            var statP = document.getElementById('statProduits');
            var statC = document.getElementById('statCategories');
            var statPr = document.getElementById('statPrix');
            if (statP) statP.textContent = tousLesProduits.filter(function (p) { return p.disponible; }).length;
            if (statC) {
                var cats = [];
                tousLesProduits.forEach(function (p) { if (cats.indexOf(p.categorie) === -1) cats.push(p.categorie); });
                statC.textContent = cats.length;
            }
            if (statPr) {
                var min = Math.min.apply(null, tousLesProduits.filter(function (p) { return p.disponible; }).map(function (p) { return p.prix; }));
                statPr.textContent = min.toFixed(2) + '€';
            }
            construireFiltres();
            afficherProduits();
        })
        .catch(function (err) {
            grille.innerHTML = '<p style="color:red;">Erreur : ' + err.message + '<br><small>Ouvre cette page via un serveur local (pas en double-clic).</small></p>';
        });
});

// --- ALERTES DEPUIS L'URL ---
function showPageAlert(message, type) {
    var card = document.querySelector('.login-card');
    if (!card) return;
    var h1 = card.querySelector('h1');
    if (!h1) return;
    var div = document.createElement('div');
    div.className = 'alert alert-' + (type === 'success' ? 'success' : 'error');
    div.textContent = message;
    div.style.animation = 'fadeIn 0.4s ease';
    h1.after(div);
    window.history.replaceState({}, '', window.location.pathname);
}

// --- FILTRES ---
function construireFiltres() {
    var categories = ['Tous'];
    tousLesProduits.forEach(function (p) {
        if (categories.indexOf(p.categorie) === -1) categories.push(p.categorie);
    });
    var conteneur = document.getElementById('filtres');
    if (!conteneur) return;
    conteneur.innerHTML = '';
    categories.forEach(function (cat) {
        var btn = document.createElement('button');
        btn.className = 'filter-btn' + (cat === 'Tous' ? ' active' : '');
        btn.textContent = cat;
        btn.addEventListener('click', function () {
            categorieActive = cat;
            conteneur.querySelectorAll('.filter-btn').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            afficherProduits();
        });
        conteneur.appendChild(btn);
    });
}

// --- AFFICHAGE PRODUITS ---
function afficherProduits() {
    var grille = document.getElementById('grille');
    if (!grille) return;
    var produits = categorieActive === 'Tous'
        ? tousLesProduits
        : tousLesProduits.filter(function (p) { return p.categorie === categorieActive; });
    grille.innerHTML = '';
    produits.forEach(function (p) {
        var card = document.createElement('div');
        card.className = 'product-card' + (!p.disponible ? ' indisponible' : '');
        card.innerHTML =
            '<div class="product-img-wrap">' +
            '<img src="' + p.image + '" alt="' + p.nom + '" class="product-img" onerror="this.style.display=\'none\'">' +
            '<span class="badge-categorie">' + p.categorie + '</span>' +
            (!p.disponible ? '<span class="badge-rupture">Rupture</span>' : '') +
            (p.stock > 0 && p.stock <= 10 ? '<span class="badge-stock-bas">Plus que ' + p.stock + '</span>' : '') +
            '</div>' +
            '<div class="product-info">' +
            '<h3>' + p.nom + '</h3>' +
            '<p class="product-desc">' + p.description + '</p>' +
            '<div class="product-footer">' +
            '<span class="product-price">' + p.prix.toFixed(2) + '€</span>' +
            (p.disponible ? '<button class="btn-add" data-id="' + p.id + '">Commander</button>' : '<span class="btn-disabled">Indisponible</span>') +
            '</div>' +
            '</div>';
        var btn = card.querySelector('.btn-add');
        if (btn) btn.addEventListener('click', function () { openOrder(p); });
        grille.appendChild(card);
    });
    var cards = grille.querySelectorAll('.product-card');
    cards.forEach(function (c, i) {
        c.style.opacity = '0';
        c.style.transform = 'translateY(15px)';
        c.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
        setTimeout(function () { c.style.opacity = '1'; c.style.transform = 'translateY(0)'; }, i * 50);
    });
}

// --- MODAL COMMANDE ---
function openOrder(produit) {
    currentProduct = produit;
    currentQty = 1;
    document.getElementById('modalProduct').textContent = produit.nom + ' — ' + produit.prix.toFixed(2) + '€';
    document.getElementById('qtyValue').textContent = '1';

    var optionsHTML = '';
    var options = produit.options || [];
    if (options.length > 0) {
        optionsHTML += '<label>Personnalisation :</label><div class="options-grid">';
        if (options.indexOf('sucre') !== -1) optionsHTML += '<label class="option-check"><input type="checkbox" name="option" value="sucre" data-prix="0"><span class="option-label">Sucre <small>+0.00€</small></span></label>';
        if (options.indexOf('lait') !== -1) optionsHTML += '<label class="option-check"><input type="checkbox" name="option" value="lait" data-prix="0.10"><span class="option-label">Lait <small>+0.10€</small></span></label>';
        if (options.indexOf('chantilly') !== -1) optionsHTML += '<label class="option-check"><input type="checkbox" name="option" value="chantilly" data-prix="0.20"><span class="option-label">Chantilly <small>+0.20€</small></span></label>';
        if (options.indexOf('sirop_vanille') !== -1) optionsHTML += '<label class="option-check"><input type="checkbox" name="option" value="vanille" data-prix="0.20"><span class="option-label">Sirop vanille <small>+0.20€</small></span></label>';
        if (options.indexOf('double_dose') !== -1) optionsHTML += '<label class="option-check"><input type="checkbox" name="option" value="double" data-prix="0.30"><span class="option-label">Double dose <small>+0.30€</small></span></label>';
        optionsHTML += '</div>';
    }
    document.getElementById('optionsContainer').innerHTML = optionsHTML;

    var slidersHTML = '';
    if (options.indexOf('dose_cafe') !== -1) {
        slidersHTML += '<div class="slider-group"><label>Intensité du café :</label><div class="slider-row"><span class="slider-label-left">Léger</span><input type="range" min="1" max="5" value="3" class="dose-slider" id="sliderCafe"><span class="slider-label-right">Fort</span></div><div class="slider-value" id="sliderCafeVal">Moyen</div></div>';
    }
    if (options.indexOf('sucre') !== -1) {
        slidersHTML += '<div class="slider-group"><label>Dose de sucre :</label><div class="slider-row"><span class="slider-label-left">Sans</span><input type="range" min="0" max="4" value="1" class="dose-slider" id="sliderSucre"><span class="slider-label-right">Très sucré</span></div><div class="slider-value" id="sliderSucreVal">1 sucre</div></div>';
    }
    if (options.indexOf('lait') !== -1) {
        slidersHTML += '<div class="slider-group"><label>Dose de lait :</label><div class="slider-row"><span class="slider-label-left">Un nuage</span><input type="range" min="1" max="3" value="1" class="dose-slider" id="sliderLait"><span class="slider-label-right">Beaucoup</span></div><div class="slider-value" id="sliderLaitVal">Un nuage</div></div>';
    }
    document.getElementById('slidersContainer').innerHTML = slidersHTML;

    var sliderCafe = document.getElementById('sliderCafe');
    if (sliderCafe) sliderCafe.addEventListener('input', function () {
        var labels = ['', 'Léger', 'Doux', 'Moyen', 'Corsé', 'Très fort'];
        document.getElementById('sliderCafeVal').textContent = labels[this.value];
    });
    var sliderSucre = document.getElementById('sliderSucre');
    if (sliderSucre) sliderSucre.addEventListener('input', function () {
        var labels = ['Sans sucre', '1 sucre', '2 sucres', '3 sucres', '4 sucres'];
        document.getElementById('sliderSucreVal').textContent = labels[this.value];
    });
    var sliderLait = document.getElementById('sliderLait');
    if (sliderLait) sliderLait.addEventListener('input', function () {
        var labels = ['', 'Un nuage', 'Normal', 'Beaucoup'];
        document.getElementById('sliderLaitVal').textContent = labels[this.value];
    });

    document.querySelectorAll('#optionsContainer input[type="checkbox"]').forEach(function (cb) {
        cb.addEventListener('change', updateTotal);
    });
    updateTotal();
    document.getElementById('orderModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeOrder() {
    document.getElementById('orderModal').classList.remove('open');
    document.body.style.overflow = '';
}
document.addEventListener('click', function (e) { var modal = document.getElementById('orderModal'); if (modal && e.target === modal) closeOrder(); });
document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeOrder(); });

function changeQty(delta) {
    currentQty = Math.max(1, Math.min(10, currentQty + delta));
    document.getElementById('qtyValue').textContent = currentQty;
    updateTotal();
}

function updateTotal() {
    if (!currentProduct) return;
    var supplements = 0;
    document.querySelectorAll('#optionsContainer input[type="checkbox"]:checked').forEach(function (cb) {
        supplements += parseFloat(cb.dataset.prix) || 0;
    });
    var total = (currentProduct.prix + supplements) * currentQty;
    document.getElementById('modalTotal').textContent = total.toFixed(2) + '€';
}

function confirmOrder() {
    closeOrder();
    showToast(currentProduct.nom + ' ajouté à votre commande !');
}

// --- TOAST ---
function showToast(message) {
    var toast = document.getElementById('toast');
    var toastMsg = document.getElementById('toastMsg');
    if (toast && toastMsg) {
        toastMsg.textContent = message;
        toast.classList.add('show');
        setTimeout(function () { toast.classList.remove('show'); }, 3000);
    }
}