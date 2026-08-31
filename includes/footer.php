<?php
/**
 * includes/footer.php
 * Pied de page partagé pour Mutuka.com.
 */
?>
<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <span class="logo">MUTUKA<span class="logo-accent">.COM</span></span>
            <p>La plateforme automobile de confiance pour l'achat, la vente et la location de véhicules sécurisés.</p>
            <div class="footer-badge-trust">
                <span>🛡️ Transactions & contacts vérifiés</span>
            </div>
        </div>
        <div class="footer-col">
            <h4>Transactions</h4>
            <a href="<?php echo url('vente.php'); ?>">Acheter un véhicule</a>
            <a href="<?php echo url('location.php'); ?>">Louer un véhicule</a>
            <a href="<?php echo url('publier.php'); ?>">Déposer une annonce</a>
        </div>
        <div class="footer-col">
            <h4>Espace Membre</h4>
            <?php if (isLoggedIn()): ?>
                <a href="<?php echo url('compte/index.php'); ?>">Mon tableau de bord</a>
                <a href="<?php echo url('compte/index.php#annonces'); ?>">Mes annonces</a>
                <a href="<?php echo url('compte/index.php#achats'); ?>">Mes achats & locations</a>
                <a href="<?php echo url('deconnexion.php'); ?>">Se déconnecter</a>
            <?php else: ?>
                <a href="<?php echo url('connexion.php'); ?>">Se connecter</a>
                <a href="<?php echo url('inscription.php'); ?>">Créer un compte gratuit</a>
            <?php endif; ?>
        </div>
        <div class="footer-col">
            <h4>Confiance & Support</h4>
            <span class="footer-info">Support 7j/7</span>
            <span class="footer-info">Séparation stricte vente / location</span>
            <span class="footer-info">Contact direct vendeur / loueur</span>
        </div>
    </div>
    <div class="footer-bottom">
        <div>© <?php echo date("Y"); ?> Mutuka.com — Tous droits réservés.</div>
        <div class="footer-bottom-links">
            <span>Plateforme automobile sobre, rapide et sécurisée.</span>
        </div>
    </div>
</footer>

<?php if (!isLoggedIn()): ?>
<!-- Modale informative : Création de compte requise pour publier / vendre -->
<div class="modal-backdrop" id="modal-auth-required" aria-hidden="true" role="dialog" aria-labelledby="modal-auth-title">
    <div class="modal-box" style="position: relative; max-width: 480px; text-align: center; padding: var(--space-6) var(--space-5);">
        <button type="button" data-close-modal class="modal-close" style="position: absolute; top: 14px; right: 16px; font-size: 1.6rem;" aria-label="Fermer la boîte de dialogue">✕</button>
        
        <div style="width: 60px; height: 60px; background: rgba(30, 114, 71, 0.12); color: var(--accent); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-3); font-size: 1.7rem;">
            🚗
        </div>
        
        <h3 id="modal-auth-title" style="font-size: 1.35rem; margin-bottom: var(--space-2); color: var(--text);">Création de compte requise</h3>
        
        <p style="color: var(--muted); font-size: 0.95rem; line-height: 1.5; margin-bottom: var(--space-5);">
            Pour publier une annonce ou vendre votre véhicule sur <strong>Mutuka.com</strong>, vous devez d'abord créer un compte gratuit (ou vous connecter si vous en possédez déjà un).
        </p>

        <div style="display: flex; flex-direction: column; gap: var(--space-3);">
            <a href="<?php echo url('inscription.php'); ?>" class="btn btn-accent btn-block" style="justify-content: center; padding: 12px; font-weight: 700;">
                + Créer un compte gratuit
            </a>
            
            <a href="<?php echo url('connexion.php'); ?>" class="btn btn-outline btn-block" style="justify-content: center; padding: 12px;">
                Déjà membre ? Se connecter
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="<?php echo url('js/script.js'); ?>"></script>
</body>
</html>
