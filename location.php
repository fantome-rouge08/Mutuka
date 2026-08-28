<?php
/**
 * location.php
 * Catalogue exclusif des véhicules en location sur Mutuka.com.
 */
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/database.php';

// Filtres
$q = trim($_GET['q'] ?? '');
$typeVehicule = trim($_GET['type_vehicule'] ?? '');
$carburant = trim($_GET['carburant'] ?? '');
$boite = trim($_GET['boite_vitesse'] ?? '');
$ville = trim($_GET['ville'] ?? '');
$budgetMax = (float)($_GET['budget_max'] ?? 0);
$tri = trim($_GET['tri'] ?? 'recent');

// Construction de la requête SQL
$where = ["v.type_transaction = 'location'", "v.statut != 'vendu'"];
$params = [];

if (!empty($q)) {
    $where[] = "(v.titre LIKE ? OR v.marque LIKE ? OR v.modele LIKE ? OR v.description LIKE ?)";
    $qParam = "%{$q}%";
    $params[] = $qParam;
    $params[] = $qParam;
    $params[] = $qParam;
    $params[] = $qParam;
}

if (!empty($typeVehicule)) {
    $where[] = "v.type_vehicule = ?";
    $params[] = $typeVehicule;
}

if (!empty($carburant)) {
    $where[] = "v.carburant = ?";
    $params[] = $carburant;
}

if (!empty($boite)) {
    $where[] = "v.boite_vitesse = ?";
    $params[] = $boite;
}

if (!empty($ville)) {
    $where[] = "v.ville = ?";
    $params[] = $ville;
}

if ($budgetMax > 0) {
    $where[] = "v.prix <= ?";
    $params[] = $budgetMax;
}

$orderBy = "v.created_at DESC";
if ($tri === 'prix_asc') {
    $orderBy = "v.prix ASC";
} elseif ($tri === 'prix_desc') {
    $orderBy = "v.prix DESC";
}

$sql = "SELECT v.*, u.nom as loueur_nom, u.prenom as loueur_prenom, u.telephone as loueur_tel,
               (SELECT photo_url FROM vehicle_photos WHERE vehicle_id = v.id ORDER BY is_primary DESC, id ASC LIMIT 1) as photo_principale
        FROM vehicles v
        JOIN users u ON v.user_id = u.id
        WHERE " . implode(" AND ", $where) . "
        ORDER BY {$orderBy}";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vehicles = $stmt->fetchAll();

$pageTitle = "Véhicules en location — Mutuka.com";
require_once __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-top: var(--space-5);">
    <div class="catalog-header">
        <div>
            <p class="eyebrow">Location Automobile</p>
            <h1 style="font-size: 2rem; margin-bottom: 4px;">Véhicules disponibles à la location</h1>
            <p class="results-count"><?php echo count($vehicles); ?> véhicule(s) disponible(s) à la location</p>
        </div>
        <div>
            <a href="<?php echo url('publier.php'); ?>" class="btn btn-accent btn-sm">+ Mettre en location</a>
        </div>
    </div>

    <!-- Panneau de Filtres -->
    <div class="filters-panel">
        <form action="<?php echo url('location.php'); ?>" method="get" class="filters-grid">
            <div class="filter-group">
                <label for="q">Modèle / Marque</label>
                <input type="text" id="q" name="q" value="<?php echo e($q); ?>" placeholder="Ex: Hilux, Tucson, Jimny..." />
            </div>

            <div class="filter-group">
                <label for="type_vehicule">Type d'usage</label>
                <select id="type_vehicule" name="type_vehicule">
                    <option value="">Tous les types</option>
                    <option value="suv" <?php echo ($typeVehicule === 'suv') ? 'selected' : ''; ?>>SUV / 4x4 Tout-terrain</option>
                    <option value="utilitaire" <?php echo ($typeVehicule === 'utilitaire') ? 'selected' : ''; ?>>Pick-up & Utilitaire</option>
                    <option value="voiture" <?php echo ($typeVehicule === 'voiture') ? 'selected' : ''; ?>>Berline & Citadine</option>
                    <option value="camion" <?php echo ($typeVehicule === 'camion') ? 'selected' : ''; ?>>Bus / Camion</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="budget_max">Budget max ($/jour)</label>
                <input type="number" id="budget_max" name="budget_max" value="<?php echo $budgetMax > 0 ? e((string)$budgetMax) : ''; ?>" placeholder="Ex: 100" />
            </div>

            <div class="filter-group">
                <label for="ville">Ville</label>
                <select id="ville" name="ville">
                    <option value="">Toutes les villes</option>
                    <option value="Kinshasa" <?php echo ($ville === 'Kinshasa') ? 'selected' : ''; ?>>Kinshasa</option>
                    <option value="Lubumbashi" <?php echo ($ville === 'Lubumbashi') ? 'selected' : ''; ?>>Lubumbashi</option>
                    <option value="Goma" <?php echo ($ville === 'Goma') ? 'selected' : ''; ?>>Goma</option>
                    <option value="Kolwezi" <?php echo ($ville === 'Kolwezi') ? 'selected' : ''; ?>>Kolwezi</option>
                    <option value="Matadi" <?php echo ($ville === 'Matadi') ? 'selected' : ''; ?>>Matadi</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="boite_vitesse">Boîte de vitesses</label>
                <select id="boite_vitesse" name="boite_vitesse">
                    <option value="">Toutes</option>
                    <option value="automatique" <?php echo ($boite === 'automatique') ? 'selected' : ''; ?>>Automatique</option>
                    <option value="manuelle" <?php echo ($boite === 'manuelle') ? 'selected' : ''; ?>>Manuelle</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="tri">Trier par</label>
                <select id="tri" name="tri">
                    <option value="recent" <?php echo ($tri === 'recent') ? 'selected' : ''; ?>>Plus récents</option>
                    <option value="prix_asc" <?php echo ($tri === 'prix_asc') ? 'selected' : ''; ?>>Prix/jour croissant</option>
                    <option value="prix_desc" <?php echo ($tri === 'prix_desc') ? 'selected' : ''; ?>>Prix/jour décroissant</option>
                </select>
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-dark" style="flex: 1;">Filtrer</button>
                <a href="<?php echo url('location.php'); ?>" class="btn btn-outline" title="Réinitialiser">↻</a>
            </div>
        </form>
    </div>

    <!-- Grille des annonces de location -->
    <?php if (empty($vehicles)): ?>
        <div style="text-align: center; padding: var(--space-7) var(--space-4); background: var(--surface); border-radius: var(--radius); border: 1px dashed var(--border);">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto var(--space-3); color: var(--muted);"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <h3>Aucun véhicule en location ne correspond à votre recherche</h3>
            <p>Essayez de modifier votre recherche ou votre budget journalier.</p>
            <a href="<?php echo url('location.php'); ?>" class="btn btn-outline">Réinitialiser les filtres</a>
        </div>
    <?php else: ?>
        <div class="vehicles-grid">
            <?php foreach ($vehicles as $veh): ?>
                <div class="car-card">
                    <div class="car-card-image">
                        <span class="badge-tag badge-location">Location</span>
                        <span class="badge-status badge-status-<?php echo e($veh['statut']); ?>">
                            <?php echo ($veh['statut'] === 'disponible') ? 'Libre' : ucfirst(e($veh['statut'])); ?>
                        </span>
                        <img src="<?php echo getVehiclePhoto($veh['photo_principale'], $veh['type_vehicule'], $veh['titre']); ?>" alt="<?php echo e($veh['titre']); ?>" loading="lazy" />
                    </div>

                    <div class="car-card-body">
                        <h3 class="car-card-title"><?php echo e($veh['titre']); ?></h3>
                        
                        <div class="car-card-location">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span><?php echo e($veh['ville']); ?><?php echo !empty($veh['adresse']) ? ' • ' . e($veh['adresse']) : ''; ?></span>
                        </div>

                        <div class="car-card-specs">
                            <span class="spec-pill"><?php echo e((string)$veh['annee']); ?></span>
                            <span class="spec-pill"><?php echo ucfirst(e($veh['carburant'])); ?></span>
                            <span class="spec-pill"><?php echo ucfirst(e($veh['boite_vitesse'])); ?></span>
                            <?php if ($veh['caution'] > 0): ?>
                                <span class="spec-pill" style="color: var(--accent-dark); background: var(--accent-bg);">Caution: <?php echo formatPrice((float)$veh['caution']); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="car-card-footer">
                            <div class="car-price">
                                <span class="price-value"><?php echo formatPrice((float)$veh['prix']); ?></span>
                                <span class="price-unit">par jour</span>
                            </div>
                            <a href="<?php echo url('details.php?id=' . $veh['id']); ?>" class="btn btn-outline btn-sm">
                                Louer ce véhicule →
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
