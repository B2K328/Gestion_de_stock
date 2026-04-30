<?php
/**
 * Fonctions de Calcul et Facturation
 * Gestion de Stock - Transco
 * 
 * Ce fichier contient UNIQUEMENT les fonctions réutilisables
 * sans contrôle d'accès. À inclure dans d'autres fichiers.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/fonctions-commons.php';
require_once __DIR__ . '/fonctions-produits.php';
require_once __DIR__ . '/fonctions-facture.php';

/**
 * Calculer le montant TVA pour une facture
 * @param float $montantHT Montant hors taxe
 * @param float $tauxTVA Taux TVA (défaut 18%)
 * @return float Montant TVA
 */
function calculateTVA(float $montantHT, float $tauxTVA = TVA_RATE): float {
    return round($montantHT * $tauxTVA, 2);
}

/**
 * Calculer le total TTC (Toutes Taxes Comprises)
 * @param float $montantHT Montant hors taxe
 * @param float $tauxTVA Taux TVA (défaut 18%)
 * @return float Montant TTC
 */
function calculateTTC(float $montantHT, float $tauxTVA = TVA_RATE): float {
    $tva = calculateTVA($montantHT, $tauxTVA);
    return round($montantHT + $tva, 2);
}

/**
 * Générer un identifiant unique de facture
 * Format : FAC-YYYYMMDD-NNNNN (ex: FAC-20260417-00001)
 * @return string Identifiant unique
 */
function generateUniqueInvoiceId(): string {
    static $lastDate = null;
    static $dayCounter = 0;
    
    $date = date('Ymd');
    $invoices = getAllInvoices();
    
    // Réinitialiser le compteur si c'est un nouveau jour
    if ($lastDate !== $date) {
        $lastDate = $date;
        $dayCounter = 0;
    }
    
    // Compter les factures du jour
    $countToday = 0;
    foreach ($invoices as $invoice) {
        $invoiceDate = substr($invoice['id'] ?? '', 4, 8);
        if ($invoiceDate === $date) {
            $countToday++;
        }
    }
    
    // Utiliser le max entre le compteur statique et le compteur du fichier
    $dayCounter = max($dayCounter + 1, $countToday + 1);
    
    $num = str_pad($dayCounter, 5, '0', STR_PAD_LEFT);
    return "FAC-{$date}-{$num}";
}

/**
 * Valider les articles d'une facture avant calcul
 * @param array $articles Articles à valider
 * @return array Array avec clés 'valid' (bool) et 'errors' (array)
 */
function validateInvoiceItems(array $articles): array {
    $errors = [];
    
    if (empty($articles)) {
        $errors[] = "La facture doit contenir au moins un article.";
        return ['valid' => false, 'errors' => $errors];
    }
    
    foreach ($articles as $index => $article) {
        // Vérifier que le produit existe
        if (empty($article['produit_id'])) {
            $errors[] = "Article {$index}: ID produit manquant.";
            continue;
        }
        
        $product = findProductById($article['produit_id']);
        if (!$product) {
            $errors[] = "Article {$index}: Produit non trouvé.";
            continue;
        }
        
        // Vérifier la quantité
        if (empty($article['quantite']) || $article['quantite'] <= 0) {
            $errors[] = "Article {$index}: Quantité invalide.";
            continue;
        }
        
        // Vérifier le stock disponible
        if ($product['quantite_stock'] < $article['quantite']) {
            $errors[] = "Article {$index} ({$product['nom']}): Stock insuffisant. "
                      . "Disponible: {$product['quantite_stock']}, Demandé: {$article['quantite']}.";
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Calculer le récapitulatif d'une facture
 * @param array $articles Articles de la facture
 * @return array Récapitulatif avec calculs TVA
 */
function calculateInvoiceSummary(array $articles): array {
    $montantHT = 0;
    $detailArticles = [];
    
    foreach ($articles as $article) {
        $product = findProductById($article['produit_id']);
        if (!$product) continue;
        
        $quantite = (int)$article['quantite'];
        $prixUnitaire = (float)$product['prix_vente'];
        $sousTotal = $quantite * $prixUnitaire;
        $montantHT += $sousTotal;
        
        $detailArticles[] = [
            'produit_id' => $article['produit_id'],
            'code' => $product['code'],
            'nom' => $product['nom'],
            'unite' => $product['unite'],
            'quantite' => $quantite,
            'prix_unitaire' => $prixUnitaire,
            'sous_total' => round($sousTotal, 2)
        ];
    }
    
    $montantHT = round($montantHT, 2);
    $montantTVA = calculateTVA($montantHT);
    $montantTTC = calculateTTC($montantHT);
    
    return [
        'montant_ht' => $montantHT,
        'taux_tva' => TVA_RATE * 100,
        'montant_tva' => $montantTVA,
        'montant_ttc' => $montantTTC,
        'net_a_payer' => $montantTTC,
        'articles' => $detailArticles,
        'nombre_articles' => count($detailArticles)
    ];
}

/**
 * Créer et enregistrer une facture avec gestion du stock
 * @param array $data Données de la facture
 * @return array|false Facture créée ou false en cas d'erreur
 */
function createInvoiceWithStockUpdate(array $data): array|false {
    // Valider les articles
    $validation = validateInvoiceItems($data['articles'] ?? []);
    if (!$validation['valid']) {
        if (isset($_SESSION)) {
            $_SESSION['errors'] = $validation['errors'];
        }
        return false;
    }
    
    // Calculer le récapitulatif
    $summary = calculateInvoiceSummary($data['articles']);
    
    // Créer la facture
    $factures = getAllInvoices();
    $newInvoice = [
        'id' => generateUniqueInvoiceId(),
        'numero' => generateInvoiceNumber(),
        'date' => date('Y-m-d'),
        'heure' => date('H:i:s'),
        'caissier_id' => $_SESSION['user_id'] ?? '',
        'caissier_nom' => $_SESSION['user_name'] ?? 'Unknown',
        'client' => $data['client'] ?? [
            'nom' => 'Client Standard',
            'contact' => ''
        ],
        'articles' => $summary['articles'],
        'montant_ht' => $summary['montant_ht'],
        'taux_tva' => $summary['taux_tva'],
        'montant_tva' => $summary['montant_tva'],
        'montant_ttc' => $summary['montant_ttc'],
        'net_a_payer' => $summary['net_a_payer'],
        'statut' => 'creee',
        'mode_paiement' => $data['mode_paiement'] ?? 'espece',
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Mettre à jour le stock pour chaque article
    foreach ($summary['articles'] as $article) {
        $product = findProductById($article['produit_id']);
        if ($product) {
            $newQty = $product['quantite_stock'] - $article['quantite'];
            updateProduct($article['produit_id'], ['quantite_stock' => $newQty]);
        }
    }
    
    // Enregistrer la facture
    $factures[] = $newInvoice;
    if (writeJsonFile(INVOICES_FILE, $factures)) {
        return $newInvoice;
    }
    
    return false;
}

/**
 * Obtenir les alertes de stock (produits sous le seuil d'alerte)
 * @return array Produits en alerte
 */
function getStockAlerts(): array {
    $products = getAllProducts();
    $alerts = [];
    
    foreach ($products as $product) {
        if ($product['quantite_stock'] < $product['seuil_alerte']) {
            $alerts[] = [
                'produit_id' => $product['id'],
                'code' => $product['code'],
                'nom' => $product['nom'],
                'stock_actuel' => $product['quantite_stock'],
                'seuil_alerte' => $product['seuil_alerte'],
                'deficit' => $product['seuil_alerte'] - $product['quantite_stock']
            ];
        }
    }
    
    return $alerts;
}
?>
