<?php
session_start();
require_once '../classes/seances.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if user is a coach
if ($_SESSION['role'] !== 'coach') {
    header('Location: sportif_dashboard.php'); // Redirect sportifs to their dashboard
    exit();
}

$coach_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'create':
                $date = $_POST['date'] ?? '';
                $heure = $_POST['heure'] ?? '';
                $duree = intval($_POST['duree'] ?? 0);

                if (empty($date) || empty($heure) || $duree <= 0) {
                    throw new Exception("Tous les champs sont obligatoires");
                }

                $seance = new Seance(null, $coach_id, $date, $heure, $duree);
                $seance->creer();
                $success = "Séance créée avec succès !";
                break;

            case 'update':
                $id = intval($_POST['id'] ?? 0);
                $date = $_POST['date'] ?? '';
                $heure = $_POST['heure'] ?? '';
                $duree = intval($_POST['duree'] ?? 0);
                $statut = $_POST['statut'] ?? 'disponible';

                if (empty($date) || empty($heure) || $duree <= 0) {
                    throw new Exception("Tous les champs sont obligatoires");
                }

                $seance = new Seance($id, $coach_id, $date, $heure, $duree, $statut);
                if ($seance->modifier()) {
                    $success = "Séance modifiée avec succès !";
                } else {
                    throw new Exception("Erreur lors de la modification");
                }
                break;

            case 'delete':
                $id = intval($_POST['id'] ?? 0);
                $seance = new Seance($id, $coach_id, null, null, null);
                if ($seance->supprimer()) {
                    $success = "Séance supprimée avec succès !";
                } else {
                    throw new Exception("Erreur lors de la suppression");
                }
                break;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get all sessions for this coach
$seances = Seance::getByCoach($coach_id);

// Get statistics
$disponibles = Seance::countByStatus($coach_id, 'disponible');
$reservees = Seance::countByStatus($coach_id, 'reservee');
$total = count($seances);

// Get reservations for this coach
require_once '../classes/Reservation.php';
$reservations = Reservation::getByCoach($coach_id);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Coach - Sport Manager</title>
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

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
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

        .main-content {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }

        .card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 15px;
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

        .form-group {
            margin-bottom: 20px;
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

        input[type="date"],
        input[type="time"],
        input[type="number"],
        select {
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

        input:focus,
        select:focus {
            outline: none;
            border-color: #00d4ff;
            background: rgba(0, 212, 255, 0.08);
            box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.1);
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
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 212, 255, 0.35);
        }

        .seances-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .seance-item {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .seance-item:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(0, 212, 255, 0.3);
        }

        .seance-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .seance-date {
            font-size: 18px;
            font-weight: 600;
            color: #00d4ff;
        }

        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-disponible {
            background: rgba(76, 175, 80, 0.2);
            color: #81c784;
            border: 1px solid rgba(76, 175, 80, 0.3);
        }

        .status-reservee {
            background: rgba(255, 152, 0, 0.2);
            color: #ffb74d;
            border: 1px solid rgba(255, 152, 0, 0.3);
        }

        .seance-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 15px;
            color: #a0a0b0;
            font-size: 14px;
        }

        .seance-actions {
            display: flex;
            gap: 10px;
        }

        .btn-edit,
        .btn-delete {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-edit {
            background: rgba(0, 212, 255, 0.2);
            color: #00d4ff;
            border: 1px solid rgba(0, 212, 255, 0.3);
        }

        .btn-edit:hover {
            background: rgba(0, 212, 255, 0.3);
        }

        .btn-delete {
            background: rgba(255, 71, 87, 0.2);
            color: #ff6b7a;
            border: 1px solid rgba(255, 71, 87, 0.3);
        }

        .btn-delete:hover {
            background: rgba(255, 71, 87, 0.3);
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #808090;
        }

        .empty-state svg {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: rgba(26, 26, 46, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            color: #00d4ff;
            font-size: 20px;
        }

        .close-modal {
            background: none;
            border: none;
            color: #a0a0b0;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: auto;
        }

        @media (max-width: 968px) {
            .main-content {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="header">
        <div>
            <h1>Dashboard Coach</h1>
            <p style="color: #a0a0b0; margin-top: 5px;">Bienvenue, <?php echo htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?></p>
        </div>
        <div class="user-info">
            <a href="logout.php" class="logout-btn">Déconnexion</a>
        </div>
    </div>

    <div class="stats-container">
        <div class="stat-card">
            <h3>Total Séances</h3>
            <div class="number"><?php echo $total; ?></div>
        </div>
        <div class="stat-card">
            <h3>Disponibles</h3>
            <div class="number"><?php echo $disponibles; ?></div>
        </div>
        <div class="stat-card">
            <h3>Réservées</h3>
            <div class="number"><?php echo $reservees; ?></div>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="main-content">
        <div class="card">
            <h2>➕ Nouvelle Séance</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="create">

                <div class="form-group">
                    <label for="date">Date</label>
                    <input type="date" id="date" name="date" required min="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label for="heure">Heure</label>
                    <input type="time" id="heure" name="heure" required>
                </div>

                <div class="form-group">
                    <label for="duree">Durée (minutes)</label>
                    <input type="number" id="duree" name="duree" required min="15" step="15" value="60">
                </div>

                <button type="submit">Créer la séance</button>
            </form>
        </div>

        <div style="display: flex; flex-direction: column; gap: 30px;">
            <div class="card">
                <h2>📅 Mes Séances</h2>
                <div class="seances-list">
                    <?php if (empty($seances)): ?>
                        <div class="empty-state">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" />
                            </svg>
                            <p>Aucune séance créée pour le moment</p>
                            <p style="font-size: 14px; margin-top: 10px;">Créez votre première séance pour commencer</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($seances as $seance): ?>
                            <div class="seance-item">
                                <div class="seance-header">
                                    <div class="seance-date">
                                        <?php
                                        $date = new DateTime($seance['date']);
                                        echo $date->format('d/m/Y');
                                        ?>
                                    </div>
                                    <span class="status-badge status-<?php echo $seance['statut']; ?>">
                                        <?php echo ucfirst($seance['statut']); ?>
                                    </span>
                                </div>
                                <div class="seance-details">
                                    <div>🕐 <strong>Heure:</strong> <?php echo substr($seance['heure'], 0, 5); ?></div>
                                    <div>⏱️ <strong>Durée:</strong> <?php echo $seance['duree']; ?> min</div>
                                </div>
                                <div class="seance-actions">
                                    <button class="btn-edit" onclick="editSeance(<?php echo htmlspecialchars(json_encode($seance)); ?>)">
                                        ✏️ Modifier
                                    </button>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette séance ?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $seance['id']; ?>">
                                        <button type="submit" class="btn-delete">🗑️ Supprimer</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Reservations Section -->
            <div class="card">
                <h2>📋 Réservations de mes séances</h2>
                <div class="seances-list">
                    <?php if (empty($reservations)): ?>
                        <div class="empty-state">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                            </svg>
                            <p>Aucune réservation pour le moment</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($reservations as $res): ?>
                            <div class="seance-item">
                                <div class="seance-header">
                                    <div class="seance-date">
                                        <?php
                                        $date = new DateTime($res['date']);
                                        echo $date->format('d/m/Y');
                                        ?>
                                    </div>
                                    <span class="status-badge status-reservee">Réservée</span>
                                </div>
                                <div class="seance-details">
                                    <div>🕐 <strong>Heure:</strong> <?php echo substr($res['heure'], 0, 5); ?></div>
                                    <div>⏱️ <strong>Durée:</strong> <?php echo $res['duree']; ?> min</div>
                                </div>
                                <div style="background: rgba(0, 212, 255, 0.1); padding: 15px; border-radius: 8px; margin-top: 10px;">
                                    <div style="font-size: 12px; color: #a0a0b0; margin-bottom: 5px;">SPORTIF</div>
                                    <div style="font-weight: 600; color: #00d4ff;">
                                        <?php echo htmlspecialchars($res['sportif_prenom'] . ' ' . $res['sportif_nom']); ?>
                                    </div>
                                    <div style="font-size: 13px; color: #a0a0b0; margin-top: 3px;">
                                        📧 <?php echo htmlspecialchars($res['sportif_email']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Modifier la séance</h3>
                <button class="close-modal" onclick="closeEditModal()">×</button>
            </div>
            <form method="POST" action="" id="editForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit-id">

                <div class="form-group">
                    <label for="edit-date">Date</label>
                    <input type="date" id="edit-date" name="date" required>
                </div>

                <div class="form-group">
                    <label for="edit-heure">Heure</label>
                    <input type="time" id="edit-heure" name="heure" required>
                </div>

                <div class="form-group">
                    <label for="edit-duree">Durée (minutes)</label>
                    <input type="number" id="edit-duree" name="duree" required min="15" step="15">
                </div>

                <div class="form-group">
                    <label for="edit-statut">Statut</label>
                    <select id="edit-statut" name="statut" required>
                        <option value="disponible">Disponible</option>
                        <option value="reservee">Réservée</option>
                    </select>
                </div>

                <button type="submit">Enregistrer les modifications</button>
            </form>
        </div>
    </div>

    <script>
        function editSeance(seance) {
            document.getElementById('edit-id').value = seance.id;
            document.getElementById('edit-date').value = seance.date;
            document.getElementById('edit-heure').value = seance.heure;
            document.getElementById('edit-duree').value = seance.duree;
            document.getElementById('edit-statut').value = seance.statut;

            document.getElementById('editModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    </script>
</body>

</html>