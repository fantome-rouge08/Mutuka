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

<script src="<?php echo url('js/script.js'); ?>"></script>
</body>
</html>
