<?php

if( !defined('_INDEX') ) {
	header('HTTP/1.1 403 Forbidden');
	exit;
}

/**
 * Model Class. Implementation of the MVC System
 *
 * @author Jeremy 'Korko' Lemesle <jeremy.lemesle@korko.fr>
 * @package ifips.framework.mvc
 */
abstract class Model
{
	public abstract function getView($params, $synchrone);

	protected static final function setBody(ViewItem $content, $title='', $short=FALSE)
	{
		include_once(_MODEL_DIR . '/connect.php');
		
		$form = new Form(Connect::getFields());
	
		if( $short )
		{
			$view = View::setFile('short_header');
			
			$view->setValue('SITE_TITLE', (!empty($title) ? $title . ' &laquo; ' : '')._SITE_TITLE);
		}
		else
		{
			$view = View::setFile('header');
		
			$view->setValue('SITE_TITLE', (!empty($title) ? $title . ' &laquo; ' : '')._SITE_TITLE);
			$view->setValue('L_WELCOME', sprintf(F::i('Lang')->getKey('Welcome_Explain'), F::i('Session')->isConnected() ? F::i('Session')->getUsername() : F::i('Lang')->getKey('Guest')));
			$view->setValue('FORM_CONNECT', $form->getHTML('', '?action=connect', 'POST'));
		}
		
		// Static Switches
		$view->setStaticSwitch('debug', _DEBUG);
		$view->setStaticSwitch('is_guest', !F::i('Session')->isConnected());
		$view->setStaticSwitch('is_member', F::i('Session')->getAuth() >= _AUTH_MEMBER);
		$view->setStaticSwitch('is_admin', F::i('Session')->getAuth() >= _AUTH_ADMIN);
			
		$header = $view->getContent();

		$view = View::setFile('footer');
			$view->setValue('BENCHMARK', F::i('Benchmark')->getTotal());
		$footer = $view->getContent();

		return $header . $content->getContent() . $footer;
	}
	
	public static final function showError($error)
	{
		$view = View::setFile('error');
			$view->setValue('L_message', $error);
			$view->setValue('U_ok', '?');
		return self::setBody($view);
	}
}
