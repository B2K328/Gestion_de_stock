<?php
/**
 * Liste des produits
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

$pageTitle = 'Liste des Produits';
$products = getAllProducts();
?>

<div class="page-header">
    <h1>📦 Liste des Produits</h1>
    <p>Tous les produits enregistrés dans le système</p>
</div>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <div></div>
        <div>
            <?php if (isAdmin()): ?>
                <a href="/modules/produits/enregistrer.php" class="btn btn-primary">+ Enregistrer Produit</a>
            <?php endif; ?>
            <a href="/modules/facturation/nouvelle-facture.php" class="btn btn-success">Nouvelle Facture</a>
        </div>
    </div>

    <?php if (empty($products)): ?>
        <div class="alert alert-info">Aucun produit trouvé. Utilisez "Enregistrer Produit" pour ajouter des articles.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Nom</th>
                        <th>Catégorie</th>
                        <th>Prix Vente (CDF)</th>
                        <th>Stock</th>
                        <th>Seuil Alerte</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['code'] ?? '') ?></td>
                            <td><?= htmlspecialchars($p['nom'] ?? '') ?></td>
                            <td><?= htmlspecialchars($p['categorie'] ?? '') ?></td>
                            <td><?= number_format($p['prix_vente'] ?? 0, 2) ?></td>
                            <td><?= (int)($p['quantite_stock'] ?? 0) ?></td>
                            <td><?= (int)($p['seuil_alerte'] ?? 0) ?></td>
                            <td>
                                <a href="/modules/produits/lire.php?id=<?= urlencode($p['id']) ?>" class="btn btn-secondary">Voir</a>
                                <?php if (isAdmin()): ?>
                                    <a href="/modules/produits/enregistrer.php?edit=<?= urlencode($p['id']) ?>" class="btn btn-primary">Modifier</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>