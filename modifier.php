<?php
/**
 * modifier.php
 * Modification d'une annonce existante (Vente ou Location) sur Mutuka.com.
 */
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/database.php';

// Exiger d'être connecté
requireAuth('compte/index.php', 'connexion.php', 'Vous devez être connecté pour modifier une annonce.');
$currentUser = getCurrentUser();

$vehicleId = (int)($_GET['id'] ?? $_POST['vehicle_id'] ?? 0);
if ($vehicleId <= 0) {
    setFlash('error', "Annonce introuvable.");
    header('Location: ' . url('compte/index.php#annonces'));
    exit;
}

// Vérifier que le véhicule existe et appartient bien à l'utilisateur connecté
$stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->execute([$vehicleId, $currentUser['id']]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    setFlash('error', "Cette annonce n'existe pas ou vous n'avez pas l'autorisation de la modifier.");
    header('Location: ' . url('compte/index.php#annonces'));
    exit;
}

// Récupérer les photos existantes
$stmtPhotos = $pdo->prepare("SELECT * FROM vehicle_photos WHERE vehicle_id = ? ORDER BY is_primary DESC, id ASC");
$stmtPhotos->execute([$vehicleId]);
$existingPhotos = $stmtPhotos->fetchAll();

$errors = [];
$formData = [
    'type_transaction' => $vehicle['type_transaction'],
    'type_vehicule'    => $vehicle['type_vehicule'],
    'titre'            => $vehicle['titre'],
    'marque'           => $vehicle['marque'],
    'modele'           => $vehicle['modele'],
    'annee'            => (int)$vehicle['annee'],
    'kilometrage'      => (int)$vehicle['kilometrage'],
    'carburant'        => $vehicle['carburant'],
    'boite_vitesse'    => $vehicle['boite_vitesse'],
    'couleur'          => $vehicle['couleur'],
    'nombre_places'    => (int)$vehicle['nombre_places'],
    'prix'             => (float)$vehicle['prix'],
    'caution'          => (float)$vehicle['caution'],
    'ville'            => $vehicle['ville'],
    'adresse'          => $vehicle['adresse'],
    'description'      => $vehicle['description'],
    'statut'           => $vehicle['statut']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['type_transaction'] = in_array($_POST['type_transaction'] ?? '', ['vente', 'location']) ? $_POST['type_transaction'] : $formData['type_transaction'];
    $formData['type_vehicule']    = in_array($_POST['type_vehicule'] ?? '', ['voiture', 'moto', 'camion', 'suv', 'utilitaire']) ? $_POST['type_vehicule'] : $formData['type_vehicule'];
    $formData['titre']            = trim($_POST['titre'] ?? '');
    $formData['marque']           = trim($_POST['marque'] ?? '');
    $formData['modele']           = trim($_POST['modele'] ?? '');
    $formData['annee']            = (int)($_POST['annee'] ?? date('Y'));
    $formData['kilometrage']      = (int)($_POST['kilometrage'] ?? 0);
    $formData['carburant']        = in_array($_POST['carburant'] ?? '', ['essence', 'diesel', 'hybride', 'electrique']) ? $_POST['carburant'] : 'essence';
    $formData['boite_vitesse']    = in_array($_POST['boite_vitesse'] ?? '', ['automatique', 'manuelle']) ? $_POST['boite_vitesse'] : 'automatique';
    $formData['couleur']          = trim($_POST['couleur'] ?? '');
    $formData['nombre_places']    = (int)($_POST['nombre_places'] ?? 5);
    $formData['prix']             = (float)($_POST['prix'] ?? 0);
    $formData['caution']          = (float)($_POST['caution'] ?? 0);
    $formData['ville']            = trim($_POST['ville'] ?? 'Kinshasa');
    $formData['adresse']          = trim($_POST['adresse'] ?? '');
    $formData['description']      = trim($_POST['description'] ?? '');
    $formData['statut']           = in_array($_POST['statut'] ?? '', ['disponible', 'reserve', 'vendu', 'loue']) ? $_POST['statut'] : 'disponible';

    // Validations
    if (empty($formData['marque']) || empty($formData['modele'])) {
        $errors[] = "Veuillez renseigner la marque et le modèle du véhicule.";
    }
    if (empty($formData['titre'])) {
        $formData['titre'] = "{$formData['marque']} {$formData['modele']} ({$formData['annee']})";
    }
    if ($formData['prix'] <= 0) {
        $errors[] = "Veuillez indiquer un tarif valide supérieur à 0.";
    }
    if ($formData['annee'] < 1980 || $formData['annee'] > ((int)date('Y') + 1)) {
        $errors[] = "Veuillez renseigner une année de mise en circulation valide.";
    }

    if (empty($errors)) {
        // Mise à jour du véhicule
        $stmtUpdate = $pdo->prepare("UPDATE vehicles SET 
            type_transaction = ?,
            type_vehicule = ?,
            titre = ?,
            marque = ?,
            modele = ?,
            annee = ?,
            kilometrage = ?,
            carburant = ?,
            boite_vitesse = ?,
            couleur = ?,
            nombre_places = ?,
            prix = ?,
            caution = ?,
            ville = ?,
            adresse = ?,
            description = ?,
            statut = ?,
            updated_at = NOW()
            WHERE id = ? AND user_id = ?");

        $stmtUpdate->execute([
            $formData['type_transaction'],
            $formData['type_vehicule'],
            $formData['titre'],
            $formData['marque'],
            $formData['modele'],
            $formData['annee'],
            $formData['kilometrage'],
            $formData['carburant'],
            $formData['boite_vitesse'],
            $formData['couleur'],
            $formData['nombre_places'],
            $formData['prix'],
            $formData['caution'],
            $formData['ville'],
            $formData['adresse'],
            $formData['description'],
            $formData['statut'],
            $vehicleId,
            $currentUser['id']
        ]);

        // Suppression des photos sélectionnées
        if (!empty($_POST['delete_photos']) && is_array($_POST['delete_photos'])) {
            $delStmt = $pdo->prepare("DELETE FROM vehicle_photos WHERE id = ? AND vehicle_id = ?");
            foreach ($_POST['delete_photos'] as $delPhotoId) {
                $delStmt->execute([(int)$delPhotoId, $vehicleId]);
            }
        }

        // Définir la photo principale si demandé
        $primaryPhotoId = (int)($_POST['primary_photo_id'] ?? 0);
        if ($primaryPhotoId > 0) {
            $pdo->prepare("UPDATE vehicle_photos SET is_primary = 0 WHERE vehicle_id = ?")->execute([$vehicleId]);
            $pdo->prepare("UPDATE vehicle_photos SET is_primary = 1 WHERE id = ? AND vehicle_id = ?")->execute([$primaryPhotoId, $vehicleId]);
        }

        // Upload de nouvelles photos supplémentaires
        if (!empty($_FILES['photos']['name'][0])) {
            $totalFiles = count($_FILES['photos']['name']);
            // Vérifier s'il y a déjà une photo principale
            $countPrimary = (int)$pdo->query("SELECT COUNT(*) FROM vehicle_photos WHERE vehicle_id = {$vehicleId} AND is_primary = 1")->fetchColumn();
            $isPrimary = ($countPrimary === 0) ? 1 : 0;

            for ($i = 0; $i < $totalFiles; $i++) {
                if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_OK) {
                    $singleFile = [
                        'name'     => $_FILES['photos']['name'][$i],
                        'type'     => $_FILES['photos']['type'][$i],
                        'tmp_name' => $_FILES['photos']['tmp_name'][$i],
                        'error'    => $_FILES['photos']['error'][$i],
                        'size'     => $_FILES['photos']['size'][$i]
                    ];
                    $savedPath = uploadVehiclePhoto($singleFile);
                    if ($savedPath) {
                        $stmtPhoto = $pdo->prepare("INSERT INTO vehicle_photos (vehicle_id, photo_url, is_primary) VALUES (?, ?, ?)");
                        $stmtPhoto->execute([$vehicleId, $savedPath, $isPrimary]);
                        $isPrimary = 0;
                    }
                }
            }
        }

        // S'assurer qu'au moins une photo restante est marquée comme principale
        $hasPrimary = (int)$pdo->query("SELECT COUNT(*) FROM vehicle_photos WHERE vehicle_id = {$vehicleId} AND is_primary = 1")->fetchColumn();
        if ($hasPrimary === 0) {
            $pdo->prepare("UPDATE vehicle_photos SET is_primary = 1 WHERE vehicle_id = ? ORDER BY id ASC LIMIT 1")->execute([$vehicleId]);
        }

        setFlash('success', "Votre annonce « {$formData['titre']} » a été modifiée avec succès !");
        header('Location: ' . url("details.php?id={$vehicleId}"));
        exit;
    }
}

$pageTitle = "Modifier l'annonce « {$vehicle['titre']} » — Mutuka.com";
require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="form-card" style="max-width: 820px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-4); flex-wrap: wrap; gap: var(--space-2);">
            <div>
                <a href="<?php echo url('compte/index.php#annonces'); ?>" class="nav-link-subtle" style="font-size: 0.88rem;">
                    ← Retour à mes annonces
                </a>
                <h2 style="margin-top: 4px;">Modifier votre annonce</h2>
            </div>
            <a href="<?php echo url("details.php?id={$vehicleId}"); ?>" target="_blank" class="btn btn-outline btn-sm">
                👁️ Voir la fiche actuelle
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="flash-alert flash-error" style="margin-bottom: var(--space-4);">
                <div>
                    <strong>Erreurs à corriger :</strong>
                    <ul style="margin: 6px 0 0 18px; padding: 0;">
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo e($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <form action="<?php echo url("modifier.php?id={$vehicleId}"); ?>" method="post" enctype="multipart/form-data">
            <input type="hidden" name="vehicle_id" value="<?php echo $vehicleId; ?>" />
            
            <!-- Type de transaction -->
            <label style="font-size: 0.95rem; margin-bottom: 8px;">Type d'offre <span class="req">*</span></label>
            <div class="type-toggle">
                <label class="type-option <?php echo ($formData['type_transaction'] === 'vente') ? 'selected' : ''; ?>">
                    <input type="radio" name="type_transaction" value="vente" <?php echo ($formData['type_transaction'] === 'vente') ? 'checked' : ''; ?> />
                    <h4>🏷️ Vente de véhicule</h4>
                    <p>Pour vendre définitivement votre véhicule.</p>
                </label>
                <label class="type-option <?php echo ($formData['type_transaction'] === 'location') ? 'selected' : ''; ?>">
                    <input type="radio" name="type_transaction" value="location" <?php echo ($formData['type_transaction'] === 'location') ? 'checked' : ''; ?> />
                    <h4>🔑 Mise en location</h4>
                    <p>Pour louer votre véhicule avec tarif par jour et caution.</p>
                </label>
            </div>

            <!-- Statut de disponibilité -->
            <div class="form-group" style="background: var(--surface-alt); padding: var(--space-3) var(--space-4); border-radius: var(--radius-sm); border: 1px solid var(--border); margin-bottom: var(--space-4);">
                <label for="statut" style="font-weight: 600;">Statut de publication</label>
                <select id="statut" name="statut" style="max-width: 300px;">
                    <option value="disponible" <?php echo ($formData['statut'] === 'disponible') ? 'selected' : ''; ?>>● Disponible (visible)</option>
                    <option value="reserve" <?php echo ($formData['statut'] === 'reserve') ? 'selected' : ''; ?>>● Réservé</option>
                    <option value="vendu" <?php echo ($formData['statut'] === 'vendu') ? 'selected' : ''; ?>>● Vendu (retiré de la recherche)</option>
                    <option value="loue" <?php echo ($formData['statut'] === 'loue') ? 'selected' : ''; ?>>● Loué</option>
                </select>
                <span class="form-help">Passez le statut à « Vendu » ou « Loué » dès que la transaction est conclue.</span>
            </div>

            <!-- Caractéristiques principales -->
            <div class="form-row">
                <div class="form-group">
                    <label for="type_vehicule">Type de carrosserie <span class="req">*</span></label>
                    <select id="type_vehicule" name="type_vehicule">
                        <option value="voiture" <?php echo ($formData['type_vehicule'] === 'voiture') ? 'selected' : ''; ?>>Berline / Citadine</option>
                        <option value="suv" <?php echo ($formData['type_vehicule'] === 'suv') ? 'selected' : ''; ?>>SUV / 4x4</option>
                        <option value="utilitaire" <?php echo ($formData['type_vehicule'] === 'utilitaire') ? 'selected' : ''; ?>>Utilitaire / Pick-up</option>
                        <option value="camion" <?php echo ($formData['type_vehicule'] === 'camion') ? 'selected' : ''; ?>>Camion / Bus</option>
                        <option value="moto" <?php echo ($formData['type_vehicule'] === 'moto') ? 'selected' : ''; ?>>Moto / Scooter</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="titre">Titre de l'annonce</label>
                    <input type="text" id="titre" name="titre" value="<?php echo e($formData['titre']); ?>" placeholder="Ex: Toyota Land Cruiser Prado TXL 2022" />
                    <span class="form-help">Laisser vide pour générer automatiquement</span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="marque">Marque <span class="req">*</span></label>
                    <input type="text" id="marque" name="marque" value="<?php echo e($formData['marque']); ?>" required placeholder="Ex: Toyota, Mercedes, Hyundai..." />
                </div>
                <div class="form-group">
                    <label for="modele">Modèle <span class="req">*</span></label>
                    <input type="text" id="modele" name="modele" value="<?php echo e($formData['modele']); ?>" required placeholder="Ex: RAV4, Classe C, Hilux..." />
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="annee">Année de fabrication <span class="req">*</span></label>
                    <input type="number" id="annee" name="annee" min="1980" max="<?php echo date('Y') + 1; ?>" value="<?php echo e((string)$formData['annee']); ?>" required />
                </div>
                <div class="form-group">
                    <label for="kilometrage">Kilométrage (km)</label>
                    <input type="number" id="kilometrage" name="kilometrage" min="0" value="<?php echo e((string)$formData['kilometrage']); ?>" placeholder="Ex: 45000" />
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="carburant">Carburant <span class="req">*</span></label>
                    <select id="carburant" name="carburant">
                        <option value="essence" <?php echo ($formData['carburant'] === 'essence') ? 'selected' : ''; ?>>Essence</option>
                        <option value="diesel" <?php echo ($formData['carburant'] === 'diesel') ? 'selected' : ''; ?>>Diesel</option>
                        <option value="hybride" <?php echo ($formData['carburant'] === 'hybride') ? 'selected' : ''; ?>>Hybride</option>
                        <option value="electrique" <?php echo ($formData['carburant'] === 'electrique') ? 'selected' : ''; ?>>Électrique</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="boite_vitesse">Boîte de vitesses <span class="req">*</span></label>
                    <select id="boite_vitesse" name="boite_vitesse">
                        <option value="automatique" <?php echo ($formData['boite_vitesse'] === 'automatique') ? 'selected' : ''; ?>>Automatique</option>
                        <option value="manuelle" <?php echo ($formData['boite_vitesse'] === 'manuelle') ? 'selected' : ''; ?>>Manuelle</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="couleur">Couleur</label>
                    <input type="text" id="couleur" name="couleur" value="<?php echo e($formData['couleur']); ?>" placeholder="Ex: Noir métallisé, Blanc..." />
                </div>
                <div class="form-group">
                    <label for="nombre_places">Nombre de places</label>
                    <input type="number" id="nombre_places" name="nombre_places" min="1" max="60" value="<?php echo e((string)$formData['nombre_places']); ?>" />
                </div>
            </div>

            <!-- Tarification & Localisation -->
            <div class="form-row">
                <div class="form-group">
                    <label id="price_label" for="prix">
                        <?php echo ($formData['type_transaction'] === 'location') ? 'Tarif journalier ($ / jour)' : 'Prix de vente ($)'; ?> <span class="req">*</span>
                    </label>
                    <input type="number" id="prix" name="prix" step="0.01" min="1" value="<?php echo e((string)$formData['prix']); ?>" required placeholder="Ex: 25000 ou 80" />
                </div>
                <div class="form-group" id="caution_group" style="<?php echo ($formData['type_transaction'] === 'location') ? '' : 'display:none;'; ?>">
                    <label for="caution">Caution de garantie ($)</label>
                    <input type="number" id="caution" name="caution" step="0.01" min="0" value="<?php echo e((string)$formData['caution']); ?>" placeholder="Ex: 200" />
                    <span class="form-help">Montant restitué en fin de location</span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="ville">Ville <span class="req">*</span></label>
                    <select id="ville" name="ville">
                        <option value="Kinshasa" <?php echo ($formData['ville'] === 'Kinshasa') ? 'selected' : ''; ?>>Kinshasa</option>
                        <option value="Lubumbashi" <?php echo ($formData['ville'] === 'Lubumbashi') ? 'selected' : ''; ?>>Lubumbashi</option>
                        <option value="Goma" <?php echo ($formData['ville'] === 'Goma') ? 'selected' : ''; ?>>Goma</option>
                        <option value="Kolwezi" <?php echo ($formData['ville'] === 'Kolwezi') ? 'selected' : ''; ?>>Kolwezi</option>
                        <option value="Matadi" <?php echo ($formData['ville'] === 'Matadi') ? 'selected' : ''; ?>>Matadi</option>
                        <option value="Autre" <?php echo ($formData['ville'] === 'Autre') ? 'selected' : ''; ?>>Autre ville</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="adresse">Quartier / Adresse indicative</label>
                    <input type="text" id="adresse" name="adresse" value="<?php echo e($formData['adresse']); ?>" placeholder="Ex: Gombe, Limete, Boulevard du 30 Juin..." />
                </div>
            </div>

            <!-- Photos existantes & Gestion -->
            <?php if (!empty($existingPhotos)): ?>
                <div class="form-group" style="margin-top: var(--space-4);">
                    <label style="font-weight: 600;">Photos actuelles du véhicule</label>
                    <p class="form-help" style="margin-bottom: var(--space-2);">Cochez « Supprimer » pour retirer une photo, ou sélectionnez le bouton radio pour la définir comme photo principale.</p>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; margin-top: 8px;">
                        <?php foreach ($existingPhotos as $ph): ?>
                            <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 8px; text-align: center; position: relative;">
                                <div style="width: 100%; height: 90px; border-radius: 4px; overflow: hidden; background: #1c2024; margin-bottom: 6px;">
                                    <img src="<?php echo getVehiclePhoto($ph['photo_url'], $formData['type_vehicule']); ?>" alt="Photo véhicule" style="width: 100%; height: 100%; object-fit: cover;" />
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 4px; font-size: 0.78rem; text-align: left;">
                                    <label style="display: flex; align-items: center; gap: 4px; cursor: pointer; color: var(--accent-dark); font-weight: 600;">
                                        <input type="radio" name="primary_photo_id" value="<?php echo $ph['id']; ?>" <?php echo ($ph['is_primary'] == 1) ? 'checked' : ''; ?> />
                                        <span>Principale</span>
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 4px; cursor: pointer; color: var(--danger);">
                                        <input type="checkbox" name="delete_photos[]" value="<?php echo $ph['id']; ?>" />
                                        <span>Supprimer</span>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Ajouter de nouvelles photos -->
            <div class="form-group" style="margin-top: var(--space-4);">
                <label>Ajouter de nouvelles photos</label>
                <div class="dropzone" onclick="document.getElementById('vehicle_photos_input').click()">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto var(--space-2); color: var(--accent);"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    <p style="margin: 0; font-weight: 600;">Cliquez pour ajouter des photos supplémentaires</p>
                    <span class="form-help">JPG, PNG, WEBP (jusqu'à 8 Mo par photo)</span>
                    <input type="file" id="vehicle_photos_input" name="photos[]" multiple accept="image/*" style="display: none;" />
                </div>
                <div id="photos_preview_container" class="preview-gallery"></div>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label for="description">Description détaillée</label>
                <textarea id="description" name="description" rows="5" placeholder="Mentionnez l'état général, l'historique d'entretien, les options..."><?php echo e($formData['description']); ?></textarea>
            </div>

            <div style="display: flex; gap: var(--space-3); margin-top: var(--space-5); flex-wrap: wrap;">
                <button type="submit" class="btn btn-accent btn-lg" style="flex: 1; min-width: 200px;">
                    💾 Enregistrer les modifications
                </button>
                <a href="<?php echo url('compte/index.php#annonces'); ?>" class="btn btn-outline btn-lg" style="text-align: center;">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
