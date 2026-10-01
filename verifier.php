<?php
/**
 * verifier.php
 * Vérification du code OTP envoyé par email pour activer le compte.
 */
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/database.php';

if (isLoggedIn()) {
    header('Location: ' . url('compte/index.php'));
    exit;
}

$email = trim($_GET['email'] ?? '');
$isLoginFlow = isset($_GET['login']);
$errors = [];
$success = false;
$locked = false;
$resendCooldown = 0;

if (empty($email)) {
    setFlash('error', 'Email manquant. Veuillez recommencer l\'inscription.');
    header('Location: ' . url('inscription.php'));
    exit;
}

// Récupérer l'utilisateur
$user = getUserByEmail($pdo, $email);
if (!$user) {
    setFlash('error', 'Aucun compte trouvé pour cet email.');
    header('Location: ' . url('inscription.php'));
    exit;
}

// Déjà vérifié ?
if ((int)$user['email_verified'] === 1) {
    if ($isLoginFlow) {
        header('Location: ' . url('connexion.php'));
    } else {
        header('Location: ' . url('compte/index.php'));
    }
    exit;
}

// Calculer cooldown resend
$lastSent = $_SESSION['otp_last_sent'] ?? 0;
$resendCooldown = max(0, OTP_RESEND_COOLDOWN - (time() - $lastSent));

// Traitement POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Vérification du code OTP
    if ($action === 'verify') {
        $otp = trim($_POST['otp'] ?? '');
        
        if (empty($otp) || strlen($otp) !== 6 || !ctype_digit($otp)) {
            $errors[] = 'Veuillez saisir le code à 6 chiffres.';
        } else {
            $result = verifyOtp($pdo, $user['id'], $otp);
            
            if ($result['valid']) {
                // Succès : nettoyer OTP, marquer vérifié, connecter
                clearOtp($pdo, $user['id']);
                
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
                
                // Envoyer email de bienvenue (async, non bloquant)
                sendWelcomeEmail($user['email'], $user['prenom']);
                
                unset($_SESSION['otp_pending_email'], $_SESSION['otp_last_sent']);
                
                if ($isLoginFlow) {
                    setFlash('success', "Connexion réussie ! Bienvenue {$user['prenom']}.");
                    header('Location: ' . url('compte/index.php'));
                } else {
                    setFlash('success', "Votre compte a été vérifié avec succès ! Bienvenue sur Mutuka, {$user['prenom']}.");
                    header('Location: ' . url('compte/index.php'));
                }
                exit;
            } else {
                $errors[] = $result['message'];
                $locked = $result['locked'];
            }
        }
    }
    
    // Renvoi du code
    if ($action === 'resend') {
        if (!canResendOtp()) {
            $errors[] = 'Veuillez patienter ' . $resendCooldown . ' seconde(s) avant de demander un nouveau code.';
        } else {
            $otp = generateOtp();
            storeOtp($pdo, $user['id'], $otp);
            $sent = sendOtpEmail($user['email'], $user['prenom'], $otp);
            
            if ($sent) {
                $_SESSION['otp_last_sent'] = time();
                $success = 'Un nouveau code a été envoyé à votre adresse email.';
                $resendCooldown = OTP_RESEND_COOLDOWN;
            } else {
                $errors[] = 'Erreur lors de l\'envoi. Veuillez réessayer dans quelques instants.';
            }
        }
    }
}

$pageTitle = "Vérification de votre compte — Mutuka.com";
require_once __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-top: var(--space-5);">
    <div class="form-card form-card-auth" style="max-width: 480px;">
        <div class="section-head" style="margin-bottom: var(--space-5); text-align: center;">
            <div style="width: 72px; height: 72px; background: linear-gradient(135deg, #A9814A 0%, #1e2024 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-4); font-size: 28px;">
                📧
            </div>
            <p class="eyebrow">Vérification Email</p>
            <h2 style="margin-bottom: 8px;">Vérifiez votre adresse email</h2>
            <p style="color: var(--muted); margin: 0;">Nous avons envoyé un code à 6 chiffres à :</p>
            <p style="font-weight: 600; color: var(--ink); margin-top: 4px; word-break: break-all;"><?php echo e($email); ?></p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="flash-alert flash-error" style="margin-bottom: var(--space-4);">
                <div style="display: flex; gap: 10px;">
                    <span class="flash-icon">✕</span>
                    <div class="flash-text">
                        <?php foreach ($errors as $err): ?>
                            <div><?php echo e($err); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="flash-alert flash-success" style="margin-bottom: var(--space-4);">
                <div style="display: flex; gap: 10px;">
                    <span class="flash-icon">✓</span>
                    <div class="flash-text"><?php echo e($success); ?></div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Formulaire saisie OTP -->
        <?php if (!$success || (isset($result) && !$result['valid'])): ?>
        <form action="<?php echo url("verifier.php?email=" . urlencode($email) . ($isLoginFlow ? '&login=1' : '')); ?>" method="post" id="otp-form">
            <input type="hidden" name="action" value="verify" />
            
            <div class="form-group" style="margin-bottom: var(--space-4);">
                <label style="display: block; margin-bottom: 10px; font-weight: 600; color: var(--ink);">Code de vérification <span class="req">*</span></label>
                <div style="display: flex; gap: 8px; justify-content: center;" id="otp-inputs">
                    <input type="text" name="otp" id="otp-full" maxlength="6" pattern="\d{6}" inputmode="numeric" autocomplete="one-time-code" required style="width: 100%; text-align: center; letter-spacing: 8px; font-size: 1.5rem; font-family: 'Courier New', monospace; font-weight: 600; padding: 16px; border: 2px solid var(--border); border-radius: var(--radius);" placeholder="000000" aria-label="Code de vérification à 6 chiffres" />
                </div>
                <p style="font-size: 0.82rem; color: var(--muted); text-align: center; margin-top: 8px;">Saisissez les 6 chiffres reçus par email</p>
            </div>

            <?php if ($locked): ?>
                <div style="background: var(--warning-bg); border: 1px solid var(--warning); color: var(--warning); padding: var(--space-3); border-radius: var(--radius-sm); text-align: center; font-size: 0.9rem; font-weight: 600; margin-bottom: var(--space-4);">
                    ⏳ Compte temporairement bloqué. Réessayez plus tard.
                </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-accent btn-lg btn-block" style="margin-bottom: var(--space-4);" <?php echo $locked ? 'disabled' : ''; ?>>
                <?php echo $locked ? 'Compte bloqué' : 'Vérifier le code'; ?>
            </button>
        </form>

        <!-- Renvoi code -->
        <div style="text-align: center; padding-top: var(--space-4); border-top: 1px solid var(--border);">
            <form action="<?php echo url("verifier.php?email=" . urlencode($email) . ($isLoginFlow ? '&login=1' : '')); ?>" method="post" id="resend-form" style="display: inline;">
                <input type="hidden" name="action" value="resend" />
                <button type="submit" class="btn btn-outline btn-sm" id="resend-btn" <?php echo $resendCooldown > 0 ? 'disabled' : ''; ?> style="min-width: 200px;">
                    <span id="resend-text">Renvoyer le code</span>
                    <?php if ($resendCooldown > 0): ?>
                        <span id="resend-timer" style="margin-left: 8px;">(<span id="cooldown"><?php echo $resendCooldown; ?></span>s)</span>
                    <?php endif; ?>
                </button>
            </form>
            <p style="font-size: 0.82rem; color: var(--muted); margin-top: var(--space-3);">
                Vous n'avez pas reçu l'email ? Vérifiez vos spams ou <a href="<?php echo url("verifier.php?email=" . urlencode($email) . ($isLoginFlow ? '&login=1' : '')); ?>" style="color: var(--accent-dark); font-weight: 600;">demandez un nouveau code</a>.
            </p>
        </div>

        <p style="text-align: center; margin-top: var(--space-5); font-size: 0.9rem; color: var(--muted);">
            Retour à <a href="<?php echo url($isLoginFlow ? 'connexion.php' : 'inscription.php'); ?>" style="color: var(--accent-dark); font-weight: 600;"><?php echo $isLoginFlow ? 'la connexion' : "l'inscription" ?></a>
        </p>
        <?php endif; ?>
    </div>
</section>

<script>
// Auto-focus et navigation OTP
document.addEventListener('DOMContentLoaded', () => {
    const otpInput = document.getElementById('otp-full');
    if (otpInput) {
        otpInput.focus();
        
        // N'autoriser que les chiffres
        otpInput.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/\D/g, '');
        });
        
        // Soumettre automatiquement si 6 chiffres (optionnel)
        otpInput.addEventListener('input', (e) => {
            if (e.target.value.length === 6) {
                // Optionnel: document.getElementById('otp-form').submit();
            }
        });
    }

    // Timer cooldown resend
    let cooldown = <?php echo $resendCooldown; ?>;
    const timerEl = document.getElementById('cooldown');
    const resendBtn = document.getElementById('resend-btn');
    const resendText = document.getElementById('resend-text');
    
    if (cooldown > 0 && timerEl && resendBtn) {
        resendBtn.disabled = true;
        const interval = setInterval(() => {
            cooldown--;
            timerEl.textContent = cooldown;
            if (cooldown <= 0) {
                clearInterval(interval);
                resendBtn.disabled = false;
                resendText.textContent = 'Renvoyer le code';
                timerEl.parentElement.style.display = 'none';
            }
        }, 1000);
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>