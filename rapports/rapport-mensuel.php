<?php
/**
 * Rapport Mensuel
 * Gestion de Stock - Transco
 * 
 * Génère un rapport agrégé des ventes et activités du mois
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/fonctions-commons.php';
require_once __DIR__ . '/../../includes/fonctions-produits.php';
require_once __DIR__ . '/../../includes/fonctions-facture.php';
require_once __DIR__ . '/../../auth/session.php';

// Vérifier l'authentification
requireAuth();

// Seuls les rôles Manager et Super Administrateur peuvent accéder
$allowedRoles = ['manager', 'super_admin'];
if (!in_array(getUserRole(), $allowedRoles)) {
    setFlashMessage('error', 'Accès refusé. Seul les Managers peuvent consulter les rapports.');
    redirectTo('index.php');
}

/**
 * Obtenir les factures d'un mois spécifique
 * @param int $mois Mois (1-12)
 * @param int $annee Année (YYYY)
 * @return array Factures du mois
 */
function getInvoicesByMonth(int $mois, int $annee): array {
    $invoices = getAllInvoices();
    $result = [];
    
    $moisStr = str_pad($mois, 2, '0', STR_PAD_LEFT);
    $pattern = "{$annee}-{$moisStr}";
    
    foreach ($invoices as $invoice) {
        if (strpos($invoice['date'] ?? '', $pattern) === 0) {
            $result[] = $invoice;
        }
    }
    
    return $result;
}

/**
 * Calculer les statistiques mensuelles
 * @param array $invoices Factures du mois
 * @return array Statistiques
 */
function calculateMonthlyStats(array $invoices): array {
    $totalHT = 0;
    $totalTVA = 0;
    $totalTTC = 0;
    $nombreFactures = 0;
    $nombreJours = [];
    $caissiers = [];
    $produits = [];
    
    foreach ($invoices as $invoice) {
        $nombreFactures++;
        $totalHT += $invoice['montant_ht'] ?? 0;
        $totalTVA += $invoice['montant_tva'] ?? 0;
        $totalTTC += $invoice['montant_ttc'] ?? 0;
        
        // Compter les jours avec ventes
        $jour = substr($invoice['date'] ?? '', 8, 2);
        $nombreJours[$jour] = 1;
        
        // Compter par caissier
        $caissier = $invoice['caissier_nom'] ?? 'Unknown';
        if (!isset($caissiers[$caissier])) {
            $caissiers[$caissier] = [
                'nombre_factures' => 0,
                'montant_ht' => 0,
                'montant_tva' => 0,
                'montant_ttc' => 0
            ];
        }
        $caissiers[$caissier]['nombre_factures']++;
        $caissiers[$caissier]['montant_ht'] += $invoice['montant_ht'] ?? 0;
        $caissiers[$caissier]['montant_tva'] += $invoice['montant_tva'] ?? 0;
        $caissiers[$caissier]['montant_ttc'] += $invoice['montant_ttc'] ?? 0;
        
        // Compter par produit
        foreach ($invoice['articles'] ?? [] as $article) {
            $produitId = $article['produit_id'];
            if (!isset($produits[$produitId])) {
                $produits[$produitId] = [
                    'nom' => $article['nom'],
                    'code' => $article['code'],
                    'quantite_vendue' => 0,
                    'montant_ht' => 0,
                    'montant_ttc' => 0
                ];
            }
            $produits[$produitId]['quantite_vendue'] += $article['quantite'];
            $produits[$produitId]['montant_ht'] += $article['sous_total'];
            $produits[$produitId]['montant_ttc'] += $article['sous_total'] * (1 + TVA_RATE);
        }
    }
    
    // Trier les produits par montant TTC décroissant
    uasort($produits, function($a, $b) {
        return $b['montant_ttc'] <=> $a['montant_ttc'];
    });
    
    return [
        'nombre_factures' => $nombreFactures,
        'nombre_jours_ventes' => count($nombreJours),
        'montant_ht' => round($totalHT, 2),
        'montant_tva' => round($totalTVA, 2),
        'montant_ttc' => round($totalTTC, 2),
        'par_caissier' => $caissiers,
        'par_produit' => $produits,
        'montant_moyen_facture' => $nombreFactures > 0 ? round($totalTTC / $nombreFactures, 2) : 0,
        'montant_moyen_par_jour' => count($nombreJours) > 0 ? round($totalTTC / count($nombreJours), 2) : 0
    ];
}

/**
 * Générer un rapport mensuel au format HTML
 * @param int $mois Mois (défaut: mois actuel)
 * @param int $annee Année (défaut: année actuelle)
 * @return string HTML du rapport
 */
function generateMonthlyReportHTML(int $mois = null, int $annee = null): string {
    if ($mois === null) {
        $mois = (int)date('m');
    }
    if ($annee === null) {
        $annee = (int)date('Y');
    }
    
    // Valider les paramètres
    $mois = max(1, min(12, $mois));
    
    $invoices = getInvoicesByMonth($mois, $annee);
    $stats = calculateMonthlyStats($invoices);
    
    $nomMois = [
        'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
        'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
    ];
    
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Rapport Mensuel - <?= $nomMois[$mois-1] ?> <?= $annee ?></title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
            .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #2c3e50; padding-bottom: 20px; }
            h1 { color: #2c3e50; margin-bottom: 10px; }
            .period { color: #7f8c8d; font-size: 16px; }
            .controls { display: flex; gap: 10px; justify-content: center; margin-bottom: 20px; }
            .controls form { display: flex; gap: 10px; }
            .controls select, .controls button { padding: 8px 12px; border: 1px solid #bdc3c7; border-radius: 4px; }
            .controls button { background: #3498db; color: white; cursor: pointer; border: none; }
            .controls button:hover { background: #2980b9; }
            .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
            .stat-box { background: #ecf0f1; padding: 20px; border-radius: 8px; text-align: center; border-left: 4px solid #3498db; }
            .stat-box.success { border-left-color: #27ae60; }
            .stat-box.warning { border-left-color: #f39c12; }
            .stat-box label { display: block; color: #7f8c8d; font-size: 12px; margin-bottom: 8px; text-transform: uppercase; }
            .stat-box .value { font-size: 24px; font-weight: bold; color: #2c3e50; }
            .section { margin-bottom: 30px; }
            .section h2 { color: #2c3e50; margin-bottom: 15px; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ecf0f1; }
            th { background: #34495e; color: white; font-weight: bold; }
            tr:hover { background: #f8f9fa; }
            .text-right { text-align: right; }
            .total-row { font-weight: bold; background: #ecf0f1; }
            .print-btn { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 20px 0; }
            .print-btn:hover { background: #2980b9; }
            .top-products { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 15px; }
            .product-card { background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #27ae60; }
            .product-card .product-name { font-weight: bold; margin-bottom: 8px; }
            .product-card .product-stats { font-size: 13px; color: #7f8c8d; }
            @media print {
                .print-btn, .controls { display: none; }
                body { padding: 0; }
                .container { box-shadow: none; padding: 0; }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>📈 Rapport Mensuel</h1>
                <p class="period"><?= $nomMois[$mois-1] ?> <?= $annee ?></p>
            </div>

            <!-- Contrôles de navigation -->
            <div class="controls">
                <form method="GET">
                    <select name="mois" onchange="this.form.submit()">
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?= $i ?>" <?= $i === $mois ? 'selected' : '' ?>>
                            <?= $nomMois[$i-1] ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                    <select name="annee" onchange="this.form.submit()">
                        <?php for ($y = $annee - 2; $y <= $annee + 1; $y++): ?>
                        <option value="<?= $y ?>" <?= $y === $annee ? 'selected' : '' ?>>
                            <?= $y ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </form>
            </div>

            <!-- Statistiques générales -->
            <div class="stats-grid">
                <div class="stat-box success">
                    <label>Nombre de Factures</label>
                    <div class="value"><?= $stats['nombre_factures'] ?></div>
                </div>
                <div class="stat-box">
                    <label>Jours avec Ventes</label>
                    <div class="value"><?= $stats['nombre_jours_ventes'] ?></div>
                </div>
                <div class="stat-box">
                    <label>Montant HT</label>
                    <div class="value"><?= number_format($stats['montant_ht'], 2, ',', ' ') ?></div>
                </div>
                <div class="stat-box warning">
                    <label>Montant TVA (18%)</label>
                    <div class="value"><?= number_format($stats['montant_tva'], 2, ',', ' ') ?></div>
                </div>
                <div class="stat-box success">
                    <label>Total TTC</label>
                    <div class="value"><?= number_format($stats['montant_ttc'], 2, ',', ' ') ?></div>
                </div>
                <div class="stat-box">
                    <label>Montant Moyen/Facture</label>
                    <div class="value"><?= number_format($stats['montant_moyen_facture'], 2, ',', ' ') ?></div>
                </div>
                <div class="stat-box">
                    <label>Montant Moyen/Jour</label>
                    <div class="value"><?= number_format($stats['montant_moyen_par_jour'], 2, ',', ' ') ?></div>
                </div>
            </div>

            <!-- Produits les plus vendus -->
            <div class="section">
                <h2>🏆 Top Produits</h2>
                <div class="top-products">
                    <?php 
                    $topProducts = array_slice($stats['par_produit'], 0, 6);
                    foreach ($topProducts as $produit): 
                    ?>
                    <div class="product-card">
                        <div class="product-name"><?= htmlspecialchars($produit['nom']) ?></div>
                        <div class="product-stats">
                            <div>Quantité: <?= $produit['quantite_vendue'] ?></div>
                            <div>Montant HT: <?= number_format($produit['montant_ht'], 2, ',', ' ') ?></div>
                            <div>Montant TTC: <strong><?= number_format($produit['montant_ttc'], 2, ',', ' ') ?></strong></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Ventes par caissier -->
            <div class="section">
                <h2>👥 Ventes par Caissier</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Caissier</th>
                            <th class="text-right">Nombre de Factures</th>
                            <th class="text-right">Montant HT</th>
                            <th class="text-right">TVA (18%)</th>
                            <th class="text-right">Montant TTC</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['par_caissier'] as $caissier => $data): ?>
                        <tr>
                            <td><?= htmlspecialchars($caissier) ?></td>
                            <td class="text-right"><?= $data['nombre_factures'] ?></td>
                            <td class="text-right"><?= number_format($data['montant_ht'], 2, ',', ' ') ?></td>
                            <td class="text-right"><?= number_format($data['montant_tva'], 2, ',', ' ') ?></td>
                            <td class="text-right"><?= number_format($data['montant_ttc'], 2, ',', ' ') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td><strong>TOTAL</strong></td>
                            <td class="text-right"><strong><?= $stats['nombre_factures'] ?></strong></td>
                            <td class="text-right"><strong><?= number_format($stats['montant_ht'], 2, ',', ' ') ?></strong></td>
                            <td class="text-right"><strong><?= number_format($stats['montant_tva'], 2, ',', ' ') ?></strong></td>
                            <td class="text-right"><strong><?= number_format($stats['montant_ttc'], 2, ',', ' ') ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Tous les produits vendus -->
            <div class="section">
                <h2>📦 Tous les Produits Vendus</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Nom du Produit</th>
                            <th class="text-right">Quantité Vendue</th>
                            <th class="text-right">Montant HT</th>
                            <th class="text-right">Montant TTC</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['par_produit'] as $produit): ?>
                        <tr>
                            <td><?= htmlspecialchars($produit['code']) ?></td>
                            <td><?= htmlspecialchars($produit['nom']) ?></td>
                            <td class="text-right"><?= $produit['quantite_vendue'] ?></td>
                            <td class="text-right"><?= number_format($produit['montant_ht'], 2, ',', ' ') ?></td>
                            <td class="text-right"><?= number_format($produit['montant_ttc'], 2, ',', ' ') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="2"><strong>TOTAL</strong></td>
                            <td class="text-right"><strong><?php 
                                $totalQty = array_sum(array_column($stats['par_produit'], 'quantite_vendue'));
                                echo $totalQty;
                            ?></strong></td>
                            <td class="text-right"><strong><?= number_format($stats['montant_ht'], 2, ',', ' ') ?></strong></td>
                            <td class="text-right"><strong><?= number_format($stats['montant_ttc'], 2, ',', ' ') ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <button class="print-btn" onclick="window.print()">🖨️ Imprimer</button>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

// Gestion de la requête
$pageTitle = 'Rapport Mensuel - Gestion de Stock';
$mois = (int)($_GET['mois'] ?? date('m'));
$annee = (int)($_GET['annee'] ?? date('Y'));

echo generateMonthlyReportHTML($mois, $annee);
?>
