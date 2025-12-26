<?php
session_start();
require_once '../classes/Utilisateur.php';

// Check for registration success message
$success = '';
if (isset($_SESSION['registration_success'])) {
    $success = $_SESSION['registration_success'];
    unset($_SESSION['registration_success']);
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Basic validation
        if (empty($email) || empty($password)) {
            throw new Exception("Tous les champs sont obligatoires");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Email invalide");
        }

        // Attempt login
        $user = Utilisateur::signin($email, $password);

        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['nom'] = $user['nom'];
        $_SESSION['prenom'] = $user['prenom'];
        $_SESSION['email'] = $user['email'];

        // Redirect based on role
        if ($user['role'] === 'coach') {
            header('Location: dashboard.php');
        } else {
            header('Location: sportif_dashboard.php');
        }
        exit();
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
    <title>Connexion - Sport Manager</title>
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
            max-width: 450px;
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

        input[type="email"],
        input[type="password"] {
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

        input::placeholder {
            color: #808090;
        }

        input:focus {
            outline: none;
            border-color: #00d4ff;
            background: rgba(0, 212, 255, 0.08);
            box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.1);
        }

        .forgot-password {
            text-align: right;
            margin-bottom: 24px;
        }

        .forgot-password a {
            color: #00d4ff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .forgot-password a:hover {
            color: #00e8ff;
            text-decoration: underline;
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

        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .divider {
            text-align: center;
            margin: 28px 0;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
        }

        .divider span {
            background: rgba(255, 255, 255, 0.08);
            padding: 0 15px;
            position: relative;
            color: #808090;
            font-size: 13px;
            font-weight: 600;
        }

        .register-link {
            text-align: center;
            margin-top: 24px;
            color: #a0a0b0;
            font-size: 14px;
        }

        .register-link a {
            color: #00d4ff;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .register-link a:hover {
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
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Connexion</h1>
        <p class="subtitle">Bienvenue ! Connectez-vous à votre compte</p>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm">
            <div class="form-group">
                <label for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    placeholder="votre@email.com">
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    placeholder="••••••••">
            </div>

            <div class="forgot-password">
                <a href="#">Mot de passe oublié ?</a>
            </div>

            <button type="submit">Se connecter</button>
        </form>

        <div class="divider">
            <span>OU</span>
        </div>

        <div class="register-link">
            Vous n'avez pas de compte ? <a href="register.php">S'inscrire</a>
        </div>
    </div>

    <script>
        // Add loading state to button on submit
        document.getElementById('loginForm').addEventListener('submit', function() {
            const button = this.querySelector('button[type="submit"]');
            button.disabled = true;
            button.textContent = 'Connexion en cours...';
        });
    </script>
</body>

</html>