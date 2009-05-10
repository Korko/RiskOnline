<?php

if( !defined('_INDEX') ) {
	header('HTTP/1.1 403 Forbidden');
	exit;
}

class Chatbox extends Model
{
	const PRIVACY = _PRIVACY_ALL;
	
	public static $fields = array(
		'name' => array(
			'type' => 'text',
			'check' => 'notempty'
		),
		'content' => array(
			'type' => 'text',
			'check' => 'notempty'
		)
	);
	
	public static function getNickname()
	{
		return (!F::i('Session')->isConnected() && !Tools::isEmpty(F::i('Cookie')->get('name'))) ? F::i('Cookie')->get('name') : F::i('Session')->getUsername();
	}
	
	public static function setNickname()
	{
		F::i('Cookie')->set('name', $params['name']);
	}
	
	public static function getFields()
	{
		$fields = self::$fields;
		if( F::i('Session')->isConnected() )
		{
			$fields['name']['disabled'] = TRUE;
		}
		
		return $fields;
	}
	
	public function getView($params, $synchrone)
	{
		$form = new Form(self::getFields(), $params);
		$view = View::setFile('chatbox', View::JSON_FILE);
		
		$fields = Chatbox::getFields();
		if( F::i('Session')->isConnected() )
		{
			$fields['name']['disabled'] = TRUE;
		}
		
		$errors = $form->getErrors();
		if( ! Tools::isEmpty($errors) )
		{
			foreach($errors as $field => $msg)
			{
				$view->setGroupValues('error', array('field'=>$field, 'errmsg'=>$msg));
			}
		}
		
		// If Submitting
		if( $form->isSubmitting() && Tools::isEmpty($form->getErrors()) )
		{
			// TODO Sauvegarde du pseudo en cookie
			
			// Enregistrement des données
			if( F::i('Session')->isConnected() )
			{
				$m_name = '';
			}
			else
			{
				$m_name = $params['name'];
				
				self::setNickname($m_name);
			}
			
			F::i(_DBMS_SYS)->exec('INSERT INTO !prefix_chatbox_messages (m_id, m_name, mes_content) VALUES (?, ?, ?)', array(F::i('Session')->getMid(), $m_name, $params['content']));
			
			// Et sortie
			return;
		}
		
		// Recuperation des derniers messages
		$sql = 'SELECT mes.m_name, m.m_login, mes.m_id, mes_content, UNIX_TIMESTAMP(mes_date) AS mes_date FROM !prefix_chatbox_messages mes, !prefix_members m WHERE m.m_id = mes.m_id';
		$array = array();
		
		// si une date est donnée, tous les messages depuis cette date
		if( isset($params['since']) )
		{
			$sql .= ' AND mes_date > FROM_UNIXTIME(?)';
			$array[] = $params['since'];
		}
		// sinon, limiter à _LIMIT_LAST_CHATBOX messages
		
		$sql .= ' ORDER BY mes_date DESC';
		
		if( !isset($params['since']) )
		{
			$sql .= ' LIMIT ?';
			$array[] = _LIMIT_LAST_CHATBOX;
		}
		
		// Generation d'un pseudo si non connecté et pseudo non fourni
		
		// Si connecté, verouiller le pseudo
		
		$result = F::i(_DBMS_SYS)->query($sql, $array);
		
		// Recover messages and switch the order to get the last _LIMIT_LAST_CHATBOX messages but the last at the end.
		$messages = array();
		for($i=0; $i<$result->getNumRows(); $i++)
		{
			$obj = $result->getObject();
			$messages[] = array(
				'author_mid' => $obj->m_id,
				'author_name' => ($obj->m_id == _ID_VISITOR) ? $obj->m_name : $obj->m_login,
				'content' => htmlentities($obj->mes_content, ENT_QUOTES, 'UTF-8', TRUE),
				'date' => $obj->mes_date
			);
		}
		
		$messages = array_reverse($messages);
		for($i=0; $i<count($messages); $i++)
		{
			$view->setGroupValues('message', $messages[$i]);
		}
		
		return $view->getContent();
	}
}
