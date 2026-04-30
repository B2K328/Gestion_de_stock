<?php
require_once '../../config/config.php';
require_once '../../auth/session.php';
require_once '../../includes/fonctions-Auth.php';
require_once '../../includes/fonctions-commons.php';
require_once __DIR__ . '/calcul.php';

requireAuth(['admin', 'vendeur']);

if (!isset($_SESSION['panier'])) $_SESSION['panier'] = [];

$produits = readJsonFile(PRODUCTS_FILE) ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code'])) {
    $code = sanitizeInput($_POST['code']);
    foreach ($produits as $produit) {
        if ($produit['code'] === $code && $produit['active']) {
            if ($produit['quantite_stock'] <= 0) {
                setFlashMessage('error', 'Stock insuffisant');
            } else {
                $found = false;
                foreach ($_SESSION['panier'] as &$item) {
                    if ($item['code'] === $code) {
                        $item['quantite']++;
                        $item['sous_total_ht'] = calculSousTotal($item['prix_unitaire_ht'], $item['quantite']);
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $_SESSION['panier'][] = [
                        'code' => $code,
                        'nom' => $produit['nom'],
                        'prix_unitaire_ht' => $produit['prix_vente'],
                        'quantite' => 1,
                        'sous_total_ht' => calculSousTotal($produit['prix_vente'], 1)
                    ];
                }
                setFlashMessage('success', 'Produit ajouté');
            }
            break;
        }
    }
}

$panier = $_SESSION['panier'];
$totalHT = calculTotalHT($panier);
$tva = calculTVA($totalHT);
$ttc = calculTTC($totalHT, $tva);

include '../../includes/header.php';
?>

<div class="container">
    <div class="card">
        <h2>Nouvelle Facture</h2>
        
        <?php if ($msg = getFlashMessage('success')): ?>
            <div class="alert alert-success">✓ <?= htmlspecialchars($msg) ?></div>
        <?php elseif ($msg = getFlashMessage('error')): ?>
            <div class="alert alert-error">✗ <?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <div id="reader" style="max-width: 300px; margin: 1rem 0;"></div>

        <form method="POST">
            <input type="text" name="code" id="code" placeholder="Code produit" required autofocus>
            <button type="submit">Ajouter</button>
        </form>

        <?php if (!empty($panier)): ?>
            <table class="table">
                <thead>
                    <tr><th>Produit</th><th>Prix</th><th>Qté</th><th>Total</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($panier as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['nom']) ?></td>
                            <td><?= formaterPrix($item['prix_unitaire_ht']) ?></td>
                            <td><?= (int)$item['quantite'] ?></td>
                            <td><?= formaterPrix($item['sous_total_ht']) ?></td>
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
                <form method="POST" action="<?= url('/modules/facturation/afficher-facture.php') ?>" style="flex:1;">
                    <button type="submit" class="btn-primary">Valider</button>
                </form>
                <a href="<?= url('/modules/facturation/vider-panier.php') ?>" class="btn-danger">Vider</a>
            </div>
        <?php else: ?>
            <p class="empty">Panier vide</p>
        <?php endif; ?>

        <hr>
        <nav class="links">
            <a href="<?= url('/modules/produits/liste.php') ?>">Produits</a> |
            <a href="<?= url('/modules/admin/gestion-compte.php') ?>">Admin</a>
        </nav>
    </div>
</div>

<script>
document.querySelectorAll('.alert').forEach(el => setTimeout(() => el.style.display='none', 2000));
document.querySelector('form')?.addEventListener('submit', function(e) {
    setTimeout(() => {
        const codeInput = document.getElementById('code');
        if (codeInput) codeInput.value = '';
    }, 100);
});
</script>
<script src="https://unpkg.com/html5-qrcode"></script>
<script src="../../assets/js/scanner.js"></script>

<?php include '../../includes/footer.php'; ?>