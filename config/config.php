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
define('DEBUG_MODE', true);

// Messages d'erreur/succès
$messages = [];