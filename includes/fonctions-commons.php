<?php
/**
 * Fonctions communes utilitaires
 * Gestion de Stock - Transco
 */

/**
 * Lire un fichier JSON
 * @param string $filePath Chemin du fichier
 * @return array|null
 */
function readJsonFile(string $filePath): ?array {
    if (!file_exists($filePath)) {
        return null;
    }
    $content = file_get_contents($filePath);
    return json_decode($content, true) ?? [];
}

/**
 * Écrire dans un fichier JSON
 * @param string $filePath Chemin du fichier
 * @param array $data Données à écrire
 * @return bool
 */
function writeJsonFile(string $filePath, array $data): bool {
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

/**
 * Générer un ID unique
 * @return string
 */
function generateId(): string {
    return uniqid() . '-' . bin2hex(random_bytes(4));
}

/**
 * Formater une date
 * @param string|null $date
 * @return string
 */
function formatDate(?string $date = null): string {
    return $date ? date('d/m/Y H:i', strtotime($date)) : date('d/m/Y H:i');
}

/**
 * Sanitiser une entrée utilisateur
 * @param mixed $input 
 * @return mixed
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Vérifier si l'utilisateur est connecté
 * @return bool
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Obtenir le rôle de l'utilisateur
 * @return string|null
 */
function getUserRole(): ?string {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Vérifier si l'utilisateur est admin
 * @return bool
 */
function isAdmin(): bool {
    return getUserRole() === 'admin';
}

/**
 * Rediriger vers une URL
 * @param string $url
 * @return void
 */
function redirectTo(string $url): void {
    header("Location: $url");
    exit;
}

/**
 * Afficher un message flash
 * @param string $type success|error|warning|info
 * @param string $message
 * @return void
 */
function setFlashMessage(string $type, string $message): void {
    $_SESSION['flash'][$type] = $message;
}

/**
 * Récupérer et effacer le message flash
 * @param string $type
 * @return string|null
 */
function getFlashMessage(string $type): ?string {
    if (isset($_SESSION['flash'][$type])) {
        $msg = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $msg;
    }
    return null;
}