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
function requireAuth(string $redirectUrl = 'connexion.php'): void {
    if (!isLoggedIn()) {
        setFlash('warning', 'Vous devez être connecté pour accéder à cette page.');
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        $_SESSION['redirect_after_login'] = $currentUri;
        header('Location: ' . url($redirectUrl));
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

    // 6. Mercedes ML / autre Mercedes
    if (str_contains($titleLower, 'ml') || str_contains($titleLower, 'mercedes')) {
        return 'assets/Mercedes_ML350.jpg';
    }

    // 7. Jimny / Suzuki
    if (str_contains($titleLower, 'jimny') || str_contains($titleLower, 'suzuki')) {
        return 'assets/Mercedes_ML350.jpg';
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
    }
    
    // 3. Génère un placeholder SVG dynamique et esthétique selon le type
    return generateCarPlaceholderSvgUrl($type);
}

/**
 * Génère une URL Data SVG élégante pour représenter un véhicule sans photo
 */
function generateCarPlaceholderSvgUrl(string $type = 'voiture'): string {
    $color = '#202327';
    $accent = '#A9814A';
    $title = strtoupper($type);
    
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="400" viewBox="0 0 600 400" style="background:linear-gradient(135deg, #1e2024 0%, #15171a 100%);">
        <defs>
            <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#32363c" />
                <stop offset="100%" stop-color="#1b1d20" />
            </linearGradient>
        </defs>
        <rect width="600" height="400" fill="url(#grad)" />
        <g transform="translate(130, 100)" stroke="' . $accent . '" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 120 C20 90 45 70 80 68 L115 48 C130 38 145 34 165 34 L240 34 C255 34 270 42 280 55 L300 78 L330 84 C342 86 350 96 350 108 L350 120 L320 120" />
            <path d="M20 120 L20 110 C20 105 25 102 30 102 L320 102 C328 102 335 106 340 112 L350 120" />
            <line x1="115" y1="102" x2="125" y2="55" opacity="0.6"/>
            <line x1="180" y1="102" x2="180" y2="38" opacity="0.6"/>
            <circle cx="85" cy="122" r="24" stroke="' . $accent . '" fill="#15171a"/>
            <circle cx="85" cy="122" r="8" fill="' . $accent . '"/>
            <circle cx="280" cy="122" r="24" stroke="' . $accent . '" fill="#15171a"/>
            <circle cx="280" cy="122" r="8" fill="' . $accent . '"/>
        </g>
        <text x="300" y="300" text-anchor="middle" fill="#FFFFFF" font-family="sans-serif" font-size="16" font-weight="600" letter-spacing="3" opacity="0.85">MUTUKA • ' . htmlspecialchars($title) . '</text>
        <text x="300" y="325" text-anchor="middle" fill="' . $accent . '" font-family="sans-serif" font-size="12" letter-spacing="1.5">VÉHICULE CERTIFIÉ</text>
    </svg>';

    return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
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
