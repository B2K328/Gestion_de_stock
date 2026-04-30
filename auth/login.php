<?php
/**
 * Page de connexion
 * Gestion de Stock - Transco
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/fonctions-commons.php';
require_once __DIR__ . '/../includes/fonctions-Auth.php';
require_once __DIR__ . '/session.php';

// Protéger: déjà connecté → redirection
requireGuest();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        $user = authenticate($email, $password);
        
        if ($user) {
<<<<<<< HEAD
            setFlashMessage('success', 'Bienvenue, ' . $user['name'] . ' !');
            redirectTo('http://localhost/Gestion_de_stock/index.php');
=======
            setFlashMessage('success', 'Bienvenue, ' . ($user['nom'] ?? 'Utilisateur') . ' !');
            redirectTo('/index.php');
>>>>>>> Gestion_SP
        } else {
            $error = 'Email ou mot de passe incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Gestion de Stock</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        h1 { text-align: center; color: #333; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; color: #555; font-weight: 500; }
        input[type="email"], input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        input:focus { outline: none; border-color: #667eea; }
        .btn {
            width: 100%;
            padding: 0.75rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn:hover { background: #5568d3; }
        .error { color: #e74c3c; margin-bottom: 1rem; text-align: center; }
        .links { text-align: center; margin-top: 1rem; }
        .links a { color: #667eea; text-decoration: none; }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>🔐 Connexion</h1>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Se connecter</button>
        </form>
    </div>
</body>
</html>