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

<section class="section" style="padding-top: var(--space-4);">
    <div style="margin-bottom: var(--space-4);">
        <p class="eyebrow">Administration Générale</p>
        <h1 style="font-size: 1.8rem; margin-bottom: 4px;">Tableau de bord administrateur</h1>
        <p style="color: var(--muted); margin: 0;">Vue d'ensemble de la plateforme et des données enregistrées.</p>
    </div>

    <div class="stat-cards-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
        <div class="stat-card">
            <div class="stat-icon-box">👥</div>
            <div class="stat-info">
                <div class="stat-number"><?php echo $totalUsers; ?></div>
                <div class="stat-label">Utilisateurs inscrits</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon-box">🚗</div>
            <div class="stat-info">
                <div class="stat-number"><?php echo $totalVehicles; ?></div>
                <div class="stat-label">Véhicules répertoriés</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon-box">💳</div>
            <div class="stat-info">
                <div class="stat-number"><?php echo $totalPurchases; ?></div>
                <div class="stat-label">Demandes d'achat</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon-box">📅</div>
            <div class="stat-info">
                <div class="stat-number"><?php echo $totalRentals; ?></div>
                <div class="stat-label">Réservations location</div>
            </div>
        </div>
    </div>

    <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: var(--space-4); margin-top: var(--space-4);">
        <h3 style="margin-bottom: var(--space-3); font-size: 1.15rem;">Derniers membres inscrits</h3>
        
        <!-- Table Desktop -->
        <div class="desktop-table-view data-table-wrap">
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

        <!-- Cartes Mobiles -->
        <div class="mobile-cards-view">
            <?php foreach ($latestUsers as $u): ?>
                <div class="mobile-data-card">
                    <div class="mobile-card-top">
                        <h4 class="mobile-card-veh-title"><?php echo e($u['prenom']); ?> <?php echo e($u['nom']); ?></h4>
                        <span class="spec-pill"><?php echo e($u['role']); ?></span>
                    </div>
                    <div class="mobile-card-details-grid">
                        <div class="mobile-card-col">
                            <span class="mobile-card-label">Email</span>
                            <span class="mobile-card-val" style="word-break: break-all;"><?php echo e($u['email']); ?></span>
                        </div>
                        <div class="mobile-card-col">
                            <span class="mobile-card-label">Ville</span>
                            <span class="mobile-card-val"> <?php echo e($u['ville']); ?></span>
                        </div>
                    </div>
                    <div class="mobile-contact-row">
                        <div class="mobile-contact-info">
                            <span class="mobile-card-label">Téléphone</span>
                            <span class="mobile-contact-phone"><?php echo e($u['telephone']); ?></span>
                        </div>
                        <a href="tel:<?php echo e($u['telephone']); ?>" class="mobile-call-btn">
                            <span> Appeler</span>
                        </a>
                    </div>
                    <div style="font-size: 0.78rem; color: var(--muted); text-align: right;">
                        Inscrit le <?php echo formatDateFR($u['created_at']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
