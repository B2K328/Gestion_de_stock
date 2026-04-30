<?php
/**
 * Enregistrement de Nouveaux Produits
 * Étudiant 2: Front-End & Hardware
 * 
 * Flux:
 * 1. Scanner lit un code-barres
 * 2. Vérification si produit existe
 * 3. Si nouveau → Afficher formulaire d'enregistrement
 * 4. Validation côté serveur
 * 5. Enregistrement dans JSON
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/fonctions-commons.php';
require_once __DIR__ . '/../../includes/fonctions-produits.php';
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../includes/header.php';

// Vérifier l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

// Vérifier le rôle (Manager, Admin, ou Super Admin)
$allowedRoles = ['admin', 'manager', 'super_admin'];
if (!in_array($_SESSION['role'] ?? '', $allowedRoles)) {
    echo '<div class="container"><div class="alert alert-danger">Accès refusé. Seuls les Administrateurs et Managers peuvent enregistrer des produits.</div></div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

$pageTitle = 'Enregistrement de Produits';
$errors = [];
$success = false;
$scannedBarcode = $_GET['code'] ?? '';
$existingProduct = null;
// Edition: si ?edit={id}
$editId = $_GET['edit'] ?? null;
$editProduct = null;

// Vérifier si le produit existe déjà
if ($scannedBarcode) {
    $products = getAllProducts();
    foreach ($products as $product) {
        if (($product['code'] ?? '') === $scannedBarcode) {
            $existingProduct = $product;
            break;
        }
    }
}

// Charger le produit à éditer si demandé
if ($editId) {
    $editProduct = findProductById($editId);
    if (!$editProduct) {
        $errors[] = 'Produit à éditer introuvable.';
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    // Différencier création / mise à jour
    if ($action === 'register') {
        // création (existant code dans le bloc suivant)
    }

    // Récupération et validation des données
    $idToUpdate = $_POST['id'] ?? null;
    
    // Récupération et validation des données
    $code = trim($_POST['code'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $categorie = trim($_POST['categorie'] ?? 'Non catégorisé');
    $unite = trim($_POST['unite'] ?? 'unité');
    $prix_achat = (float)($_POST['prix_achat'] ?? 0);
    $prix_vente = (float)($_POST['prix_vente'] ?? 0);
    $quantite_stock = (int)($_POST['quantite_stock'] ?? 0);
    $seuil_alerte = (int)($_POST['seuil_alerte'] ?? 10);
    $date_expiration = trim($_POST['date_expiration'] ?? '');

    // === VALIDATIONS ===
    
    // Code-barres
    if (empty($code)) {
        $errors[] = 'Le code-barres est obligatoire.';
    }

    // Nom du produit
    if (empty($nom)) {
        $errors[] = 'Le nom du produit est obligatoire.';
    } elseif (strlen($nom) < 3) {
        $errors[] = 'Le nom doit contenir au moins 3 caractères.';
    }

    // Prix d'achat
    if ($prix_achat < 0) {
        $errors[] = 'Le prix d\'achat ne peut pas être négatif.';
    }

    // Prix de vente
    if ($prix_vente < 0) {
        $errors[] = 'Le prix de vente ne peut pas être négatif.';
    }

    if ($prix_vente < $prix_achat) {
        $errors[] = 'Le prix de vente doit être supérieur ou égal au prix d\'achat.';
    }

    // Quantité
    if ($quantite_stock < 0) {
        $errors[] = 'La quantité ne peut pas être négative.';
    }

    // Date d'expiration (format YYYY-MM-DD)
    if (!empty($date_expiration)) {
        $dateObj = DateTime::createFromFormat('Y-m-d', $date_expiration);
        if (!$dateObj || $dateObj->format('Y-m-d') !== $date_expiration) {
            $errors[] = 'Format de date invalide. Utilisez YYYY-MM-DD.';
        } else if ($dateObj < new DateTime()) {
            $errors[] = 'La date d\'expiration ne peut pas être antérieure à aujourd\'hui.';
        }
    }

    // === ENREGISTREMENT / MISE A JOUR ===
    if (empty($errors)) {
        $existingProducts = getAllProducts();
        $codeExists = false;
        foreach ($existingProducts as $product) {
            if (($product['code'] ?? '') === $code) {
                // Si mise à jour, autoriser le même code pour le même produit
                if ($idToUpdate && ($product['id'] ?? '') === $idToUpdate) {
                    continue;
                }
                $codeExists = true;
                break;
            }
        }

        if ($codeExists) {
            $errors[] = 'Ce code-barres existe déjà dans le système.';
        } else {
            if (!empty($idToUpdate)) {
                // Mise à jour
                $updatedData = [
                    'code' => $code,
                    'nom' => $nom,
                    'categorie' => $categorie,
                    'unite' => $unite,
                    'prix_achat' => $prix_achat,
                    'prix_vente' => $prix_vente,
                    'quantite_stock' => $quantite_stock,
                    'seuil_alerte' => $seuil_alerte,
                    'date_expiration' => !empty($date_expiration) ? $date_expiration : null,
                    'active' => true,
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                if (updateProduct($idToUpdate, $updatedData)) {
                    $success = true;
                    $editProduct = findProductById($idToUpdate);
                    $_POST = [];
                } else {
                    $errors[] = 'Erreur lors de la mise à jour du produit.';
                }
            } else {
                // Création
                $newProduct = [
                    'id' => 'prod-' . substr(md5(time()), 0, 8),
                    'code' => $code,
                    'nom' => $nom,
                    'categorie' => $categorie,
                    'unite' => $unite,
                    'prix_achat' => $prix_achat,
                    'prix_vente' => $prix_vente,
                    'quantite_stock' => $quantite_stock,
                    'seuil_alerte' => $seuil_alerte,
                    'date_expiration' => !empty($date_expiration) ? $date_expiration : null,
                    'active' => true,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                $existingProducts[] = $newProduct;
                if (writeJsonFile(PRODUCTS_FILE, $existingProducts)) {
                    $success = true;
                    $existingProduct = $newProduct;
                    $_POST = [];
                } else {
                    $errors[] = 'Erreur lors de l\'enregistrement du produit.';
                }
            }
        }
    }
}

?>

<div class="container">
    <div class="page-header">
        <h1>📦 Enregistrement de Nouveaux Produits</h1>
        <p>Enregistrez les produits non référencés par scan de code-barres</p>
    </div>

    <!-- Messages -->
    <?php if ($success): ?>
        <div class="alert alert-success">
            <strong>✓ Succès!</strong> Le produit a été enregistré avec succès.
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <strong>✗ Erreurs détectées:</strong>
            <ul style="margin: 0.5rem 0 0 0; padding-left: 1.5rem;">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Scanner de Code-Barres -->
    <div class="card mb-3">
        <div class="card-header">
            <h3>🔍 Scanner de Code-Barres</h3>
        </div>
        <div class="card-body">
            <div class="scanner-container" id="scanner-container">
                <video id="scanner-video" style="display: none;"></video>
                <div class="scanner-controls">
                    <button class="btn btn-primary" id="btn-start-scanner">Démarrer la Caméra</button>
                    <button class="btn btn-danger" id="btn-stop-scanner" disabled>Arrêter</button>
                </div>
                <div class="scanner-status" id="scanner-status">En attente...</div>
            </div>
        </div>
    </div>

    <!-- Code Scanné -->
    <?php if ($scannedBarcode): ?>
        <div class="alert alert-info">
            Code-barres scanné: <strong><?= htmlspecialchars($scannedBarcode) ?></strong>
        </div>
    <?php endif; ?>

    <!-- Produit Existant -->
    <?php if ($existingProduct && !$success): ?>
        <div class="card mb-3" style="border-left: 4px solid var(--warning);">
            <div class="card-header" style="background: #fef5e7;">
                <h3>⚠️ Produit Déjà Existant</h3>
            </div>
            <div class="card-body">
                <table style="width: 100%;">
                    <tr>
                        <td><strong>Code:</strong></td>
                        <td><?= htmlspecialchars($existingProduct['code']) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Nom:</strong></td>
                        <td><?= htmlspecialchars($existingProduct['nom']) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Prix de Vente:</strong></td>
                        <td><?= number_format($existingProduct['prix_vente'], 2) ?> CDF</td>
                    </tr>
                    <tr>
                        <td><strong>Stock Actuel:</strong></td>
                        <td><?= $existingProduct['quantite_stock'] ?> <?= htmlspecialchars($existingProduct['unite']) ?></td>
                    </tr>
                </table>
                <div class="mt-3">
                    <p>Vous pouvez maintenant utiliser ce produit pour vos factures.</p>
                    <a href="/modules/facturation/nouvelle-facture.php?code=<?= urlencode($existingProduct['code']) ?>" class="btn btn-primary">Créer une Facture</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Formulaire d'Enregistrement -->
    <?php if ($editProduct || !$existingProduct || $success): ?>
        <div class="card">
            <div class="card-header">
                <h3>📝 Formulaire d'Enregistrement</h3>
            </div>
            <div class="card-body">
                <form method="POST" style="max-width: 600px;">
                    <?php if ($editProduct): ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($editProduct['id']) ?>">
                    <?php else: ?>
                        <input type="hidden" name="action" value="register">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="code">Code-Barres *</label>
                        <input type="text" id="code" name="code" placeholder="Scannez ou entrez le code" required 
                            value="<?= htmlspecialchars($_POST['code'] ?? ($editProduct['code'] ?? $scannedBarcode ?? '')) ?>">
                    </div>

                    <div class="form-group">
                        <label for="nom">Nom du Produit *</label>
                        <input type="text" id="nom" name="nom" placeholder="Ex: Huile de palme 1L" required 
                            value="<?= htmlspecialchars($_POST['nom'] ?? ($editProduct['nom'] ?? '')) ?>">
                    </div>

                    <div class="form-group">
                        <label for="categorie">Catégorie</label>
                        <?php $catVal = $_POST['categorie'] ?? ($editProduct['categorie'] ?? ''); ?>
                        <select id="categorie" name="categorie">
                            <option value="Alimentation" <?= $catVal === 'Alimentation' ? 'selected' : '' ?>>Alimentation</option>
                            <option value="Hygiène" <?= $catVal === 'Hygiène' ? 'selected' : '' ?>>Hygiène</option>
                            <option value="Électronique" <?= $catVal === 'Électronique' ? 'selected' : '' ?>>Électronique</option>
                            <option value="Vêtements" <?= $catVal === 'Vêtements' ? 'selected' : '' ?>>Vêtements</option>
                            <option value="Autres" <?= $catVal === 'Autres' ? 'selected' : '' ?>>Autres</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="unite">Unité de Mesure</label>
                        <?php $unitVal = $_POST['unite'] ?? ($editProduct['unite'] ?? 'unité'); ?>
                        <select id="unite" name="unite">
                            <option value="unité" <?= $unitVal === 'unité' ? 'selected' : '' ?>>unité</option>
                            <option value="kg" <?= $unitVal === 'kg' ? 'selected' : '' ?>>kg</option>
                            <option value="L" <?= $unitVal === 'L' ? 'selected' : '' ?>>Litre (L)</option>
                            <option value="m" <?= $unitVal === 'm' ? 'selected' : '' ?>>Mètre (m)</option>
                            <option value="boîte" <?= $unitVal === 'boîte' ? 'selected' : '' ?>>Boîte</option>
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="prix_achat">Prix d'Achat (CDF) *</label>
                            <input type="number" id="prix_achat" name="prix_achat" step="0.01" min="0" required 
                                value="<?= htmlspecialchars($_POST['prix_achat'] ?? ($editProduct['prix_achat'] ?? '')) ?>">
                        </div>

                        <div class="form-group">
                            <label for="prix_vente">Prix de Vente (CDF) *</label>
                            <input type="number" id="prix_vente" name="prix_vente" step="0.01" min="0" required 
                                value="<?= htmlspecialchars($_POST['prix_vente'] ?? ($editProduct['prix_vente'] ?? '')) ?>">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="quantite_stock">Quantité Initiale *</label>
                            <input type="number" id="quantite_stock" name="quantite_stock" min="0" required 
                                value="<?= htmlspecialchars($_POST['quantite_stock'] ?? ($editProduct['quantite_stock'] ?? '0')) ?>">
                        </div>

                        <div class="form-group">
                            <label for="seuil_alerte">Seuil d'Alerte</label>
                            <input type="number" id="seuil_alerte" name="seuil_alerte" min="0" 
                                value="<?= htmlspecialchars($_POST['seuil_alerte'] ?? ($editProduct['seuil_alerte'] ?? '10')) ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="date_expiration">Date d'Expiration (YYYY-MM-DD)</label>
                        <input type="date" id="date_expiration" name="date_expiration" 
                            value="<?= htmlspecialchars($_POST['date_expiration'] ?? ($editProduct['date_expiration'] ?? '')) ?>">
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn btn-success btn-block"><?= $editProduct ? 'Mettre à jour le Produit' : 'Enregistrer le Produit' ?></button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- Charger la librairie ZXing pour la détection de codes-barres -->
<script src="https://unpkg.com/@zxing/library@latest/umd/index.min.js"></script>

<!-- Charger le scanner personnalisé -->
<script src="/assets/js/scanner.js"></script>

<script>
// Intégration du scanner avec le formulaire
if (scanner) {
    scanner.onScanned((code) => {
        document.getElementById('code').value = code;
        resetLastScannedCode();
    });

    scanner.onError((error) => {
        alert('Erreur scanner: ' + error);
    });
}
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
