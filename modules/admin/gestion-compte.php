<?php
require_once '../../config/config.php';
require_once '../../auth/session.php';
require_once '../../includes/fonctions-Auth.php';
require_once '../../includes/fonctions-commons.php';

requireAuth(['admin']);

$users = getAllUsers();
include '../../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="header-flex">
            <h2>Gestion des Comptes</h2>
            <a href="<?= url('/modules/admin/ajouter-compte.php') ?>" class="btn-primary">+ Ajouter</a>
        </div>

        <?php if (!empty($users)): ?>
            <table class="table">
                <thead>
                    <tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Créé</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($u['nom']) ?></strong></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><span class="role-badge"><?= ucfirst($u['role'] ?? 'user') ?></span></td>
                            <td><?= substr($u['created_at'] ?? '', 0, 10) ?></td>
                            <td>
                                <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                    <a href="<?= url('/modules/admin/supprimer-compte.php?id=' . urlencode($u['id'])) ?>" 
                                       onclick="return confirm('Confirmer ?')">Suppr.</a>
                                <?php else: ?>
                                    <span>—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php
                $admins = count(array_filter($users, fn($u) => ($u['role'] ?? '') === 'admin'));
                $vendeurs = count(array_filter($users, fn($u) => ($u['role'] ?? '') === 'vendeur'));
            ?>
            <p><strong><?= count($users) ?></strong> utilisateurs | <strong><?= $admins ?></strong> admins | <strong><?= $vendeurs ?></strong> vendeurs</p>
        <?php else: ?>
            <p>Aucun utilisateur</p>
        <?php endif; ?>

        <hr>
        <nav class="links">
            <a href="<?= url('/modules/admin/ajouter-compte.php') ?>">Ajouter</a> |
            <a href="<?= url('/modules/facturation/nouvelle-facture.php') ?>">Facturation</a> |
            <a href="<?= url('/modules/produits/liste.php') ?>">Produits</a>
        </nav>
    </div>
</div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>