<?php
/**
 * Supprimer un compte utilisateur
 * Gestion de Stock - Transco
 */

require_once '../../config/config.php';
require_once '../../auth/session.php';
require_once '../../includes/fonctions-Auth.php';
require_once '../../includes/fonctions-commons.php';

// Protéger l'accès (admin uniquement)
requireAuth(['admin']);

// Récupérer l'ID du compte à supprimer
$id = sanitizeInput($_GET['id'] ?? '');

if (empty($id)) {
    setFlashMessage('error', 'ID utilisateur invalide');
    redirectTo('modules/admin/gestion-compte.php');
}

// Vérifier que l'utilisateur n'essaie pas de se supprimer lui-même
if ($id === $_SESSION['user_id']) {
    setFlashMessage('error', 'Vous ne pouvez pas supprimer votre propre compte');
    redirectTo('modules/admin/gestion-compte.php');
}

// Vérifier que l'utilisateur existe
$user = findUserById($id);
if (!$user) {
    setFlashMessage('error', 'Utilisateur non trouvé');
    redirectTo('modules/admin/gestion-compte.php');
}

// Supprimer l'utilisateur
if (deleteUser($id)) {
    setFlashMessage('success', 'Compte supprimé avec succès');
} else {
    setFlashMessage('error', 'Erreur lors de la suppression du compte');
}

// Rediriger
redirectTo('modules/admin/gestion-compte.php');
