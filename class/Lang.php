<?php

if( !defined('_INDEX') ) {
	header('HTTP/1.1 403 Forbidden');
	exit;
}

/**
 * Lang class. Permits to get lang key between fr,en,es, etc
 *
 * @author Jeremy 'Korko' Lemesle <jeremy.lemesle@korko.fr>
 * @package ifips.framework.tools
 */
class Lang extends Singleton
{
	private $lang;
	private $lang_key;

	/**
	 * Constructor for the class Lang
	 * @throws Exception Default Lang isn't available
	 */
	public function __construct()
	{
		parent::__construct();

		// Lang is set to default
		$this->setLang(_DEFAULT_LANG);

		// Try to use the visitor's lang
		// If not possible, use the default one
		//TODO
		// $this->setLang();
	}

	/*
	 * Get the lang name
	 * @return String Name
	 */
	public function getLang()
	{
		return $this->getKey($this->lang) ? $this->getKey($this->lang) : $this->lang;
	}

	/**
	 * Set the lang
	 * @param String $lang Lang directory to use
	 * @throws Exception Lang isn't available
	 */
	public function setLang($lang)
	{
		assert(is_dir(_LANG_DIR . '/' . $lang));

		$this->lang = $lang;
		$this->lang_key = array();

		// Get the general lang file
		$this->importLangFile('main', TRUE);
	}

	/**
	 * Permit the user to import a specific file for each page for example
	 * @param String $langfile Basename of the file. Example : for the file main.ini, $langfile = 'main'
	 */
	public function importLangFile($langfile, $require=FALSE)
	{
		$is_file = is_file(_LANG_DIR . '/' . $this->lang . '/' . $langfile . '.ini');

		if( $require && !$is_file )
			trigger_error('File '.$langfile.' is not available for lang '.$this->lang, E_USER_ERROR);
		elseif( !$is_file )
			return;

		$new_keys = @parse_ini_file(_LANG_DIR . '/' . $this->lang . '/' . $langfile . '.ini');

		assert('$new_keys !== FALSE');

		if( !empty($new_keys) )
		{
			$temp_new_keys = array();
			foreach($new_keys as $key => $value)
			{
				$temp_new_keys[strtolower($key)] = $value;
			}

			$this->lang_key = array_merge($this->lang_key, $temp_new_keys);
		}

		return TRUE;
	}

	/**
	 * Get the string for the lang and the key given or "" if not exists
	 * @param String $key Key for the string
	 * @return String Lang String
	 */
	public function getKey($key)
	{
		$key = strtolower($key);
		assert('$this->isKey($key)');
		return $this->lang_key[$key];
	}

	public function isKey($key)
	{
		return isset($this->lang_key[strtolower($key)]);
	}

	public function getKeys()
	{
		return $this->lang_key;
	}
}
