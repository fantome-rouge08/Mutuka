# Mutuka.com — Plateforme Automobile (Vente & Location)

Bienvenue sur **Mutuka.com**, la plateforme web complète développée en PHP / MySQL / HTML / CSS / JavaScript pour acheter, vendre et louer des véhicules (voitures, SUV, utilitaires, motos) en toute confiance.

---

##  Fonctionnalités Complètes

1. **Système de Compte & Authentification** :
   - Inscription complète (`inscription.php`) avec mot de passe sécurisé (hashage `bcrypt`) et validation d'email unique.
   - Connexion (`connexion.php`) avec gestion de sessions et déconnexion sécurisée (`deconnexion.php`).
   - Détection automatique de l'utilisateur connecté dans l'en-tête et les actions.

2. **Séparation Stricte Vente & Location** :
   - **Vente de véhicules (`vente.php`)** : Catalogue dédié avec filtres par marque/modèle, type de carrosserie, prix min/max, énergie (essence, diesel, hybride, électrique), boîte de vitesses et ville.
   - **Location de véhicules (`location.php`)** : Catalogue dédié avec prix par jour, gestion des cautions de garantie, filtres par budget journalier et type de véhicule.

3. **Fiche Détaillée & Actions d'Achat / Location (`details.php`)** :
   - Galerie photos interactive avec prévisualisation plein format et vignettes cliquables.
   - Caractéristiques techniques complètes (kilométrage, carburant, transmission, nombre de places, etc.).
   - Contact direct du propriétaire en 1 clic : **Bouton WhatsApp pré-rempli** et **Bouton Appel direct**.
   - **Module Demande d'Achat** : Formulaire modal pour formuler une offre enregistrée dans la base de données.
   - **Simulateur & Réservation de Location** : Sélecteur de dates avec calcul automatique en direct du nombre de jours et du montant total.

4. **Publication d'Annonces (`publier.php`)** :
   - Choix intuitif Vente ou Location avec adaptation automatique des champs (prix vs tarif/jour + caution).
   - Formulaire complet de spécifications du véhicule.
   - Upload de photos multiples avec prévisualisation dynamique immédiate.

5. **Espace Personnel « Mon Compte » (`compte/index.php`)** :
   - Vue d'ensemble avec statistiques (annonces, transactions, demandes reçues).
   - **Mes Annonces** : Liste de ses véhicules avec modification rapide du statut (*Disponible*, *Réservé*, *Vendu*, *Loué*) et suppression.
   - **Mes Achats** : Suivi de toutes ses demandes d'achat formulées.
   - **Mes Locations** : Historique et dates de ses réservations de location.
   - **Demandes Reçues** : Gestion des demandes d'achat et de location envoyées par des clients sur ses véhicules (avec possibilité de confirmer, finaliser ou refuser).
   - **Mon Profil** : Modification de ses coordonnées et de son mot de passe.

6. **Base de Données SQL Relationnelle (`database/mutuka.sql` & `config/database.php`)** :
   - Tables : `users`, `vehicles`, `vehicle_photos`, `purchases`, `rentals`, `favorites`.
   - Initialisation automatique : la base de données et les données de démonstration s'initialisent automatiquement dès le premier chargement.

---

## Comment lancer le projet avec WAMP / XAMPP

1. Assurez-vous que votre serveur **WampServer** (ou **XAMPP**) est démarré avec les services **Apache** et **MySQL** activés (icône verte).
2. Ouvrez votre navigateur web et accédez à l'URL :
   ```
   http://localhost/mutuka-php-etape1/
   ```
   *(ou `http://localhost/mutuka/` si vous avez renommé le dossier)*

3. **Compte de test pré-configuré** (pour tester immédiatement) :
   - **Email** : `patrick.mutombo@mutuka.com`
   - **Mot de passe** : `Pass1234!`

---

## Design & Ergonomie

- **Élégance & Sobriété** : Palette épurée à dominante ivoire, noir profond et touches de bronze chaud (`#A9814A`).
- **Typographie** : Fraunces (titres) & Inter (lisibilité technique).
- **Responsive Mobile First** : Menu hamburger fluide, mise en page adaptative et boutons tactiles larges.
