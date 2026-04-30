/**
 * Scanner de Codes-Barres
 * Utilise QuaggaJS pour la lecture de codes-barres via webcam
 */

class BarcodeScanner {
    constructor(videoElementId = 'scanner-video', containerId = 'scanner-container') {
        this.videoElement = document.getElementById(videoElementId);
        this.container = document.getElementById(containerId);
        this.isRunning = false;
        this.lastScannedCode = null;
        this.scannedCallback = null;
        this.errorCallback = null;
        this.constraints = {
            audio: false,
            video: {
                facingMode: 'environment',
                width: { ideal: 1280 },
                height: { ideal: 720 }
            }
        };
    }

    /**
     * Initialiser la caméra
     */
    async init() {
        try {
            // Vérifier la disponibilité de getUserMedia
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                throw new Error('getUserMedia n\'est pas supporté par votre navigateur');
            }

            // Accéder à la caméra
            const stream = await navigator.mediaDevices.getUserMedia(this.constraints);
            
            // Attacher le stream à la vidéo
            if (this.videoElement.srcObject !== undefined) {
                this.videoElement.srcObject = stream;
            } else {
                this.videoElement.src = URL.createObjectURL(stream);
            }

            // Attendre que la vidéo soit prête
            return new Promise((resolve) => {
                this.videoElement.onloadedmetadata = () => {
                    this.videoElement.play();
                    this.isRunning = true;
                    this.updateStatus('Caméra activée - En attente de codes-barres...', 'active');
                    this.startDetection();
                    resolve();
                };
            });

        } catch (error) {
            console.error('Erreur accès caméra:', error);
            this.updateStatus('Erreur: ' + error.message, 'error');
            if (this.errorCallback) {
                this.errorCallback(error.message);
            }
            throw error;
        }
    }

    /**
     * Démarrer la détection des codes-barres
     */
    startDetection() {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        
        const detect = () => {
            if (!this.isRunning) return;

            // Adapter la taille du canvas
            canvas.width = this.videoElement.videoWidth;
            canvas.height = this.videoElement.videoHeight;

            // Dessiner le frame vidéo
            ctx.drawImage(this.videoElement, 0, 0);

            // Essayer de détecter un code-barres
            this.decodeFrame(canvas);

            requestAnimationFrame(detect);
        };

        detect();
    }

    /**
     * Décoder un frame pour détecter les codes-barres
     */
    decodeFrame(canvas) {
        const imageData = canvas.getContext('2d').getImageData(0, 0, canvas.width, canvas.height);
        
        // Essayer avec ZXing si disponible
        if (typeof ZXing !== 'undefined') {
            try {
                const hints = new Map();
                hints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS, 
                    [ZXing.BarcodeFormat.EAN_13, ZXing.BarcodeFormat.EAN_8, ZXing.BarcodeFormat.CODE_128]);
                
                const reader = new ZXing.BrowserMultiFormatReader(hints);
                const result = reader.decodeFromImageData(imageData);
                
                if (result && result.text !== this.lastScannedCode) {
                    this.lastScannedCode = result.text;
                    this.onBarcodeDetected(result.text);
                }
            } catch (error) {
                // Pas de code-barres trouvé dans ce frame
            }
        } else if (typeof Quagga !== 'undefined') {
            // Fallback sur Quagga si disponible
            this.decodeWithQuagga(canvas);
        }
    }

    /**
     * Décoder avec Quagga
     */
    decodeWithQuagga(canvas) {
        // Quagga.decodeSingle est utilisé pour décoder des images statiques
        Quagga.decodeSingle({
            src: canvas.toDataURL(),
            numOfWorkers: 1,
            inputStream: {
                size: 800
            },
            decoder: {
                readers: ['ean_reader', 'ean_8_reader', 'code_128_reader']
            }
        }, (result) => {
            if (result.codeResult && result.codeResult.code !== this.lastScannedCode) {
                this.lastScannedCode = result.codeResult.code;
                this.onBarcodeDetected(result.codeResult.code);
            }
        });
    }

    /**
     * Callback quand un code-barres est détecté
     */
    onBarcodeDetected(code) {
        console.log('Code-barres détecté:', code);
        this.updateStatus('✓ Code détecté: ' + code, 'active');
        
        if (this.scannedCallback) {
            this.scannedCallback(code);
        }

        // Béep sonore (optionnel)
        this.playBeep();
    }

    /**
     * Jouer un beep sonore
     */
    playBeep() {
        if (typeof AudioContext !== 'undefined' || typeof webkitAudioContext !== 'undefined') {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            oscillator.frequency.value = 800;
            oscillator.type = 'sine';

            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);

            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.1);
        }
    }

    /**
     * Arrêter le scanner
     */
    stop() {
        this.isRunning = false;
        
        if (this.videoElement.srcObject) {
            this.videoElement.srcObject.getTracks().forEach(track => track.stop());
        }
        
        this.updateStatus('Scanner arrêté', '');
    }

    /**
     * Mettre à jour le statut
     */
    updateStatus(message, className = '') {
        const statusElement = document.getElementById('scanner-status');
        if (statusElement) {
            statusElement.textContent = message;
            statusElement.className = 'scanner-status ' + className;
        }
    }

    /**
     * Setter pour le callback de scan
     */
    onScanned(callback) {
        this.scannedCallback = callback;
    }

    /**
     * Setter pour le callback d'erreur
     */
    onError(callback) {
        this.errorCallback = callback;
    }
}

// ============================================
// INTÉGRATION AVEC LA PAGE
// ============================================

let scanner = null;

/**
 * Initialiser le scanner quand le DOM est prêt
 */
document.addEventListener('DOMContentLoaded', () => {
    const scannerContainer = document.getElementById('scanner-container');
    
    if (scannerContainer) {
        scanner = new BarcodeScanner('scanner-video', 'scanner-container');

        // Boutons de contrôle
        const startBtn = document.getElementById('btn-start-scanner');
        const stopBtn = document.getElementById('btn-stop-scanner');

        if (startBtn) {
            startBtn.addEventListener('click', async () => {
                try {
                    await scanner.init();
                    startBtn.disabled = true;
                    if (stopBtn) stopBtn.disabled = false;
                } catch (error) {
                    console.error('Erreur scanner:', error);
                }
            });
        }

        if (stopBtn) {
            stopBtn.addEventListener('click', () => {
                scanner.stop();
                if (startBtn) startBtn.disabled = false;
                stopBtn.disabled = true;
            });
        }
    }
});

/**
 * Fonction globale pour obtenir le code dernièrement scanné
 */
function getLastScannedCode() {
    return scanner ? scanner.lastScannedCode : null;
}

/**
 * Fonction globale pour réinitialiser le dernier code scanné
 */
function resetLastScannedCode() {
    if (scanner) {
        scanner.lastScannedCode = null;
    }
}
