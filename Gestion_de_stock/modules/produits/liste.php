<?php
require_once '../../config/config.php';
require_once '../../auth/session.php';
require_once '../../includes/fonctions-Auth.php';
require_once '../../includes/fonctions-commons.php';
require_once '../facturation/calcul.php';

requireAuth(['admin', 'vendeur']);

$produits = array_filter(readJsonFile(PRODUCTS_FILE) ?? [], fn($p) => $p['active'] ?? true);

include '../../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
            <h2>Produits</h2>
            <?php if (isAdmin()): ?>
                <a href="<?= url('/modules/produits/enregistrer.php') ?>" class="btn-primary">+ Ajouter</a>
            <?php endif; ?>
        </div>

        <?php if (!empty($produits)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Produit</th>
                        <th>Prix HT</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produits as $p): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($p['code']) ?></strong></td>
                            <td>
                                <?= htmlspecialchars($p['nom']) ?><br>
                                <small><?= htmlspecialchars($p['categorie'] ?? '') ?></small>
                            </td>
                            <td><?= formaterPrix($p['prix_vente'] ?? 0) ?></td>
                            <td>
                                <?php 
                                    $stock = (int)($p['quantite_stock'] ?? 0);
                                    $seuil = (int)($p['seuil_alerte'] ?? 10);
                                    $color = ($stock <= $seuil) ? 'red' : 'green';
                                ?>
                                <span style="color: <?= $color ?>;">●</span> <?= $stock ?> / <?= htmlspecialchars($p['unite'] ?? '') ?>
                            </td>
                            <td>
                                <a href="<?= url('/modules/produits/lire.php?code=' . urlencode($p['code'])) ?>">Détails</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Aucun produit</p>
        <?php endif; ?>

        <hr>
        <nav class="links">
            <a href="<?= url('/modules/facturation/nouvelle-facture.php') ?>">Facturation</a> |
            <a href="<?= url('/modules/admin/gestion-compte.php') ?>">Admin</a>
        </nav>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>