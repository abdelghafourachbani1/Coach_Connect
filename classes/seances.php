<?php
require_once __DIR__ . '/../config/database.php';

class Seance{
    private $id;
    private $coach_id;
    private $date;
    private $heure;
    private $duree;
    private $statut;

    public function __construct($id = null, $coach_id, $date, $heure, $duree, $statut = 'disponible'){
        $this->id = $id;
        $this->coach_id = $coach_id;
        $this->date = $date;
        $this->heure = $heure;
        $this->duree = $duree;
        $this->statut = $statut;
    }

    // Getters
    public function getId(){
        return $this->id;
    }

    public function getCoachId(){
        return $this->coach_id;
    }

    public function getDate(){
        return $this->date;
    }

    public function getHeure(){
        return $this->heure;
    }

    public function getDuree(){
        return $this->duree;
    }

    public function getStatut(){
        return $this->statut;
    }

    // Setters
    public function setDate($date){
        $this->date = $date;
    }

    public function setHeure($heure){
        $this->heure = $heure;
    }

    public function setDuree($duree){
        $this->duree = $duree;
    }

    public function setStatut($statut){
        $this->statut = $statut;
    }

    public function creer(){
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("
            INSERT INTO seances (coach_id, date, heure, duree, statut)
            VALUES (:coach_id, :date, :heure, :duree, :statut)
        ");

        $stmt->execute([
            ':coach_id' => $this->coach_id,
            ':date' => $this->date,
            ':heure' => $this->heure,
            ':duree' => $this->duree,
            ':statut' => $this->statut
        ]);

        $this->id = $db->lastInsertId();
        return $this->id;
    }

    public function modifier(){
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("
            UPDATE seances 
            SET date = :date, heure = :heure, duree = :duree, statut = :statut
            WHERE id = :id AND coach_id = :coach_id
        ");

        return $stmt->execute([
            ':id' => $this->id,
            ':coach_id' => $this->coach_id,
            ':date' => $this->date,
            ':heure' => $this->heure,
            ':duree' => $this->duree,
            ':statut' => $this->statut
        ]);
    }

    public function supprimer(){
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("
            DELETE FROM seances 
            WHERE id = :id AND coach_id = :coach_id
        ");

        return $stmt->execute([
            ':id' => $this->id,
            ':coach_id' => $this->coach_id
        ]);
    }

    public static function getByCoach($coach_id){
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("
            SELECT * FROM seances 
            WHERE coach_id = :coach_id 
            ORDER BY date DESC, heure DESC
        ");

        $stmt->execute([':coach_id' => $coach_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getById($id, $coach_id){
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("
            SELECT * FROM seances 
            WHERE id = :id AND coach_id = :coach_id
        ");

        $stmt->execute([
            ':id' => $id,
            ':coach_id' => $coach_id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function countByStatus($coach_id, $statut){
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("
            SELECT COUNT(*) as count FROM seances 
            WHERE coach_id = :coach_id AND statut = :statut
        ");

        $stmt->execute([
            ':coach_id' => $coach_id,
            ':statut' => $statut
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
}
