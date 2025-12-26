SELECT u.nom , count(*) 
FROM coachs c 
INNER JOIN users u ON u.id = c.user_id
INNER JOIN seances s ON c.user_id = s.id
INNER JOIN reservations r ON r.id = s.id
GROUP BY u.nom


SELECT u.id, count(s.id) 
FROM coachs c 
INNER JOIN seances s ON c.user_id = s.id
INNER JOIN reservations r ON r.id = s.id
INNER JOIN users u ON u.id = c.user_id
GROUP BY u.id;

SELECT round((SELECT COUNT(*) FROM reservations) / (SELECT COUNT(*) FROM seances)*100,2); 

SELECT coach_id FROM seances 
GROUP BY seances.coach_id 
HAVING coach_id > 3; 

-- CHALENGE 2:

