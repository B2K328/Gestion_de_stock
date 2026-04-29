<?php
/**
 * Calculs de facturation
 * Gestion de Stock - Transco
 * 
 * Fonctions de calcul pour les factures :
 * - Sous-totaux
 * - Total HT
 * - TVA (18%)
 * - Total TTC
 * - Formatage des prix
 */

declare(strict_types=1);

/**
 * Calculer le sous-total HT d'une ligne
 * @param float $prix Prix unitaire HT
 * @param int $qte Quantité
 * @return float Sous-total HT
 */
function calculSousTotal(float $prix, int $qte): float {
    return max(0, $prix * $qte);
}

/**
 * Calculer le total HT du panier
 * @param array $panier Tableau du panier
 * @return float Total HT
 */
function calculTotalHT(array $panier): float {
    if (empty($panier)) {
        return 0.0;
    }
    return array_sum(array_column($panier, 'sous_total_ht'));
}

/**
 * Calculer la TVA à 18%
 * @param float $ht Total HT
 * @return float Montant TVA
 */
function calculTVA(float $ht): float {
    return round($ht * 0.18, 2);
}

/**
 * Calculer le total TTC
 * @param float $ht Total HT
 * @param float $tva Montant TVA
 * @return float Total TTC
 */
function calculTTC(float $ht, float $tva): float {
    return round($ht + $tva, 2);
}

/**
 * Formater un prix pour affichage
 * @param float $montant Montant à formater
 * @return string Montant formaté
 */
function formaterPrix(float $montant): string {
    return number_format($montant, 2, ',', ' ') . ' DH';
}