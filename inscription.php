<?php
/**
 * inscription.php
 * Création de compte utilisateur sur Mutuka.com.
 */
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/database.php';

if (isLoggedIn()) {
    header('Location: ' . url('compte/index.php'));
    exit;
}

$errors = [];
$formData = [
    'nom' => '',
    'prenom' => '',
    'email' => '',
    'telephone' => '',
    'whatsapp' => '',
    'ville' => 'Kinshasa'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['nom'] = trim($_POST['nom'] ?? '');
    $formData['prenom'] = trim($_POST['prenom'] ?? '');
    $formData['email'] = trim($_POST['email'] ?? '');
    $formData['telephone'] = trim($_POST['telephone'] ?? '');
    $formData['whatsapp'] = trim($_POST['whatsapp'] ?? '');
    $formData['ville'] = trim($_POST['ville'] ?? 'Kinshasa');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    // Validations
    if (empty($formData['nom']) || empty($formData['prenom'])) {
        $errors[] = "Veuillez renseigner votre nom et prénom.";
    }
    if (empty($formData['email']) || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Veuillez fournir une adresse e-mail valide.";
    }
    if (empty($formData['telephone'])) {
        $errors[] = "Le numéro de téléphone est requis pour vos contacts d'annonces.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
    }
    if ($password !== $passwordConfirm) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    // Vérification de l'unicité de l'email
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$formData['email']]);
        if ($stmt->fetch()) {
            $errors[] = "Cette adresse e-mail est déjà associée à un compte.";
        }
    }

    // Insertion
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $whatsappNumber = !empty($formData['whatsapp']) ? $formData['whatsapp'] : $formData['telephone'];

        $stmt = $pdo->prepare("INSERT INTO users (nom, prenom, email, telephone, whatsapp, password, ville, role) VALUES (?, ?, ?, ?, ?, ?, ?, 'user')");
        $stmt->execute([
            $formData['nom'],
            $formData['prenom'],
            $formData['email'],
            $formData['telephone'],
            $whatsappNumber,
            $hash,
            $formData['ville']
        ]);

        $userId = $pdo->lastInsertId();

        // // Générer et envoyer OTP
        // $otp = generateOtp();
        // storeOtp($pdo, $userId, $otp);
        // $emailSent = sendOtpEmail($formData['email'], $formData['prenom'], $otp);
        
        // // Stocker timestamp pour cooldown resend
        // $_SESSION['otp_last_sent'] = time();
        // $_SESSION['otp_pending_email'] = $formData['email'];

        // if ($emailSent) {
        //     setFlash('success', "Un code de vérification à 6 chiffres a été envoyé à <strong>" . e($formData['email']) . "</strong>. Vérifiez votre boîte de réception (et vos spams).");
        // } else {
        //     setFlash('warning', "Votre compte a été créé mais l'envoi de l'email a échoué. Utilisez le bouton \"Renvoyer le code\" sur la page de vérification.");
        // }
        
        // header('Location: ' . url("verifier.php?email=" . urlencode($formData['email'])));
        // exit;
    }
}

$pageTitle = "Créer un compte — Mutuka.com";
require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="form-card">
        <div class="section-head" style="margin-bottom: var(--space-4);">
            <p class="eyebrow">Inscription gratuite</p>
            <h2>Rejoignez la communauté Mutuka</h2>
            <p>Achetez, vendez et louez vos véhicules en toute transparence et sécurité.</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="flash-alert flash-error" style="margin-bottom: var(--space-4);">
                <div>
                    <strong>Veuillez corriger les erreurs suivantes :</strong>
                    <ul style="margin: 6px 0 0 18px; padding: 0;">
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo e($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <form action="<?php echo url('inscription.php'); ?>" method="post">
            <div class="form-row">
                <div class="form-group">
                    <label for="prenom">Prénom <span class="req">*</span></label>
                    <input type="text" id="prenom" name="prenom" value="<?php echo e($formData['prenom']); ?>" required placeholder="Ex: Jean" />
                </div>
                <div class="form-group">
                    <label for="nom">Nom de famille <span class="req">*</span></label>
                    <input type="text" id="nom" name="nom" value="<?php echo e($formData['nom']); ?>" required placeholder="Ex: Mukendi" />
                </div>
            </div>

            <div class="form-group">
                <label for="email">Adresse e-mail <span class="req">*</span></label>
                <input type="email" id="email" name="email" value="<?php echo e($formData['email']); ?>" required placeholder="jean.mukendi@exemple.com" />
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="telephone">Téléphone d'appel <span class="req">*</span></label>
                    <input type="tel" id="telephone" name="telephone" value="<?php echo e($formData['telephone']); ?>" required placeholder="+243 81 000 0000" />
                    <span class="form-help">Visible par les acheteurs ou loueurs intéressés</span>
                </div>
                <div class="form-group">
                    <label for="whatsapp">Numéro WhatsApp</label>
                    <input type="tel" id="whatsapp" name="whatsapp" value="<?php echo e($formData['whatsapp']); ?>" placeholder="+243 81 000 0000" />
                    <span class="form-help">Pour recevoir des messages directs en 1 clic</span>
                </div>
            </div>

            <div class="form-group">
                <label for="ville">Ville de résidence</label>
                <select id="ville" name="ville">
                    <option value="Kinshasa" <?php echo ($formData['ville'] === 'Kinshasa') ? 'selected' : ''; ?>>Kinshasa</option>
                    <option value="Lubumbashi" <?php echo ($formData['ville'] === 'Lubumbashi') ? 'selected' : ''; ?>>Lubumbashi</option>
                    <option value="Goma" <?php echo ($formData['ville'] === 'Goma') ? 'selected' : ''; ?>>Goma</option>
                    <option value="Kolwezi" <?php echo ($formData['ville'] === 'Kolwezi') ? 'selected' : ''; ?>>Kolwezi</option>
                    <option value="Matadi" <?php echo ($formData['ville'] === 'Matadi') ? 'selected' : ''; ?>>Matadi</option>
                    <option value="Autre" <?php echo ($formData['ville'] === 'Autre') ? 'selected' : ''; ?>>Autre ville</option>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Mot de passe <span class="req">*</span></label>
                    <input type="password" id="password" name="password" required placeholder="Minimum 6 caractères" />
                </div>
                <div class="form-group">
                    <label for="password_confirm">Confirmer le mot de passe <span class="req">*</span></label>
                    <input type="password" id="password_confirm" name="password_confirm" required placeholder="Répéter le mot de passe" />
                </div>
            </div>

            <button type="submit" class="btn btn-accent btn-lg btn-block" style="margin-top: var(--space-4);">
                Créer mon compte
            </button>
        </form>

        <p style="text-align: center; margin-top: var(--space-4); font-size: 0.92rem;">
            Vous possédez déjà un compte ? 
            <a href="<?php echo url('connexion.php'); ?>" style="color: var(--accent-dark); font-weight: 600;">Se connecter</a>
        </p>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
