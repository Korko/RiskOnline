delimiter //

DROP PROCEDURE IF EXISTS PROC_ENDGAME//

CREATE PROCEDURE PROC_ENDGAME(IN par_game_id INT(8), IN par_vict_id INT(8))
BEGIN
	INSERT INTO mvc_games_history(g_id, g_start) SELECT G.g_id, G.g_start FROM mvc_games G WHERE G.g_id=par_game_id;
	DELETE FROM mvc_games WHERE g_id=par_game_d;
	UPDATE mvc_games_history SET victor_id=par_vict_id WHERE g_id=par_game_id;
END//

delimiter ;
