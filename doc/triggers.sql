delimiter //

/* trigger qui fait que quand on change de tour les actions sont supprimees */
DROP TRIGGER IF EXISTS TRIG_CHG_TURN//

CREATE TRIGGER TRIG_CHG_TURN AFTER UPDATE ON mvc_games
FOR EACH ROW
BEGIN
	IF OLD.g_step<>NEW.g_step THEN
		DELETE FROM mvc_actions WHERE g_id=OLD.g_id;
	END IF;
END//

/* trigger qui fait qu'a la suppression d'un membre, la chatbox est mise a jour */

DROP TRIGGER IF EXISTS TRIG_DEL_MEMBER//

CREATE TRIGGER TRIG_DEL_MEMBER AFTER DELETE ON mvc_members
FOR EACH ROW
BEGIN
	/* On modifie la chatbox */
	UPDATE mvc_messages SET m_id=0, m_name=OLD.m_login WHERE m_id=OLD.m_id;
	/* On modifie les parties qu'il a gagnees */
	UPDATE mvc_games_history SET victor_id=0 WHERE victor_id=OLD.m_id;
END//

delimiter ;
