<?php
session_start();
require_once '../classes/coaches.php';
require_once '../classes/sportif.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nom = $_POST['nom'];;
        $prenom = $_POST['prenom'];
        $email = $_POST['email'];
        $password = $_POST['password'] ;
        $confirmPassword = $_POST['confirm_password'];
        $role = $_POST['role'] ;

        if (empty($nom) || empty($prenom) || empty($email) || empty($password)) {
            throw new Exception("Tous les champs sont obligatoires");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Email invalide");
        }

        if ($password !== $confirmPassword) {
            throw new Exception("Les mots de passe ne correspondent pas");
        }

        if (strlen($password) < 6) {
            throw new Exception("Le mot de passe doit contenir au moins 6 caractères");
        }

        if (Utilisateur::findByEmail($email)) {
            throw new Exception("Cet email est déjà utilisé");
        }

        if ($role === 'coach') {
            $discipline = $_POST['discipline'] ;
            $experience = $_POST['experience'];
            $description = $_POST['description'];

            if (empty($discipline)) {
                throw new Exception("La discipline est obligatoire pour les coaches");
            }

            $coach = new Coach($nom, $prenom, $email, $password, $discipline, $experience, $description);
            $coach->save();
        } elseif ($role === 'sportif') {
            $sportif = new Sportif($nom, $prenom, $email, $password);
            $sportif->save();
        } else {
            throw new Exception("Rôle invalide");
        }

        $success = "Inscription réussie ! Redirection vers la page de connexion...";

        header("refresh:0;url=login.php");
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Sport Manager</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', sans-serif;
            background: linear-gradient(135deg, #0f0f1e 0%, #1a1a2e 50%, #16213e 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: #e0e0e0;
        }

        .container {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 50px 40px;
            border-radius: 20px;
            width: 100%;
            max-width: 550px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
        }

        h1 {
            text-align: center;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 12px;
            background: linear-gradient(135deg, #00d4ff 0%, #0099ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.5px;
        }

        .subtitle {
            text-align: center;
            color: #a0a0b0;
            margin-bottom: 35px;
            font-size: 14px;
            font-weight: 500;
        }

        .alert {
            padding: 14px 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            font-weight: 500;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-error {
            background: rgba(255, 71, 87, 0.15);
            color: #ff6b7a;
            border: 1px solid rgba(255, 71, 87, 0.3);
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.15);
            color: #81c784;
            border: 1px solid rgba(76, 175, 80, 0.3);
        }

        .form-group {
            margin-bottom: 22px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #e0e0e0;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 10px;
            font-size: 14px;
            background: rgba(255, 255, 255, 0.05);
            color: #e0e0e0;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        input::placeholder,
        textarea::placeholder {
            color: #808090;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #00d4ff;
            background: rgba(0, 212, 255, 0.08);
            box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 90px;
        }

        .role-selection {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 28px;
        }

        .role-option {
            padding: 20px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.03);
            position: relative;
            overflow: hidden;
        }

        .role-option::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(0, 212, 255, 0.1), rgba(0, 153, 255, 0.05));
            transition: left 0.3s ease;
        }

        .role-option:hover {
            border-color: rgba(0, 212, 255, 0.3);
            background: rgba(0, 212, 255, 0.08);
        }

        .role-option:hover::before {
            left: 100%;
        }

        .role-option input[type="radio"] {
            display: none;
        }

        .role-option label {
            margin: 0;
            font-size: 15px;
            text-transform: none;
            letter-spacing: 0;
            font-weight: 600;
            cursor: pointer;
            position: relative;
            z-index: 1;
        }

        .role-option input[type="radio"]:checked+label {
            color: #00d4ff;
        }

        .role-option.selected {
            border-color: #00d4ff;
            background: linear-gradient(135deg, rgba(0, 212, 255, 0.15), rgba(0, 153, 255, 0.08));
            box-shadow: 0 8px 24px rgba(0, 212, 255, 0.2);
        }

        .role-option.selected::after {
            content: '✓';
            position: absolute;
            top: 8px;
            right: 12px;
            color: #00d4ff;
            font-size: 18px;
            font-weight: bold;
        }

        .coach-fields {
            display: none;
            padding: 24px;
            background: rgba(0, 212, 255, 0.08);
            border: 1px solid rgba(0, 212, 255, 0.2);
            border-radius: 12px;
            margin-bottom: 24px;
            animation: expand 0.4s ease-out;
        }

        @keyframes expand {
            from {
                opacity: 0;
                max-height: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                max-height: 500px;
                transform: translateY(0);
            }
        }

        .coach-fields.active {
            display: block;
        }

        .coach-fields .form-group {
            margin-bottom: 18px;
        }

        .coach-fields label {
            color: #b0d4ff;
        }

        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #00d4ff 0%, #0099ff 100%);
            color: #0f0f1e;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 8px 24px rgba(0, 212, 255, 0.25);
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(0, 212, 255, 0.35);
        }

        button:active {
            transform: translateY(0);
        }

        .login-link {
            text-align: center;
            margin-top: 24px;
            color: #a0a0b0;
            font-size: 14px;
        }

        .login-link a {
            color: #00d4ff;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .login-link a:hover {
            color: #00e8ff;
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .container {
                padding: 30px 24px;
            }

            h1 {
                font-size: 26px;
                margin-bottom: 8px;
            }

            .role-selection {
                grid-template-columns: 1fr;
            }

            .coach-fields {
                padding: 18px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Inscription</h1>
        <p class="subtitle">Créez votre compte pour démarrer</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        

        <form method="POST" action="" id="registerForm">
            <div class="form-group">
                <label>Je suis :</label>
                <div class="role-selection">
                    <div class="role-option" onclick="selectRole('sportif')">
                        <input type="radio" name="role" value="sportif" id="role-sportif" required>
                        <label for="role-sportif">Sportif</label>
                    </div>
                    <div class="role-option" onclick="selectRole('coach')">
                        <input type="radio" name="role" value="coach" id="role-coach" required>
                        <label for="role-coach">Coach</label>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="nom">Nom *</label>
                <input type="text" id="nom" name="nom" required value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="prenom">Prénom *</label>
                <input type="text" id="prenom" name="prenom" required value="<?php echo htmlspecialchars($_POST['prenom'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="password">Mot de passe *</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirmer le mot de passe *</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
            </div>

            <div class="coach-fields" id="coachFields">
                <div class="form-group">
                    <label for="discipline">Discipline *</label>
                    <input type="text" id="discipline" name="discipline" value="<?php echo htmlspecialchars($_POST['discipline'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="experience">Années d'expérience</label>
                    <input type="number" id="experience" name="experience" min="0" value="<?php echo htmlspecialchars($_POST['experience'] ?? '0'); ?>">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>
            </div>

            <button type="submit">S'inscrire</button>
        </form>

        <div class="login-link">
            Vous avez déjà un compte ? <a href="login.php">Se connecter</a>
        </div>
    </div>

    <script>
        function selectRole(role) {
            // Update radio selection
            document.getElementById('role-' + role).checked = true;

            // Update visual selection
            document.querySelectorAll('.role-option').forEach(option => {
                option.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');

            // Show/hide coach fields
            const coachFields = document.getElementById('coachFields');
            const disciplineInput = document.getElementById('discipline');

            if (role === 'coach') {
                coachFields.classList.add('active');
                disciplineInput.required = true;
            } else {
                coachFields.classList.remove('active');
                disciplineInput.required = false;
            }
        }

        // Maintain selection on page reload (if there was an error)
        <?php if (isset($_POST['role'])): ?>
            selectRole('<?php echo htmlspecialchars($_POST['role']); ?>');
        <?php endif; ?>
    </script>
</body>

</html>