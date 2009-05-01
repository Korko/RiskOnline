<?php

if( !defined('_INDEX') ) {
	header('HTTP/1.1 403 Forbidden');
	exit;
}

/**
 * Controller Class. Check if parameters are ok for the action wanted
 *
 * @author Jeremy 'Korko' Lemesle <jeremy.lemesle@korko.fr>
 * @package ifips.framework.mvc
 */
class Controller
{
	public static function check($action, $params=array())
	{
		Factory::include_class($action);

		$privacy = defined($action.'::PRIVACY') ? constant($action.'::PRIVACY') : _PRIVACY_ALL;

		if( !F::i('Session')->canAccess($privacy) )
		{
			if( !F::i('Session')->isConnected() )
			{
				$params['action'] = $action;
				Tools::redirect('?'.http_build_query(array('action' => 'connect', 'redirect' => rawurlencode('?'.http_build_query($params)))));
			}
			else
			{
				return Model::showError('not allowed');
			}
		}

		$instance = F::i($action, '', 'Model');

		F::i('Lang')->importLangFile($action);

		return $instance->getView($params, !(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'));
	}
}
