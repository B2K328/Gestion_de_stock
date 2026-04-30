<?php
require_once '../../config/config.php';
require_once '../../auth/session.php';
require_once '../../includes/fonctions-Auth.php';
require_once '../../includes/fonctions-commons.php';

requireAuth(['admin']);

$produits = readJsonFile(PRODUCTS_FILE) ?? [];
$produit_existant = null;
$code_barre_scan = null;

// Traiter l'enregistrement d'un nouveau produit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'enregistrer') {
    $code_barre = sanitizeInput($_POST['code_barre'] ?? '');
    $nom = sanitizeInput($_POST['nom'] ?? '');
    $prix_unitaire_ht = (float)($_POST['prix_unitaire_ht'] ?? 0);
    $date_expiration = sanitizeInput($_POST['date_expiration'] ?? '');
    $quantite_stock = (int)($_POST['quantite_stock'] ?? 0);
    
    $err = '';
    if (!$code_barre) $err = 'Code-barres obligatoire';
    elseif (!$nom) $err = 'Nom obligatoire';
    elseif ($prix_unitaire_ht <= 0) $err = 'Prix doit être positif';
    elseif (!$quantite_stock || $quantite_stock < 0) $err = 'Quantité invalide';
    elseif (!validDate($date_expiration)) $err = 'Date expiration invalide (MM-JJ-AAAA)';
    
    if ($err) {
        setFlashMessage('error', $err);
    } else {
        // Ajouter le produit
        $produits[] = [
            'code_barre' => $code_barre,
            'nom' => $nom,
            'prix_unitaire_ht' => $prix_unitaire_ht,
            'date_expiration' => convertDateToISO($date_expiration),
            'quantite_stock' => $quantite_stock,
            'date_enregistrement' => date('Y-m-d')
        ];
        
        if (writeJsonFile(PRODUCTS_FILE, $produits)) {
            setFlashMessage('success', 'Produit enregistré avec succès');
            redirectTo('/modules/produits/liste.php');
        } else {
            setFlashMessage('error', 'Erreur lors de l\'enregistrement');
        }
    }
}

// Traiter le scan d'un code-barres
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'scan') {
    $code_barre_scan = sanitizeInput($_POST['code_barre'] ?? '');
    
    // Vérifier si le code-barres existe déjà
    foreach ($produits as $p) {
        if (($p['code_barre'] ?? '') === $code_barre_scan) {
            $produit_existant = $p;
            break;
        }
    }
}

include '../../includes/header.php';
?>

<div class="container">
    <div class="card">
        <h2>📦 Enregistrement de Produit</h2>
        <p style="color: #7f8c8d; margin-bottom: 1rem;">Scannez le code-barres ou saisissez-le manuellement</p>
        
        <?php if ($msg = getFlashMessage('error')): ?>
            <div class="alert alert-error">✗ <?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <?php if ($msg = getFlashMessage('success')): ?>
            <div class="alert alert-success">✓ <?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <!-- Lecteur QR -->
        <div id="reader" style="max-width: 300px; margin: 1.5rem 0; border: 2px dashed #bdc3c7; padding: 1rem; border-radius: 5px;"></div>

        <!-- Scan manuel -->
        <form method="POST" style="margin-bottom: 1.5rem;">
            <input type="hidden" name="action" value="scan">
            <div style="display: flex; gap: 0.5rem;">
                <input type="text" name="code_barre" id="scan-input" placeholder="Code-barres" required autofocus value="<?= htmlspecialchars($code_barre_scan ?? '') ?>">
                <button type="submit">Vérifier</button>
            </div>
        </form>

        <?php if ($produit_existant): ?>
            <!-- Afficher le produit existant -->
            <div style="background: #e8f8f5; padding: 1rem; border-left: 4px solid #27ae60; margin-bottom: 1rem;">
                <h3>✓ Produit déjà enregistré</h3>
                <table style="width: 100%; margin-top: 0.5rem;">
                    <tr>
                        <td><strong>Code-barres :</strong></td>
                        <td><?= htmlspecialchars($produit_existant['code_barre']) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Nom :</strong></td>
                        <td><?= htmlspecialchars($produit_existant['nom']) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Prix HT :</strong></td>
                        <td><?= htmlspecialchars($produit_existant['prix_unitaire_ht']) ?> CDF</td>
                    </tr>
                    <tr>
                        <td><strong>Date expiration :</strong></td>
                        <td><?= htmlspecialchars($produit_existant['date_expiration'] ?? 'N/A') ?></td>
                    </tr>
                    <tr>
                        <td><strong>Stock :</strong></td>
                        <td><?= htmlspecialchars($produit_existant['quantite_stock']) ?> unités</td>
                    </tr>
                    <tr>
                        <td><strong>Enregistré le :</strong></td>
                        <td><?= htmlspecialchars($produit_existant['date_enregistrement']) ?></td>
                    </tr>
                </table>
            </div>

        <?php elseif ($code_barre_scan): ?>
            <!-- Formulaire d'enregistrement pour code inconnu -->
            <div style="background: #fef5e7; padding: 1rem; border-left: 4px solid #f39c12; margin-bottom: 1rem;">
                <h3>Enregistrer nouveau produit</h3>
                <p style="color: #7f8c8d; font-size: 0.9rem;">Code-barres <strong><?= htmlspecialchars($code_barre_scan) ?></strong> non reconnu. Complétez les informations :</p>
            </div>

            <form method="POST" style="background: #f8f9fa; padding: 1.5rem; border-radius: 5px;">
                <input type="hidden" name="action" value="enregistrer">
                <input type="hidden" name="code_barre" value="<?= htmlspecialchars($code_barre_scan) ?>">

                <div style="margin-bottom: 1rem;">
                    <label><strong>Nom du produit *</strong></label>
                    <input type="text" name="nom" placeholder="Ex: Vain amour 1L" required 
                           value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
                </div>

                <div style="margin-bottom: 1rem;">
                    <label><strong>Prix unitaire HT (CDF) *</strong></label>
                    <input type="number" name="prix_unitaire_ht" placeholder="Ex: 1200" step="0.01" min="0" required 
                           value="<?= htmlspecialchars($_POST['prix_unitaire_ht'] ?? '') ?>">
                </div>

                <div style="margin-bottom: 1rem;">
                    <label><strong>Date d'expiration (MM-JJ-AAAA) *</strong></label>
                    <input type="text" name="date_expiration" placeholder="Ex: 12-31-2026" required 
                           value="<?= htmlspecialchars($_POST['date_expiration'] ?? '') ?>">
                </div>

                <div style="margin-bottom: 1rem;">
                    <label><strong>Quantité initiale en stock *</strong></label>
                    <input type="number" name="quantite_stock" placeholder="Ex: 50" min="0" required 
                           value="<?= htmlspecialchars($_POST['quantite_stock'] ?? '') ?>">
                </div>

                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn-primary">Enregistrer</button>
                    <a href="<?= url('/modules/produits/enregistrer.php') ?>" class="btn-secondary">Annuler</a>
                </div>
            </form>
        <?php endif; ?>

        <hr>
        <nav class="links">
            <a href="<?= url('/modules/produits/liste.php') ?>">Liste des produits</a> |
            <a href="<?= url('/modules/facturation/nouvelle-facture.php') ?>">Nouvelle facture</a>
        </nav>
    </div>
</div>

<script>
document.querySelectorAll('.alert').forEach(el => setTimeout(() => el.style.display='none', 3000));
</script>
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
let html5QrCode = null;

function onScanSuccess(decodedText) {
    const input = document.getElementById('scan-input');
    input.value = decodedText.trim();
    input.focus();
    setTimeout(() => input.closest('form').submit(), 300);
}

function onScanError(errorMessage) {
    // Ignorer silencieusement
}

function initializeScanner() {
    if (typeof Html5Qrcode === 'undefined') return;
    
    const reader = document.getElementById('reader');
    if (!reader) return;
    
    html5QrCode = new Html5Qrcode('reader');
    Html5Qrcode.getCameras().then((devices) => {
        if (devices && devices.length > 0) {
            html5QrCode.start(
                devices[0].id,
                { fps: 10, qrbox: { width: 250, height: 250 } },
                onScanSuccess,
                onScanError
            ).catch(() => {
                reader.innerHTML = '<p style="color: red;">Erreur : Impossible d\'accéder à la caméra</p>';
            });
        }
    }).catch(() => {
        reader.innerHTML = '<p style="color: red;">Erreur : Aucune caméra détectée</p>';
    });
}

document.addEventListener('DOMContentLoaded', initializeScanner);
window.addEventListener('beforeunload', () => {
    if (html5QrCode) html5QrCode.stop().catch(() => {});
});
</script>

<?php include '../../includes/footer.php'; ?>