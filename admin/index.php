<?php
/**
 * admin/index.php
 * Espace administration rapide pour Mutuka.com.
 */
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/database.php';

requireAuth('admin/index.php', 'connexion.php', 'Accès réservé aux administrateurs. Veuillez vous connecter.');
$currentUser = getCurrentUser();

// Statistiques globales
$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalVehicles = (int)$pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
$totalPurchases = (int)$pdo->query("SELECT COUNT(*) FROM purchases")->fetchColumn();
$totalRentals = (int)$pdo->query("SELECT COUNT(*) FROM rentals")->fetchColumn();

// Derniers utilisateurs inscrits
$latestUsers = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 10")->fetchAll();

$pageTitle = "Administration — Mutuka.com";
require_once __DIR__ . '/../includes/header.php';
?>

<section class="section" style="padding-top: var(--space-5);">
    <div style="margin-bottom: var(--space-5);">
        <p class="eyebrow">Administration Générale</p>
        <h1>Tableau de bord administrateur</h1>
        <p>Vue d'ensemble de la plateforme et des données enregistrées.</p>
    </div>

    <div class="stat-cards-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $totalUsers; ?></div>
            <div class="stat-label">Utilisateurs enregistrés</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $totalVehicles; ?></div>
            <div class="stat-label">Véhicules répertoriés</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $totalPurchases; ?></div>
            <div class="stat-label">Demandes d'achat</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $totalRentals; ?></div>
            <div class="stat-label">Réservations de location</div>
        </div>
    </div>

    <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: var(--space-5); margin-top: var(--space-5);">
        <h3 style="margin-bottom: var(--space-4);">Derniers membres inscrits</h3>
        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nom & Prénom</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th>Ville</th>
                        <th>Rôle</th>
                        <th>Date d'inscription</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($latestUsers as $u): ?>
                        <tr>
                            <td><strong><?php echo e($u['prenom']); ?> <?php echo e($u['nom']); ?></strong></td>
                            <td><?php echo e($u['email']); ?></td>
                            <td><?php echo e($u['telephone']); ?></td>
                            <td><?php echo e($u['ville']); ?></td>
                            <td><span class="spec-pill"><?php echo e($u['role']); ?></span></td>
                            <td><?php echo formatDateFR($u['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
