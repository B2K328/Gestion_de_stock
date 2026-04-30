<?php
require_once '../../config/config.php';
require_once '../../auth/session.php';
require_once '../../includes/fonctions-Auth.php';
require_once '../../includes/fonctions-commons.php';

requireAuth(['admin']);

if ($_POST) {
    $email = sanitizeInput($_POST['email'] ?? '');
    $nom = sanitizeInput($_POST['nom'] ?? '');
    $prenom = sanitizeInput($_POST['prenom'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $role = sanitizeInput($_POST['role'] ?? 'vendeur');
    
    $err = '';
    if (!$email) $err = 'Email obligatoire';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Email invalide';
    elseif (!$nom) $err = 'Nom obligatoire';
    elseif (strlen($password) < 6) $err = 'Min 6 caractères';
    elseif ($password !== $password_confirm) $err = 'Mots de passe différents';
    elseif (findUserByEmail($email)) $err = 'Email existe déjà';

    if ($err) {
        setFlashMessage('error', $err);
    } else {
        if (createUser(['email' => $email, 'nom' => $nom, 'prenom' => $prenom, 'password' => $password, 'role' => $role])) {
            setFlashMessage('success', 'Compte créé');
            redirectTo('/modules/admin/gestion-compte.php');
        }
    }
}

include '../../includes/header.php';
?>

<div class="container">
    <div class="card">
        <h2>Ajouter Compte</h2>
        
        <?php if ($msg = getFlashMessage('error')): ?>
            <div class="alert alert-error">✗ <?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="text" name="nom" placeholder="Nom" required>
            <input type="text" name="prenom" placeholder="Prénom" required>
            <select name="role" required>
                <option value="vendeur">Vendeur</option>
                <option value="magasinier">Magasinier</option>
                <option value="admin">Admin</option>
            </select>
            <input type="password" name="password" placeholder="Mot de passe" required minlength="6">
            <input type="password" name="password_confirm" placeholder="Confirmer" required>
            <button type="submit">✓ Créer</button>
            <a href="<?= url('/modules/admin/gestion-compte.php') ?>" class="btn-secondary">Annuler</a>
        </form>

        <hr>
        <nav class="links">
            <a href="<?= url('/modules/admin/gestion-compte.php') ?>">Gestion</a> |
            <a href="<?= url('/modules/facturation/nouvelle-facture.php') ?>">Facturation</a>
        </nav>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>