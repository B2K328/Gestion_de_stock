<?php
/**
 * Déconnexion utilisateur
 * Gestion de Stock - Transco
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/fonctions-commons.php';
require_once __DIR__ . '/../includes/fonctions-Auth.php';

// Détruire la session
logoutUser();

setFlashMessage('success', 'Vous avez été déconnecté avec succès.');
<<<<<<< HEAD
redirectTo('login.php');
=======
redirectTo('auth/login.php');
>>>>>>> Gestion_SP
