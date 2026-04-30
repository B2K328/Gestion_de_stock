<?php
/**
 * Rapport Journalier
 * Gestion de Stock - Transco
 * 
 * Génère un rapport détaillé des factures et transactions du jour
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
 * Obtenir les factures d'une date spécifique
 * @param string $date Date au format YYYY-MM-DD
 * @return array Factures du jour
 */
function getInvoicesByDate(string $date): array {
    $invoices = getAllInvoices();
    $result = [];
    
    foreach ($invoices as $invoice) {
        if (substr($invoice['date'] ?? '', 0, 10) === $date) {
            $result[] = $invoice;
        }
    }
    
    return $result;
}

/**
 * Calculer les statistiques journalières
 * @param array $invoices Factures du jour
 * @return array Statistiques
 */
function calculateDailyStats(array $invoices): array {
    $totalVentes = 0;
    $totalHT = 0;
    $totalTVA = 0;
    $totalTTC = 0;
    $nombreFactures = 0;
    $caissiers = [];
    $produits = [];
    
    foreach ($invoices as $invoice) {
        // Compter les factures
        $nombreFactures++;
        $totalHT += $invoice['montant_ht'] ?? 0;
        $totalTVA += $invoice['montant_tva'] ?? 0;
        $totalTTC += $invoice['montant_ttc'] ?? 0;
        
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
    
    return [
        'date' => date('d/m/Y'),
        'nombre_factures' => $nombreFactures,
        'montant_ht' => round($totalHT, 2),
        'montant_tva' => round($totalTVA, 2),
        'montant_ttc' => round($totalTTC, 2),
        'par_caissier' => $caissiers,
        'par_produit' => $produits,
        'montant_moyen_facture' => $nombreFactures > 0 ? round($totalTTC / $nombreFactures, 2) : 0
    ];
}

/**
 * Générer un rapport journalier au format HTML
 * @param string $date Date au format YYYY-MM-DD (défaut: aujourd'hui)
 * @return string HTML du rapport
 */
function generateDailyReportHTML(string $date = null): string {
    if ($date === null) {
        $date = date('Y-m-d');
    }
    
    $invoices = getInvoicesByDate($date);
    $stats = calculateDailyStats($invoices);
    
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Rapport Journalier - <?= $stats['date'] ?></title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
            .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #2c3e50; padding-bottom: 20px; }
            h1 { color: #2c3e50; margin-bottom: 10px; }
            .date { color: #7f8c8d; font-size: 16px; }
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
            .currency::after { content: ' ' CURRENCY; }
            .print-btn { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 20px 0; }
            .print-btn:hover { background: #2980b9; }
            @media print {
                .print-btn { display: none; }
                body { padding: 0; }
                .container { box-shadow: none; padding: 0; }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>📊 Rapport Journalier</h1>
                <p class="date"><?= htmlspecialchars($stats['date']) ?></p>
            </div>

            <!-- Statistiques générales -->
            <div class="stats-grid">
                <div class="stat-box success">
                    <label>Nombre de Factures</label>
                    <div class="value"><?= $stats['nombre_factures'] ?></div>
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
            </div>

            <!-- Ventes par caissier -->
            <div class="section">
                <h2>📝 Ventes par Caissier</h2>
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
                    </tbody>
                </table>
            </div>

            <!-- Produits vendus -->
            <div class="section">
                <h2>📦 Produits Vendus</h2>
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
                    </tbody>
                </table>
            </div>

            <!-- Toutes les factures du jour -->
            <div class="section">
                <h2>📄 Toutes les Factures</h2>
                <table>
                    <thead>
                        <tr>
                            <th>N° Facture</th>
                            <th>Heure</th>
                            <th>Caissier</th>
                            <th>Montant HT</th>
                            <th>TVA</th>
                            <th class="text-right">Montant TTC</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $invoice): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($invoice['numero'] ?? $invoice['id']) ?></strong></td>
                            <td><?= htmlspecialchars(substr($invoice['heure'] ?? '', 0, 5)) ?></td>
                            <td><?= htmlspecialchars($invoice['caissier_nom'] ?? 'Unknown') ?></td>
                            <td><?= number_format($invoice['montant_ht'] ?? 0, 2, ',', ' ') ?></td>
                            <td><?= number_format($invoice['montant_tva'] ?? 0, 2, ',', ' ') ?></td>
                            <td class="text-right"><strong><?= number_format($invoice['montant_ttc'] ?? 0, 2, ',', ' ') ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
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
$pageTitle = 'Rapport Journalier - Gestion de Stock';
$date = sanitizeInput($_GET['date'] ?? date('Y-m-d'));

// Valider le format de la date
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}

echo generateDailyReportHTML($date);
?>
