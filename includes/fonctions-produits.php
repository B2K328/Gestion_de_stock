<?php
/**
 * Fonctions de gestion des produits
 * Gestion de Stock - Transco
 */

require_once __DIR__ . '/fonctions-commons.php';

/**
 * Charger tous les produits
 * @return array
 */
function getAllProducts(): array {
    return readJsonFile(PRODUCTS_FILE) ?? [];
}

/**
 * Trouver un produit par ID
 * @param string $id
 * @return array|null
 */
function findProductById(string $id): ?array {
    $products = getAllProducts();
    foreach ($products as $product) {
        if (($product['id'] ?? '') === $id) {
            return $product;
        }
    }
    return null;
}

/**
 * Trouver un produit par code
 * @param string $code
 * @return array|null
 */
function findProductByCode(string $code): ?array {
    $products = getAllProducts();
    foreach ($products as $product) {
        if (($product['code'] ?? '') === $code) {
            return $product;
        }
    }
    return null;
}

/**
 * Créer un nouveau produit
 * @param array $data
 * @return array|false
 */
function createProduct(array $data): array|false {
    $products = getAllProducts();
    
    // Vérifier si le code existe déjà
    if (findProductByCode($data['code'])) {
        return false;
    }
    
    $newProduct = [
        'id' => generateId(),
        'code' => $data['code'],
        'nom' => $data['nom'],
        'categorie' => $data['categorie'] ?? '',
        'unite' => $data['unite'] ?? 'piece',
        'prix_achat' => (float)($data['prix_achat'] ?? 0),
        'prix_vente' => (float)($data['prix_vente'] ?? 0),
        'quantite_stock' => (int)($data['quantite_stock'] ?? 0),
        'seuil_alerte' => (int)($data['seuil_alerte'] ?? 10),
        'active' => true,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $products[] = $newProduct;
    
    if (writeJsonFile(PRODUCTS_FILE, $products)) {
        return $newProduct;
    }
    
    return false;
}

/**
 * Mettre à jour un produit
 * @param string $id
 * @param array $data
 * @return bool
 */
function updateProduct(string $id, array $data): bool {
    $products = getAllProducts();
    
    foreach ($products as &$product) {
        if ($product['id'] === $id) {
            $fields = ['code', 'nom', 'categorie', 'unite', 'prix_achat', 'prix_vente', 'quantite_stock', 'seuil_alerte', 'active'];
            foreach ($fields as $field) {
                if (isset($data[$field])) {
                    $product[$field] = $data[$field];
                }
            }
            $product['updated_at'] = date('Y-m-d H:i:s');
            
            return writeJsonFile(PRODUCTS_FILE, $products);
        }
    }
    
    return false;
}

/**
 * Supprimer un produit
 * @param string $id
 * @return bool
 */
function deleteProduct(string $id): bool {
    $products = getAllProducts();
    $products = array_filter($products, fn($p) => $p['id'] !== $id);
    return writeJsonFile(PRODUCTS_FILE, array_values($products));
}

/**
 * Mettre à jour le stock
 * @param string $productId
 * @param int $quantity Quantité positive ou négative
 * @return bool
 */
function updateStock(string $productId, int $quantity): bool {
    $products = getAllProducts();
    
    foreach ($products as &$product) {
        if ($product['id'] === $productId) {
            $product['quantite_stock'] = max(0, $product['quantite_stock'] + $quantity);
            $product['updated_at'] = date('Y-m-d H:i:s');
            
            return writeJsonFile(PRODUCTS_FILE, $products);
        }
    }
    
    return false;
}

/**
 * Obtenir les produits en alerte de stock
 * @return array
 */
function getLowStockProducts(): array {
    $products = getAllProducts();
    return array_filter($products, fn($p) => $p['quantite_stock'] <= ($p['seuil_alerte'] ?? 10));
}

/**
 * Rechercher des produits
 * @param string $query
 * @return array
 */
function searchProducts(string $query): array {
    $products = getAllProducts();
    $query = strtolower($query);
    
    return array_filter($products, fn($p) => 
        strpos(strtolower($p['nom'] ?? ''), $query) !== false ||
        strpos(strtolower($p['code'] ?? ''), $query) !== false ||
        strpos(strtolower($p['categorie'] ?? ''), $query) !== false
    );
}