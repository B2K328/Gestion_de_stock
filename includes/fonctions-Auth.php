<?php
/**
 * Fonctions d'authentification
 * Gestion de Stock - Transco
 */

require_once __DIR__ . '/fonctions-commons.php';

/**
 * Charger tous les utilisateurs
 * @return array
 */
function getAllUsers(): array {
    return readJsonFile(USERS_FILE) ?? [];
}

/**
 * Trouver un utilisateur par email
 * @param string $email
 * @return array|null
 */
function findUserByEmail(string $email): ?array {
    $users = getAllUsers();
    foreach ($users as $user) {
        if (($user['email'] ?? '') === $email) {
            return $user;
        }
    }
    return null;
}

/**
 * Trouver un utilisateur par ID
 * @param string $id
 * @return array|null
 */
function findUserById(string $id): ?array {
    $users = getAllUsers();
    foreach ($users as $user) {
        if (($user['id'] ?? '') === $id) {
            return $user;
        }
    }
    return null;
}

/**
 * Vérifier le mot de passe
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

/**
 * Hasher un mot de passe
 * @param string $password
 * @return string
 */
function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Connecter un utilisateur
 * @param array $user
 * @return void
 */
function loginUser(array $user): void {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['nom'] . ' ' . ($user['prenom'] ?? '');
    $_SESSION['user_role'] = $user['role'] ?? 'utilisateur';
    $_SESSION['login_time'] = time();
    
    // Régénérer l'ID de session
    session_regenerate_id(true);
}

/**
 * Déconnecter l'utilisateur
 * @return void
 */
function logoutUser(): void {
    // Détruire les variables de session
    $_SESSION = [];
    
    // Détruire la session
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    
    session_destroy();
}

/**
 * Créer un nouvel utilisateur
 * @param array $data
 * @return array|false
 */
function createUser(array $data): array|false {
    $users = getAllUsers();
    
    // Vérifier si l'email existe déjà
    if (findUserByEmail($data['email'])) {
        return false;
    }
    
    $newUser = [
        'id' => generateId(),
        'email' => $data['email'],
        'password' => hashPassword($data['password']),
        'nom' => $data['nom'],
        'prenom' => $data['prenom'] ?? '',
        'role' => $data['role'] ?? 'utilisateur',
        'created_at' => date('Y-m-d H:i:s'),
        'active' => true
    ];
    
    $users[] = $newUser;
    
    if (writeJsonFile(USERS_FILE, $users)) {
        unset($newUser['password']);
        return $newUser;
    }
    
    return false;
}

/**
 * Mettre à jour un utilisateur
 * @param string $id
 * @param array $data
 * @return bool
 */
function updateUser(string $id, array $data): bool {
    $users = getAllUsers();
    
    foreach ($users as &$user) {
        if ($user['id'] === $id) {
            if (isset($data['nom'])) $user['nom'] = $data['nom'];
            if (isset($data['prenom'])) $user['prenom'] = $data['prenom'];
            if (isset($data['role'])) $user['role'] = $data['role'];
            if (isset($data['password'])) $user['password'] = hashPassword($data['password']);
            if (isset($data['active'])) $user['active'] = $data['active'];
            $user['updated_at'] = date('Y-m-d H:i:s');
            
            return writeJsonFile(USERS_FILE, $users);
        }
    }
    
    return false;
}

/**
 * Supprimer un utilisateur
 * @param string $id
 * @return bool
 */
function deleteUser(string $id): bool {
    $users = getAllUsers();
    $users = array_filter($users, fn($u) => $u['id'] !== $id);
    return writeJsonFile(USERS_FILE, array_values($users));
}

/**
 * Vérifier les identifiants et connecter
 * @param string $email
 * @param string $password
 * @return array|false
 */
function authenticate(string $email, string $password) {
    $user = findUserByEmail($email);
    
    if (!$user) {
        return false;
    }
    
    if (!($user['active'] ?? true)) {
        return false;
    }
    
    if (!verifyPassword($password, $user['password'])) {
        return false;
    }
    
    loginUser($user);
    
    unset($user['password']);
    return $user;
<<<<<<< HEAD
=======
}

/**
 * Vérifier si c'est un utilisateur invité (non connecté)
 * @return bool
 */
function isGuest(): bool {
    return !isLoggedIn();
}

/**
 * Protéger une page (auth requise)
 * @param array $roles Rôles autorisés (vide = tous connectés)
 * @return void
 */
function requireAuth(array $roles = []): void {
    if (!isLoggedIn()) {
        setFlashMessage('error', 'Connexion requise');
        redirectTo('auth/login.php');
    }
    
    if (!empty($roles) && !in_array(getUserRole(), $roles)) {
        setFlashMessage('error', 'Accès non autorisé');
        redirectTo('index.php');
    }
}

/**
 * Protéger une page (invité requis)
 * @return void
 */
function requireGuest(): void {
    if (isLoggedIn()) {
        redirectTo('index.php');
    }
>>>>>>> Gestion_SP
}