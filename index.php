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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f5f6fa; }
        .navbar { background: #2c3e50; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .navbar-brand { font-size: 1.5rem; font-weight: bold; }
        .navbar-menu { display: flex; gap: 1.5rem; align-items: center; }
        .navbar-menu a { color: white; text-decoration: none; padding: 0.5rem 1rem; border-radius: 5px; }
        .navbar-menu a:hover { background: #34495e; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .stat-card h3 { color: #7f8c8d; font-size: 0.9rem; margin-bottom: 0.5rem; }
        .stat-card .value { font-size: 2rem; font-weight: bold; color: #2c3e50; }
        .stat-card .value.success { color: #27ae60; }
        .stat-card .value.warning { color: #f39c12; }
        .stat-card .value.danger { color: #e74c3c; }
        .card { background: white; border-radius: 8px; padding: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 1.5rem; }
        .card h2 { margin-bottom: 1rem; color: #2c3e50; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #ecf0f1; }
        th { background: #f8f9fa; font-weight: 600; }
        .btn { padding: 0.5rem 1rem; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary { background: #3498db; color: white; }
        .alert { padding: 1rem; border-radius: 5px; margin-bottom: 1rem; }
        .alert-success { background: #d4edda; color: #155724; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">📦 Gestion de Stock</div>
        <div class="navbar-menu">
            <a href="/index.php">Accueil</a>
            <a href="/modules/produits/liste.php">Produits</a>
            <a href="/modules/facturation/nouvelle-facture.php">Nouvelle Facture</a>
            <?php if (isAdmin()): ?>
            <a href="/modules/admin/gestion-compte.php">Comptes</a>
            <?php endif; ?>
            <span>| <?= $_SESSION['user_name'] ?? '' ?> (<?= getUserRole() ?>)</span>
            <a href="auth/logout.php">Déconnexion</a>
        </div>
    </nav>
    <div class="container">
        <h1>Bienvenue, <?= htmlspecialchars($_SESSION['user_name'] ?? '') ?> !</h1>
        <p style="color: #7f8c8d; margin-bottom: 2rem;">Voici un aperçu de votre activité</p>
        
        <div class="stats">
            <div class="stat-card">
                <h3>📊 Total Ventes</h3>
                <div class="value">38 880 FC</div>
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
                        <td>9 600 FC</td>
                        <td><span style="color: #27ae60;">Payée</span></td>
                    </tr>
                    <tr>
                        <td>FAC-2026-0002</td>
                        <td>Construction Moderne</td>
                        <td>20/04/2026</td>
                        <td>13 080 FC</td>
                        <td><span style="color: #f39c12;">En attente</span></td>
                    </tr>
                    <tr>
                        <td>FAC-2026-0001</td>
                        <td>Entreprise BTP Sahara</td>
                        <td>15/04/2026</td>
                        <td>16 200 FC</td>
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