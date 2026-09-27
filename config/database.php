<?php

$isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1'])
    || in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1'])
    || (php_sapi_name() === 'cli');

if (!defined('DB_HOST')) {
    if ($isLocal) {
        // Configuration Locale (WampServer / XAMPP)
        define('DB_HOST', 'localhost');
        define('DB_PORT', '3306');
        define('DB_NAME', 'mutuka');
        define('DB_USER', 'root');
        define('DB_PASS', 'michel');
    } else {
        // Configuration Hébergement InfinityFree
        define('DB_HOST', 'sql313.infinityfree.com');
        define('DB_PORT', '3306');
        define('DB_NAME', 'if0_42775047_mutuka');
        define('DB_USER', 'if0_42775047');
        define('DB_PASS', 'mutuka64');
    }
}

/**
 * Récupère ou initialise la connexion PDO
 * @return PDO
 */
function getPDO(): PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1'])
        || in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1'])
        || (php_sapi_name() === 'cli');

    // Connexion directe à la base de données
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

        // Vérification et initialisation automatique si base vide
        initDatabaseIfNeeded($pdo);

    } catch (PDOException $e) {
        // En local uniquement, tentative sur port alternatif 3307 (MariaDB sur Wamp)
        if ($isLocal && DB_PORT === '3306') {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";port=3307;dbname=" . DB_NAME . ";charset=utf8mb4";
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                initDatabaseIfNeeded($pdo);
                return $pdo;
            } catch (PDOException $e2) {
                // Ignore pour lever l'erreur principale
            }
        }

        $hostInfo = htmlspecialchars(DB_HOST . " (Base: " . DB_NAME . ", Port: " . DB_PORT . ")");
        die("<div style='font-family:sans-serif;padding:30px;max-width:650px;margin:50px auto;border:1px solid #e0b4b4;background:#fff6f6;border-radius:12px;color:#9f3a38;line-height:1.6;'>" .
            "<h3 style='margin-top:0;'>Erreur de connexion MySQL</h3>" .
            "<p>Impossible de se connecter à la base de données :</p>" .
            "<ul>" .
            "<li><strong>Cible :</strong> " . $hostInfo . "</li>" .
            "<li><strong>Message d'erreur :</strong> " . htmlspecialchars($e->getMessage()) . "</li>" .
            "</ul>" .
            "<p><strong>Conseil :</strong> " . ($isLocal 
                ? "Vérifiez que votre serveur WampServer / MySQL est bien lancé (icône verte)." 
                : "Vérifiez dans votre panneau InfinityFree que le nom d'hôte, l'utilisateur et le mot de passe du compte sont corrects.") . "</p></div>");
    }

    return $pdo;
}

/**
 * Initialise les tables et injecte des données de démonstration réalistes
 */
function initDatabaseIfNeeded(PDO $pdo): void {
    // 1. Vérifier si les tables existent, sinon importer le schéma
    $stmt = $pdo->query("SHOW TABLES LIKE 'vehicles'");
    if ($stmt->rowCount() === 0) {
        $sqlPath = __DIR__ . '/../database/mutuka.sql';
        if (file_exists($sqlPath)) {
            $sql = file_get_contents($sqlPath);
            $pdo->exec($sql);
        }
    }

    // 2. Vérifier si la table vehicles contient déjà des données
    try {
        $countVeh = (int)$pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
        if ($countVeh > 0) {
            return; // La base a déjà des véhicules enregistrés
        }
    } catch (PDOException $e) {
        return;
    }

    // 3. Vérifier ou insérer les utilisateurs de test
    $stmtUserCount = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($stmtUserCount === 0) {
        $passwordHash = password_hash('Pass1234!', PASSWORD_BCRYPT);
        $stmtUser = $pdo->prepare("INSERT INTO users (nom, prenom, email, telephone, whatsapp, password, ville, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        // User 1: Vendeur Pro
        $stmtUser->execute(['Mutombo', 'Patrick', 'patrick.mutombo@mutuka.com', '+243810000001', '+243810000001', $passwordHash, 'Kinshasa (Gombe)', 'admin']);
        $user1Id = $pdo->lastInsertId();

        // User 2: Loueur Particulier
        $stmtUser->execute(['Kabila', 'Sarah', 'sarah.kabila@mutuka.com', '+243890000002', '+243890000002', $passwordHash, 'Lubumbashi', 'user']);
        $user2Id = $pdo->lastInsertId();

        // User 3: Acheteur / Client
        $stmtUser->execute(['Tshisekedi', 'Alain', 'alain.t@mutuka.com', '+243820000003', '+243820000003', $passwordHash, 'Goma', 'user']);
        $user3Id = $pdo->lastInsertId();
    } else {
        $user1Id = (int)$pdo->query("SELECT id FROM users ORDER BY id ASC LIMIT 1")->fetchColumn();
        $user2Id = $user1Id;
        $user3Id = $user1Id;
    }

    // 4. Insertion de véhicules de démonstration
    $stmtVeh = $pdo->prepare("INSERT INTO vehicles 
        (user_id, type_transaction, type_vehicule, titre, marque, modele, annee, kilometrage, carburant, boite_vitesse, couleur, nombre_places, prix, caution, ville, adresse, description, statut, vues) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // 1. Toyota Land Cruiser Prado TXL - VENTE
    $stmtVeh->execute([
        $user1Id, 'vente', 'suv',
        'Toyota Land Cruiser Prado TXL 2022',
        'Toyota', 'Land Cruiser Prado', 2022, 38500, 'diesel', 'automatique', 'Noir Métallisé', 7,
        52000.00, 0.00, 'Kinshasa', 'Boulevard du 30 Juin, Gombe',
        'Magnifique Toyota Prado TXL en excellent état. Entretien régulier en concession, intérieur cuir impeccable, toit ouvrant, climatisation multi-zone, caméra 360°, pneus neufs. Papiers complets en règle.',
        'disponible', 142
    ]);
    $v1 = $pdo->lastInsertId();

    // 2. Mercedes-Benz Classe C 300 - VENTE
    $stmtVeh->execute([
        $user1Id, 'vente', 'voiture',
        'Mercedes-Benz Classe C 300 AMG Line 2021',
        'Mercedes-Benz', 'Classe C 300', 2021, 45000, 'essence', 'automatique', 'Gris Sélénite', 5,
        36500.00, 0.00, 'Kinshasa', 'Quartier Macampagne, Ngaliema',
        'Mercedes Classe C avec finition AMG Line. Écran panoramique MBUX, son Burmester, pack éclairage d\'ambiance 64 couleurs, jantes 19 pouces AMG. Véhicule non accidenté, première main.',
        'disponible', 98
    ]);
    $v2 = $pdo->lastInsertId();

    // 3. Toyota Hilux Double Cabine 4x4 - LOCATION
    $stmtVeh->execute([
        $user2Id, 'location', 'utilitaire',
        'Toyota Hilux Revo Double Cabine 4x4',
        'Toyota', 'Hilux Revo', 2023, 22000, 'diesel', 'manuelle', 'Blanc Pur', 5,
        95.00, 300.00, 'Kinshasa', 'Avenue du Port, Gombe',
        'Robuste 4x4 idéal pour vos déplacements urbains ou missions en province. Moteur 2.8L D-4D puissant, climatisation renforcée, attache remorque, réservoir supplémentaire. Disponible avec ou sans chauffeur.',
        'disponible', 230
    ]);
    $v3 = $pdo->lastInsertId();

    // 4. Hyundai Tucson Executive - LOCATION
    $stmtVeh->execute([
        $user2Id, 'location', 'suv',
        'Hyundai Tucson 1.6 T-GDi Executive 2023',
        'Hyundai', 'Tucson', 2023, 16000, 'essence', 'automatique', 'Bleu Nuit', 5,
        75.00, 200.00, 'Lubumbashi', 'Avenue Mama Yemo',
        'SUV moderne et très confortable pour vos séjours professionnels ou en famille. Consommation économique, Apple CarPlay / Android Auto sans fil, aide au stationnement.',
        'disponible', 85
    ]);
    $v4 = $pdo->lastInsertId();

    // 5. Range Rover Sport HSE Dynamic - VENTE
    $stmtVeh->execute([
        $user1Id, 'vente', 'suv',
        'Range Rover Sport HSE Dynamic V6 Supercharged',
        'Land Rover', 'Range Rover Sport', 2020, 54000, 'essence', 'automatique', 'Blanc Fuji', 5,
        59000.00, 0.00, 'Kinshasa', 'Gombe',
        'Véhicule de prestige, suspension pneumatique adaptative, système audio Meridian Surround, sièges massants ventilés, historique limpide.',
        'disponible', 165
    ]);
    $v5 = $pdo->lastInsertId();

    // Photos associées dans assets/
    $stmtPhoto = $pdo->prepare("INSERT INTO vehicle_photos (vehicle_id, photo_url, is_primary) VALUES (?, ?, ?)");
    $stmtPhoto->execute([$v1, 'assets/Toyota_Land_Cruiser2022.jpg', 1]);
    $stmtPhoto->execute([$v2, 'assets/Mercedes_ClasseC2021.jpg', 1]);
    $stmtPhoto->execute([$v3, 'assets/Toyota_Hilux.jpg', 1]);
    $stmtPhoto->execute([$v4, 'assets/Hyundai_Tucson.jpg', 1]);
    $stmtPhoto->execute([$v5, 'assets/Ranger_RoverV6.jpg', 1]);
}

// Initialisation globale de la variable $pdo
$pdo = getPDO();
