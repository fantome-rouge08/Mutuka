-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nom` VARCHAR(100) NOT NULL,
    `prenom` VARCHAR(100) NOT NULL,
    `email` VARCHAR(191) NOT NULL UNIQUE,
    `telephone` VARCHAR(30) NOT NULL,
    `whatsapp` VARCHAR(30) NULL,
    `password` VARCHAR(255) NOT NULL,
    `ville` VARCHAR(100) DEFAULT 'Kinshasa',
    `avatar` VARCHAR(255) DEFAULT NULL,
    `role` ENUM('user', 'admin') DEFAULT 'user',
    `email_verified` TINYINT(1) DEFAULT 0,
    `otp_code` VARCHAR(6) NULL,
    `otp_expires_at` DATETIME NULL,
    `otp_attempts` TINYINT(1) DEFAULT 0,
    `otp_locked_until` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des véhicules
CREATE TABLE IF NOT EXISTS `vehicles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `type_transaction` ENUM('vente', 'location') NOT NULL,
    `type_vehicule` ENUM('voiture', 'moto', 'camion', 'suv', 'utilitaire') NOT NULL DEFAULT 'voiture',
    `titre` VARCHAR(200) NOT NULL,
    `marque` VARCHAR(100) NOT NULL,
    `modele` VARCHAR(100) NOT NULL,
    `annee` INT NOT NULL,
    `kilometrage` INT DEFAULT 0,
    `carburant` ENUM('essence', 'diesel', 'hybride', 'electrique') NOT NULL DEFAULT 'essence',
    `boite_vitesse` ENUM('automatique', 'manuelle') NOT NULL DEFAULT 'automatique',
    `couleur` VARCHAR(50) DEFAULT NULL,
    `nombre_places` INT DEFAULT 5,
    `prix` DECIMAL(12, 2) NOT NULL COMMENT 'Prix de vente ou tarif journalier de location',
    `caution` DECIMAL(12, 2) DEFAULT 0.00 COMMENT 'Caution pour la location',
    `ville` VARCHAR(100) NOT NULL DEFAULT 'Kinshasa',
    `adresse` VARCHAR(255) DEFAULT NULL,
    `description` TEXT,
    `statut` ENUM('disponible', 'reserve', 'vendu', 'loue') NOT NULL DEFAULT 'disponible',
    `vues` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des photos des véhicules
CREATE TABLE IF NOT EXISTS `vehicle_photos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `vehicle_id` INT NOT NULL,
    `photo_url` VARCHAR(255) NOT NULL,
    `is_primary` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des achats (demandes et transactions d'achat)
CREATE TABLE IF NOT EXISTS `purchases` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `vehicle_id` INT NOT NULL,
    `buyer_id` INT NOT NULL,
    `seller_id` INT NOT NULL,
    `montant` DECIMAL(12, 2) NOT NULL,
    `statut` ENUM('en_attente', 'confirme', 'finalise', 'annule') DEFAULT 'en_attente',
    `message` TEXT,
    `telephone_contact` VARCHAR(30) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`buyer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`seller_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des locations (réservations et contrats de location)
CREATE TABLE IF NOT EXISTS `rentals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `vehicle_id` INT NOT NULL,
    `renter_id` INT NOT NULL,
    `owner_id` INT NOT NULL,
    `date_debut` DATE NOT NULL,
    `date_fin` DATE NOT NULL,
    `nb_jours` INT NOT NULL DEFAULT 1,
    `prix_par_jour` DECIMAL(12, 2) NOT NULL,
    `montant_total` DECIMAL(12, 2) NOT NULL,
    `caution` DECIMAL(12, 2) DEFAULT 0.00,
    `statut` ENUM('en_attente', 'validee', 'terminee', 'annulee') DEFAULT 'en_attente',
    `message` TEXT,
    `telephone_contact` VARCHAR(30) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`renter_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des favoris
CREATE TABLE IF NOT EXISTS `favorites` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `vehicle_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `user_vehicle_fav` (`user_id`, `vehicle_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- DONNÉES DE DÉMONSTRATION (DÉMO MUTUKA)
-- =============================================

-- Mot de passe par défaut : Pass1234!
INSERT INTO `users` (`id`, `nom`, `prenom`, `email`, `telephone`, `whatsapp`, `password`, `ville`, `role`, `email_verified`) VALUES
(1, 'Mutombo', 'Patrick', 'patrick.mutombo@mutuka.com', '+243810000001', '+243810000001', '$2y$10$w8B7o5o0uE352J0L.t6KpeM5j2P/q/Y0G3k8w4z6GgD7R1z6P9uKe', 'Kinshasa (Gombe)', 'admin', 1),
(2, 'Kabila', 'Sarah', 'sarah.kabila@mutuka.com', '+243890000002', '+243890000002', '$2y$10$w8B7o5o0uE352J0L.t6KpeM5j2P/q/Y0G3k8w4z6GgD7R1z6P9uKe', 'Lubumbashi', 'user', 1),
(3, 'Tshisekedi', 'Alain', 'alain.t@mutuka.com', '+243820000003', '+243820000003', '$2y$10$w8B7o5o0uE352J0L.t6KpeM5j2P/q/Y0G3k8w4z6GgD7R1z6P9uKe', 'Goma', 'user', 1)
ON DUPLICATE KEY UPDATE `id` = `id`;

-- Véhicules de démonstration
INSERT INTO `vehicles` (`id`, `user_id`, `type_transaction`, `type_vehicule`, `titre`, `marque`, `modele`, `annee`, `kilometrage`, `carburant`, `boite_vitesse`, `couleur`, `nombre_places`, `prix`, `caution`, `ville`, `adresse`, `description`, `statut`, `vues`) VALUES
(1, 1, 'vente', 'suv', 'Toyota Land Cruiser Prado TXL 2022', 'Toyota', 'Land Cruiser Prado', 2022, 38500, 'diesel', 'automatique', 'Noir Métallisé', 7, 52000.00, 0.00, 'Kinshasa', 'Boulevard du 30 Juin, Gombe', 'Magnifique Toyota Prado TXL en excellent état. Entretien régulier en concession, intérieur cuir impeccable, toit ouvrant, climatisation multi-zone, caméra 360°, pneus neufs. Papiers complets en règle.', 'disponible', 142),
(2, 1, 'vente', 'voiture', 'Mercedes-Benz Classe C 300 AMG Line 2021', 'Mercedes-Benz', 'Classe C 300', 2021, 45000, 'essence', 'automatique', 'Gris Sélénite', 5, 36500.00, 0.00, 'Kinshasa', 'Quartier Macampagne, Ngaliema', 'Mercedes Classe C avec finition AMG Line. Écran panoramique MBUX, son Burmester, pack éclairage d\'ambiance 64 couleurs, jantes 19 pouces AMG. Véhicule non accidenté, première main.', 'disponible', 98),
(3, 2, 'location', 'utilitaire', 'Toyota Hilux Revo Double Cabine 4x4', 'Toyota', 'Hilux Revo', 2023, 22000, 'diesel', 'manuelle', 'Blanc Pur', 5, 95.00, 300.00, 'Kinshasa', 'Avenue du Port, Gombe', 'Robuste 4x4 idéal pour vos déplacements urbains ou missions en province. Moteur 2.8L D-4D puissant, climatisation renforcée, attache remorque, réservoir supplémentaire. Disponible avec ou sans chauffeur.', 'disponible', 230),
(4, 2, 'location', 'suv', 'Hyundai Tucson 1.6 T-GDi Executive 2023', 'Hyundai', 'Tucson', 2023, 16000, 'essence', 'automatique', 'Bleu Nuit', 5, 75.00, 200.00, 'Lubumbashi', 'Avenue Mama Yemo', 'SUV moderne et très confortable pour vos séjours professionnels ou en famille. Consommation économique, Apple CarPlay / Android Auto sans fil, aide au stationnement.', 'disponible', 85),
(5, 1, 'vente', 'suv', 'Range Rover Sport HSE Dynamic V6 Supercharged', 'Land Rover', 'Range Rover Sport', 2020, 54000, 'essence', 'automatique', 'Blanc Fuji', 5, 59000.00, 0.00, 'Kinshasa', 'Gombe', 'Véhicule de prestige, suspension pneumatique adaptative, système audio Meridian Surround, sièges massants ventilés, historique limpide.', 'disponible', 165)
ON DUPLICATE KEY UPDATE `id` = `id`;

-- Photos des véhicules dans assets/
INSERT INTO `vehicle_photos` (`vehicle_id`, `photo_url`, `is_primary`) VALUES
(1, 'assets/Toyota_Land_Cruiser2022.jpg', 1),
(2, 'assets/Mercedes_ClasseC2021.jpg', 1),
(3, 'assets/Toyota_Hilux.jpg', 1),
(4, 'assets/Hyundai_Tucson.jpg', 1),
(5, 'assets/Ranger_RoverV6.jpg', 1);
