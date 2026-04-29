<?php
/**
 * Gestion de la session utilisateur
 * Gestion de Stock - Transco
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/fonctions-commons.php';
require_once __DIR__ . '/../includes/fonctions-Auth.php';

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
            redirectTo('auth/login.php');
        }
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