<?php
require_once __DIR__ . '/../config/database.php';

class Reservation
{
    private $seance_id;
    private $sportif_id;

    public function __construct($seance_id, $sportif_id)
    {
        $this->seance_id = $seance_id;
        $this->sportif_id = $sportif_id;
    }

    // Create a reservation
    public function reserver()
    {
        $db = Database::getInstance()->getConnection();

        // Start transaction
        $db->beginTransaction();

        try {
            // Check if already reserved
            if (self::isAlreadyReserved($this->seance_id, $this->sportif_id)) {
                throw new Exception("Vous avez déjà réservé cette séance");
            }

            // Check if session is available
            $stmt = $db->prepare("SELECT statut FROM seances WHERE id = :id");
            $stmt->execute([':id' => $this->seance_id]);
            $seance = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$seance || $seance['statut'] !== 'disponible') {
                throw new Exception("Cette séance n'est plus disponible");
            }

            // Create reservation
            $stmt = $db->prepare("
                INSERT INTO reservations (seance_id, sportif_id)
                VALUES (:seance_id, :sportif_id)
            ");
            $stmt->execute([
                ':seance_id' => $this->seance_id,
                ':sportif_id' => $this->sportif_id
            ]);

            // Update session status
            $stmt = $db->prepare("
                UPDATE seances 
                SET statut = 'reservee' 
                WHERE id = :id
            ");
            $stmt->execute([':id' => $this->seance_id]);

            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    // Cancel a reservation
    public function annuler()
    {
        $db = Database::getInstance()->getConnection();

        $db->beginTransaction();

        try {
            // Delete reservation
            $stmt = $db->prepare("
                DELETE FROM reservations 
                WHERE seance_id = :seance_id AND sportif_id = :sportif_id
            ");
            $stmt->execute([
                ':seance_id' => $this->seance_id,
                ':sportif_id' => $this->sportif_id
            ]);

            // Update session status back to available
            $stmt = $db->prepare("
                UPDATE seances 
                SET statut = 'disponible' 
                WHERE id = :id
            ");
            $stmt->execute([':id' => $this->seance_id]);

            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    // Check if already reserved by this sportif
    public static function isAlreadyReserved($seance_id, $sportif_id)
    {
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM reservations 
            WHERE seance_id = :seance_id AND sportif_id = :sportif_id
        ");
        $stmt->execute([
            ':seance_id' => $seance_id,
            ':sportif_id' => $sportif_id
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    // Get all reservations for a sportif with session and coach details
    public static function getBySportif($sportif_id)
    {
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("
            SELECT 
                s.*,
                u.nom as coach_nom,
                u.prenom as coach_prenom,
                c.descipline as coach_discipline
            FROM reservations r
            JOIN seances s ON r.seance_id = s.id
            JOIN coaches c ON s.coach_id = c.id
            JOIN users u ON c.id = u.id
            WHERE r.sportif_id = :sportif_id
            ORDER BY s.date DESC, s.heure DESC
        ");

        $stmt->execute([':sportif_id' => $sportif_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get all reservations for a coach's sessions
    public static function getByCoach($coach_id)
    {
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("
            SELECT 
                s.*,
                u.nom as sportif_nom,
                u.prenom as sportif_prenom,
                u.email as sportif_email
            FROM reservations r
            JOIN seances s ON r.seance_id = s.id
            JOIN sportifs sp ON r.sportif_id = sp.id
            JOIN users u ON sp.id = u.id
            WHERE s.coach_id = :coach_id
            ORDER BY s.date DESC, s.heure DESC
        ");

        $stmt->execute([':coach_id' => $coach_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get available sessions (not reserved) with coach details
    public static function getAvailableSessions()
    {
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("
            SELECT 
                s.*,
                u.nom as coach_nom,
                u.prenom as coach_prenom,
                c.descipline as coach_discipline,
                c.experience as coach_experience,
                c.description as coach_description
            FROM seances s
            JOIN coaches c ON s.coach_id = c.id
            JOIN users u ON c.id = u.id
            WHERE s.statut = 'disponible'
            AND s.date >= CURDATE()
            ORDER BY s.date ASC, s.heure ASC
        ");

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
