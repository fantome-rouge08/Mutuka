<?php
/**
 * includes/helpers.php
 * Fonctions utilitaires globales pour Mutuka.com :
 * Gestion de session, authentification, formatage, uploads, redirection dynamique.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Calcule l'URL de base dynamique du projet (ex: /mutuka-php-etape1 ou /mutuka)
 */
function getBaseUrl(): string {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = str_replace('\\', '/', dirname($scriptName));
    
    // Si on est dans un sous-dossier comme /compte ou /admin, remonter à la racine du projet
    if (preg_match('#/(compte|admin|includes|config|database)$#', $dir)) {
        $dir = dirname($dir);
    }
    $dir = str_replace('\\', '/', $dir);
    return rtrim($dir, '/');
}

/**
 * Génère une URL relative au projet
 */
function url(string $path = ''): string {
    $base = getBaseUrl();
    $path = ltrim($path, '/');
    return ($base === '' ? '/' : $base . '/') . $path;
}

/**
 * Vérifie si un utilisateur est connecté
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

/**
 * Récupère l'utilisateur connecté actuellement
 */
function getCurrentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Exige une authentification pour accéder à la page
 */
function requireAuth(string $redirectTarget = '', string $loginUrl = 'connexion.php', string $message = 'Vous devez être connecté pour accéder à cette page.'): void {
    if (!isLoggedIn()) {
        setFlash('warning', $message);
        $currentUri = !empty($redirectTarget) ? url($redirectTarget) : ($_SERVER['REQUEST_URI'] ?? '');
        $_SESSION['redirect_after_login'] = $currentUri;
        header('Location: ' . url($loginUrl));
        exit;
    }
}

/**
 * Déconnecte l'utilisateur
 */
function logoutUser(): void {
    $_SESSION['user'] = null;
    unset($_SESSION['user']);
    session_destroy();
}

/**
 * Enregistre un message flash (success, error, warning, info)
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Récupère et vide le message flash en cours
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Formate un montant monétaire élégamment
 */
function formatPrice(float $amount, string $currency = '$'): string {
    return number_format($amount, 0, ',', ' ') . ' ' . $currency;
}

/**
 * Formate une date en français
 */
function formatDateFR(string $dateStr): string {
    if (empty($dateStr)) return '';
    $timestamp = strtotime($dateStr);
    $mois = [
        1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
        5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
        9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre'
    ];
    $j = date('j', $timestamp);
    $m = $mois[(int)date('n', $timestamp)];
    $y = date('Y', $timestamp);
    return "$j $m $y";
}

/**
 * Sécurise une chaîne pour l'affichage HTML
 */
function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Recherche avec précision l'image correspondante dans assets/ selon le titre du véhicule
 */
function findAssetPhotoForTitle(?string $title): ?string {
    if (empty($title)) {
        return null;
    }

    $titleLower = function_exists('mb_strtolower') ? mb_strtolower($title, 'UTF-8') : strtolower($title);

    // 1. Toyota Land Cruiser / Prado
    if (str_contains($titleLower, 'land cruiser') || str_contains($titleLower, 'prado')) {
        return 'assets/Toyota_Land_Cruiser2022.jpg';
    }

    // 2. Mercedes-Benz Classe C
    if (str_contains($titleLower, 'classe c') || str_contains($titleLower, 'classe-c') || str_contains($titleLower, 'c 300') || str_contains($titleLower, 'c300') || str_contains($titleLower, 'c2021')) {
        return 'assets/Mercedes_ClasseC2021.jpg';
    }

    // 3. Range Rover / Supercharged
    if (str_contains($titleLower, 'range rover') || str_contains($titleLower, 'ranger rover') || str_contains($titleLower, 'supercharged')) {
        return 'assets/Ranger_RoverV6.jpg';
    }

    // 4. Toyota Hilux
    if (str_contains($titleLower, 'hilux')) {
        return 'assets/Toyota_Hilux.jpg';
    }

    // 5. Hyundai Tucson
    if (str_contains($titleLower, 'tucson') || str_contains($titleLower, 'hyundai')) {
        return 'assets/Hyundai_Tucson.jpg';
    }

    return null;
}

/**
 * Récupère la photo principale d'un véhicule, l'image correspondante dans assets/, ou un placeholder SVG
 */
function getVehiclePhoto(?string $photoUrl, string $type = 'voiture', ?string $title = null): string {
    // 1. Recherche directe dans assets/ via le titre du véhicule
    if (!empty($title)) {
        $asset = findAssetPhotoForTitle($title);
        if ($asset && file_exists(__DIR__ . '/../' . $asset)) {
            return url($asset);
        }
    }

    // 2. Si une photo réelle existe dans uploads/ ou assets/
    if (!empty($photoUrl)) {
        $cleanPath = ltrim($photoUrl, '/');
        if (file_exists(__DIR__ . '/../' . $cleanPath) && !is_dir(__DIR__ . '/../' . $cleanPath)) {
            return url($cleanPath);
        }

        // Tolérance : vérifier dans uploads/vehicules si le chemin contient uploads/vehicles (ou inversement)
        $altPath1 = str_replace('uploads/vehicles/', 'uploads/vehicules/', $cleanPath);
        if (file_exists(__DIR__ . '/../' . $altPath1) && !is_dir(__DIR__ . '/../' . $altPath1)) {
            return url($altPath1);
        }
        $altPath2 = str_replace('uploads/vehicules/', 'uploads/vehicles/', $cleanPath);
        if (file_exists(__DIR__ . '/../' . $altPath2) && !is_dir(__DIR__ . '/../' . $altPath2)) {
            return url($altPath2);
        }

        // Si c'est un nom d'asset directement
        if (file_exists(__DIR__ . '/../assets/' . basename($cleanPath))) {
            return url('assets/' . basename($cleanPath));
        }
    }

    // 3. Image de secours locale si disponible dans assets/
    if (file_exists(__DIR__ . '/../assets/Toyota_Land_Cruiser2022.jpg')) {
        return url('assets/Toyota_Land_Cruiser2022.jpg');
    }
    
    // 4. Génère un placeholder SVG dynamique si aucune image physique
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="400" viewBox="0 0 600 400"><rect width="100%" height="100%" fill="#e2e8f0"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="sans-serif" font-weight="bold" font-size="22" fill="#64748b">Mutuka.com</text></svg>';
    return 'data:image/svg+xml;charset=utf-8,' . rawurlencode($svg);
}


/**
 * Traitement sécurisé de l'upload de photo d'un véhicule
 */
function uploadVehiclePhoto(array $file): ?string {
    if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    $fileMime = mime_content_type($file['tmp_name']);

    if (!in_array($fileMime, $allowedMimes, true)) {
        return null;
    }

    if ($file['size'] > 8 * 1024 * 1024) { // Limite 8 Mo
        return null;
    }

    $uploadDir = __DIR__ . '/../uploads/vehicles/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $ext = strtolower($ext);
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
        $ext = 'jpg';
    }

    $fileName = 'veh_' . uniqid('', true) . '.' . $ext;
    $targetPath = $uploadDir . $fileName;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return 'uploads/vehicles/' . $fileName;
    }

    return null;
}
