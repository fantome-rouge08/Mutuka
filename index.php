<?php
/**
 * index.php
 * Page d'accueil officielle de Mutuka.com.
 */
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/database.php';

// Récupération de quelques véhicules en vente récents
$stmtVente = $pdo->prepare("SELECT v.*, 
    (SELECT photo_url FROM vehicle_photos WHERE vehicle_id = v.id ORDER BY is_primary DESC, id ASC LIMIT 1) as photo_principale
    FROM vehicles v 
    WHERE v.type_transaction = 'vente' AND v.statut = 'disponible'
    ORDER BY v.created_at DESC LIMIT 3");
$stmtVente->execute();
$featuredVentes = $stmtVente->fetchAll();

// Récupération de quelques véhicules en location récents
$stmtLoc = $pdo->prepare("SELECT v.*, 
    (SELECT photo_url FROM vehicle_photos WHERE vehicle_id = v.id ORDER BY is_primary DESC, id ASC LIMIT 1) as photo_principale
    FROM vehicles v 
    WHERE v.type_transaction = 'location' AND v.statut = 'disponible'
    ORDER BY v.created_at DESC LIMIT 3");
$stmtLoc->execute();
$featuredLocs = $stmtLoc->fetchAll();

$pageTitle = "Mutuka.com — Achetez, vendez et louez votre véhicule en toute confiance";
require_once __DIR__ . '/includes/header.php';
?>

<!-- ============ HERO ============ -->
<section class="hero">
    <div class="hero-inner">
        <div class="hero-text">
            <span class="eyebrow">Mutuka.com</span>
            <h1>Achetez, vendez et louez<br>votre véhicule <em>en toute confiance.</em></h1>
            <p class="hero-sub">Voitures, SUV, pick-ups et motos — une plateforme sobre et rapide où la vente et la location restent toujours distinctes.</p>

            <div class="hero-ctas">
                <a href="<?php echo url('vente.php'); ?>" class="btn btn-dark">Explorer les ventes</a>
                <a href="<?php echo url('location.php'); ?>" class="btn btn-accent">Découvrir la location</a>
            </div>

            <!-- Moteur de recherche rapide -->
            <form class="hero-search" action="<?php echo url('vente.php'); ?>" method="get" id="hero-search-form">
                <select id="hero_type_select" onchange="document.getElementById('hero-search-form').action = this.value;">
                    <option value="<?php echo url('vente.php'); ?>">Vente</option>
                    <option value="<?php echo url('location.php'); ?>">Location</option>
                </select>
                <input type="text" name="q" placeholder="Marque, modèle..." />
                <button type="submit" class="btn btn-dark">Rechercher</button>
            </form>
        </div>

        <div class="hero-visual" aria-hidden="true" style="background: transparent !important; box-shadow: none !important; padding: 0 !important;">
            <span class="hero-badge-overlay">Certifié Mutuka</span>
            <img src="<?php echo url('assets/logo-mutuka.jpg'); ?>" alt="Logo Mutuka" class="hero-logo-img" style="max-width: 100%; max-height: 320px; width: 100%; height: auto; object-fit: cover; border-radius: var(--radius-lg);" />
        </div>
    </div>
</section>

<!-- ============ DEUX PORTES D'ENTRÉE CLAIRES ============ -->
<section class="section gateways">
    <div class="gateway-grid">
        <a href="<?php echo url('vente.php'); ?>" class="gateway-card">
            <div>
                <span class="gateway-index">01</span>
                <h2>Vente de véhicules</h2>
                <p>Voitures, pick-ups, SUV et motos proposés à la vente par des particuliers et des professionnels vérifiés. Contact direct par appel et WhatsApp.</p>
            </div>
            <span class="gateway-link">Voir les annonces de vente →</span>
        </a>
        <a href="<?php echo url('location.php'); ?>" class="gateway-card gateway-card-dark">
            <div>
                <span class="gateway-index">02</span>
                <h2>Location de véhicules</h2>
                <p>Véhicules disponibles à la journée ou pour de longues durées avec estimation en direct du montant total et gestion transparente des cautions.</p>
            </div>
            <span class="gateway-link">Voir les annonces de location →</span>
        </a>
    </div>
</section>

<!-- ============ SECTION VENTE ============ -->
<?php if (!empty($featuredVentes)): ?>
<section class="section">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: var(--space-4); flex-wrap: wrap; gap: var(--space-2);">
        <div>
            <span class="eyebrow">Acheter un véhicule</span>
            <h2 style="margin: 0;">Dernières opportunités à la vente</h2>
        </div>
        <a href="<?php echo url('vente.php'); ?>" class="nav-link font-bold" style="color: var(--accent-dark);">
            Consulter toutes les ventes (→)
        </a>
    </div>

    <div class="vehicles-grid">
        <?php foreach ($featuredVentes as $veh): ?>
            <div class="car-card">
                <div class="car-card-image">
                    <span class="badge-tag badge-vente">Vente</span>
                    <span class="badge-status badge-status-disponible">Disponible</span>
                    <img src="<?php echo getVehiclePhoto($veh['photo_principale'], $veh['type_vehicule'], $veh['titre']); ?>" alt="<?php echo e($veh['titre']); ?>" loading="lazy" />
                </div>
                <div class="car-card-body">
                    <h3 class="car-card-title"><?php echo e($veh['titre']); ?></h3>
                    <div class="car-card-location">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <span><?php echo e($veh['ville']); ?></span>
                    </div>
                    <div class="car-card-specs">
                        <span class="spec-pill"><?php echo e((string)$veh['annee']); ?></span>
                        <span class="spec-pill"><?php echo number_format($veh['kilometrage'], 0, ',', ' '); ?> km</span>
                        <span class="spec-pill"><?php echo ucfirst(e($veh['carburant'])); ?></span>
                        <span class="spec-pill"><?php echo ucfirst(e($veh['boite_vitesse'])); ?></span>
                    </div>
                    <div class="car-card-footer">
                        <div class="car-price">
                            <span class="price-value"><?php echo formatPrice((float)$veh['prix']); ?></span>
                            <span class="price-unit">Prix de vente</span>
                        </div>
                        <a href="<?php echo url('details.php?id=' . $veh['id']); ?>" class="btn btn-outline btn-sm">Détails →</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- ============ SECTION LOCATION ============ -->
<?php if (!empty($featuredLocs)): ?>
<section class="section" style="padding-top: var(--space-4);">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: var(--space-4); flex-wrap: wrap; gap: var(--space-2);">
        <div>
            <span class="eyebrow">Louer un véhicule</span>
            <h2 style="margin: 0;">Disponibles immédiatement à la location</h2>
        </div>
        <a href="<?php echo url('location.php'); ?>" class="nav-link font-bold" style="color: var(--accent-dark);">
            Consulter toutes les locations (→)
        </a>
    </div>

    <div class="vehicles-grid">
        <?php foreach ($featuredLocs as $veh): ?>
            <div class="car-card">
                <div class="car-card-image">
                    <span class="badge-tag badge-location">Location</span>
                    <span class="badge-status badge-status-disponible">Libre</span>
                    <img src="<?php echo getVehiclePhoto($veh['photo_principale'], $veh['type_vehicule'], $veh['titre']); ?>" alt="<?php echo e($veh['titre']); ?>" loading="lazy" />
                </div>
                <div class="car-card-body">
                    <h3 class="car-card-title"><?php echo e($veh['titre']); ?></h3>
                    <div class="car-card-location">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <span><?php echo e($veh['ville']); ?></span>
                    </div>
                    <div class="car-card-specs">
                        <span class="spec-pill"><?php echo e((string)$veh['annee']); ?></span>
                        <span class="spec-pill"><?php echo ucfirst(e($veh['carburant'])); ?></span>
                        <span class="spec-pill"><?php echo ucfirst(e($veh['boite_vitesse'])); ?></span>
                    </div>
                    <div class="car-card-footer">
                        <div class="car-price">
                            <span class="price-value"><?php echo formatPrice((float)$veh['prix']); ?></span>
                            <span class="price-unit">par jour</span>
                        </div>
                        <a href="<?php echo url('details.php?id=' . $veh['id']); ?>" class="btn btn-outline btn-sm">Réserver →</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- ============ POURQUOI MUTUKA.COM ============ -->
<section class="section trust">
    <div class="section-head">
        <span class="eyebrow">Pourquoi Mutuka.com</span>
        <h2>Une plateforme pensée pour la confiance et la clarté</h2>
    </div>
    <div class="trust-grid">
        <div class="trust-item">
            <div class="trust-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3 6 6 1-4.5 4.5L18 20l-6-3-6 3 1.5-6.5L3 9l6-1 3-6z"/></svg>
            </div>
            <h3>Contact direct & sans intermédiaire</h3>
            <p>Discutez en toute liberté avec le propriétaire par téléphone ou WhatsApp direct en un clic.</p>
        </div>
        <div class="trust-item">
            <div class="trust-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-4.35-9.5-9C1 8 2.5 4 6.5 4c2 0 3.5 1.2 4.5 2.6C12 5.2 13.5 4 15.5 4 19.5 4 21 8 21 12c-2.5 4.65-9 9-9 9z"/></svg>
            </div>
            <h3>Vente & location 100% séparées</h3>
            <p>Deux espaces complètement étanches pour trouver exactement ce dont vous avez besoin sans confusion.</p>
        </div>
        <div class="trust-item">
            <div class="trust-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M8 3v3M16 3v3"/></svg>
            </div>
            <h3>Historique & suivi complet</h3>
            <p>Toutes vos opérations (achats, ventes, réservations de location) sont enregistrées et consultables dans votre compte.</p>
        </div>
    </div>
</section>

<!-- ============ BANDEAU CTA ============ -->
<section class="cta-band" style="background: var(--dark);">
    <div class="cta-inner" style="max-width: var(--container); margin: 0 auto; padding: var(--space-7) var(--space-4); text-align: center;">
        <h2 style="color: #fff; font-size: 2.2rem; margin-bottom: var(--space-2);">Un véhicule à vendre ou à louer ?</h2>
        <p style="color: rgba(255,255,255,0.7); max-width: 480px; margin: 0 auto var(--space-5);">Rejoignez Mutuka dès aujourd'hui et publiez votre annonce gratuitement en quelques clics.</p>
        <a href="<?php echo url('publier.php'); ?>" class="btn btn-accent btn-lg">+ Déposer mon annonce gratuitement</a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
