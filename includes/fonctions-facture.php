<?php
/**
 * Fonctions de gestion des factures
 * Gestion de Stock - Transco
 */

require_once __DIR__ . '/fonctions-commons.php';
require_once __DIR__ . '/fonctions-produits.php';

/**
 * Charger toutes les factures
 * @return array
 */
function getAllInvoices(): array {
    return readJsonFile(INVOICES_FILE) ?? [];
}

/**
 * Trouver une facture par ID
 * @param string $id
 * @return array|null
 */
function findInvoiceById(string $id): ?array {
    $invoices = getAllInvoices();
    foreach ($invoices as $invoice) {
        if (($invoice['id'] ?? '') === $id) {
            return $invoice;
        }
    }
    return null;
}

/**
 * Trouver une facture par numéro
 * @param string $numero
 * @return array|null
 */
function findInvoiceByNumero(string $numero): ?array {
    $invoices = getAllInvoices();
    foreach ($invoices as $invoice) {
        if (($invoice['numero'] ?? '') === $numero) {
            return $invoice;
        }
    }
    return null;
}

/**
 * Générer le prochain numéro de facture
 * @return string
 */
function generateInvoiceNumber(): string {
    $invoices = getAllInvoices();
    $year = date('Y');
    $num = count($invoices) + 1;
    return sprintf('FAC-%s-%04d', $year, $num);
}

/**
 * Créer une nouvelle facture
 * @param array $data
 * @return array|false
 */
function createInvoice(array $data): array|false {
    $invoices = getAllInvoices();
    
    $articles = [];
    $sousTotal = 0;
    
    foreach ($data['articles'] as $article) {
        $product = findProductById($article['produit_id']);
        if (!$product) continue;
        
        $total = $article['quantite'] * $article['prix_unitaire'];
        $sousTotal += $total;
        
        $articles[] = [
            'produit_id' => $article['produit_id'],
            'code' => $product['code'],
            'nom' => $product['nom'],
            'quantite' => $article['quantite'],
            'prix_unitaire' => $article['prix_unitaire'],
            'total' => $total
        ];
        
        // Mettre à jour le stock
        updateStock($article['produit_id'], -$article['quantite']);
    }
    
    $tva = $data['tva'] ?? 20;
    $totalTva = $sousTotal * ($tva / 100);
    $total = $sousTotal + $totalTva;
    
    $newInvoice = [
        'id' => generateId(),
        'numero' => generateInvoiceNumber(),
        'date' => date('Y-m-d H:i:s'),
        'client' => $data['client'],
        'vendeur_id' => $_SESSION['user_id'] ?? '',
        'articles' => $articles,
        'sous_total' => $sousTotal,
        'tva' => $tva,
        'total_tva' => $totalTva,
        'total' => $total,
        'statut' => $data['statut'] ?? 'en_attente',
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $invoices[] = $newInvoice;
    
    if (writeJsonFile(INVOICES_FILE, $invoices)) {
        return $newInvoice;
    }
    
    return false;
}

/**
 * Mettre à jour le statut d'une facture
 * @param string $id
 * @param string $statut
 * @return bool
 */
function updateInvoiceStatus(string $id, string $statut): bool {
    $invoices = getAllInvoices();
    
    foreach ($invoices as &$invoice) {
        if ($invoice['id'] === $id) {
            $invoice['statut'] = $statut;
            $invoice['updated_at'] = date('Y-m-d H:i:s');
            
            return writeJsonFile(INVOICES_FILE, $invoices);
        }
    }
    
    return false;
}

/**
 * Supprimer une facture
 * @param string $id
 * @return bool
 */
function deleteInvoice(string $id): bool {
    $invoices = getAllInvoices();
    $invoices = array_filter($invoices, fn($i) => $i['id'] !== $id);
    return writeJsonFile(INVOICES_FILE, array_values($invoices));
}

/**
 * Obtenir les factures par période
 * @param string $startDate
 * @param string $endDate
 * @return array
 */
function getInvoicesByPeriod(string $startDate, string $endDate): array {
    $invoices = getAllInvoices();
    
    return array_filter($invoices, function($invoice) use ($startDate, $endDate) {
        $invoiceDate = strtotime($invoice['date']);
        return $invoiceDate >= strtotime($startDate) && $invoiceDate <= strtotime($endDate);
    });
}

/**
 * Obtenir les statistiques des factures
 * @return array
 */
function getInvoiceStats(): array {
    $invoices = getAllInvoices();
    
    $total = 0;
    $payees = 0;
    $enAttente = 0;
    
    foreach ($invoices as $invoice) {
        $total += $invoice['total'] ?? 0;
        if (($invoice['statut'] ?? '') === 'payee') {
            $payees++;
        } elseif (($invoice['statut'] ?? '') === 'en_attente') {
            $enAttente++;
        }
    }
    
    return [
        'total_ventes' => $total,
        'nombre_factures' => count($invoices),
        'factures_payees' => $payees,
        'factures_en_attente' => $enAttente
    ];
}