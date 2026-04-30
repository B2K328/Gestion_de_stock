<?php
/**
 * Affichage d'une Facture
 * 
 * Affiche les détails complets d'une facture générée
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/fonctions-commons.php';
require_once __DIR__ . '/../../includes/fonctions-produits.php';
require_once __DIR__ . '/../../includes/fonctions-facture.php';
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../includes/header.php';

// Vérifier l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

$pageTitle = 'Détails Facture';

// Récupérer l'ID de la facture
$facture_id = $_GET['id'] ?? null;

if (!$facture_id) {
    echo '<div class="container"><div class="alert alert-danger">ID facture non spécifié.</div></div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

// Charger les factures
$factures = getAllInvoices();
$facture = null;

foreach ($factures as $f) {
    if (($f['id'] ?? '') === $facture_id) {
        $facture = $f;
        break;
    }
}

if (!$facture) {
    echo '<div class="container"><div class="alert alert-danger">Facture introuvable.</div></div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

?>

<div class="container">
    <div class="page-header">
        <h1>💳 Facture #<?= htmlspecialchars($facture['id'] ?? '') ?></h1>
        <p>Détails et récapitulatif de la transaction</p>
    </div>

    <!-- En-tête de la facture -->
    <div class="card mb-3">
        <div class="card-header">
            <h3>📋 Informations Générales</h3>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <div>
                    <table style="width: 100%;">
                        <tr>
                            <td style="font-weight: bold; width: 30%;">ID Facture:</td>
                            <td><?= htmlspecialchars($facture['id'] ?? '') ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;">Date:</td>
                            <td><?= htmlspecialchars($facture['date'] ?? '') ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;">Heure:</td>
                            <td><?= htmlspecialchars($facture['heure'] ?? '') ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;">Caissier:</td>
                            <td><?= htmlspecialchars($facture['caissier_nom'] ?? 'N/A') ?></td>
                        </tr>
                    </table>
                </div>

                <div>
                    <table style="width: 100%;">
                        <tr>
                            <td style="font-weight: bold; width: 30%;">Client:</td>
                            <td><?= htmlspecialchars($facture['client']['nom'] ?? 'Non spécifié') ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;">Mode Paiement:</td>
                            <td><?= ucfirst(htmlspecialchars($facture['mode_paiement'] ?? '')) ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;">Statut:</td>
                            <td>
                                <span style="display: inline-block; padding: 0.3rem 0.8rem; border-radius: 4px; background: #d4edda; color: #155724;">
                                    <?= ucfirst(htmlspecialchars($facture['statut'] ?? '')) ?>
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Articles de la facture -->
    <div class="card mb-3">
        <div class="card-header">
            <h3>📦 Articles</h3>
        </div>
        <div class="card-body">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid #ddd;">
                        <th style="text-align: left; padding: 0.75rem;">Code</th>
                        <th style="text-align: left; padding: 0.75rem;">Produit</th>
                        <th style="text-align: center; padding: 0.75rem;">Unité</th>
                        <th style="text-align: right; padding: 0.75rem;">Quantité</th>
                        <th style="text-align: right; padding: 0.75rem;">Prix Unitaire</th>
                        <th style="text-align: right; padding: 0.75rem;">Sous-Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_ht_check = 0;
                    foreach ($facture['articles'] ?? [] as $article): 
                        $sous_total = $article['sous_total'] ?? 0;
                        $total_ht_check += $sous_total;
                    ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 0.75rem;"><?= htmlspecialchars($article['code'] ?? '') ?></td>
                            <td style="padding: 0.75rem;"><?= htmlspecialchars($article['nom'] ?? '') ?></td>
                            <td style="text-align: center; padding: 0.75rem;"><?= htmlspecialchars($article['unite'] ?? '') ?></td>
                            <td style="text-align: right; padding: 0.75rem;"><?= $article['quantite'] ?? 0 ?></td>
                            <td style="text-align: right; padding: 0.75rem;"><?= number_format($article['prix_unitaire'] ?? 0, 2) ?> CDF</td>
                            <td style="text-align: right; padding: 0.75rem; font-weight: bold;"><?= number_format($sous_total, 2) ?> CDF</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Récapitulatif Financier -->
    <div class="card">
        <div class="card-header">
            <h3>💰 Récapitulatif Financier</h3>
        </div>
        <div class="card-body">
            <div style="max-width: 400px; margin-left: auto;">
                <div class="summary-box">
                    <div class="summary-row">
                        <span class="summary-label">Total HT:</span>
                        <span class="summary-value"><?= number_format($facture['montant_ht'] ?? 0, 2) ?> CDF</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">TVA (<?= $facture['taux_tva'] ?? 18 ?>%):</span>
                        <span class="summary-value"><?= number_format($facture['montant_tva'] ?? 0, 2) ?> CDF</span>
                    </div>
                    <div class="summary-row" style="border-top: 2px solid #ddd; padding-top: 1rem; margin-top: 1rem; font-size: 1.2rem; font-weight: bold;">
                        <span class="summary-label">Net à Payer:</span>
                        <span class="summary-value"><?= number_format($facture['montant_ttc'] ?? 0, 2) ?> CDF</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: center;">
        <a href="/modules/facturation/nouvelle-facture.php" class="btn btn-primary">+ Nouvelle Facture</a>
        <button class="btn btn-secondary" onclick="window.print()">🖨️ Imprimer</button>
        <a href="/index.php" class="btn btn-secondary">Accueil</a>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
