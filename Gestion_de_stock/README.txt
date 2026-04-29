================================================================================
SYSTÈME DE FACTURATION AVEC LECTURE DE CODES-BARRES
Programmation Web PHP Procédural - L2 FASI UPC 2025-2026
================================================================================

INSTRUCTIONS DE DÉPLOIEMENT LOCAL

1. PRÉREQUIS
   - PHP 7.4 ou supérieur
   - Serveur web (Apache, Nginx, ou PHP built-in)
   - Navigateur moderne supportant les WebSockets (Chrome, Firefox, Edge)
   - Accès à la caméra (pour lecteur QR)

2. INSTALLATION

   a) Via Laragon (recommandé)
      - Placer le dossier "Gestion_de_stock" dans c:\laragon\www\
      - Démarrer Laragon
      - L'application sera accessible à http://Gestion_de_stock.local

   b) Via PHP built-in
      - Naviguer dans le dossier du projet
      - Exécuter : php -S localhost:8000
      - Accéder à http://localhost:8000

   c) Via Apache/Nginx
      - Configurer le DocumentRoot vers le dossier du projet
      - Vérifier que mod_rewrite est activé (Apache)
      - Accéder via votre domaine configuré

3. CONFIGURATION

   - Les fichiers de configuration se trouvent dans config/config.php
   - Les données JSON se trouvent dans data/
      * utilisateurs.json : liste des utilisateurs
      * produits.json : catalogue de produits
      * factures.json : historique des factures

4. PREMIER DÉMARRAGE

   - Un compte administrateur par défaut est créé :
      Email : admin@stock.local
      Mot de passe : admin123
   
   - Se connecter à http://[votre-domaine]/auth/login.php
   - Modifier le mot de passe une fois connecté

5. STRUCTURE DU PROJET

   Gestion_de_stock/
   ├── index.php                 # Page d'accueil
   ├── config/                   # Configuration globale
   ├── auth/                      # Authentification et sessions
   ├── modules/                   # Modules métier
   │   ├── produits/             # Gestion catalogue
   │   ├── facturation/          # Module de facturation
   │   └── admin/                # Administration
   ├── data/                      # Persistance JSON
   ├── includes/                  # Fonctions réutilisables
   └── assets/                    # Ressources (CSS, JavaScript)

6. FONCTIONNALITÉS

   ✓ Authentification sécurisée par email/password
   ✓ Contrôle d'accès basé sur les rôles (RBAC)
   ✓ Lecture de codes-barres via caméra
   ✓ Enregistrement de produits
   ✓ Facturation avec calcul TVA automatique
   ✓ Gestion des comptes utilisateurs
   ✓ Persistance par fichiers JSON

7. NAVIGUER DANS L'APPLICATION

   - Page d'accueil : voir statistiques et dernières factures
   - Produits → Lister : consulter le catalogue
   - Produits → Enregistrer : ajouter un nouveau produit
   - Facturation → Nouvelle facture : créer une facture
   - Admin → Gestion des comptes : gérer les utilisateurs

8. SÉCURITÉ

   - Les mots de passe sont hachés avec password_hash()
   - Les sessions PHP sont activées en mode strict
   - Tous les inputs sont validés et assainis côté serveur
   - Accès restreint par rôle et authentification

9. DÉPANNAGE

   Q: La caméra n'est pas détectée
   R: Vérifier les permissions du navigateur pour accéder à la caméra
      L'application doit être en HTTPS ou sur localhost

   Q: Les fichiers JSON ne se créent pas
   R: Vérifier que le dossier /data existe et est accessible en écriture

   Q: Erreur de connexion 
   R: Vérifier que utilisateurs.json existe dans data/

10. SUPPORT & DOCUMENTATION

   - Code commenté en français
   - Arborescence détaillée dans le rapport technique
   - Toutes les fonctions commentées en entête

================================================================================
Date de création : 2026-04-29
Version : 1.0
================================================================================
