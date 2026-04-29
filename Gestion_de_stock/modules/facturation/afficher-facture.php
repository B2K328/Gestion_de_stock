<?php
require_once '../../config/config.php';
require_once '../../auth/session.php';
require_once '../../includes/fonctions-Auth.php';
require_once '../../includes/fonctions-commons.php';
require_once __DIR__ . '/calcul.php';

requireAuth(['admin', 'vendeur']);

if (empty($_SESSION['panier'])) {
    setFlashMessage('error', 'Panier vide');
    redirectTo('/modules/facturation/nouvelle-facture.php');
}

$panier = $_SESSION['panier'];
$totalHT = calculTotalHT($panier);
$tva = calculTVA($totalHT);
$ttc = calculTTC($totalHT, $tva);

$id = 'FAC-' . date('Ymd') . '-' . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
$facture = [
    'id' => $id,
    'date' => date('Y-m-d'),
    'heure' => date('H:i:s'),
    'caissier' => $_SESSION['user_name'] ?? 'Admin',
    'articles' => $panier,
    'total_ht' => $totalHT,
    'tva' => $tva,
    'total_ttc' => $ttc,
    'created_at' => date('Y-m-d H:i:s')
];

$factures = readJsonFile(INVOICES_FILE) ?? [];
$factures[] = $facture;
writeJsonFile(INVOICES_FILE, $factures);

// Décrémenter stock
$produits = readJsonFile(PRODUCTS_FILE) ?? [];
foreach ($panier as $item) {
    foreach ($produits as &$p) {
        if ($p['code'] === $item['code']) {
            $p['quantite_stock'] -= (int)$item['quantite'];
            break;
        }
    }
}
writeJsonFile(PRODUCTS_FILE, $produits);

$_SESSION['panier'] = [];

include '../../includes/header.php';
?>

<div class="container">
    <div class="card">
        <h2>✓ Facture Validée</h2>
        
        <div class="info-box">
            <p><strong>N° Facture :</strong> <?= htmlspecialchars($id) ?></p>
            <p><strong>Date :</strong> <?= htmlspecialchars($facture['date']) ?></p>
            <p><strong>Caissier :</strong> <?= htmlspecialchars($facture['caissier']) ?></p>
        </div>

        <table class="table">
            <thead>
                <tr><th>Produit</th><th>Code</th><th>Prix</th><th>Qté</th><th>Total</th></tr>
            </thead>
            <tbody>
                <?php foreach ($panier as $a): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['nom']) ?></td>
                        <td><?= htmlspecialchars($a['code']) ?></td>
                        <td><?= formaterPrix($a['prix_unitaire_ht']) ?></td>
                        <td><?= (int)$a['quantite'] ?></td>
                        <td><?= formaterPrix($a['sous_total_ht']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="totals">
            <p><strong>Total HT :</strong> <?= formaterPrix($totalHT) ?></p>
            <p><strong>TVA 18% :</strong> <?= formaterPrix($tva) ?></p>
            <p><strong>Total TTC :</strong> <?= formaterPrix($ttc) ?></p>
        </div>

        <div class="actions">
            <a href="<?= url('/modules/facturation/nouvelle-facture.php') ?>" class="btn-primary">Nouvelle Facture</a>
            <a href="<?= url('/index.php') ?>" class="btn-secondary">Dashboard</a>
        </div>

        <hr>
        <nav class="links">
            <a href="<?= url('/modules/produits/liste.php') ?>">Produits</a> |
            <a href="<?= url('/modules/admin/gestion-compte.php') ?>">Admin</a> |
            <a href="<?= url('/auth/logout.php') ?>">Logout</a>
        </nav>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>