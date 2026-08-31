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
