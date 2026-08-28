<?php
/**
 * compte/index.php
 * Espace personnel de l'utilisateur : gestion des annonces, suivi des achats,
 * suivi des locations, traitement des demandes reçues et modification du profil.
 */
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/database.php';

requireAuth('compte/index.php');
$currentUser = getCurrentUser();
$userId = $currentUser['id'];

// Actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Mise à jour du statut d'une annonce
    if ($action === 'update_vehicle_status') {
        $vehId = (int)($_POST['vehicle_id'] ?? 0);
        $newStatut = $_POST['statut'] ?? 'disponible';
        if (in_array($newStatut, ['disponible', 'vendu', 'loue', 'reserve'])) {
            $stmt = $pdo->prepare("UPDATE vehicles SET statut = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$newStatut, $vehId, $userId]);
            setFlash('success', "Le statut de votre annonce a été mis à jour.");
        }
        header('Location: ' . url('compte/index.php#annonces'));
        exit;
    }

    // 2. Suppression d'une annonce
    if ($action === 'delete_vehicle') {
        $vehId = (int)($_POST['vehicle_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = ? AND user_id = ?");
        $stmt->execute([$vehId, $userId]);
        setFlash('success', "Votre annonce a été supprimée définitivement.");
        header('Location: ' . url('compte/index.php#annonces'));
        exit;
    }

    // 3. Mise à jour du statut d'une demande d'achat reçue
    if ($action === 'update_purchase_status') {
        $purchaseId = (int)($_POST['purchase_id'] ?? 0);
        $newStatus = $_POST['statut'] ?? 'en_attente';
        if (in_array($newStatus, ['en_attente', 'confirme', 'finalise', 'annule'])) {
            $stmt = $pdo->prepare("UPDATE purchases SET statut = ? WHERE id = ? AND seller_id = ?");
            $stmt->execute([$newStatus, $purchaseId, $userId]);
            setFlash('success', "La demande d'achat a été mise à jour.");
        }
        header('Location: ' . url('compte/index.php#demandes'));
        exit;
    }

    // 4. Mise à jour du statut d'une location reçue
    if ($action === 'update_rental_status') {
        $rentalId = (int)($_POST['rental_id'] ?? 0);
        $newStatus = $_POST['statut'] ?? 'en_attente';
        if (in_array($newStatus, ['en_attente', 'validee', 'terminee', 'annulee'])) {
            $stmt = $pdo->prepare("UPDATE rentals SET statut = ? WHERE id = ? AND owner_id = ?");
            $stmt->execute([$newStatus, $rentalId, $userId]);
            setFlash('success', "Le statut de la réservation a été mis à jour.");
        }
        header('Location: ' . url('compte/index.php#demandes'));
        exit;
    }

    // 5. Modification des informations du profil
    if ($action === 'update_profile') {
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        $ville = trim($_POST['ville'] ?? '');
        $newPass = $_POST['new_password'] ?? '';

        if (!empty($nom) && !empty($prenom) && !empty($telephone)) {
            if (!empty($newPass)) {
                $hash = password_hash($newPass, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET nom = ?, prenom = ?, telephone = ?, whatsapp = ?, ville = ?, password = ? WHERE id = ?");
                $stmt->execute([$nom, $prenom, $telephone, $whatsapp, $ville, $hash, $userId]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET nom = ?, prenom = ?, telephone = ?, whatsapp = ?, ville = ? WHERE id = ?");
                $stmt->execute([$nom, $prenom, $telephone, $whatsapp, $ville, $userId]);
            }

            $_SESSION['user']['nom'] = $nom;
            $_SESSION['user']['prenom'] = $prenom;
            $_SESSION['user']['telephone'] = $telephone;
            $_SESSION['user']['whatsapp'] = $whatsapp;
            $_SESSION['user']['ville'] = $ville;

            setFlash('success', "Vos informations de profil ont été mises à jour avec succès.");
        }
        header('Location: ' . url('compte/index.php#profil'));
        exit;
    }
}

// Récupération des annonces de l'utilisateur
$stmtMyVeh = $pdo->prepare("SELECT v.*, 
    (SELECT photo_url FROM vehicle_photos WHERE vehicle_id = v.id ORDER BY is_primary DESC, id ASC LIMIT 1) as photo_principale
    FROM vehicles v
    WHERE v.user_id = ?
    ORDER BY v.created_at DESC");
$stmtMyVeh->execute([$userId]);
$myVehicles = $stmtMyVeh->fetchAll();

// Récupération de mes achats (demandes envoyées par l'utilisateur)
$stmtPurchases = $pdo->prepare("SELECT p.*, v.titre as vehicule_titre, v.prix as vehicule_prix, v.type_vehicule,
    u.prenom as vendeur_prenom, u.nom as vendeur_nom, u.telephone as vendeur_tel
    FROM purchases p
    JOIN vehicles v ON p.vehicle_id = v.id
    JOIN users u ON p.seller_id = u.id
    WHERE p.buyer_id = ?
    ORDER BY p.created_at DESC");
$stmtPurchases->execute([$userId]);
$myPurchases = $stmtPurchases->fetchAll();

// Récupération de mes locations réservées
$stmtRentals = $pdo->prepare("SELECT r.*, v.titre as vehicule_titre, v.type_vehicule,
    u.prenom as owner_prenom, u.nom as owner_nom, u.telephone as owner_tel
    FROM rentals r
    JOIN vehicles v ON r.vehicle_id = v.id
    JOIN users u ON r.owner_id = u.id
    WHERE r.renter_id = ?
    ORDER BY r.created_at DESC");
$stmtRentals->execute([$userId]);
$myRentals = $stmtRentals->fetchAll();

// Demandes d'achat reçues sur mes véhicules
$stmtReqBuy = $pdo->prepare("SELECT p.*, v.titre as vehicule_titre,
    u.prenom as acheteur_prenom, u.nom as acheteur_nom, u.email as acheteur_email, u.telephone as acheteur_tel
    FROM purchases p
    JOIN vehicles v ON p.vehicle_id = v.id
    JOIN users u ON p.buyer_id = u.id
    WHERE p.seller_id = ?
    ORDER BY p.created_at DESC");
$stmtReqBuy->execute([$userId]);
$receivedBuys = $stmtReqBuy->fetchAll();

// Demandes de location reçues sur mes véhicules
$stmtReqRent = $pdo->prepare("SELECT r.*, v.titre as vehicule_titre,
    u.prenom as locataire_prenom, u.nom as locataire_nom, u.email as locataire_email, u.telephone as locataire_tel
    FROM rentals r
    JOIN vehicles v ON r.vehicle_id = v.id
    JOIN users u ON r.renter_id = u.id
    WHERE r.owner_id = ?
    ORDER BY r.created_at DESC");
$stmtReqRent->execute([$userId]);
$receivedRentals = $stmtReqRent->fetchAll();

$pageTitle = "Mon Compte — Mutuka.com";
require_once __DIR__ . '/../includes/header.php';
?>

<section class="section" style="padding-top: var(--space-5);">
    <div class="dashboard-layout">
        
        <!-- Colonne Gauche : Navigation du Tableau de Bord -->
        <aside class="dashboard-menu">
            <div class="dashboard-user-card">
                <div class="dashboard-avatar">
                    <?php echo strtoupper(substr($currentUser['prenom'], 0, 1) . substr($currentUser['nom'], 0, 1)); ?>
                </div>
                <h3 style="margin-bottom: 2px; font-size: 1.05rem;"><?php echo e($currentUser['prenom']); ?> <?php echo e($currentUser['nom']); ?></h3>
                <span style="font-size: 0.8rem; color: var(--muted);"><?php echo e($currentUser['email']); ?></span>
                <div style="margin-top: 8px;">
                    <span class="spec-pill" style="background: var(--accent-bg); color: var(--accent-dark);">📍 <?php echo e($currentUser['ville'] ?? 'Kinshasa'); ?></span>
                </div>
            </div>

            <nav>
                <div class="tab-link active" data-tab="overview">
                    <span>📊 Vue d'ensemble</span>
                </div>
                <div class="tab-link" data-tab="annonces">
                    <span>🚗 Mes Annonces (<?php echo count($myVehicles); ?>)</span>
                </div>
                <div class="tab-link" data-tab="achats">
                    <span>💳 Mes Achats (<?php echo count($myPurchases); ?>)</span>
                </div>
                <div class="tab-link" data-tab="locations">
                    <span>📅 Mes Locations (<?php echo count($myRentals); ?>)</span>
                </div>
                <div class="tab-link" data-tab="demandes">
                    <span>📥 Demandes Reçues (<?php echo count($receivedBuys) + count($receivedRentals); ?>)</span>
                </div>
                <div class="tab-link" data-tab="profil">
                    <span>⚙️ Mon Profil</span>
                </div>
                <a href="<?php echo url('publier.php'); ?>" class="btn btn-accent btn-sm btn-block" style="margin-top: var(--space-4);">
                    + Publier une annonce
                </a>
                <a href="<?php echo url('deconnexion.php'); ?>" class="btn-logout" style="margin-top: var(--space-2); justify-content: center; width: 100%;">
                    <svg class="logout-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    <span>Se déconnecter</span>
                </a>
            </nav>
        </aside>

        <!-- Colonne Droite : Contenu des Onglets -->
        <main>
            
            <!-- TAB 1 : VUE D'ENSEMBLE -->
            <div class="tab-content active" id="tab-overview">
                <div style="margin-bottom: var(--space-4);">
                    <h2 style="font-size: 1.8rem; margin-bottom: 4px;">Tableau de bord</h2>
                    <p>Bienvenue dans votre espace sécurisé Mutuka.com.</p>
                </div>

                <div class="stat-cards-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($myVehicles); ?></div>
                        <div class="stat-label">Véhicules publiés</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($myPurchases) + count($myRentals); ?></div>
                        <div class="stat-label">Opérations effectuées</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($receivedBuys) + count($receivedRentals); ?></div>
                        <div class="stat-label">Demandes clients reçues</div>
                    </div>
                </div>

                <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: var(--space-5);">
                    <h3 style="margin-bottom: var(--space-3);">Accès rapide</h3>
                    <div style="display: flex; gap: var(--space-3); flex-wrap: wrap;">
                        <a href="<?php echo url('publier.php'); ?>" class="btn btn-accent">+ Publier un nouveau véhicule</a>
                        <a href="<?php echo url('vente.php'); ?>" class="btn btn-outline">Explorer les véhicules en vente</a>
                        <a href="<?php echo url('location.php'); ?>" class="btn btn-outline">Parcourir les locations</a>
                    </div>
                </div>
            </div>

            <!-- TAB 2 : MES ANNONCES -->
            <div class="tab-content" id="tab-annonces">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-4); flex-wrap: wrap; gap: var(--space-2);">
                    <div>
                        <h2 style="font-size: 1.8rem; margin-bottom: 2px;">Mes annonces publiées</h2>
                        <p style="margin: 0;">Gérez la visibilité et le statut de vos véhicules.</p>
                    </div>
                    <a href="<?php echo url('publier.php'); ?>" class="btn btn-accent btn-sm">+ Ajouter une annonce</a>
                </div>

                <?php if (empty($myVehicles)): ?>
                    <div style="text-align: center; padding: var(--space-6); background: var(--surface); border-radius: var(--radius); border: 1px dashed var(--border);">
                        <p style="margin-bottom: var(--space-3);">Vous n'avez pas encore publié de véhicule sur Mutuka.</p>
                        <a href="<?php echo url('publier.php'); ?>" class="btn btn-accent">+ Publier votre premier véhicule</a>
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: var(--space-3);">
                        <?php foreach ($myVehicles as $v): ?>
                            <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: var(--space-4); display: flex; align-items: center; gap: var(--space-4); flex-wrap: wrap;">
                                <div style="width: 110px; height: 80px; border-radius: var(--radius-sm); overflow: hidden; background: #202327; flex-shrink: 0;">
                                    <img src="<?php echo getVehiclePhoto($v['photo_principale'], $v['type_vehicule']); ?>" alt="<?php echo e($v['titre']); ?>" style="width:100%; height:100%; object-fit: cover;" />
                                </div>

                                <div style="flex: 1; min-width: 200px;">
                                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                        <span class="badge-tag badge-<?php echo e($v['type_transaction']); ?>" style="position: static; font-size: 0.68rem; padding: 2px 8px;">
                                            <?php echo ucfirst(e($v['type_transaction'])); ?>
                                        </span>
                                        <h4 style="margin: 0; font-size: 1.05rem;"><?php echo e($v['titre']); ?></h4>
                                    </div>
                                    <div style="font-size: 0.84rem; color: var(--muted);">
                                        <strong><?php echo formatPrice((float)$v['prix']); ?></strong><?php echo ($v['type_transaction'] === 'location') ? '/jour' : ''; ?> • 
                                        <?php echo e($v['ville']); ?> • 👁️ <?php echo (int)$v['vues']; ?> vue(s)
                                    </div>
                                </div>

                                <!-- Sélecteur de statut rapide -->
                                <form action="<?php echo url('compte/index.php'); ?>" method="post" style="display: flex; align-items: center; gap: 6px;">
                                    <input type="hidden" name="action" value="update_vehicle_status" />
                                    <input type="hidden" name="vehicle_id" value="<?php echo $v['id']; ?>" />
                                    <select name="statut" onchange="this.form.submit()" style="padding: 6px 10px; font-size: 0.84rem;">
                                        <option value="disponible" <?php echo ($v['statut'] === 'disponible') ? 'selected' : ''; ?>>Disponible</option>
                                        <option value="reserve" <?php echo ($v['statut'] === 'reserve') ? 'selected' : ''; ?>>Réservé</option>
                                        <option value="vendu" <?php echo ($v['statut'] === 'vendu') ? 'selected' : ''; ?>>Vendu</option>
                                        <option value="loue" <?php echo ($v['statut'] === 'loue') ? 'selected' : ''; ?>>Loué</option>
                                    </select>
                                </form>

                                <div style="display: flex; gap: 6px;">
                                    <a href="<?php echo url("details.php?id={$v['id']}"); ?>" class="btn btn-outline btn-sm">Voir</a>
                                    <form action="<?php echo url('compte/index.php'); ?>" method="post" onsubmit="return confirm('Confirmez-vous la suppression de cette annonce ?');">
                                        <input type="hidden" name="action" value="delete_vehicle" />
                                        <input type="hidden" name="vehicle_id" value="<?php echo $v['id']; ?>" />
                                        <button type="submit" class="btn btn-danger-outline btn-sm">Supprimer</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- TAB 3 : MES ACHATS -->
            <div class="tab-content" id="tab-achats">
                <div style="margin-bottom: var(--space-4);">
                    <h2 style="font-size: 1.8rem; margin-bottom: 2px;">Mes demandes d'achat</h2>
                    <p style="margin: 0;">Historique de vos offres et achats de véhicules.</p>
                </div>

                <?php if (empty($myPurchases)): ?>
                    <div style="text-align: center; padding: var(--space-6); background: var(--surface); border-radius: var(--radius); border: 1px dashed var(--border);">
                        <p style="margin-bottom: var(--space-3);">Vous n'avez formulé aucune demande d'achat pour le moment.</p>
                        <a href="<?php echo url('vente.php'); ?>" class="btn btn-dark">Découvrir les véhicules en vente</a>
                    </div>
                <?php else: ?>
                    <div class="data-table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Véhicule</th>
                                    <th>Vendeur</th>
                                    <th>Montant</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($myPurchases as $p): ?>
                                    <tr>
                                        <td><strong><?php echo e($p['vehicule_titre']); ?></strong></td>
                                        <td><?php echo e($p['vendeur_prenom']); ?> <?php echo e($p['vendeur_nom']); ?><br><small><?php echo e($p['vendeur_tel']); ?></small></td>
                                        <td><strong><?php echo formatPrice((float)$p['montant']); ?></strong></td>
                                        <td><?php echo formatDateFR($p['created_at']); ?></td>
                                        <td>
                                            <span class="badge-status badge-status-<?php echo ($p['statut'] === 'confirme' || $p['statut'] === 'finalise') ? 'disponible' : ($p['statut'] === 'annule' ? 'vendu' : 'loue'); ?>" style="position: static;">
                                                <?php echo ucfirst(str_replace('_', ' ', e($p['statut']))); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?php echo url("details.php?id={$p['vehicle_id']}"); ?>" class="btn btn-outline btn-sm">Fiche</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- TAB 4 : MES LOCATIONS -->
            <div class="tab-content" id="tab-locations">
                <div style="margin-bottom: var(--space-4);">
                    <h2 style="font-size: 1.8rem; margin-bottom: 2px;">Mes réservations de location</h2>
                    <p style="margin: 0;">Détails de vos locations de véhicules.</p>
                </div>

                <?php if (empty($myRentals)): ?>
                    <div style="text-align: center; padding: var(--space-6); background: var(--surface); border-radius: var(--radius); border: 1px dashed var(--border);">
                        <p style="margin-bottom: var(--space-3);">Aucune location réservée pour le moment.</p>
                        <a href="<?php echo url('location.php'); ?>" class="btn btn-accent">Parcourir les locations disponibles</a>
                    </div>
                <?php else: ?>
                    <div class="data-table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Véhicule</th>
                                    <th>Propriétaire</th>
                                    <th>Période</th>
                                    <th>Durée</th>
                                    <th>Total</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($myRentals as $r): ?>
                                    <tr>
                                        <td><strong><?php echo e($r['vehicule_titre']); ?></strong></td>
                                        <td><?php echo e($r['owner_prenom']); ?> <?php echo e($r['owner_nom']); ?><br><small><?php echo e($r['owner_tel']); ?></small></td>
                                        <td>Du <?php echo formatDateFR($r['date_debut']); ?><br>au <?php echo formatDateFR($r['date_fin']); ?></td>
                                        <td><?php echo (int)$r['nb_jours']; ?> jour(s)</td>
                                        <td><strong><?php echo formatPrice((float)$r['montant_total']); ?></strong></td>
                                        <td>
                                            <span class="badge-status badge-status-<?php echo ($r['statut'] === 'validee' || $r['statut'] === 'terminee') ? 'disponible' : ($r['statut'] === 'annulee' ? 'vendu' : 'loue'); ?>" style="position: static;">
                                                <?php echo ucfirst(str_replace('_', ' ', e($r['statut']))); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- TAB 5 : DEMANDES REÇUES -->
            <div class="tab-content" id="tab-demandes">
                <div style="margin-bottom: var(--space-4);">
                    <h2 style="font-size: 1.8rem; margin-bottom: 2px;">Demandes clients reçues</h2>
                    <p style="margin: 0;">Validez ou gérez les demandes d'achat et de location de vos véhicules.</p>
                </div>

                <h3 style="margin-top: var(--space-4);">Demandes d'achat reçues</h3>
                <?php if (empty($receivedBuys)): ?>
                    <p style="font-size: 0.9rem;">Aucune demande d'achat reçue pour le moment.</p>
                <?php else: ?>
                    <div class="data-table-wrap" style="margin-bottom: var(--space-5);">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Véhicule</th>
                                    <th>Acheteur</th>
                                    <th>Montant proposé</th>
                                    <th>Message / Contact</th>
                                    <th>Statut</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($receivedBuys as $rb): ?>
                                    <tr>
                                        <td><strong><?php echo e($rb['vehicule_titre']); ?></strong></td>
                                        <td><?php echo e($rb['acheteur_prenom']); ?> <?php echo e($rb['acheteur_nom']); ?><br><small>📞 <?php echo e($rb['telephone_contact']); ?></small></td>
                                        <td><strong><?php echo formatPrice((float)$rb['montant']); ?></strong></td>
                                        <td><?php echo !empty($rb['message']) ? e($rb['message']) : '<em>Aucun message</em>'; ?></td>
                                        <td>
                                            <form action="<?php echo url('compte/index.php'); ?>" method="post">
                                                <input type="hidden" name="action" value="update_purchase_status" />
                                                <input type="hidden" name="purchase_id" value="<?php echo $rb['id']; ?>" />
                                                <select name="statut" onchange="this.form.submit()" style="padding: 4px 8px; font-size: 0.82rem;">
                                                    <option value="en_attente" <?php echo ($rb['statut'] === 'en_attente') ? 'selected' : ''; ?>>En attente</option>
                                                    <option value="confirme" <?php echo ($rb['statut'] === 'confirme') ? 'selected' : ''; ?>>Confirmé</option>
                                                    <option value="finalise" <?php echo ($rb['statut'] === 'finalise') ? 'selected' : ''; ?>>Finalisé</option>
                                                    <option value="annule" <?php echo ($rb['statut'] === 'annule') ? 'selected' : ''; ?>>Annulé</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td>
                                            <a href="tel:<?php echo e($rb['telephone_contact']); ?>" class="btn btn-outline btn-sm">Appeler</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <h3 style="margin-top: var(--space-5);">Demandes de location reçues</h3>
                <?php if (empty($receivedRentals)): ?>
                    <p style="font-size: 0.9rem;">Aucune demande de location reçue pour le moment.</p>
                <?php else: ?>
                    <div class="data-table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Véhicule</th>
                                    <th>Locataire</th>
                                    <th>Dates</th>
                                    <th>Total</th>
                                    <th>Statut</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($receivedRentals as $rr): ?>
                                    <tr>
                                        <td><strong><?php echo e($rr['vehicule_titre']); ?></strong></td>
                                        <td><?php echo e($rr['locataire_prenom']); ?> <?php echo e($rr['locataire_nom']); ?><br><small>📞 <?php echo e($rr['telephone_contact']); ?></small></td>
                                        <td>Du <?php echo formatDateFR($rr['date_debut']); ?><br>au <?php echo formatDateFR($rr['date_fin']); ?> (<?php echo (int)$rr['nb_jours']; ?>j)</td>
                                        <td><strong><?php echo formatPrice((float)$rr['montant_total']); ?></strong></td>
                                        <td>
                                            <form action="<?php echo url('compte/index.php'); ?>" method="post">
                                                <input type="hidden" name="action" value="update_rental_status" />
                                                <input type="hidden" name="rental_id" value="<?php echo $rr['id']; ?>" />
                                                <select name="statut" onchange="this.form.submit()" style="padding: 4px 8px; font-size: 0.82rem;">
                                                    <option value="en_attente" <?php echo ($rr['statut'] === 'en_attente') ? 'selected' : ''; ?>>En attente</option>
                                                    <option value="validee" <?php echo ($rr['statut'] === 'validee') ? 'selected' : ''; ?>>Validée</option>
                                                    <option value="terminee" <?php echo ($rr['statut'] === 'terminee') ? 'selected' : ''; ?>>Terminée</option>
                                                    <option value="annulee" <?php echo ($rr['statut'] === 'annulee') ? 'selected' : ''; ?>>Annulée</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td>
                                            <a href="tel:<?php echo e($rr['telephone_contact']); ?>" class="btn btn-outline btn-sm">Appeler</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- TAB 6 : PROFIL -->
            <div class="tab-content" id="tab-profil">
                <div style="margin-bottom: var(--space-4);">
                    <h2 style="font-size: 1.8rem; margin-bottom: 2px;">Modifier mon profil</h2>
                    <p style="margin: 0;">Mettez à jour vos coordonnées de contact et votre mot de passe.</p>
                </div>

                <div class="form-card" style="margin: 0; max-width: 100%;">
                    <form action="<?php echo url('compte/index.php'); ?>" method="post">
                        <input type="hidden" name="action" value="update_profile" />

                        <div class="form-row">
                            <div class="form-group">
                                <label for="prenom">Prénom</label>
                                <input type="text" id="prenom" name="prenom" value="<?php echo e($currentUser['prenom']); ?>" required />
                            </div>
                            <div class="form-group">
                                <label for="nom">Nom</label>
                                <input type="text" id="nom" name="nom" value="<?php echo e($currentUser['nom']); ?>" required />
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="telephone">Téléphone d'appel</label>
                                <input type="tel" id="telephone" name="telephone" value="<?php echo e($currentUser['telephone']); ?>" required />
                            </div>
                            <div class="form-group">
                                <label for="whatsapp">Numéro WhatsApp</label>
                                <input type="tel" id="whatsapp" name="whatsapp" value="<?php echo e($currentUser['whatsapp'] ?? $currentUser['telephone']); ?>" />
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="ville">Ville</label>
                            <select id="ville" name="ville">
                                <option value="Kinshasa" <?php echo (($currentUser['ville'] ?? '') === 'Kinshasa') ? 'selected' : ''; ?>>Kinshasa</option>
                                <option value="Lubumbashi" <?php echo (($currentUser['ville'] ?? '') === 'Lubumbashi') ? 'selected' : ''; ?>>Lubumbashi</option>
                                <option value="Goma" <?php echo (($currentUser['ville'] ?? '') === 'Goma') ? 'selected' : ''; ?>>Goma</option>
                                <option value="Kolwezi" <?php echo (($currentUser['ville'] ?? '') === 'Kolwezi') ? 'selected' : ''; ?>>Kolwezi</option>
                                <option value="Matadi" <?php echo (($currentUser['ville'] ?? '') === 'Matadi') ? 'selected' : ''; ?>>Matadi</option>
                            </select>
                        </div>

                        <div class="form-group" style="margin-top: var(--space-4); border-top: 1px solid var(--border); padding-top: var(--space-4);">
                            <label for="new_password">Nouveau mot de passe (laisser vide pour ne pas modifier)</label>
                            <input type="password" id="new_password" name="new_password" placeholder="••••••••" />
                        </div>

                        <button type="submit" class="btn btn-accent btn-lg" style="margin-top: var(--space-3);">
                            Enregistrer les modifications
                        </button>
                    </form>
                </div>
            </div>

        </main>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
