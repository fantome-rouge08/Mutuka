<?php
/**
 * details.php
 * Fiche détaillée d'un véhicule (Vente ou Location) :
 * Galerie photos, caractéristiques, contact direct WhatsApp/Appel,
 * formulaire d'achat immédiat et simulateur de location avec réservation.
 */
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/database.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    setFlash('error', 'Véhicule introuvable.');
    header('Location: ' . url('index.php'));
    exit;
}

// Récupération du véhicule et de son propriétaire
$stmt = $pdo->prepare("SELECT v.*, u.id as owner_id, u.nom as owner_nom, u.prenom as owner_prenom, u.telephone as owner_tel, u.whatsapp as owner_whatsapp, u.email as owner_email, u.ville as owner_ville
    FROM vehicles v
    JOIN users u ON v.user_id = u.id
    WHERE v.id = ?
    LIMIT 1");
$stmt->execute([$id]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    setFlash('error', 'Ce véhicule n\'existe plus ou a été retiré.');
    header('Location: ' . url('index.php'));
    exit;
}

// Incrémenter les vues
$pdo->prepare("UPDATE vehicles SET vues = vues + 1 WHERE id = ?")->execute([$id]);

// Récupération des photos
$stmtPhotos = $pdo->prepare("SELECT * FROM vehicle_photos WHERE vehicle_id = ? ORDER BY is_primary DESC, id ASC");
$stmtPhotos->execute([$id]);
$photos = $stmtPhotos->fetchAll();

$currentUser = getCurrentUser();
$isOwner = ($currentUser && $currentUser['id'] == $vehicle['owner_id']);

// Traitement des requêtes POST (Achat / Réservation de location)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireAuth("details.php?id={$id}");
    $action = $_POST['action'] ?? '';

    if ($action === 'achat') {
        $message = trim($_POST['message'] ?? '');
        $telephone = trim($_POST['telephone'] ?? $currentUser['telephone']);

        $stmtBuy = $pdo->prepare("INSERT INTO purchases (vehicle_id, buyer_id, seller_id, montant, message, telephone_contact, statut) VALUES (?, ?, ?, ?, ?, ?, 'en_attente')");
        $stmtBuy->execute([
            $vehicle['id'],
            $currentUser['id'],
            $vehicle['owner_id'],
            $vehicle['prix'],
            $message,
            $telephone
        ]);

        setFlash('success', "Votre demande d'achat pour « {$vehicle['titre']} » a été envoyée au vendeur avec succès ! Vous pouvez suivre son statut dans votre espace compte.");
        header('Location: ' . url('compte/index.php#achats'));
        exit;
    }

    if ($action === 'location') {
        $dateDebut = $_POST['date_debut'] ?? '';
        $dateFin = $_POST['date_fin'] ?? '';
        $message = trim($_POST['message'] ?? '');
        $telephone = trim($_POST['telephone'] ?? $currentUser['telephone']);

        if (empty($dateDebut) || empty($dateFin) || $dateFin < $dateDebut) {
            setFlash('error', 'Veuillez sélectionner une période de location valide.');
        } else {
            $d1 = new DateTime($dateDebut);
            $d2 = new DateTime($dateFin);
            $nbJours = $d1->diff($d2)->days + 1;
            $montantTotal = $nbJours * (float)$vehicle['prix'];

            $stmtRent = $pdo->prepare("INSERT INTO rentals 
                (vehicle_id, renter_id, owner_id, date_debut, date_fin, nb_jours, prix_par_jour, montant_total, caution, message, telephone_contact, statut)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'en_attente')");
            
            $stmtRent->execute([
                $vehicle['id'],
                $currentUser['id'],
                $vehicle['owner_id'],
                $dateDebut,
                $dateFin,
                $nbJours,
                $vehicle['prix'],
                $montantTotal,
                $vehicle['caution'],
                $message,
                $telephone
            ]);

            setFlash('success', "Votre réservation pour {$nbJours} jour(s) ({$montantTotal} $) a été enregistrée avec succès ! Le propriétaire va prendre contact avec vous.");
            header('Location: ' . url('compte/index.php#locations'));
            exit;
        }
    }
}

// Formatage du numéro WhatsApp pour le lien direct
$rawWhatsapp = preg_replace('/[^0-9]/', '', $vehicle['owner_whatsapp'] ?: $vehicle['owner_tel']);
$waMessage = urlencode("Bonjour {$vehicle['owner_prenom']}, je vous contacte à propos de votre annonce « {$vehicle['titre']} » (" . formatPrice((float)$vehicle['prix']) . ($vehicle['type_transaction'] === 'location' ? '/jour' : '') . ") sur Mutuka.com.");
$waUrl = "https://wa.me/{$rawWhatsapp}?text={$waMessage}";

$pageTitle = "{$vehicle['titre']} — Mutuka.com";
require_once __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-top: var(--space-5);">
    <div style="margin-bottom: var(--space-4);">
        <a href="<?php echo ($vehicle['type_transaction'] === 'vente') ? url('vente.php') : url('location.php'); ?>" class="nav-link-subtle" style="font-size: 0.9rem;">
            ← Retour aux annonces de <?php echo e($vehicle['type_transaction']); ?>
        </a>
    </div>

    <div class="details-layout">
        <!-- Colonne Gauche : Galerie & Informations détaillées -->
        <div>
            <!-- Galerie photo -->
            <div class="details-gallery">
                <div class="main-image-wrap">
                    <?php 
                    $firstPhoto = !empty($photos) ? $photos[0]['photo_url'] : null;
                    $mainPhotoUrl = getVehiclePhoto($firstPhoto, $vehicle['type_vehicule'], $vehicle['titre']);
                    ?>
                    <img id="main-detail-img" src="<?php echo $mainPhotoUrl; ?>" alt="<?php echo e($vehicle['titre']); ?>" />
                </div>

                <?php if (count($photos) > 1): ?>
                    <div class="gallery-thumbs">
                        <?php foreach ($photos as $idx => $p): ?>
                            <div class="thumb-item <?php echo ($idx === 0) ? 'active' : ''; ?>" data-src="<?php echo getVehiclePhoto($p['photo_url'], $vehicle['type_vehicule'], $vehicle['titre']); ?>">
                                <img src="<?php echo getVehiclePhoto($p['photo_url'], $vehicle['type_vehicule'], $vehicle['titre']); ?>" alt="Vignette" />
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Titre et En-tête de l'annonce -->
            <div style="margin-top: var(--space-5);">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                    <span class="badge-tag badge-<?php echo e($vehicle['type_transaction']); ?>" style="position: static;">
                        <?php echo ucfirst(e($vehicle['type_transaction'])); ?>
                    </span>
                    <span class="badge-status badge-status-<?php echo e($vehicle['statut']); ?>" style="position: static;">
                        ● <?php echo ucfirst(e($vehicle['statut'])); ?>
                    </span>
                    <span style="font-size: 0.82rem; color: var(--muted); margin-left: auto;">
                        👁️ <?php echo (int)$vehicle['vues']; ?> consultation(s)
                    </span>
                </div>

                <h1 style="font-size: 2.2rem; margin-bottom: 8px;"><?php echo e($vehicle['titre']); ?></h1>
                
                <p style="font-size: 0.95rem; color: var(--muted); display: flex; align-items: center; gap: 6px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <strong><?php echo e($vehicle['ville']); ?></strong>
                    <?php if (!empty($vehicle['adresse'])): ?>
                        <span>— <?php echo e($vehicle['adresse']); ?></span>
                    <?php endif; ?>
                    <span>• Publiée le <?php echo formatDateFR($vehicle['created_at']); ?></span>
                </p>
            </div>

            <!-- Spécifications techniques -->
            <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: var(--space-5); margin-top: var(--space-5);">
                <h3 style="margin-bottom: var(--space-3);">Fiche technique & Caractéristiques</h3>
                <table class="specs-table">
                    <tbody>
                        <tr>
                            <td>Marque & Modèle</td>
                            <td><?php echo e($vehicle['marque']); ?> <?php echo e($vehicle['modele']); ?></td>
                        </tr>
                        <tr>
                            <td>Année de fabrication</td>
                            <td><?php echo e((string)$vehicle['annee']); ?></td>
                        </tr>
                        <tr>
                            <td>Kilométrage certifié</td>
                            <td><?php echo number_format($vehicle['kilometrage'], 0, ',', ' '); ?> km</td>
                        </tr>
                        <tr>
                            <td>Carburation / Énergie</td>
                            <td><?php echo ucfirst(e($vehicle['carburant'])); ?></td>
                        </tr>
                        <tr>
                            <td>Boîte de vitesses</td>
                            <td><?php echo ucfirst(e($vehicle['boite_vitesse'])); ?></td>
                        </tr>
                        <tr>
                            <td>Type de carrosserie</td>
                            <td><?php echo ucfirst(e($vehicle['type_vehicule'])); ?></td>
                        </tr>
                        <tr>
                            <td>Couleur carrosserie</td>
                            <td><?php echo !empty($vehicle['couleur']) ? e($vehicle['couleur']) : 'Non spécifiée'; ?></td>
                        </tr>
                        <tr>
                            <td>Nombre de places</td>
                            <td><?php echo (int)$vehicle['nombre_places']; ?> places</td>
                        </tr>
                        <?php if ($vehicle['type_transaction'] === 'location' && $vehicle['caution'] > 0): ?>
                            <tr>
                                <td>Caution requise</td>
                                <td style="color: var(--accent-dark);"><?php echo formatPrice((float)$vehicle['caution']); ?> (restituable)</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Description -->
            <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: var(--space-5); margin-top: var(--space-4);">
                <h3 style="margin-bottom: var(--space-3);">Description du véhicule</h3>
                <div style="line-height: 1.7; color: var(--ink); white-space: pre-line;">
                    <?php echo !empty($vehicle['description']) ? e($vehicle['description']) : '<em>Aucune description complémentaire fournie par le propriétaire.</em>'; ?>
                </div>
            </div>
        </div>

        <!-- Colonne Droite : Bloc de contact, Prix & Actions rapides -->
        <div class="details-sidebar">
            
            <div class="action-card">
                <div class="action-card-price">
                    <span style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted); font-weight: 600;">
                        <?php echo ($vehicle['type_transaction'] === 'vente') ? 'Prix de vente' : 'Tarif de location'; ?>
                    </span>
                    <div class="price-big">
                        <?php echo formatPrice((float)$vehicle['prix']); ?>
                        <?php if ($vehicle['type_transaction'] === 'location'): ?>
                            <span style="font-size: 0.9rem; font-family: var(--font-body); font-weight: 400; color: var(--muted);"> / jour</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Informations Vendeur / Loueur -->
                <div class="seller-info">
                    <div class="seller-avatar">
                        <?php echo strtoupper(substr($vehicle['owner_prenom'], 0, 1) . substr($vehicle['owner_nom'], 0, 1)); ?>
                    </div>
                    <div>
                        <div style="font-weight: 600; font-size: 1rem; color: var(--ink);">
                            <?php echo e($vehicle['owner_prenom']); ?> <?php echo e($vehicle['owner_nom']); ?>
                        </div>
                        <div style="font-size: 0.82rem; color: var(--muted);">
                            📍 <?php echo e($vehicle['owner_ville']); ?> • Membre certifié
                        </div>
                    </div>
                </div>

                <!-- Boutons de contact direct -->
                <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: var(--space-4);">
                    <a href="<?php echo $waUrl; ?>" target="_blank" rel="noopener" class="btn btn-whatsapp btn-block">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.664-.699c.97.545 1.769.814 2.796.814 3.181 0 5.767-2.586 5.767-5.766.001-3.18-2.585-5.766-5.767-5.766zm9.969 5.766c0 5.514-4.486 10-10 10-1.803 0-3.491-.482-4.949-1.325l-5.051 1.325 1.357-4.939c-.917-1.503-1.442-3.266-1.442-5.061 0-5.514 4.486-10 10-10s10 4.486 10 10z"/></svg>
                        Discuter sur WhatsApp
                    </a>

                    <a href="tel:<?php echo e($vehicle['owner_tel']); ?>" class="btn btn-outline btn-block">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        Appeler (<?php echo e($vehicle['owner_tel']); ?>)
                    </a>
                </div>

                <!-- Bouton d'action sécurisé (Acheter ou Réserver) -->
                <?php if ($isOwner): ?>
                    <div style="background: var(--surface-alt); padding: var(--space-3); border-radius: var(--radius-sm); text-align: center; font-size: 0.88rem; border: 1px solid var(--border);">
                        👤 <strong>Vous êtes le propriétaire de cette annonce.</strong><br>
                        <a href="<?php echo url('compte/index.php#annonces'); ?>" style="color: var(--accent-dark); font-weight: 600; text-decoration: underline;">Gérer vos annonces</a>
                    </div>
                <?php elseif ($vehicle['statut'] !== 'disponible'): ?>
                    <div style="background: var(--warning-bg); color: var(--warning); padding: var(--space-3); border-radius: var(--radius-sm); text-align: center; font-size: 0.9rem; font-weight: 600;">
                        Ce véhicule est actuellement <?php echo e($vehicle['statut']); ?>.
                    </div>
                <?php elseif ($vehicle['type_transaction'] === 'vente'): ?>
                    <button type="button" class="btn btn-dark btn-lg btn-block" data-open-modal="modal-achat">
                        💳 Demander l'achat du véhicule
                    </button>
                <?php else: ?>
                    <button type="button" class="btn btn-accent btn-lg btn-block" data-open-modal="modal-location">
                        📅 Calculer & Réserver la location
                    </button>
                <?php endif; ?>
            </div>

            <!-- Carte de réassurance -->
            <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: var(--space-4); font-size: 0.88rem;">
                <div style="font-weight: 600; margin-bottom: 8px; color: var(--ink);">🛡️ Engagement Mutuka Confiance</div>
                <ul style="margin: 0; padding-left: 18px; color: var(--muted); line-height: 1.6;">
                    <li>Coordonnées vérifiées du propriétaire</li>
                    <li>Pas de frais cachés sur la plateforme</li>
                    <li>Règlement direct en main propre ou sécurisé</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- MODALE DEMANDE D'ACHAT -->
<div class="modal-backdrop" id="modal-achat">
    <div class="modal-box">
        <div class="modal-head">
            <h3>Demande d'achat</h3>
            <button class="modal-close" data-close-modal>×</button>
        </div>

        <form action="<?php echo url("details.php?id={$vehicle['id']}"); ?>" method="post">
            <input type="hidden" name="action" value="achat" />
            
            <p style="font-size: 0.92rem;">
                Vous êtes sur le point de formuler une offre pour :<br>
                <strong><?php echo e($vehicle['titre']); ?></strong> au prix de <strong><?php echo formatPrice((float)$vehicle['prix']); ?></strong>.
            </p>

            <div class="form-group">
                <label for="buy_phone">Votre numéro de téléphone pour être rappelé <span class="req">*</span></label>
                <input type="tel" id="buy_phone" name="telephone" value="<?php echo e($currentUser['telephone'] ?? ''); ?>" required placeholder="+243 81 000 0000" />
            </div>

            <div class="form-group">
                <label for="buy_message">Message au vendeur (optionnel)</label>
                <textarea id="buy_message" name="message" rows="3" placeholder="Bonjour, je souhaite acquérir votre véhicule. Pouvons-nous convenir d'une visite pour vérifier les papiers et l'état ?"></textarea>
            </div>

            <div style="display: flex; gap: 10px; margin-top: var(--space-4);">
                <button type="button" class="btn btn-outline" data-close-modal style="flex: 1;">Annuler</button>
                <button type="submit" class="btn btn-dark" style="flex: 1.5;">Confirmer la demande</button>
            </div>
        </form>
    </div>
</div>

<!-- MODALE RÉSERVATION DE LOCATION -->
<div class="modal-backdrop" id="modal-location">
    <div class="modal-box">
        <div class="modal-head">
            <h3>Réserver ce véhicule en location</h3>
            <button class="modal-close" data-close-modal>×</button>
        </div>

        <form action="<?php echo url("details.php?id={$vehicle['id']}"); ?>" method="post">
            <input type="hidden" name="action" value="location" />
            <input type="hidden" id="rent_price_per_day" value="<?php echo e((string)$vehicle['prix']); ?>" />

            <div class="form-row">
                <div class="form-group">
                    <label for="rent_date_debut">Date de début <span class="req">*</span></label>
                    <input type="date" id="rent_date_debut" name="date_debut" min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" required />
                </div>
                <div class="form-group">
                    <label for="rent_date_fin">Date de fin <span class="req">*</span></label>
                    <input type="date" id="rent_date_fin" name="date_fin" min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d', strtotime('+2 days')); ?>" required />
                </div>
            </div>

            <!-- Calcul du coût en temps réel -->
            <div style="background: var(--surface-alt); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: var(--space-3); margin: var(--space-3) 0;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 0.9rem;">
                    <span>Tarif par jour :</span>
                    <strong><?php echo formatPrice((float)$vehicle['prix']); ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 0.9rem;">
                    <span>Durée estimée :</span>
                    <strong id="preview_nb_days">3 jours</strong>
                </div>
                <?php if ($vehicle['caution'] > 0): ?>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 0.9rem; color: var(--muted);">
                        <span>Caution (restituable) :</span>
                        <span><?php echo formatPrice((float)$vehicle['caution']); ?></span>
                    </div>
                <?php endif; ?>
                <div style="border-top: 1px solid var(--border); margin-top: 8px; padding-top: 8px; display: flex; justify-content: space-between; font-size: 1.1rem;">
                    <strong>Total estimé :</strong>
                    <strong style="color: var(--accent-dark);" id="preview_total_price">
                        <?php echo formatPrice((float)$vehicle['prix'] * 3); ?>
                    </strong>
                </div>
            </div>

            <div class="form-group">
                <label for="rent_phone">Votre numéro de téléphone <span class="req">*</span></label>
                <input type="tel" id="rent_phone" name="telephone" value="<?php echo e($currentUser['telephone'] ?? ''); ?>" required placeholder="+243 81 000 0000" />
            </div>

            <div class="form-group">
                <label for="rent_message">Précisions ou demandes (avec/sans chauffeur...)</label>
                <textarea id="rent_message" name="message" rows="2" placeholder="Précisez votre itinéraire ou vos besoins particuliers..."></textarea>
            </div>

            <div style="display: flex; gap: 10px; margin-top: var(--space-4);">
                <button type="button" class="btn btn-outline" data-close-modal style="flex: 1;">Annuler</button>
                <button type="submit" class="btn btn-accent" style="flex: 1.5;">Envoyer la réservation</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
