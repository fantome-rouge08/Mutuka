<?php
/**
 * vente.php
 * Catalogue exclusif des véhicules en vente sur Mutuka.com.
 */
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/database.php';

// Filtres
$q = trim($_GET['q'] ?? '');
$marque = trim($_GET['marque'] ?? '');
$typeVehicule = trim($_GET['type_vehicule'] ?? '');
$carburant = trim($_GET['carburant'] ?? '');
$boite = trim($_GET['boite_vitesse'] ?? '');
$ville = trim($_GET['ville'] ?? '');
$prixMin = (float)($_GET['prix_min'] ?? 0);
$prixMax = (float)($_GET['prix_max'] ?? 0);
$anneeMin = (int)($_GET['annee_min'] ?? 0);
$tri = trim($_GET['tri'] ?? 'recent');

// Construction de la requête SQL
$where = ["v.type_transaction = 'vente'", "v.statut != 'vendu'"];
$params = [];

if (!empty($q)) {
    $where[] = "(v.titre LIKE ? OR v.marque LIKE ? OR v.modele LIKE ? OR v.description LIKE ?)";
    $qParam = "%{$q}%";
    $params[] = $qParam;
    $params[] = $qParam;
    $params[] = $qParam;
    $params[] = $qParam;
}

if (!empty($marque)) {
    $where[] = "v.marque LIKE ?";
    $params[] = "%{$marque}%";
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

if ($prixMin > 0) {
    $where[] = "v.prix >= ?";
    $params[] = $prixMin;
}

if ($prixMax > 0) {
    $where[] = "v.prix <= ?";
    $params[] = $prixMax;
}

if ($anneeMin > 0) {
    $where[] = "v.annee >= ?";
    $params[] = $anneeMin;
}

$orderBy = "v.created_at DESC";
if ($tri === 'prix_asc') {
    $orderBy = "v.prix ASC";
} elseif ($tri === 'prix_desc') {
    $orderBy = "v.prix DESC";
} elseif ($tri === 'annee_desc') {
    $orderBy = "v.annee DESC";
}

$sql = "SELECT v.*, u.nom as vendeur_nom, u.prenom as vendeur_prenom, u.telephone as vendeur_tel,
               (SELECT photo_url FROM vehicle_photos WHERE vehicle_id = v.id ORDER BY is_primary DESC, id ASC LIMIT 1) as photo_principale
        FROM vehicles v
        JOIN users u ON v.user_id = u.id
        WHERE " . implode(" AND ", $where) . "
        ORDER BY {$orderBy}";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vehicles = $stmt->fetchAll();

$pageTitle = "Véhicules à vendre — Mutuka.com";
require_once __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-top: var(--space-5);">
    <div class="catalog-header">
        <div>
            <p class="eyebrow">Achat & Vente</p>
            <h1 style="font-size: 2rem; margin-bottom: 4px;">Véhicules disponibles à la vente</h1>
            <p class="results-count"><?php echo count($vehicles); ?> véhicule(s) en vente trouvé(s)</p>
        </div>
        <div>
            <a href="<?php echo url('publier.php'); ?>" class="btn btn-accent btn-sm">+ Vendre mon véhicule</a>
        </div>
    </div>

    <!-- Panneau de Filtres -->
    <div class="filters-panel">
        <form action="<?php echo url('vente.php'); ?>" method="get" class="filters-grid">
            <div class="filter-group">
                <label for="q">Mots-clés / Modèle</label>
                <input type="text" id="q" name="q" value="<?php echo e($q); ?>" placeholder="Ex: Prado, RAV4..." />
            </div>

            <div class="filter-group">
                <label for="type_vehicule">Carrosserie</label>
                <select id="type_vehicule" name="type_vehicule">
                    <option value="">Tous les types</option>
                    <option value="voiture" <?php echo ($typeVehicule === 'voiture') ? 'selected' : ''; ?>>Berline / Citadine</option>
                    <option value="suv" <?php echo ($typeVehicule === 'suv') ? 'selected' : ''; ?>>SUV / 4x4</option>
                    <option value="utilitaire" <?php echo ($typeVehicule === 'utilitaire') ? 'selected' : ''; ?>>Pick-up / Utilitaire</option>
                    <option value="camion" <?php echo ($typeVehicule === 'camion') ? 'selected' : ''; ?>>Camion / Bus</option>
                    <option value="moto" <?php echo ($typeVehicule === 'moto') ? 'selected' : ''; ?>>Moto</option>
                </select>
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
                <label for="carburant">Énergie</label>
                <select id="carburant" name="carburant">
                    <option value="">Tous</option>
                    <option value="essence" <?php echo ($carburant === 'essence') ? 'selected' : ''; ?>>Essence</option>
                    <option value="diesel" <?php echo ($carburant === 'diesel') ? 'selected' : ''; ?>>Diesel</option>
                    <option value="hybride" <?php echo ($carburant === 'hybride') ? 'selected' : ''; ?>>Hybride</option>
                    <option value="electrique" <?php echo ($carburant === 'electrique') ? 'selected' : ''; ?>>Électrique</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="boite_vitesse">Transmission</label>
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
                    <option value="prix_asc" <?php echo ($tri === 'prix_asc') ? 'selected' : ''; ?>>Prix croissant</option>
                    <option value="prix_desc" <?php echo ($tri === 'prix_desc') ? 'selected' : ''; ?>>Prix décroissant</option>
                    <option value="annee_desc" <?php echo ($tri === 'annee_desc') ? 'selected' : ''; ?>>Année la plus récente</option>
                </select>
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-dark" style="flex: 1;">Filtrer</button>
                <a href="<?php echo url('vente.php'); ?>" class="btn btn-outline" title="Réinitialiser">↻</a>
            </div>
        </form>
    </div>

    <!-- Grille des annonces -->
    <?php if (empty($vehicles)): ?>
        <div style="text-align: center; padding: var(--space-7) var(--space-4); background: var(--surface); border-radius: var(--radius); border: 1px dashed var(--border);">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto var(--space-3); color: var(--muted);"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <h3>Aucun véhicule ne correspond à votre recherche</h3>
            <p>Essayez de modifier ou d'élargir vos critères de filtrage pour voir plus de résultats.</p>
            <a href="<?php echo url('vente.php'); ?>" class="btn btn-outline">Réinitialiser les filtres</a>
        </div>
    <?php else: ?>
        <div class="vehicles-grid">
            <?php foreach ($vehicles as $veh): ?>
                <div class="car-card">
                    <div class="car-card-image">
                        <span class="badge-tag badge-vente">Vente</span>
                        <span class="badge-status badge-status-<?php echo e($veh['statut']); ?>">
                            <?php echo ucfirst(e($veh['statut'])); ?>
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
                            <span class="spec-pill"><?php echo number_format($veh['kilometrage'], 0, ',', ' '); ?> km</span>
                            <span class="spec-pill"><?php echo ucfirst(e($veh['carburant'])); ?></span>
                            <span class="spec-pill"><?php echo ucfirst(e($veh['boite_vitesse'])); ?></span>
                        </div>

                        <div class="car-card-footer">
                            <div class="car-price">
                                <span class="price-value"><?php echo formatPrice((float)$veh['prix']); ?></span>
                                <span class="price-unit">Prix de vente</span>
                            </div>
                            <a href="<?php echo url('details.php?id=' . $veh['id']); ?>" class="btn btn-outline btn-sm">
                                Voir détails →
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
