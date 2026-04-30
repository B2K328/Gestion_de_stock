<?php
/**
 * En-tête commun
 * Gestion de Stock - Transco
 * Étudiant 2: Front-End & Hardware
 */

// Vérifier les fonctions d'authentification
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    function isAdmin() {
        return in_array($_SESSION['role'] ?? '', ['admin', 'manager', 'super_admin']);
    }
    
    function getUserRole() {
        $roles = ['caissier' => 'Caissier', 'admin' => 'Admin', 'manager' => 'Manager', 'super_admin' => 'Super Admin'];
        return $roles[$_SESSION['role'] ?? 'caissier'] ?? 'Utilisateur';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Gestion de Stock' ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body style="display: flex; flex-direction: column; height: 100vh;">
    <nav class="navbar">
        <div class="navbar-brand">📦 Gestion de Stock</div>
        <?php if (isLoggedIn()): ?>
        <div class="navbar-menu">
            <a href="/">Accueil</a>
            <a href="/modules/produits/liste.php">Produits</a>
            <a href="/modules/facturation/nouvelle-facture.php">Factures</a>
            <?php if (isAdmin()): ?>
            <a href="/modules/produits/enregistrer.php">Enregistrer Produit</a>
            <?php endif; ?>
            <?php if ($_SESSION['role'] === 'super_admin'): ?>
            <a href="/modules/admin/gestion-compte.php">Comptes</a>
            <a href="/rapports/rapport-journalier.php">Rapports</a>
            <?php endif; ?>
        </div>
        <div class="user-info">
            <span>👤 <?= htmlspecialchars($_SESSION['user_name'] ?? 'Utilisateur') ?> (<?= getUserRole() ?>)</span>
            <a href="/auth/logout.php" style="margin-left: 1rem;">Déconnexion</a>
        </div>
        <?php endif; ?>
    </nav>
    <div class="container" style="flex: 1;">
        <?php if (isset($_SESSION['flash'])): ?>
            <?php foreach ($_SESSION['flash'] as $type => $message): ?>
                <div class="alert alert-<?= $type ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endforeach; ?>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>