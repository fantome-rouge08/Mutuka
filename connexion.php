<?php
/**
 * connexion.php
 * Connexion à l'espace utilisateur Mutuka.com.
 */
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/database.php';

if (isLoggedIn()) {
    header('Location: ' . url('compte/index.php'));
    exit;
}

$error = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Veuillez renseigner votre adresse e-mail et votre mot de passe.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Vérifier si l'email est vérifié
            // if ((int)$user['email_verified'] !== 1) {
            //     // Renvoi OTP si expiré ou absent
            //     if (!$user['otp_expires_at'] || strtotime($user['otp_expires_at']) < time()) {
            //         $otp = generateOtp();
            //         storeOtp($pdo, $user['id'], $otp);
            //         sendOtpEmail($user['email'], $user['prenom'], $otp);
            //         $_SESSION['otp_last_sent'] = time();
            //     }
            //     $_SESSION['otp_pending_email'] = $user['email'];
            //     header('Location: ' . url("verifier.php?email=" . urlencode($user['email']) . "&login=1"));
            //     exit;
            // }

            // Création de la session sécurisée
            $_SESSION['user'] = [
                'id' => $user['id'],
                'nom' => $user['nom'],
                'prenom' => $user['prenom'],
                'email' => $user['email'],
                'telephone' => $user['telephone'],
                'whatsapp' => $user['whatsapp'],
                'ville' => $user['ville'],
                'role' => $user['role']
            ];

            setFlash('success', "Ravi de vous revoir, {$user['prenom']} !");

            $redirect = $_SESSION['redirect_after_login'] ?? url('compte/index.php');
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = "Adresse e-mail ou mot de passe incorrect.";
        }
    }
}

$pageTitle = "Connexion — Mutuka.com";
require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="form-card form-card-auth">
        <div class="section-head" style="margin-bottom: var(--space-4);">
            <p class="eyebrow">Espace Membre</p>
            <h2>Connexion</h2>
            <p>Accédez à vos annonces, achats et locations en cours.</p>
        </div>

        <?php if ($error): ?>
            <div class="flash-alert flash-error" style="margin-bottom: var(--space-4);">
                <div class="flash-icon">✕</div>
                <div class="flash-text"><?php echo e($error); ?></div>
            </div>
        <?php endif; ?>

        <form action="<?php echo url('connexion.php'); ?>" method="post">
            <div class="form-group">
                <label for="email">Adresse e-mail</label>
                <input type="email" id="email" name="email" value="<?php echo e($email); ?>" required autofocus placeholder="votre.email@exemple.com" />
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required placeholder="Votre mot de passe" />
            </div>

            <button type="submit" class="btn btn-accent btn-lg btn-block" style="margin-top: var(--space-4);">
                Se connecter
            </button>
        </form>

        <div style="background: var(--surface-alt); padding: var(--space-3); border-radius: var(--radius-sm); margin-top: var(--space-4); font-size: 0.82rem; border: 1px dashed var(--border);">
            <strong>Compte de test pré-configuré :</strong><br>
            Email: <code>patrick.mutombo@mutuka.com</code><br>
            Mot de passe: <code>Pass1234!</code>
        </div>

        <p style="text-align: center; margin-top: var(--space-4); font-size: 0.92rem;">
            Pas encore de compte ? 
            <a href="<?php echo url('inscription.php'); ?>" style="color: var(--accent-dark); font-weight: 600;">Créer un compte</a>
        </p>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
