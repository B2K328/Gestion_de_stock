<?php
/**
 * Page d'accueil
 * Gestion de Stock - Transco
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/fonctions-commons.php';
require_once __DIR__ . '/includes/fonctions-Auth.php';
require_once __DIR__ . '/auth/session.php';

// Vérifier l'authentification
requireAuth();

$pageTitle = 'Tableau de Bord - Gestion de Stock';
include __DIR__ . '/includes/header.php';
?>
<body>
    <nav class="navbar">
        <div class="navbar-brand">📦 Gestion de Stock</div>
        <div class="navbar-menu">
            <a href="<?= url('/index.php') ?>">Accueil</a>
            <a href="<?= url('/modules/produits/liste.php') ?>">Produits</a>
            <a href="<?= url('/modules/facturation/nouvelle-facture.php') ?>">Nouvelle Facture</a>
            <?php if (isAdmin()): ?>
            <a href="<?= url('/modules/admin/gestion-compte.php') ?>">Comptes</a>
            <?php endif; ?>
            <span>| <?= $_SESSION['user_name'] ?? '' ?> (<?= getUserRole() ?>)</span>
            <a href="<?= url('/auth/logout.php') ?>">Déconnexion</a>
        </div>
    </nav>
    <div class="container">
        <h1>Bienvenue, <?= htmlspecialchars($_SESSION['user_name'] ?? '') ?> !</h1>
        <p style="color: #7f8c8d; margin-bottom: 2rem;">Voici un aperçu de votre activité</p>
        
        <div class="stats">
            <div class="stat-card">
                <h3>📊 Total Ventes</h3>
                <div class="value">38 880 DH</div>
            </div>
            <div class="stat-card">
                <h3>🧾 Factures</h3>
                <div class="value success">3</div>
            </div>
            <div class="stat-card">
                <h3>📦 Produits</h3>
                <div class="value warning">5</div>
            </div>
            <div class="stat-card">
                <h3>⚠️ Alertes Stock</h3>
                <div class="value danger">0</div>
            </div>
        </div>
        
        <div class="card">
            <h2>📋 Dernières factures</h2>
            <table>
                <thead>
                    <tr>
                        <th>N° Facture</th>
                        <th>Client</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>FAC-2026-0003</td>
                        <td>BTP Atlas</td>
                        <td>25/04/2026</td>
                        <td>9 600 DH</td>
                        <td><span style="color: #27ae60;">Payée</span></td>
                    </tr>
                    <tr>
                        <td>FAC-2026-0002</td>
                        <td>Construction Moderne</td>
                        <td>20/04/2026</td>
                        <td>13 080 DH</td>
                        <td><span style="color: #f39c12;">En attente</span></td>
                    </tr>
                    <tr>
                        <td>FAC-2026-0001</td>
                        <td>Entreprise BTP Sahara</td>
                        <td>15/04/2026</td>
                        <td>16 200 DH</td>
                        <td><span style="color: #27ae60;">Payée</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="card">
            <h2>⚠️ Produits en alerte de stock</h2>
            <p style="color: #7f8c8d;">Aucun produit en dessous du seuil d'alerte.</p>
        </div>
    </div>
</body>
</html>
<?php include __DIR__ . '/includes/footer.php'; ?>