<?php

if( !defined('_INDEX') ) {
	header('HTTP/1.1 403 Forbidden');
	exit;
}

include_once('chatbox.php');
/**
 * @package ifips.framework.mvc.models
 */
class Index extends Model
{
	public function getView($params, $synchrone)
	{
		include_once(_MODEL_DIR.'/chatbox.php');
		
		$chatbox = new Form(Chatbox::getFields(), array('name' => F::i('Session')->getUsername()));
		
		// Display Index page...
		$view = View::setFile('index', View::HTML_FILE);
		$view->setValue('u_chatbox', '?action=chatbox');
		$view->setValue('chatbox_form', $chatbox->getHTML('', '?action=chatbox', 'ajax', 'chatbox_form', 'cron.socket', 'clear_chatbox'));
		$view->setValue('login', F::i('Session')->getUsername());
		return parent::setBody($view, F::i('Lang')->getKey('title_index'));
	}
}
