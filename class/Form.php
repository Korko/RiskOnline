<?php

if( !defined('_INDEX') ) {
	header('HTTP/1.1 403 Forbidden');
	exit;
}

/**
 * @package ifips.framework.tools
 */
class Form
{
	private static $formats = array(
		'text' => '<label for="%1$s">%2$s :</label><input type="text" name="%1$s" id="__%1$s" check="%3$s" params="%4$s" value="%5$s" class="%6$s" %7$s />',
		'hidden' => '<input type="hidden" name="%1$s" id="%1$s" value="%5$s" />',
		'password' => '<label for="%1$s">%2$s :</label><input type="password" name="%1$s" id="__%1$s" check="%3$s" params="%4$s" value="%5$s" class="%6$s" %7$s />'
	);

	private $fields = array();
	private $params = array();
	private $submitting = FALSE;
	private $hash = NULL;
	
	public function __construct($fields, $params=array())
	{
		assert('!empty($fields)');
		
		$this->fields = $fields;
		$this->params = $params;
		
		try
		{
			$this->errors = self::check($this->fields, $this->params);
			$this->submitting = TRUE;
		}
		catch(Exception $e)
		{
			$this->errors = array();
			$this->submitting = FALSE;
		}
	}
	
	public function getErrors()
	{
		return $this->errors;
	}
	
	public function isSubmitting()
	{
		return $this->submitting;
	}
	
	public function getHTML($title='', $target='#', $method="POST", $style='', $callback='', $aftersubmit='')
	{
		return self::generate($this->fields, $this->params, $title, $target, $method, $this->errors, $style, $callback, $aftersubmit);
	}
	
	public static function hash($fields)
	{
		return sha1(var_export($fields, TRUE));
	}
	
	/**
	 * Generate a form with an array of fields
	 * array(
	 * 	NAME => array(
	 *		'type' => TYPE,
	 * 		'value' => DEFAULT_VALUE,
	 * 		'check' => 'check',
	 * 		'params' => array(
	 * 			'regex' => REGEX (for CHECK == REGEX)
	 * 			'maxlength' => MAXLENGTH ()
	 * 		)
	 *	)
	 * );
	 *
	 * @param Array $fields
	 * @param Array $params
	 * @param String $title
	 * @param String $target
	 * @param String $method (GET|POST)
	 * @return String HTML Form to display and to use ^^
	 */
	private static function generate($fields, $params, $title, $target, $method, $checks, $style, $callback, $aftersubmit) {
		$string = '';

		$title = (!empty($title)) ? '<legend>'.htmlentities($title).'</legend>' : '';

		assert('!empty($fields)');
		
		foreach($fields as $name => $field)
		{
			if( isset($params[$name]) )
				$field_value = $params[$name];
			else if( isset($field['value']) )
				$field_value = $field['value'];
			else
				$field_value = '';

			$field_key = (isset($field['name']) && F::i('Lang')->isKey($field['name'])) ? $field['name'] : (F::i('Lang')->isKey($name) ? $name : NULL);
			$field_name = !is_null($field_key) ? F::i('Lang')->getKey($field_key) : $name;	
			$field_explain = !is_null($field_key) && F::i('Lang')->isKey($field_key.'_explain') ? '<p class="field_explain">'.F::i('Lang')->getKey($field_key.'_explain').'</p>' : ''; 
			$field_check = isset($field['check']) ? $field['check'] : '';
			$field_params = isset($field['params']) ? $field['params'] : '';
			$field_type = $field['type'];
			$field_error = (isset($checks[$field_key])&& !empty($checks[$field_key])) ? 'field_error' : '';
			$field_disabled = isset($field['disabled']) ? 'disabled=true' : '';
			
			// TODO :
			// Style
			// width:
			// disable:
			// height:
			// maxlength:
				
			$string .= '<p>'.sprintf(self::$formats[$field_type], $name, $field_name, $field_check, $field_params, $field_value, $field_error, $field_disabled).'</p>';
		}

		//$string .= '<input type="reset" name="reset" id="__reset" value="'.F::i('Lang')->getKey('reset').'" />';
		$string .= '<input type="submit" name="submit" id="__submit" value="'.F::i('Lang')->getKey('submit').'" />';

		$check = '<input type="hidden" name="__check_hash" value="'.self::hash($fields).'" />';
		
		return '<fieldset class="'.$style.'">'.$title.'<form class="'.$style.'" action="'.$target.'" method="'.$method.'" callback="'.$callback.'" aftersubmit="'.$aftersubmit.'">'.$string.$check.'</form></fieldset>';
	}

	/**
	 * @param Array $fields
	 * @return Array Array of fields where there is an error
	 * @throws Exception Form not submitted
	 */
	private static function check($fields, $params)
	{
		if( !isset($params['submit']) || !isset($params['__check_hash']) || ($params['__check_hash'] != self::hash($fields)) )
			throw new Exception('Form Not Submitted');

		$errors = array();

		foreach($fields as $field => $array)
		{
			if( !isset($array['check']) || empty($array['check']) )
				continue; // If not check needed, see next
				
			$field_name = $field;
			$field_type = isset($array['type']) ? $array['type'] : '';
			$field_check = isset($array['check']) ? $array['check'] : '';
			$field_params = isset($array['params']) ? $array['params'] : '';
			
			$checks = explode(';', $field_check);

			foreach($checks as $check)
			{
				$check = trim($check);

				assert('method_exists("Form", "check_'.$check.'")');

				if( !call_user_func(array('Form', 'check_'.$check), $field_name, $field_type, $params, $field_params) )
				{ 	
					$errors[$field_name] = $check;
					break; // If an error occured, stop this field
				}
			}
		}

		return $errors;
	}
	
	/**
	 * @param Array $fields
	 * @param Array $params
	 * @return boolean
	 */
	private static function areDefined($fields, $params)
	{
		foreach($fields as $field => $array)
		{
			if( isset($params[$field]) && !empty($param[$fields]) )
				return TRUE;
		}

		return FALSE;
	}

	/**
	 * @param String $field
	 * @param String $type
	 * @param Array $values
	 * @param Array $params
	 * @return boolean Is this field ok or not
	 */
	private static function check_notempty($field, $type, $values, $params)
	{
		return isset($values[$field]) && !empty($values[$field]);
	}

	/**
	 * @param String $field
	 * @param String $type
	 * @param Array $values
	 * @param Array $params
	 * @return boolean Is this field ok or not
	 */
	private static function check_regex($field, $type, $values, $params)
	{
		assert('isset($values["'.$field.'"])');
		assert('isset($params["regex"])');

		return isset($values[$field]) && preg_match('~'.str_replace('~', '\~', $params['regex']).'~', $values[$field]);
	}
}
