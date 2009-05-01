<?php

if( !defined('_INDEX') ) {
	header('HTTP/1.1 403 Forbidden');
	exit;
}

/**
 * @package ifips.framework.mvc.models
 */
class New_Game extends Model
{
	const PRIVACY = _PRIVACY_MEMBER;
	
	private $fields = array(
		'name' => array(
			'type' => 'text',
			'check' => 'notempty'
		),
		'access_key' => array(
			'type' => 'password'
		)
	);
	
	public function getView($params, $synchrone)
	{
		$form = new Form($this->fields, $params);
		$title = '';
		
		if( $form->isSubmitting() && Tools::isEmpty($form->getErrors()) )
		{
			$access_key = (isset($params['access_key']) && !empty($params['access_key'])) ? Tools::saltHash($params['access_key']) : 'NULL';
			
			// Create Game
			$game = Game::create(F::i('Session')->getMid(), $params['name'], $access_key);
			
			Tools::redirect('?action=wait_game&game='.$game->g_id);
		}
		else
		{
			// Generate form
			$view = View::setFile('formular', View::HTML_FILE);
			
			$errors = $form->getErrors();
			// Errors in the filling
			if( !empty($errors) )
			{
				$view->setSwitch('form_errors', TRUE);
				foreach($errors as $field => $error)
				{
					$view->setGroupValues('form_errors', array('error' => F::i('Lang')->getKey('error_'.$field.'_'.$error)));
				}
			}
			
			$view->setValue('form', $form->getHTML(F::i('Lang')->getKey('Create'), '#', 'POST', 'tabbed_form'));	
			$title = F::i('Lang')->getKey('title_new_game');		
		}
		
		return parent::setBody($view, $title);
	}
}
