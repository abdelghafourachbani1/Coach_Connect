<?php
require_once 'Utilisateur.php';

class Coach extends Utilisateur{
    private $discipline;
    private $experience;
    private $description;

    public function __construct($nom, $prenom, $email, $password, $discipline, $experience, $description){
        parent::__construct(null, $nom, $prenom, $email);
        $this->setPasswordHash($password);
        $this->discipline = $discipline;
        $this->experience = $experience;
        $this->description = $description;
    }

    public function save(){
        $db = Database::getInstance()->getConnection();
        $this->saveBaseUser('coach');

        $stmt = $db->prepare("
            INSERT INTO coaches (id, discipline, experience, description)
            VALUES (:id, :discipline, :experience, :description)
        ");

        $stmt->execute([
            ':id' => $this->id,
            ':discipline' => $this->discipline,
            ':experience' => $this->experience,
            ':description' => $this->description
        ]);
    }
}
