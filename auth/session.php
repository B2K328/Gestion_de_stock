<?php
/**
 * Gestion de la session utilisateur
 * Gestion de Stock - Transco
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/fonctions-commons.php';

/**
 * Vérifier et protéger l'accès aux pages
 * @param array|null $allowedRoles Rôles autorisés (null = tous utilisateurs connectés)
 * @return void
 */
function requireAuth(?array $allowedRoles = null): void {
    if (!isLoggedIn()) {
        setFlashMessage('warning', 'Veuillez vous connecter pour accéder à cette page.');
        redirectTo('/auth/login.php');
    }
    
    if ($allowedRoles !== null && !in_array(getUserRole(), $allowedRoles)) {
        setFlashMessage('error', 'Vous n\'avez pas l\'autorisation d\'accéder à cette page.');
        redirectTo('/index.php');
    }
}

/**
 * Protéger contre l'accès si déjà connecté
 * @return void
 */
function requireGuest(): void {
    if (isLoggedIn()) {
        redirectTo('/index.php');
    }
}

/**
 * Vérifier l'expiration de la session
 * @param int $timeout Timeout en secondes (défaut: 30 minutes)
 * @return void
 */
function checkSessionTimeout(int $timeout = 1800): void {
    if (isset($_SESSION['login_time'])) {
        $elapsed = time() - $_SESSION['login_time'];
        if ($elapsed > $timeout) {
            logoutUser();
            setFlashMessage('error', 'Votre session a expiré. Veuillez vous reconnecter.');
            redirectTo('/auth/login.php');
        }
        // Rafraîchir le temps de session
        $_SESSION['login_time'] = time();
    }
}

/**
 * Obtenir les infos de l'utilisateur courant
 * @return array|null
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'email' => $_SESSION['user_email'] ?? null,
        'name' => $_SESSION['user_name'] ?? null,
        'role' => $_SESSION['user_role'] ?? null
    ];
}