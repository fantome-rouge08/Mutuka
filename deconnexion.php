<?php
/**
 * deconnexion.php
 * Déconnexion sécurisée de la session utilisateur.
 */
require_once __DIR__ . '/includes/helpers.php';

logoutUser();
setFlash('info', 'Vous avez été déconnecté avec succès.');
header('Location: ' . url('index.php'));
exit;
