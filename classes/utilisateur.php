<?php
require_once __DIR__ . '/../config/database.php';

abstract class Utilisateur {
    protected $id;
    protected $nom;
    protected $prenom;
    protected $email;
    protected $passwordHash;

    public function __construct($id, $nom, $prenom, $email, $passwordHash = null){
        $this->id = $id;
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
    }

    public function __toString(){
        return $this->nom . ' ' . $this->prenom;
    }

    // Getters
    public function getId(){
        return $this->id;
    }
    public function getNom(){
        return $this->nom;
    }
    public function getPrenom(){
        return $this->prenom;
    }
    public function getEmail(){
        return $this->email;
    }

    protected function setPasswordHash($password){
        $this->passwordHash = password_hash($password, PASSWORD_BCRYPT);
    }

    // Shared DB methods
    protected function saveBaseUser($role){
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("
            INSERT INTO users (nom, prenom, email, password, role)
            VALUES (:nom, :prenom, :email, :password, :role)
        ");

        $stmt->execute([
            ':nom' => $this->nom,
            ':prenom' => $this->prenom,
            ':email' => $this->email,
            ':password' => $this->passwordHash,
            ':role' => $role
        ]);

        $this->id = $db->lastInsertId();
        return $this->id;
    }

    /* ---------- AUTH ---------- */

    public static function findByEmail($email){
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function signin($email, $password) {
        $data = self::findByEmail($email);

        if (!$data || !password_verify($password, $data['password'])) {
            throw new Exception("Invalid email or password");
        }

        return $data; // session will use this
    }
}
