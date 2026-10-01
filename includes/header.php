<?php
/**
 * includes/header.php
 * En-tête global du site Mutuka.com : navigation, session, mode sombre, alertes flash.
 */
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../config/database.php';

if (!isset($pageTitle)) {
    $pageTitle = "Mutuka.com — Achetez, vendez et louez votre véhicule en toute confiance";
}

$currentUser = getCurrentUser();
$isAuth = ($currentUser !== null);
$flash = getFlash();

$currentScript = basename($_SERVER['SCRIPT_NAME']);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Script préventif pour appliquer le mode sombre instantanément sans clignotement -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('mutuka_theme');
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
                document.documentElement.setAttribute('data-theme', 'dark');
            } else {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
        window.MUTUKA_IS_AUTH = <?php echo $isAuth ? 'true' : 'false'; ?>;
        window.MUTUKA_BASE_URL = "<?php echo getBaseUrl(); ?>";
    </script>

    <!-- Police simple, moderne et épurée -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo url('css/style.css?v=' . filemtime(__DIR__ . '/../css/style.css')); ?>" />
</head>
<body>

<header class="site-header">
    <div class="header-inner">
        <a href="<?php echo url('index.php'); ?>" class="logo">
            MUTUKA<span class="logo-accent">.COM</span>
        </a>

        <nav class="main-nav" id="main-nav">
            <a href="<?php echo url('index.php'); ?>" class="nav-link <?php echo ($currentScript === 'index.php') ? 'active' : ''; ?>">Accueil</a>
            <a href="<?php echo url('vente.php'); ?>" class="nav-link <?php echo ($currentScript === 'vente.php') ? 'active' : ''; ?>">Vente</a>
            <a href="<?php echo url('location.php'); ?>" class="nav-link <?php echo ($currentScript === 'location.php') ? 'active' : ''; ?>">Location</a>
            
            <?php if ($isAuth): ?>
                <a href="<?php echo url('compte/index.php'); ?>" class="nav-link <?php echo ($currentScript === 'index.php' && strpos($_SERVER['SCRIPT_NAME'], 'compte') !== false) ? 'active' : ''; ?>">
                    Mon Compte
                </a>
            <?php else: ?>
                <a href="<?php echo url('connexion.php'); ?>" class="nav-link <?php echo ($currentScript === 'connexion.php') ? 'active' : ''; ?>">Connexion</a>
                <a href="<?php echo url('inscription.php'); ?>" class="nav-link <?php echo ($currentScript === 'inscription.php') ? 'active' : ''; ?>">Inscription</a>
            <?php endif; ?>
        </nav>

        <div class="header-actions">
            <!-- Bouton Mode Sombre -->
            <button type="button" class="theme-toggle" id="theme-toggle-btn" aria-label="Changer de thème" title="Activer / Désactiver le mode sombre">
                <svg class="sun-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                <svg class="moon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
            </button>

            <?php if ($isAuth): ?>
                <div class="user-greeting">
                    <span>Bonjour, <strong><?php echo e($currentUser['prenom']); ?></strong></span>
                    <!-- Bouton Déconnexion avec icône rouge -->
                    <a href="<?php echo url('deconnexion.php'); ?>" class="btn-logout" title="Se déconnecter de votre compte">
                        <svg class="logout-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        <span>Déconnexion</span>
                    </a>
                </div>
            <?php endif; ?>

            <a href="<?php echo url('publier.php'); ?>" class="btn btn-accent header-publish-btn">
                <span class="btn-text-full">+ Publier une annonce</span>
                <span class="btn-text-short">+ Publier</span>
            </a>
            
            <button class="burger" id="burger" aria-label="Ouvrir le menu" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>

    <!-- Menu Mobile -->
    <nav class="mobile-nav" id="mobile-nav">
        <a href="<?php echo url('index.php'); ?>" class="nav-link">Accueil</a>
        <a href="<?php echo url('vente.php'); ?>" class="nav-link">Vente de véhicules</a>
        <a href="<?php echo url('location.php'); ?>" class="nav-link">Location de véhicules</a>
        
        <?php if ($isAuth): ?>
            <a href="<?php echo url('compte/index.php'); ?>" class="nav-link font-bold">Mon ComptentUser['prenom']); ?>)</a>
            <a href="<?php echo url('deconnexion.php'); ?>" class="btn-logout" style="margin: 8px 0; align-self: flex-start;">
                <svg class="logout-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                <span>Se déconnecter</span>
            </a>
        <?php else: ?>
            <a href="<?php echo url('connexion.php'); ?>" class="nav-link">Se connecter</a>
            <a href="<?php echo url('inscription.php'); ?>" class="nav-link">Créer un compte</a>
        <?php endif; ?>

        <div style="display: flex; align-items: center; justify-content: space-between; margin-top: var(--space-3); padding-top: var(--space-3); border-top: 1px solid var(--border);">
            <span style="font-size: 0.9rem; color: var(--muted);">Thème d'affichage</span>
            <button type="button" class="theme-toggle" id="mobile-theme-toggle" aria-label="Changer de thème">
                <svg class="sun-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: none;"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                <svg class="moon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
            </button>
        </div>

        <a href="<?php echo url('publier.php'); ?>" class="btn btn-accent mobile-cta">+ Publier une annonce</a>
    </nav>
</header>

<?php if ($flash): ?>
    <div class="flash-container">
        <div class="flash-alert flash-<?php echo e($flash['type']); ?>">
            <div class="flash-icon">
                <?php if ($flash['type'] === 'success'): ?>
                    ✓
                <?php elseif ($flash['type'] === 'error'): ?>
                    ✕
                <?php else: ?>
                    ℹ
                <?php endif; ?>
            </div>
            <div class="flash-text"><?php echo e($flash['message']); ?></div>
            <button class="flash-close" onclick="this.parentElement.remove()">×</button>
        </div>
    </div>
<?php endif; ?>
