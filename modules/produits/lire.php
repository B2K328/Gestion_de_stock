<?php
/**
 * Détail d'un produit
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/fonctions-commons.php';
require_once __DIR__ . '/../../includes/fonctions-produits.php';
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../includes/header.php';

if (!isLoggedIn()) {
    header('Location: /auth/login.php');
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    echo '<div class="alert alert-danger">ID produit non spécifié.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

$product = findProductById($id);
if (!$product) {
    echo '<div class="alert alert-danger">Produit introuvable.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

$pageTitle = 'Produit - ' . ($product['nom'] ?? 'Détail');
?>

<div class="page-header">
    <h1>📦 Détail Produit</h1>
    <p>Détails complets du produit sélectionné</p>
</div>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <div>
            <h2><?= htmlspecialchars($product['nom'] ?? '') ?></h2>
            <div style="color:var(--gray);">Code: <?= htmlspecialchars($product['code'] ?? '') ?></div>
        </div>
        <div>
            <a href="/modules/produits/liste.php" class="btn btn-secondary">← Retour</a>
            <?php if (isAdmin()): ?>
                <a href="/modules/produits/enregistrer.php?edit=<?= urlencode($product['id']) ?>" class="btn btn-primary">Modifier</a>
                <form method="POST" action="/modules/produits/lire.php?id=<?= urlencode($product['id']) ?>" style="display:inline;" onsubmit="return confirm('Supprimer ce produit ?');">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <table>
        <tr><th>Nom</th><td><?= htmlspecialchars($product['nom'] ?? '') ?></td></tr>
        <tr><th>Code</th><td><?= htmlspecialchars($product['code'] ?? '') ?></td></tr>
        <tr><th>Catégorie</th><td><?= htmlspecialchars($product['categorie'] ?? '') ?></td></tr>
        <tr><th>Unité</th><td><?= htmlspecialchars($product['unite'] ?? '') ?></td></tr>
        <tr><th>Prix d'Achat</th><td><?= number_format($product['prix_achat'] ?? 0,2) ?> CDF</td></tr>
        <tr><th>Prix de Vente</th><td><?= number_format($product['prix_vente'] ?? 0,2) ?> CDF</td></tr>
        <tr><th>Quantité en Stock</th><td><?= (int)($product['quantite_stock'] ?? 0) ?></td></tr>
        <tr><th>Seuil d'Alerte</th><td><?= (int)($product['seuil_alerte'] ?? 0) ?></td></tr>
        <tr><th>Date création</th><td><?= htmlspecialchars($product['created_at'] ?? '') ?></td></tr>
        <tr><th>Date mise à jour</th><td><?= htmlspecialchars($product['updated_at'] ?? '') ?></td></tr>
    </table>
</div>

<?php
// Traitement suppression si demandé
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (isAdmin()) {
        if (deleteProduct($product['id'])) {
            $_SESSION['flash'] = ['success' => 'Produit supprimé avec succès.'];
            header('Location: /modules/produits/liste.php');
            exit;
        } else {
            echo '<div class="alert alert-danger">Erreur lors de la suppression.</div>';
        }
    } else {
        echo '<div class="alert alert-danger">Accès refusé.</div>';
    }
}

require_once __DIR__ . '/../../includes/footer.php';
?>