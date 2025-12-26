<?php
session_start();
require_once '../classes/reservation.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if user is a sportif
if ($_SESSION['role'] !== 'sportif') {
    header('Location: dashboard.php'); // Redirect coaches to their dashboard
    exit();
}

$sportif_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'reserve':
                $seance_id = intval($_POST['seance_id'] ?? 0);
                $reservation = new Reservation($seance_id, $sportif_id);
                $reservation->reserver();
                $success = "Séance réservée avec succès !";
                break;

            case 'cancel':
                $seance_id = intval($_POST['seance_id'] ?? 0);
                $reservation = new Reservation($seance_id, $sportif_id);
                $reservation->annuler();
                $success = "Réservation annulée avec succès !";
                break;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get available sessions
$availableSessions = Reservation::getAvailableSessions();

// Get my reservations
$myReservations = Reservation::getBySportif($sportif_id);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Sportif - Sport Manager</title>
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
            color: #e0e0e0;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 28px;
            background: linear-gradient(135deg, #00d4ff 0%, #0099ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logout-btn {
            padding: 10px 20px;
            background: rgba(255, 71, 87, 0.2);
            color: #ff6b7a;
            border: 1px solid rgba(255, 71, 87, 0.3);
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: rgba(255, 71, 87, 0.3);
            transform: translateY(-2px);
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 25px;
            border-radius: 15px;
            text-align: center;
        }

        .stat-card h3 {
            font-size: 14px;
            color: #a0a0b0;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-card .number {
            font-size: 36px;
            font-weight: 700;
            background: linear-gradient(135deg, #00d4ff 0%, #0099ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }

        .tab {
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: #a0a0b0;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .tab.active {
            background: linear-gradient(135deg, rgba(0, 212, 255, 0.2), rgba(0, 153, 255, 0.1));
            border-color: #00d4ff;
            color: #00d4ff;
        }

        .tab:hover {
            background: rgba(255, 255, 255, 0.08);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 20px;
        }

        .card h2 {
            font-size: 22px;
            margin-bottom: 20px;
            color: #00d4ff;
        }

        .alert {
            padding: 14px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
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

        .sessions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .session-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 25px;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .session-card:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(0, 212, 255, 0.3);
            transform: translateY(-5px);
        }

        .coach-info {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .coach-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #00d4ff 0%, #0099ff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 700;
            color: #0f0f1e;
        }

        .coach-details h3 {
            color: #e0e0e0;
            font-size: 16px;
            margin-bottom: 5px;
        }

        .coach-discipline {
            color: #00d4ff;
            font-size: 13px;
            font-weight: 600;
        }

        .session-info {
            margin-bottom: 20px;
        }

        .info-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            color: #a0a0b0;
            font-size: 14px;
        }

        .info-row strong {
            color: #e0e0e0;
        }

        .description {
            color: #a0a0b0;
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .btn-reserve {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #00d4ff 0%, #0099ff 100%);
            color: #0f0f1e;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-reserve:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 212, 255, 0.35);
        }

        .btn-cancel {
            width: 100%;
            padding: 12px;
            background: rgba(255, 71, 87, 0.2);
            color: #ff6b7a;
            border: 1px solid rgba(255, 71, 87, 0.3);
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-cancel:hover {
            background: rgba(255, 71, 87, 0.3);
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #808090;
        }

        .empty-state svg {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .empty-state h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #a0a0b0;
        }

        .empty-state p {
            font-size: 14px;
        }

        .reservations-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .reservation-item {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 12px;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 20px;
            align-items: center;
        }

        .reservation-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        @media (max-width: 768px) {
            .sessions-grid {
                grid-template-columns: 1fr;
            }

            .reservation-item {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="header">
        <div>
            <h1>Dashboard Sportif</h1>
            <p style="color: #a0a0b0; margin-top: 5px;">Bienvenue, <?php echo htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?></p>
        </div>
        <div>
            <a href="logout.php" class="logout-btn">Déconnexion</a>
        </div>
    </div>

    <div class="stats-container">
        <div class="stat-card">
            <h3>Séances Disponibles</h3>
            <div class="number"><?php echo count($availableSessions); ?></div>
        </div>
        <div class="stat-card">
            <h3>Mes Réservations</h3>
            <div class="number"><?php echo count($myReservations); ?></div>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="tabs">
        <div class="tab active" onclick="switchTab('available')">
            🔍 Séances Disponibles
        </div>
        <div class="tab" onclick="switchTab('reservations')">
            📋 Mes Réservations
        </div>
    </div>

    <!-- Available Sessions Tab -->
    <div class="tab-content active" id="available-tab">
        <div class="card">
            <h2>🏃 Séances Disponibles</h2>
            <?php if (empty($availableSessions)): ?>
                <div class="empty-state">
                    <svg fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" />
                    </svg>
                    <h3>Aucune séance disponible</h3>
                    <p>Il n'y a pas de séances disponibles pour le moment. Revenez plus tard !</p>
                </div>
            <?php else: ?>
                <div class="sessions-grid">
                    <?php foreach ($availableSessions as $session): ?>
                        <div class="session-card">
                            <div class="coach-info">
                                <div class="coach-avatar">
                                    <?php echo strtoupper(substr($session['coach_prenom'], 0, 1) . substr($session['coach_nom'], 0, 1)); ?>
                                </div>
                                <div class="coach-details">
                                    <h3><?php echo htmlspecialchars($session['coach_prenom'] . ' ' . $session['coach_nom']); ?></h3>
                                    <div class="coach-discipline"><?php echo htmlspecialchars($session['coach_discipline']); ?></div>
                                </div>
                            </div>

                            <div class="session-info">
                                <div class="info-row">
                                    📅 <strong>Date:</strong> <?php
                                                                $date = new DateTime($session['date']);
                                                                echo $date->format('d/m/Y');
                                                                ?>
                                </div>
                                <div class="info-row">
                                    🕐 <strong>Heure:</strong> <?php echo substr($session['heure'], 0, 5); ?>
                                </div>
                                <div class="info-row">
                                    ⏱️ <strong>Durée:</strong> <?php echo $session['duree']; ?> minutes
                                </div>
                                <div class="info-row">
                                    🎯 <strong>Expérience:</strong> <?php echo $session['coach_experience']; ?> ans
                                </div>
                            </div>

                            <?php if (!empty($session['coach_description'])): ?>
                                <div class="description">
                                    <?php echo htmlspecialchars($session['coach_description']); ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="">
                                <input type="hidden" name="action" value="reserve">
                                <input type="hidden" name="seance_id" value="<?php echo $session['id']; ?>">
                                <button type="submit" class="btn-reserve">Réserver cette séance</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- My Reservations Tab -->
    <div class="tab-content" id="reservations-tab">
        <div class="card">
            <h2>📋 Mes Réservations</h2>
            <?php if (empty($myReservations)): ?>
                <div class="empty-state">
                    <svg fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                    </svg>
                    <h3>Aucune réservation</h3>
                    <p>Vous n'avez pas encore réservé de séance</p>
                </div>
            <?php else: ?>
                <div class="reservations-list">
                    <?php foreach ($myReservations as $reservation): ?>
                        <div class="reservation-item">
                            <div class="reservation-details">
                                <div>
                                    <div style="color: #a0a0b0; font-size: 12px; margin-bottom: 5px;">COACH</div>
                                    <div style="font-weight: 600; color: #00d4ff;">
                                        <?php echo htmlspecialchars($reservation['coach_prenom'] . ' ' . $reservation['coach_nom']); ?>
                                    </div>
                                    <div style="font-size: 13px; color: #a0a0b0;">
                                        <?php echo htmlspecialchars($reservation['coach_discipline']); ?>
                                    </div>
                                </div>
                                <div>
                                    <div style="color: #a0a0b0; font-size: 12px; margin-bottom: 5px;">DATE & HEURE</div>
                                    <div style="font-weight: 600;">
                                        <?php
                                        $date = new DateTime($reservation['date']);
                                        echo $date->format('d/m/Y');
                                        ?> à <?php echo substr($reservation['heure'], 0, 5); ?>
                                    </div>
                                </div>
                                <div>
                                    <div style="color: #a0a0b0; font-size: 12px; margin-bottom: 5px;">DURÉE</div>
                                    <div style="font-weight: 600;"><?php echo $reservation['duree']; ?> min</div>
                                </div>
                            </div>
                            <div>
                                <form method="POST" action="" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ?');">
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="seance_id" value="<?php echo $reservation['id']; ?>">
                                    <button type="submit" class="btn-cancel">Annuler</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            // Remove active class from all tabs and contents
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

            // Add active class to selected tab and content
            event.target.classList.add('active');
            document.getElementById(tab + '-tab').classList.add('active');
        }
    </script>
</body>

</html>