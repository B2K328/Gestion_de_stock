<?php
/**
 * Configuration globale du projet
 * Gestion de Stock - Transco
 */

// Chemins absolus
define('BASE_PATH', dirname(__DIR__));
define('DATA_PATH', BASE_PATH . '/data');
define('CONFIG_PATH', BASE_PATH . '/config');
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('AUTH_PATH', BASE_PATH . '/auth');

// URL de base
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$appPath = str_replace('\\', '/', BASE_PATH);
if (strpos($appPath, $docRoot) === 0) {
    $basePath = substr($appPath, strlen($docRoot));
} else {
    $basePath = '/Gestion_de_stock';
}
define('BASE_URL', $protocol . '://' . $host . $basePath);

// Fichiers de données
define('USERS_FILE', DATA_PATH . '/utilisateurs.json');
define('PRODUCTS_FILE', DATA_PATH . '/produits.json');
define('INVOICES_FILE', DATA_PATH . '/factures.json');

// Configuration session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

// Fuseau horaire
date_default_timezone_set('Africa/Casablanca');

// Encodage
header('Content-Type: text/html; charset=utf-8');

// Mode debug (à désactiver en production)
define('DEBUG_MODE', false);

// Initialiser les fichiers de données
function initializeDataFiles() {
    // Créer le dossier data s'il n'existe pas
    if (!is_dir(DATA_PATH)) {
        mkdir(DATA_PATH, 0755, true);
    }
    
    // Initialiser les utilisateurs
    if (!file_exists(USERS_FILE) || empty(file_get_contents(USERS_FILE))) {
        $defaultUsers = [
            [
                'id' => 'admin-' . bin2hex(random_bytes(4)),
                'email' => 'admin@stock.local',
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
                'nom' => 'Administrateur',
                'prenom' => 'Système',
                'role' => 'admin',
                'created_at' => date('Y-m-d H:i:s'),
                'active' => true
            ]
        ];
        file_put_contents(USERS_FILE, json_encode($defaultUsers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    // Initialiser les produits
    if (!file_exists(PRODUCTS_FILE) || empty(file_get_contents(PRODUCTS_FILE))) {
        $defaultProducts = [
            [
                'id' => 'prod-' . bin2hex(random_bytes(4)),
                'code' => 'PROD-001',
                'nom' => 'Produit Exemple',
                'categorie' => 'Divers',
                'unite' => 'piece',
                'prix_achat' => 100,
                'prix_vente' => 150,
                'quantite_stock' => 50,
                'seuil_alerte' => 10,
                'active' => true,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];
        file_put_contents(PRODUCTS_FILE, json_encode($defaultProducts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    // Initialiser les factures
    if (!file_exists(INVOICES_FILE) || empty(file_get_contents(INVOICES_FILE))) {
        file_put_contents(INVOICES_FILE, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

// Appeler l'initialisation
initializeDataFiles();