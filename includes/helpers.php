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

// ============================================
// OTP / EMAIL VERIFICATION FUNCTIONS
// ============================================

/**
 * Génère un code OTP à 6 chiffres
 */
function generateOtp(): string {
    return (string)random_int(100000, 999999);
}

// /**
//  * Envoie un email via SMTP natif (fsockopen) - compatible Gmail TLS 587
//  */
// function sendSmtpEmail(string $to, string $subject, string $htmlBody): bool {
//     $host = SMTP_HOST;
//     $port = SMTP_PORT;
//     $user = SMTP_USER;
//     $pass = SMTP_PASS;
//     $from = SMTP_FROM;
//     $fromName = SMTP_FROM_NAME;

//     $socket = @fsockopen($host, $port, $errno, $errstr, 10);
//     if (!$socket) {
//         error_log("SMTP connect failed: $errstr ($errno)");
//         return false;
//     }
//     stream_set_timeout($socket, 10);

//     $read = function () use ($socket) {
//         $response = '';
//         while ($line = fgets($socket, 512)) {
//             $response .= $line;
//             if (strlen($line) < 4 || $line[3] === ' ') break;
//         }
//         return $response;
//     };
//     $write = function ($cmd) use ($socket) {
//         fwrite($socket, $cmd . "\r\n");
//     };

//     // Connexion
//     $resp = $read();
//     if (!preg_match('/^220/', $resp)) { fclose($socket); return false; }

//     $write("EHLO " . gethostname());
//     $resp = $read();
//     if (!preg_match('/^250/', $resp)) { fclose($socket); return false; }

//     // STARTTLS
//     $write("STARTTLS");
//     $resp = $read();
//     if (!preg_match('/^220/', $resp)) { fclose($socket); return false; }

//     if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
//         error_log("SMTP STARTTLS failed");
//         fclose($socket);
//         return false;
//     }

//     // Re-EHLO après TLS
//     $write("EHLO " . gethostname());
//     $resp = $read();
//     if (!preg_match('/^250/', $resp)) { fclose($socket); return false; }

//     // AUTH LOGIN
//     $write("AUTH LOGIN");
//     $resp = $read();
//     if (!preg_match('/^334/', $resp)) { fclose($socket); return false; }

//     $write(base64_encode($user));
//     $resp = $read();
//     if (!preg_match('/^334/', $resp)) { fclose($socket); return false; }

//     $write(base64_encode($pass));
//     $resp = $read();
//     if (!preg_match('/^235/', $resp)) { fclose($socket); return false; }

//     // MAIL FROM
//     $write("MAIL FROM:<$from>");
//     $resp = $read();
//     if (!preg_match('/^250/', $resp)) { fclose($socket); return false; }

//     // RCPT TO
//     $write("RCPT TO:<$to>");
//     $resp = $read();
//     if (!preg_match('/^250/', $resp)) { fclose($socket); return false; }

//     // DATA
//     $write("DATA");
//     $resp = $read();
//     if (!preg_match('/^354/', $resp)) { fclose($socket); return false; }

//     $headers = "From: $fromName <$from>\r\n";
//     $headers .= "To: $to\r\n";
//     $headers .= "Subject: $subject\r\n";
//     $headers .= "MIME-Version: 1.0\r\n";
//     $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
//     $headers .= "\r\n";

//     $write($headers . $htmlBody . "\r\n.");
//     $resp = $read();
//     if (!preg_match('/^250/', $resp)) { fclose($socket); return false; }

//     $write("QUIT");
//     $read();
//     fclose($socket);
//     return true;
// }

/**
 * Template HTML élégant pour email OTP
 */
function getOtpEmailHtml(string $prenom, string $otp): string {
    return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification Mutuka.com</title>
</head>
<body style="margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);">
                    <!-- Header Gradient -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#A9814A 0%,#1e2024 100%);padding:36px 30px;text-align:center;">
                            <h1 style="color:#ffffff;margin:0;font-size:28px;font-weight:700;letter-spacing:0.5px;">Mutuka.com</h1>
                            <p style="color:rgba(255,255,255,0.85);margin:10px 0 0;font-size:15px;">Vérification de votre compte</p>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding:48px 40px;">
                            <p style="font-size:16px;color:#2d3748;line-height:1.6;margin:0 0 16px;">Bonjour <strong style="color:#1a202c;">{$prenom}</strong>,</p>
                            <p style="font-size:16px;color:#4a5568;line-height:1.6;margin:0 0 24px;">Merci de vous inscrire sur Mutuka.com. Voici votre code de vérification :</p>
                            
                            <!-- Code OTP -->
                            <div style="text-align:center;margin:36px 0;">
                                <span style="display:inline-block;background:#1e2024;color:#A9814A;font-family:'Courier New',monospace;font-size:36px;letter-spacing:8px;padding:18px 40px;border-radius:10px;font-weight:700;box-shadow:0 4px 14px rgba(169,129,74,0.3);">{$otp}</span>
                            </div>
                            
                            <p style="font-size:14px;color:#718096;text-align:center;margin:0 0 32px;">Ce code est valide pendant <strong style="color:#1a202c;">15 minutes</strong>.</p>
                            
                            <hr style="border:none;border-top:1px solid #e2e8f0;margin:32px 0;">
                            
                            <p style="font-size:13px;color:#a0aec0;text-align:center;margin:0;">Si vous n'avez pas demandé ce code, ignorez simplement cet email.<br>Votre compte ne sera pas activé sans ce code.</p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background:#fafafa;padding:24px 40px;text-align:center;border-top:1px solid #e2e8f0;">
                            <p style="font-size:12px;color:#a0aec0;margin:0 0 8px;">© 2025 Mutuka.com — Plateforme véhicule RDC</p>
                            <p style="font-size:11px;color:#cbd5e0;margin:0;">Kinshasa • Lubumbashi • Goma • Kolwezi • Matadi</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
}

/**
//  * Envoie l'email OTP à l'utilisateur
//  */
// function sendOtpEmail(string $email, string $prenom, string $otp): bool {
//     $subject = "Votre code de vérification Mutuka.com — {$otp}";
//     $html = getOtpEmailHtml($prenom, $otp);
//     return sendSmtpEmail($email, $subject, $html);
// }

/**
 * Envoie l'email de bienvenue après vérification
 */
// function sendWelcomeEmail(string $email, string $prenom): bool {
//     $subject = "Bienvenue sur Mutuka.com !";
//     $html = <<<HTML
// <!DOCTYPE html>
// <html lang="fr">
// <head>
//     <meta charset="UTF-8">
//     <meta name="viewport" content="width=device-width, initial-scale=1.0">
//     <title>Bienvenue Mutuka.com</title>
// </head>
// <body style="margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif;">
//     <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:40px 20px;">
//         <tr><td align="center">
//             <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);">
//                 <tr><td style="background:linear-gradient(135deg,#A9814A 0%,#1e2024 100%);padding:36px 30px;text-align:center;">
//                     <h1 style="color:#ffffff;margin:0;font-size:28px;font-weight:700;">Mutuka.com</h1>
//                     <p style="color:rgba(255,255,255,0.85);margin:10px 0 0;font-size:15px;">Votre compte est activé ✓</p>
//                 </td></tr>
//                 <tr><td style="padding:48px 40px;">
//                     <p style="font-size:16px;color:#2d3748;line-height:1.6;margin:0 0 16px;">Bonjour <strong>{$prenom}</strong>,</p>
//                     <p style="font-size:16px;color:#4a5568;line-height:1.6;margin:0 0 24px;">Félicitations ! Votre adresse email a été vérifiée avec succès. Vous avez maintenant accès à toutes les fonctionnalités de Mutuka.com :</p>
//                     <ul style="font-size:15px;color:#4a5568;line-height:2;padding-left:20px;margin:0 0 24px;">
//                         <li>📝 Publier vos annonces (vente & location)</li>
//                         <li>💬 Contacter directement les vendeurs/loueurs via WhatsApp</li>
//                         <li>💳 Faire des demandes d'achat sécurisées</li>
//                         <li>📅 Réserver des véhicules en location</li>
//                         <li>📊 Suivre vos opérations dans votre espace compte</li>
//                     </ul>
//                     <div style="text-align:center;margin:32px 0;">
//                         <a href="https://mutuka.com" style="display:inline-block;background:#A9814A;color:#fff;padding:14px 32px;border-radius:8px;font-weight:600;text-decoration:none;font-size:15px;">Accéder à mon espace</a>
//                     </div>
//                 </td></tr>
//                 <tr><td style="background:#fafafa;padding:24px 40px;text-align:center;border-top:1px solid #e2e8f0;">
//                     <p style="font-size:12px;color:#a0aec0;margin:0;">© 2025 Mutuka.com</p>
//                 </td></tr>
//             </table>
//         </td></tr>
//     </table>
// </body>
// </html>
// HTML;
   
// }

