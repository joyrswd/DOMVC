<?PHP
if (realpath(filter_input(INPUT_SERVER, 'SCRIPT_FILENAME', FILTER_SANITIZE_STRIPPED)) === realpath(__FILE__)) {
    exit;
}
define('SYSTEM_ROOT', 	dirname(__FILE__) );
define('SITE_ROOT', 	dirname(filter_input(INPUT_SERVER, 'SCRIPT_FILENAME', FILTER_SANITIZE_STRIPPED)) );
define('CORE_ROOT', 	SYSTEM_ROOT.DIRECTORY_SEPARATOR.'core' );

if (defined('SITE_CODE') === false) {
	define('SITE_CODE',  basename(SITE_ROOT));
}

define('CORE_SITE_ROOT', 	CORE_ROOT.DIRECTORY_SEPARATOR.SITE_CODE );

function __autoload($className)
{
	//$libs = array('assets');
	if (class_exists($className) === false) {
		$name = str_replace('\\', DIRECTORY_SEPARATOR, $className).'.php';
		if (is_file($filepath = CORE_ROOT.DIRECTORY_SEPARATOR.$name) === true) {
			require_once($filepath);
		} elseif (is_file($filepath = CORE_SITE_ROOT.DIRECTORY_SEPARATOR.$name) === true) {
			require_once($filepath);
		} elseif (empty($libs) === false) {
			foreach ($libs as $lib) {
				if ( is_file( $filepath = CORE_ROOT.DIRECTORY_SEPARATOR.$lib.DIRECTORY_SEPARATOR.$name) === true ) {
					require_once($filepath);
				}
			}
		}
	}
}

require_once(CORE_SITE_ROOT.DIRECTORY_SEPARATOR.'site_ini.php');
Controller::execute();
exit;