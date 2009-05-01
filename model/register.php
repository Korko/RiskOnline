<?php

if( !defined('_INDEX') ) {
	header('HTTP/1.1 403 Forbidden');
	exit;
}

/**
 * @package ifips.framework.mvc.models
 */
class Register extends Model
{
	const PRIVACY = _PRIVACY_VISITOR_ONLY;

	private $fields = array(
		'login' => array(
			'type' => 'text',
			'check' => 'notempty'
		),
		'password' => array(
			'type' => 'password',
			'check' => 'notempty'
		),
		'email' => array(
			'type' => 'text',
			'check' => 'notempty ; regex',
			'params' => array(
				'regex' => '.+@.+\.[a-z]{2,4}'
			)
		)
	);

	public function getView($params, $synchrone)
	{
		$form = new Form($this->fields, $params);
		
		if( $form->isSubmitting() && Tools::isEmpty($form->getErrors()) )
		{
			// Register
			$salt = Tools::generateSalt();
			$password = Tools::saltHash($params['password'], $salt);

			$error = FALSE;
			try
			{
				F::i(_DBMS_SYS)->exec('INSERT INTO !prefix_members (m_login, m_password, m_email, m_salt, m_auth) VALUES (?, ?, ?, ?, ?)', array($params['login'], $password, $params['email'], $salt, _AUTH_MEMBER));
			}
			catch(DBMSError $e)
			{
				// Login already given
				$error = TRUE;
			}
			
			if( !$error )
			{
				$view = View::setFile('info', View::HTML_FILE);
				$view->setValue('L_message', F::i('Lang')->getKey('register_successful'));
				$view->setValue('U_ok', '?');
			
				F::i('Session')->connect($params['login'], $params['password']);
			}
			else
			{
				$view = View::setFile('error', View::HTML_FILE);
				$view->setValue('l_message', F::i('Lang')->getKey('login_taken'));
				$view->setValue('u_ok', '?');
			}
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
			
			$view->setValue('form', $form->getHTML(F::i('Lang')->getKey('register'), '#', 'POST', 'tabbed_form'));
		}

		return parent::setBody($view);
	}
}
