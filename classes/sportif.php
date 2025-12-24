<?php
require_once 'Utilisateur.php';

class Sportif extends Utilisateur{

    public function __construct($nom, $prenom, $email, $password){
        parent::__construct(null, $nom, $prenom, $email);
        $this->setPasswordHash($password);
    }

    public function save()
    {
        $db = Database::getInstance()->getConnection();
        $this->saveBaseUser('sportif');

        $stmt = $db->prepare("INSERT INTO sportifs (id) VALUES (:id)");
        $stmt->execute([':id' => $this->id]);
    }
}
