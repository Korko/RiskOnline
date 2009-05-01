<?php

if( !defined('_INDEX') ) {
	header('HTTP/1.1 403 Forbidden');
	exit;
}

/**
 * @package ifips.framework.mvc.models
 */
class Connect extends Model
{
	const PRIVACY = _PRIVACY_VISITOR_ONLY;

	private static $fields = array(
		'login' => array(
			'type' => 'text',
			'check' => 'notempty'
		),
		'password' => array(
			'type' => 'password',
			'check' => 'notempty'
		),
		'redirect' => array(
			'type' => 'hidden'
		)
	);

	public static function getFields()
	{
		return self::$fields;
	}
	
	public function getView($params, $synchrone)
	{
		$form = new Form(self::$fields, $params);
		$title = '';
		
		// Ah ? Is the visitor trying to authentificate ?
		if( $form->isSubmitting() && Tools::isEmpty($form->getErrors()))
		{
			try
			{
				F::i('Session')->connect($params['login'], $params['password']);
			}
			catch(AccessException $e)
			{
				// Error...
			}
		}

		if( $form->isSubmitting() && F::i('Session')->isConnected())
		{
			if( isset($params['redirect']) && !empty($params['redirect']) )
			{
				Tools::redirect(rawurldecode($params['redirect']));
			}

			$view = View::setFile('info', View::HTML_FILE);
			$view->setValue('L_message', F::i('Lang')->getKey('connexion_successful'));
			$view->setValue('U_ok', '?');
			$title = F::i('Lang')->getKey('title_info');
		}
		else
		{
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
			// Filling OK But alreay here ? So, invalid login/pass
			else if( $form->isSubmitting() )
			{
				$view->setSwitch('form_errors', TRUE);
				$view->setGroupValues('form_errors', array('error' => F::i('Lang')->getKey('error_access')));
			}
			
			$view->setValue('form', $form->getHTML(F::i('Lang')->getKey('Connexion'), '#', 'POST', 'tabbed_form'));
			$title = F::i('Lang')->getKey('title_connexion');
		}
		
		return parent::setBody($view, $title);
	}
}
