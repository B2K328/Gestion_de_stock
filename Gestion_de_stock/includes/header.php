<?php
/**
 * En-tête commun
 * Gestion de Stock - Transco
 */
require_once __DIR__ . '/fonctions-commons.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Gestion de Stock' ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            min-height: 100vh;
        }
        .navbar {
            background: #2c3e50;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar-brand { font-size: 1.5rem; font-weight: bold; }
        .navbar-menu { display: flex; gap: 1.5rem; align-items: center; }
        .navbar-menu a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .navbar-menu a:hover { background: #34495e; }
        .user-info { color: #bdc3c7; font-size: 0.9rem; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-secondary { background: #95a5a6; color: white; }
        .btn:hover { opacity: 0.9; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #ecf0f1; }
        th { background: #f8f9fa; font-weight: 600; }
        .alert { padding: 1rem; border-radius: 5px; margin-bottom: 1rem; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .alert-warning { background: #fff3cd; color: #856404; }
        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .role-badge { display: inline-block; background: #3498db; color: white; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85rem; }
        .info-box { background: #f8f9fa; padding: 1rem; border-left: 4px solid #3498db; margin: 1rem 0; }
        .totals { background: #ecf0f1; padding: 1rem; border-radius: 5px; margin: 1rem 0; }
        .totals p { margin: 0.5rem 0; font-weight: 500; }
        .actions { display: flex; gap: 1rem; margin: 1rem 0; }
        .actions form { flex: 1; }
        .actions form button { width: 100%; }
        .empty { text-align: center; color: #7f8c8d; padding: 2rem; }
        .links { text-align: center; color: #7f8c8d; margin-top: 1rem; }
        .links a { color: #3498db; text-decoration: none; margin: 0 0.5rem; }
        .links a:hover { text-decoration: underline; }
        form { display: flex; flex-direction: column; gap: 1rem; }
        form input, form select { padding: 0.75rem; border: 1px solid #ddd; border-radius: 5px; }
        form button { padding: 0.75rem; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; }
        form button:hover { background: #2980b9; }
        form button[type="submit"] { font-weight: 600; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">📦 Gestion de Stock</div>
        <?php if (isLoggedIn()): ?>
        <div class="navbar-menu">
            <a href="<?= url('/index.php') ?>">Accueil</a>
            <a href="<?= url('/modules/produits/liste.php') ?>">Produits</a>
            <a href="<?= url('/modules/facturation/nouvelle-facture.php') ?>">Nouvelle Facture</a>
            <?php if (isAdmin()): ?>
            <a href="<?= url('/modules/admin/gestion-compte.php') ?>">Comptes</a>
            <?php endif; ?>
            <span class="user-info">| <?= $_SESSION['user_name'] ?? '' ?> (<?= getUserRole() ?>)</span>
            <a href="<?= url('/auth/logout.php') ?>">Déconnexion</a>
        </div>
        <?php endif; ?>
    </nav>
    <div class="container">
        <?php if (isset($_SESSION['flash'])): ?>
            <?php foreach ($_SESSION['flash'] as $type => $message): ?>
                <div class="alert alert-<?= $type ?>"><?= htmlspecialchars($message) ?></div>
            <?php endforeach; ?>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>