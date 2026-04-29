<?php
require_once '../../config/config.php';
require_once '../../auth/session.php';
require_once '../../includes/fonctions-Auth.php';
require_once '../../includes/fonctions-commons.php';

requireAuth(['admin', 'vendeur']);

$produits = readJsonFile(PRODUCTS_FILE) ?? [];
$search = sanitizeInput($_GET['search'] ?? '');
$category = sanitizeInput($_GET['category'] ?? '');

// Filtrage
$filtered = array_filter($produits, function($p) use ($search, $category) {
    $matchSearch = !$search || stripos($p['nom'], $search) !== false || stripos($p['code'], $search) !== false;
    $matchCategory = !$category || ($p['categorie'] ?? '') === $category;
    return $matchSearch && $matchCategory && ($p['active'] ?? true);
});

// Tri par nom
usort($filtered, fn($a, $b) => strcmp($a['nom'], $b['nom']));

// Statistiques
$totalProduits = count($filtered);
$stockFaible = count(array_filter($filtered, fn($p) => ($p['quantite_stock'] ?? 0) <= ($p['seuil_alerte'] ?? 0)));
$valeurStock = array_sum(array_map(fn($p) => ($p['prix_vente'] ?? 0) * ($p['quantite_stock'] ?? 0), $filtered));

// Catégories uniques
$categories = array_unique(array_column($produits, 'categorie'));
sort($categories);

include '../../includes/header.php';
?>

<div class="container">
    <div class="card">
        <h2>📦 Liste des Produits</h2>

        <!-- Statistiques -->
        <div class="stats">
            <div class="stat">
                <span class="stat-number"><?= $totalProduits ?></span>
                <span class="stat-label">Produits actifs</span>
            </div>
            <div class="stat">
                <span class="stat-number" style="color: <?= $stockFaible > 0 ? '#e74c3c' : '#27ae60' ?>;"><?= $stockFaible ?></span>
                <span class="stat-label">Stock faible</span>
            </div>
            <div class="stat">
                <span class="stat-number"><?= formaterPrix($valeurStock) ?></span>
                <span class="stat-label">Valeur stock</span>
            </div>
        </div>

        <!-- Filtres -->
        <form method="GET" style="margin: 1rem 0; display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <input type="text" name="search" placeholder="Rechercher..." value="<?= htmlspecialchars($search) ?>" style="flex: 1; min-width: 200px;">
            <select name="category">
                <option value="">Toutes catégories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Filtrer</button>
            <a href="<?= url('/modules/produits/liste.php') ?>" class="btn-secondary">Effacer</a>
        </form>

        <!-- Table -->
        <?php if (empty($filtered)): ?>
            <p class="empty">Aucun produit trouvé</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Nom</th>
                        <th>Catégorie</th>
                        <th>Prix</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filtered as $produit): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($produit['code']) ?></code></td>
                            <td><strong><?= htmlspecialchars($produit['nom']) ?></strong></td>
                            <td><?= htmlspecialchars($produit['categorie'] ?? '') ?></td>
                            <td><?= formaterPrix($produit['prix_vente'] ?? 0) ?></td>
                            <td>
                                <span style="color: <?= ($produit['quantite_stock'] ?? 0) > ($produit['seuil_alerte'] ?? 0) ? 'green' : 'red' ?>;">
                                    ●
                                </span>
                                <?= (int)($produit['quantite_stock'] ?? 0) ?> / <?= htmlspecialchars($produit['unite'] ?? '') ?>
                            </td>
                            <td>
                                <a href="<?= url('/modules/produits/lire.php?code=' . urlencode($produit['code'])) ?>" class="btn-small">Voir</a>
                                <a href="<?= url('/modules/facturation/nouvelle-facture.php?code=' . urlencode($produit['code'])) ?>" class="btn-small btn-primary">Facturer</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <hr>
        <nav class="links">
            <a href="<?= url('/modules/produits/enregistrer.php') ?>">Enregistrer produit</a> |
            <a href="<?= url('/modules/facturation/nouvelle-facture.php') ?>">Nouvelle facture</a> |
            <a href="<?= url('/modules/admin/gestion-compte.php') ?>">Admin</a>
        </nav>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>