<?php

if( !defined('_INDEX') ) {
	header('HTTP/1.1 403 Forbidden');
	exit;
}

/**
 * This class parse HTML to include PHP
 * And eval it at the end
 *
 * @author Jeremy 'Korko' Lemesle <jeremy.lemesle@korko.fr>
 * @package ifips.framework.mvc
 */
class View extends Singleton
{
	const HTML_FILE = 0;
	const JSON_FILE = 1;
	const XML_FILE = 2;
	const SVG_FILE = 3;

	public function __construct() {
		parent::__construct();
	}

	private static $types = array(
		self::HTML_FILE => array('extension' => 'html', 'mime' => 'text/html'),
		self::JSON_FILE => array('extension' => 'json', 'mime' => 'text/json'),
		self::XML_FILE  => array('extension' => 'xml', 'mime' => 'text/xml'),
		self::SVG_FILE  => array('extension' => 'svg', 'mime' => 'image/svg+xml')
	);

	/**
	 * Assign a file to the view
	 * Create a new ViewItem and
	 * return it to be used by the page
	 *
	 * @param String $file File name
	 * @return ViewItem View item to manage according to the file name
	 * @throws ErrorException bad file
	 */
	public static function setFile($file, $type=self::HTML_FILE)
	{
		if( !isset(self::$types[$type]) || is_dir(self::getFileName($file, $type)) )
		{
			throw new ErrorException('Parameter given is not a good file ! : '.self::getFileName($file, $type));
		}

		return new ViewItem($file,$type);
	}

	/**
	 * This function will return the name of a file
	 * according to its type
	 *
	 * @param String $file File name
	 * @param int $type File type (see View's consts)
	 * @return String Complete file name
	 */
	public static function getFileName($file, $type)
	{
		return $file . '.' . self::$types[$type]['extension'];
	}
	
	public static function getMIME($type)
	{
		return self::$types[$type]['mime'];
	}
	
	public static function protect($value, $function)
	{
		switch($function)
		{
			case 'escape':
				$value = str_replace("'", "\'", $value);
				$value = str_replace('"', '\"', $value);
				break;

			case 'escape_simple':
				$value = str_replace("'", "\'", $value);
				break;

			case 'escape_double':
				$value = str_replace('"', '\"', $value);
				break;

			case 'escape_json':
				$value = json_escape($value);
				break;
		}
		
		return $value;
	}
}


/**
 * ViewItem is the object used by the pages
 * to parse an element. Thanks to this system,
 * there is not any alias needed and no possibility of
 * overwrited datas
 *
 * @author Jeremy 'Korko' Lemesle <jeremy.lemesle@korko.fr>
 */
class ViewItem
{
	const VAR_MATCH = '#{\$%s(?:\|(.*?))?}#i';
	
	/**
	 * Name of the view file
	 *
	 * @var String
	 */
	private $file;
	private $type;
	
	/**
	 * Vars for this view file
	 */
	private $vars = array();
	private $groups = array();
	private $switches = array();
	
	private static $static_switches = array();

	/**
	 * Constructor have to only be called by View
	 *
	 * @param String $file
	 */
	public function __construct($file,$type)
	{
		// Pass by a variable because assert will change the call events ^^
		$calling = Tools::getCallingClass();
		assert('$calling == "View"');

		$this->file = View::getFileName($file, $type);
		$this->type = $type;
	}

	/**
	 * Assign a value to the view
	 * the value must be String or numeric
	 *
	 * @param String $key
	 * @param mixed $value
	 */
	public function setValue($key, $value)
	{
		$this->vars[$key] = $value;
	}

	/**
	 * Assign an array of values to the view
	 *
	 * @param Array $array
	 */
	public function setValues($array)
	{
		$this->vars = array_merge($this->vars, $array);
	}

	/**
	 * Assign an array to the group
	 *
	 * array(
	 * 	'key' => 'value',
	 * );
	 *
	 * @param String $group
	 * @param Array $array
	 */
	public function setGroupValues($group, $array)
	{
		if( !isset($this->groups[$group]) )
		{
			$this->groups[$group] = array();
		}
		
		$i = count($this->groups[$group]);
		$this->groups[$group][$i] = $array;
		$this->groups[$group][$i]['_cursor'] = $i;
	}

	public function setSwitch($switch, $value=TRUE)
	{
		$this->switches[$switch] = ($value) ? TRUE : FALSE;
	}

	public function setStaticSwitch($switch, $value=TRUE)
	{
		self::$static_switches[$switch] = ($value) ? TRUE : FALSE;
	}
	
	/**
	 * Parse the view and print its content
	 *
	 */
	public function getContent()
	{
		header('Content-Type: '.View::getMIME($this->type));
		return $this->parse_file($this->file);
	}

	private function parse_file($file)
	{
		$content = file_get_contents(_VIEW_DIR . $file);

		$content = $this->cleanComments($content);
		$content = $this->parseGenericVars($content);

		$content = $this->parseIncludes($content);
		$content = $this->parseSwitches($content);
		$content = $this->parseVars($content);
		$content = $this->parseGroups($content);
		
		// Caution : Eval begin the code with a <?php so close it !
		ob_start();
		eval(' ?>'.$content);
		$content = ob_get_contents();
		ob_end_clean();
		
		$content = $this->parseCalcs($content);
		
		return $content;
	}

	/**
	 * Parse the constants vars
	 * VIEW_DIR => Views Directory
	 *
	 * @param String $content
	 * @return String
	 */
	private function parseGenericVars($content)
	{
		$constants = get_defined_constants();

		// All vars
		$vars = array(
			'c' => $constants,
			'lg' => F::i('Lang')->getKeys()
		);

		foreach($vars as $prefix=>$variables)
		{
			foreach($variables as $key=>$value)
			{
				if( $key[0] == '_' ) $key = substr($key, 1);
				$key = $prefix.'_'.$key;
				$content = $this->parseReplaceVar($key, $value, $content);
			}
		}

		return $content;
	}

	private function parseReplaceVar($key, $value, $content)
	{
		if( preg_match_all(sprintf(self::VAR_MATCH, $key), $content, $matches, PREG_SET_ORDER) )
		{
			foreach($matches as $match)
			{
				$function = (isset($match[1])) ? $match[1] : '';

				$value = View::protect($value, $function);

				$content = str_replace($match[0], $value, $content);
			}
		}

		return $content;
	}

	/**
	 * Parse the vars in the content
	 * This function parse the constants too.
	 * 
	 * @param String $content
	 * @return String
	 */
	private function parseVars($content)
	{
		if( !empty($this->vars) )
		{
			foreach($this->vars as $key=>$value)
			{
				$content = $this->parseReplaceVar($key, $value, $content);
			}
		}

		return $content;
	}

	/**
	 * Function to parse switches
	 * <switch name="">
	 * </switch>
	 *
	 * @param String $content
	 * @return String
	 */
	private function parseSwitches($content)
	{
		$token_loop_begin = '#<!-- BEGIN SWITCH ([[:print:]]+?) -->#i';
		$end = '<!-- END SWITCH -->';

		$content = preg_replace($token_loop_begin, '<?php if( $this->getSwitch("$1") ) { ?>', $content);
		$content = str_replace($end, '<?php } ?>', $content);
		
		return $content;
	}

	public function getSwitch($switch)
	{
		return (
			(isset($this->switches[$switch]) && $this->switches[$switch]) ||
			(isset(self::$static_switches[$switch]) && self::$static_switches[$switch])
		);
	}
	
	/**
	 * Function to parse groups in the content
	 * <!-- BEGIN group -->
	 * <!-- END group -->
	 *
	 * array(
	 *  'group' => array(
	 * 		0 => array(
	 * 			'key' => 'value',
	 * 		),
	 * 	),
	 * );
	 *
	 * @param String $content
	 * @param Array $groups
	 * @return String
	 */
	private function parseGroups($content)
	{
		$token_loop_begin = '#<!-- BEGIN LOOP ([[:print:]]+?) -->#i';
		$end = '<!-- END LOOP -->';
		
		$content = preg_replace($token_loop_begin, '<?php if( isset($this->groups["$1"]) ) for( $cursor_group_$1=0; $cursor_group_$1 < count($this->groups["$1"]); $cursor_group_$1++ ) { ?> ', $content);
		$content = str_replace($end, '<?php } ?>', $content);
		
		// Replace group vars
		// TODO : Cascading group !
		$content = preg_replace(sprintf(self::VAR_MATCH, '([a-z0-9_-]+?)\.([a-z0-9_-]+?)'), '<?php if( isset($this->groups["$1"][$cursor_group_$1]["$2"]) ) echo View::protect($this->groups["$1"][$cursor_group_$1]["$2"], "$3"); ?>', $content);

		return $content;
	}

	/**
	 * Clean comments in the view
	 * <!!-- Comment -->
	 * @param String $content Content to clean
	 * @return String content cleaned
	 */
	private function cleanComments($content)
	{
		return preg_replace('#<!!-- .*? -->#', '', $content);
	}

	private function parseIncludes($content)
	{
		if( preg_match_all('#<!-- INCLUDE FILE ([[:print:]]+?) -->#i', $content, $matches, PREG_SET_ORDER) )
		{
			foreach($matches as $match)
			{
				$new_content = $this->parse_file($match[1]);
				$content = str_replace($match[0], $new_content, $content);
			}
		}

		return $content;
	}
	
	private function parseCalcs($content)
	{
		if( preg_match_all(sprintf(self::VAR_MATCH, '=(.+?)'), $content, $matches, PREG_SET_ORDER) )
		{
			foreach($matches as $match)
			{
				eval('$value = '.$match[1].';');
				$function = (isset($match[2])) ? $match[2] : '';
				$value = View::protect($value, $function);
				
				$content = str_replace($match[0], $value, $content);
			}
		}
		
		return $content;
	}
}
