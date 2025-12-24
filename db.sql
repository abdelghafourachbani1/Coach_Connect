CREATE DATABASE coachconnect;
USE coachconnect;

CREATE TABLE users (
	id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('coach', 'sportif') NOT NULL
);

CREATE TABLE coaches (
    id INT PRIMARY KEY,                                                            
    experience INT NOT NULL,
    description TEXT,
    descipline VARCHAR(100) NOT NULL,
    FOREIGN KEY (id) REFERENCES users(id) ON DELETE CASCADE
);


CREATE TABLE sportifs (
    id INT PRIMARY KEY,
    FOREIGN KEY (id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE seances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coach_id INT NOT NULL,
    date DATE NOT NULL,
    heure TIME NOT NULL,
    duree INT NOT NULL,
    statut ENUM('disponible', 'reservee') DEFAULT 'disponible',
    FOREIGN KEY (coach_id) REFERENCES coaches(id) ON DELETE CASCADE
);

CREATE TABLE reservations (
    seance_id INT NOT NULL UNIQUE,
    sportif_id INT NOT NULL,
	PRIMARY KEY(seance_id,sportif_id),
    FOREIGN KEY (seance_id) REFERENCES seances(id) ON DELETE CASCADE,
    FOREIGN KEY (sportif_id) REFERENCES sportifs(id) ON DELETE CASCADE
);

