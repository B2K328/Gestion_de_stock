<?php
/**
 * Vider le panier
 * Gestion de Stock - Transco
 */

require_once '../../config/config.php';
require_once '../../auth/session.php';
require_once '../../includes/fonctions-Auth.php';
require_once '../../includes/fonctions-commons.php';

// Protéger l'accès
requireAuth(['admin', 'vendeur']);

// Vider le panier
$_SESSION['panier'] = [];

// Message de succès
setFlashMessage('success', 'Le panier a été vidé.');

// Rediriger vers nouvelle facture
redirectTo('/modules/facturation/nouvelle-facture.php');