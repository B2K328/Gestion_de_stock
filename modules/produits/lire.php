<?php
require_once '../../config/config.php';
require_once '../../auth/session.php';
require_once '../../includes/fonctions-Auth.php';
require_once '../../includes/fonctions-commons.php';
require_once '../facturation/calcul.php';

requireAuth(['admin', 'vendeur']);

$code = sanitizeInput($_GET['code'] ?? '');
if (!$code) redirectTo('/modules/produits/liste.php');

$produits = readJsonFile(PRODUCTS_FILE) ?? [];
$produit = null;
foreach ($produits as $p) {
    if ($p['code'] === $code) {
        $produit = $p;
        break;
    }
}

if (!$produit) {
    setFlashMessage('error', 'Produit non trouvé');
    redirectTo('/modules/produits/liste.php');
}

include '../../includes/header.php';
?>

<div class="container">
    <div class="card">
        <h2><?= htmlspecialchars($produit['nom']) ?></h2>

        <div class="info-box">
            <p><strong>Code :</strong> <?= htmlspecialchars($produit['code']) ?></p>
            <p><strong>Catégorie :</strong> <?= htmlspecialchars($produit['categorie'] ?? '') ?></p>
            <p><strong>Prix Vente :</strong> <?= formaterPrix($produit['prix_vente'] ?? 0) ?></p>
            <p><strong>Stock :</strong> <span style="color: <?= ($produit['quantite_stock'] > $produit['seuil_alerte'] ? 'green' : 'red') ?>;">●</span> <?= (int)$produit['quantite_stock'] ?> / <?= htmlspecialchars($produit['unite'] ?? '') ?></p>
        </div>

        <?php if ((int)$produit['quantite_stock'] <= (int)$produit['seuil_alerte']): ?>
            <div class="alert alert-warning">⚠️ Stock faible - Seuil alerte : <?= (int)$produit['seuil_alerte'] ?></div>
        <?php endif; ?>

        <div class="actions">
            <a href="<?= url('/modules/produits/liste.php') ?>" class="btn-secondary">Retour</a>
            <a href="<?= url('/modules/facturation/nouvelle-facture.php') ?>" class="btn-primary">Facturer</a>
        </div>

        <hr>
        <nav class="links">
            <a href="<?= url('/modules/facturation/nouvelle-facture.php') ?>">Facturation</a> |
            <a href="<?= url('/modules/admin/gestion-compte.php') ?>">Admin</a>
        </nav>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>