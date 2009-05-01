/* trigger qui fait que quand on change de tour les actions sont supprimees */
DROP TRIGGER IF EXISTS TRIG_CHG_TURN_DEL_ACTIONS//

CREATE TRIGGER TRIG_CHG_TURN_DEL_ACTIONS AFTER UPDATE ON mvc_games
FOR EACH ROW
BEGIN
	IF OLD.g_step<>NEW.g_step THEN
		DELETE FROM mvc_actions WHERE g_id=OLD.g_id;
	END IF;
END//

/* trigger qui fait qu'a la suppression d'un membre, la chatbox est mise a jour */

DROP TRIGGER IF EXISTS TRIG_DEL_MEMBER_UPD_MESSAGES//

CREATE TRIGGER TRIG_DEL_MEMBER_UPD_MESSAGES AFTER DELETE ON mvc_members
FOR EACH ROW
BEGIN
	UPDATE mvc_messages SET m_id=1, m_name=OLD.m_login WHERE m_id=OLD.m_id;
END//

DROP TRIGGER IF EXISTS TRIG_DEL_MEMBER_UPD_MESSAGES//

CREATE TRIGGER TRIG_DEL_MEMBER_UPD_MESSAGES AFTER DELETE ON mvc_members
FOR EACH ROW
BEGIN
	UPDATE mvc_messages SET m_id=1, m_name=OLD.m_login WHERE m_id=OLD.m_id;
END//
