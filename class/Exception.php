<?php

if( !defined('_INDEX') ) {
	header('HTTP/1.1 403 Forbidden');
	exit;
}

/**
 * @package ifips.framework.exceptions
 */

class ModelException extends Exception {}

class ViewException extends Exception {}

class ControllerException extends Exception {}
class NotImplementedException extends ControllerException { function __construct($message){parent::__construct('Function Not Implemented : '.$message);} }
class FileNotFoundException extends ControllerException { function __construct($message){parent::__construct('File Not Found : '.$message);} }
class ClassExtendException extends ControllerException { function __construct($message){parent::__construct('Class Extend : '.$message);} }

class DatabaseException extends Exception {}
class CannotConnectException extends DatabaseException {}
class DBMSError extends DatabaseException {}

class SessionException extends Exception {}
class NullSIDException extends SessionException { function __construct($message){parent::__construct('Null SID : '.$message);} }
class BadSIDException extends SessionException { function __construct($message){parent::__construct('Bad SID : '.$message);} }

class LangException extends Exception {}
class LangNotFoundException extends LangException {}
class ParseLangException extends LangException {}

class AccessException extends Exception {}
class BadUserException extends AccessException { function __construct($message){parent::__construct('Bad User : '.$message);} }
class ConnexionNeededException extends AccessException { function __construct($message){parent::__construct('Connexion Needed : '.$message);} }
