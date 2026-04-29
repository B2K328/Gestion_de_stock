/**
 * Scanner QR Code
 * Gestion de Stock - Transco
 * 
 * Utilise html5-qrcode pour scanner les codes produits
 * Intègre caméra en temps réel avec BIP sonore
 */

// Créer une instance de lecteur QR
let html5QrCode = null;
let scannerIsRunning = false;

/**
 * Émettre un BIP sonore après scan réussi
 */
function playBeep() {
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();

    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);

    oscillator.frequency.value = 800;
    oscillator.type = 'sine';

    gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.2);

    oscillator.start(audioContext.currentTime);
    oscillator.stop(audioContext.currentTime + 0.2);
}

/**
 * Callback en cas de scan réussi
 * @param {string} decodedText Code scanné
 */
function onScanSuccess(decodedText) {
    if (!decodedText || decodedText.trim() === '') {
        return;
    }

    // Émettre un BIP
    playBeep();

    // Remplir le champ d'entrée
    const inputField = document.getElementById('code');
    if (inputField) {
        inputField.value = decodedText.trim();
        inputField.focus();

        // Soumettre le formulaire automatiquement
        const form = inputField.closest('form');
        if (form) {
            setTimeout(() => {
                form.submit();
            }, 500);
        }
    }
}

/**
 * Callback en cas d'erreur de scan
 * @param {string} errorMessage Message d'erreur
 */
function onScanError(errorMessage) {
    // Ignorer les erreurs silencieusement (normal pendant la lecture)
}

/**
 * Initialiser le scanner QR
 */
function initializeScanner() {
    // Vérifier que html5QrCode est disponible
    if (typeof Html5Qrcode === 'undefined') return;

    const readerElement = document.getElementById('reader');
    if (!readerElement) return;

    html5QrCode = new Html5Qrcode('reader');

    // Obtenir les caméras disponibles
    Html5Qrcode.getCameras()
        .then((devices) => {
            if (devices && devices.length > 0) {
                // Utiliser la première caméra disponible
                const cameraId = devices[0].id;
                const config = {
                    fps: 10,
                    qrbox: { width: 250, height: 250 },
                    aspectRatio: 1.0,
                    disableFlip: false
                };

                html5QrCode.start(cameraId, config, onScanSuccess, onScanError)
                    .then(() => {
                        scannerIsRunning = true;
                    })
                    .catch((error) => {
                        readerElement.innerHTML = '<p style="color: red; padding: 1rem;">Erreur : Impossible d\'accéder à la caméra. Vérifiez les permissions.</p>';
                    });
            } else {
                readerElement.innerHTML = '<p style="color: red; padding: 1rem;">Erreur : Aucune caméra détectée.</p>';
            }
        })
        .catch((error) => {
            readerElement.innerHTML = '<p style="color: red; padding: 1rem;">Erreur : Impossible d\'accéder aux caméras.</p>';
        });
}

/**
 * Arrêter le scanner
 */
function stopScanner() {
    if (html5QrCode && scannerIsRunning) {
        html5QrCode.stop()
            .then(() => {
                scannerIsRunning = false;
            })
            .catch(() => {});
    }
}

// Initialiser le scanner au chargement du DOM
document.addEventListener('DOMContentLoaded', initializeScanner);

// Arrêter le scanner avant de quitter la page
window.addEventListener('beforeunload', stopScanner);