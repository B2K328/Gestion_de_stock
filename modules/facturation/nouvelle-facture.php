<?php
/**
 * Nouvelle Facture - Interface de Facturation
 * Étudiant 2: Front-End & Hardware
 * 
 * Flux:
 * 1. Scanner lit code-barres
 * 2. Produit affiché automatiquement
 * 3. Saisie quantité
 * 4. Récapitulatif en temps réel
 * 5. Validation et enregistrement
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/fonctions-commons.php';
require_once __DIR__ . '/../../includes/fonctions-produits.php';
require_once __DIR__ . '/../../includes/fonctions-facture.php';
require_once __DIR__ . '/../../includes/fonctions-calcul.php';
require_once __DIR__ . '/../../auth/session.php';
// NOTE: header.php outputs HTML. We include it only for GET (page) requests
// to avoid sending HTML before JSON responses for AJAX POST requests.

// Vérifier l'authentification
if (!isset($_SESSION['user_id'])) {
    // Si la requête est une requête POST AJAX, renvoyer JSON d'erreur au lieu de rediriger
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Utilisateur non authentifié']);
        exit;
    }
    header('Location: /auth/login.php');
    exit;
}

$pageTitle = 'Nouvelle Facture';
$errors = [];
$success = false;

// Initialiser le panier en session
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// === TRAITEMENTS AJAX ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    $products = getAllProducts();
    
    switch ($action) {
        case 'search_product':
            // Rechercher un produit par code
            $code = trim($_POST['code'] ?? '');
            
            if (empty($code)) {
                echo json_encode(['success' => false, 'error' => 'Code vide']);
                exit;
            }
            
            $product = null;
            foreach ($products as $p) {
                if ($p['code'] === $code) {
                    $product = $p;
                    break;
                }
            }
            
            if (!$product) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Produit non trouvé',
                    'register_url' => '/modules/produits/enregistrer.php?code=' . urlencode($code)
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'product' => $product
                ]);
            }
            exit;
        
        case 'add_to_cart':
            // Ajouter au panier
            $product_id = trim($_POST['product_id'] ?? '');
            $quantity = (int)($_POST['quantity'] ?? 1);
            
            if (empty($product_id) || $quantity <= 0) {
                echo json_encode(['success' => false, 'error' => 'Données invalides']);
                exit;
            }
            
            $product = findProductById($product_id);
            if (!$product) {
                echo json_encode(['success' => false, 'error' => 'Produit non trouvé']);
                exit;
            }
            
            // Vérifier le stock
            if ($quantity > $product['quantite_stock']) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Stock insuffisant. Disponible: ' . $product['quantite_stock']
                ]);
                exit;
            }
            
            // Ajouter ou mettre à jour le panier
            $found = false;
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['product_id'] === $product_id) {
                    $item['quantity'] += $quantity;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $_SESSION['cart'][] = [
                    'product_id' => $product_id,
                    'quantity' => $quantity
                ];
            }
            
            echo json_encode(['success' => true, 'cart_count' => count($_SESSION['cart'])]);
            exit;
        
        case 'remove_from_cart':
            // Supprimer du panier
            $product_id = trim($_POST['product_id'] ?? '');
            $_SESSION['cart'] = array_filter($_SESSION['cart'], 
                fn($item) => $item['product_id'] !== $product_id
            );
            $_SESSION['cart'] = array_values($_SESSION['cart']);
            
            echo json_encode(['success' => true, 'cart_count' => count($_SESSION['cart'])]);
            exit;
        
        case 'get_cart':
            // Retourner le panier actuel
            $cart_data = [];
            $total_ht = 0;
            
            foreach ($_SESSION['cart'] as $item) {
                $product = findProductById($item['product_id']);
                if ($product) {
                    $sous_total = $product['prix_vente'] * $item['quantity'];
                    $total_ht += $sous_total;
                    
                    $cart_data[] = [
                        'product_id' => $product['id'],
                        'code' => $product['code'],
                        'nom' => $product['nom'],
                        'prix_vente' => $product['prix_vente'],
                        'quantity' => $item['quantity'],
                        'sous_total' => $sous_total
                    ];
                }
            }
            
            $tva = $total_ht * TVA_RATE;
            $total_ttc = $total_ht + $tva;
            
            echo json_encode([
                'success' => true,
                'items' => $cart_data,
                'total_ht' => $total_ht,
                'tva' => $tva,
                'total_ttc' => $total_ttc,
                'count' => count($cart_data)
            ]);
            exit;
        
        case 'finalize_invoice':
            // Créer la facture
            if (empty($_SESSION['cart'])) {
                echo json_encode(['success' => false, 'error' => 'Panier vide']);
                exit;
            }
            
            // Préparer les articles
            $articles = [];
            foreach ($_SESSION['cart'] as $item) {
                $product = findProductById($item['product_id']);
                if ($product) {
                    $articles[] = [
                        'produit_id' => $product['id'],
                        'quantite' => $item['quantity']
                    ];
                }
            }
            
            // Créer la facture via la fonction de l'étudiant 3
            require_once __DIR__ . '/../../includes/fonctions-facture.php';
            
            $data = [
                'client' => $_POST['client_name'] ?? 'Client',
                'articles' => $articles,
                'mode_paiement' => $_POST['payment_method'] ?? 'espece'
            ];
            
            $invoice = createInvoiceWithStockUpdate($data);
            
            if ($invoice) {
                unset($_SESSION['cart']);
                echo json_encode([
                    'success' => true,
                    'invoice_id' => $invoice['id'],
                    'message' => 'Facture créée avec succès'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Erreur lors de la création de la facture'
                ]);
            }
            exit;
    }
}

// === PAGE GET ===
// Inclure l'entête HTML uniquement pour l'affichage de la page (GET)
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>💳 Créer une Nouvelle Facture</h1>
        <p>Scannez les produits et générez une facture détaillée</p>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        
        <!-- Colonne Gauche: Scanner & Produits -->
        <div>
            <!-- Scanner -->
            <div class="card mb-3">
                <div class="card-header">
                    <h3>🔍 Scanner de Code-Barres</h3>
                </div>
                <div class="card-body">
                    <div class="scanner-container" id="scanner-container">
                        <video id="scanner-video" style="display: none;"></video>
                        <div class="scanner-controls">
                            <button class="btn btn-primary" id="btn-start-scanner">Démarrer</button>
                            <button class="btn btn-danger" id="btn-stop-scanner" disabled>Arrêter</button>
                        </div>
                        <div class="scanner-status" id="scanner-status">En attente...</div>
                    </div>
                    
                    <!-- Alternative: Saisie manuelle -->
                    <div style="margin-top: 1rem;">
                        <input type="text" id="manual-code" placeholder="Ou entrez le code manuellement..."
                            style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                </div>
            </div>

            <!-- Détails du Produit Sélectionné -->
            <div class="card" id="product-details" style="display: none;">
                <div class="card-header">
                    <h3>📦 Produit Sélectionné</h3>
                </div>
                <div class="card-body">
                    <table style="width: 100%;">
                        <tr>
                            <td><strong>Code:</strong></td>
                            <td><span id="detail-code"></span></td>
                        </tr>
                        <tr>
                            <td><strong>Nom:</strong></td>
                            <td><span id="detail-name"></span></td>
                        </tr>
                        <tr>
                            <td><strong>Prix:</strong></td>
                            <td><span id="detail-price"></span> CDF</td>
                        </tr>
                        <tr>
                            <td><strong>Stock:</strong></td>
                            <td><span id="detail-stock"></span></td>
                        </tr>
                    </table>
                    
                    <div class="form-group mt-3">
                        <label for="quantity">Quantité à vendre:</label>
                        <input type="number" id="quantity" name="quantity" min="1" value="1"
                            style="width: 100%; padding: 0.75rem;">
                    </div>
                    
                    <button class="btn btn-success btn-block" id="btn-add-to-cart">
                        ✓ Ajouter au Panier
                    </button>
                    
                    <button class="btn btn-secondary btn-block mt-2" id="btn-reset-product">
                        Annuler
                    </button>
                </div>
            </div>

            <!-- Messages d'erreur -->
            <div id="error-message" class="alert alert-danger" style="display: none; margin-top: 1rem;"></div>
        </div>

        <!-- Colonne Droite: Panier & Récapitulatif -->
        <div>
            <!-- Panier -->
            <div class="card mb-3">
                <div class="card-header">
                    <h3>🛒 Panier (<span id="cart-count">0</span> articles)</h3>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <div id="cart-items">
                        <p style="text-align: center; color: #999;">Panier vide</p>
                    </div>
                </div>
            </div>

            <!-- Récapitulatif -->
            <div class="summary-box">
                <div class="summary-row">
                    <span class="summary-label">Total HT:</span>
                    <span class="summary-value"><span id="total-ht">0.00</span> CDF</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">TVA (18%):</span>
                    <span class="summary-value"><span id="total-tva">0.00</span> CDF</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Net à Payer:</span>
                    <span class="summary-value"><span id="total-ttc">0.00</span> CDF</span>
                </div>

                <!-- Informations Client -->
                <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #ddd;">
                    <div class="form-group">
                        <label for="client-name">Nom du Client:</label>
                        <input type="text" id="client-name" placeholder="Optionnel"
                            style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px;">
                    </div>

                    <div class="form-group">
                        <label for="payment-method">Mode de Paiement:</label>
                        <select id="payment-method"
                            style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="espece">Espèces</option>
                            <option value="cheque">Chèque</option>
                            <option value="virement">Virement</option>
                            <option value="mobile_money">Mobile Money</option>
                        </select>
                    </div>

                    <!-- Boutons -->
                    <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                        <button class="btn btn-success" id="btn-finalize" style="flex: 1;" disabled>
                            ✓ Valider la Facture
                        </button>
                        <button class="btn btn-secondary" id="btn-cancel-cart" style="flex: 1;">
                            ✗ Annuler
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charger ZXing pour la détection de codes-barres -->
<script src="https://unpkg.com/@zxing/library@latest/umd/index.min.js"></script>

<!-- Charger le scanner personnalisé -->
<script src="/assets/js/scanner.js"></script>

<script>
// ============================================
// VARIABLES GLOBALES
// ============================================
let currentProduct = null;

// ============================================
// FONCTIONS UTILITAIRES
// ============================================
async function searchProduct(code) {
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=search_product&code=' + encodeURIComponent(code)
        });
        const data = await fetchJson(response);
        
        if (data.success) {
            currentProduct = data.product;
            displayProductDetails(data.product);
            document.getElementById('error-message').style.display = 'none';
        } else {
            showError(data.error);
            if (data.register_url) {
                showError(data.error + 
                    ' - <a href="' + data.register_url + '" class="btn btn-primary" style="display: inline;">Enregistrer</a>'
                );
            }
        }
    } catch (error) {
        showError('Erreur: ' + error.message);
    }
}

async function addToCart() {
    if (!currentProduct) return;
    
    const quantity = parseInt(document.getElementById('quantity').value) || 1;
    
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=add_to_cart&product_id=' + currentProduct.id + '&quantity=' + quantity
        });
        const data = await fetchJson(response);
        
        if (data.success) {
            updateCart();
            resetProduct();
            if (scanner) {
                resetLastScannedCode();
            }
        } else {
            showError(data.error);
        }
    } catch (error) {
        showError('Erreur: ' + error.message);
    }
}

async function removeFromCart(productId) {
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=remove_from_cart&product_id=' + encodeURIComponent(productId)
        });
        const data = await fetchJson(response);
        if (data.success) {
            updateCart();
        }
    } catch (error) {
        showError('Erreur: ' + error.message);
    }
}

async function updateCart() {
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=get_cart'
        });
        const data = await fetchJson(response);
        
        if (data.success) {
            document.getElementById('cart-count').textContent = data.count;
            document.getElementById('total-ht').textContent = data.total_ht.toFixed(2);
            document.getElementById('total-tva').textContent = data.tva.toFixed(2);
            document.getElementById('total-ttc').textContent = data.total_ttc.toFixed(2);
            
            const cartHtml = data.items.length > 0 ? 
                data.items.map(item => `
                    <div class="invoice-line">
                        <div class="invoice-line-name">${item.nom}</div>
                        <div class="invoice-line-price">${item.prix_vente.toFixed(2)} CDF</div>
                        <div class="invoice-line-qty">× ${item.quantity}</div>
                        <div class="invoice-line-total">${item.sous_total.toFixed(2)} CDF</div>
                        <button type="button" class="invoice-line-remove" 
                            onclick="removeFromCart('${item.product_id}')">✕</button>
                    </div>
                `).join('') :
                '<p style="text-align: center; color: #999;">Panier vide</p>';
            
            document.getElementById('cart-items').innerHTML = cartHtml;
            document.getElementById('btn-finalize').disabled = data.count === 0;
        }
    } catch (error) {
        showError('Erreur: ' + error.message);
    }
}

// Wrapper pour lire la réponse JSON en vérifiant le Content-Type.
async function fetchJson(response) {
    const contentType = response.headers.get('content-type') || '';
    const text = await response.text();
    // Si ce n'est pas du JSON, lever une erreur en affichant le contenu (généralement HTML)
    if (!contentType.includes('application/json')) {
        // Retourner une erreur lisible qui contiendra le début de la réponse HTML
        const snippet = text.length > 1000 ? text.slice(0, 1000) + '...' : text;
        throw new Error('Réponse inattendue du serveur (non JSON): ' + snippet);
    }
    try {
        return JSON.parse(text);
    } catch (e) {
        throw new Error('Impossible de parser le JSON: ' + e.message + '\nRéponse: ' + text.slice(0, 1000));
    }
}

function displayProductDetails(product) {
    document.getElementById('detail-code').textContent = product.code;
    document.getElementById('detail-name').textContent = product.nom;
    document.getElementById('detail-price').textContent = product.prix_vente.toFixed(2);
    document.getElementById('detail-stock').textContent = product.quantite_stock + ' ' + product.unite;
    document.getElementById('quantity').value = '1';
    document.getElementById('quantity').max = product.quantite_stock;
    document.getElementById('product-details').style.display = 'block';
}

function resetProduct() {
    currentProduct = null;
    document.getElementById('product-details').style.display = 'none';
    document.getElementById('manual-code').value = '';
}

function showError(message) {
    const errorDiv = document.getElementById('error-message');
    errorDiv.innerHTML = message;
    errorDiv.style.display = 'block';
}

// ============================================
// ÉVÉNEMENTS
// ============================================

// Scanner intégré
if (scanner) {
    scanner.onScanned((code) => {
        document.getElementById('manual-code').value = code;
        searchProduct(code);
    });
}

// Saisie manuelle
document.getElementById('manual-code').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        const code = document.getElementById('manual-code').value.trim();
        if (code) {
            searchProduct(code);
        }
    }
});

// Boutons du panier
document.getElementById('btn-add-to-cart').addEventListener('click', addToCart);
document.getElementById('btn-reset-product').addEventListener('click', resetProduct);
document.getElementById('btn-cancel-cart').addEventListener('click', () => {
    if (confirm('Êtes-vous sûr d\'annuler cette facture?')) {
        window.location.reload();
    }
});

// Valider la facture
document.getElementById('btn-finalize').addEventListener('click', async () => {
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=finalize_invoice&client_name=' + encodeURIComponent(document.getElementById('client-name').value) +
                  '&payment_method=' + encodeURIComponent(document.getElementById('payment-method').value)
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('✓ Facture ' + data.invoice_id + ' créée avec succès!');
            window.location.href = '/modules/facturation/afficher-facture.php?id=' + encodeURIComponent(data.invoice_id);
        } else {
            showError(data.error);
        }
    } catch (error) {
        showError('Erreur: ' + error.message);
    }
});

// Charger le panier au démarrage
updateCart();
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
