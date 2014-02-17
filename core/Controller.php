<?PHP
class Controller
{
	public static $query_key;
	public static $presets=array();
	public static $finalizers=array();
	public static $directions = array();

	public static function execute()
	{
            $path = self::getPath();
            foreach (self::$directions as $regExp => $class_name) {
                $is_page = false;		
                if (empty($regExp) === true && empty($path) === true) {
                        $is_page = true;		
                        break;
                } elseif (preg_match('@^'.$regExp.'$@uis', $path) === 1) {
                        $is_page = true;
                        break;
                }
            }

            if ($is_page === false) {
                    View::$http_status = 404;
                    $class_name = '';
            }

            if (empty(self::$presets) === false) {
                    foreach (self::$presets as $file) {
                            class_exists($file, true);
                    }
            }

            if (class_exists($class_name,true) === true) {
                $file_name = trim(str_replace('\\', DIRECTORY_SEPARATOR, $class_name), DIRECTORY_SEPARATOR).'.php';
                if ((is_file($class_path = CORE_ROOT.DIRECTORY_SEPARATOR.$file_name) === true
                                || is_file($class_path = CORE_ROOT.DIRECTORY_SEPARATOR.SITE_CODE.DIRECTORY_SEPARATOR.$file_name) === true)
                        && in_array($class_path, get_included_files()) === true) {
                        $list = explode(DIRECTORY_SEPARATOR, $class_path);
                        Model::$view = new View(substr(array_pop($list), 0, -4), $path, $class_path);
                        new $class_name();
                }
            } else {
                new View($class_name, $path);
            }	

            if (empty(self::$finalizers) === false) {
                foreach (self::$finalizers as $file) {
                    if (is_file($file) === true) {
                       require_once($file);
                    }
                }
            }
	}

	public static function getPath()
	{
            $root = self::getWebSiteRoot();
            $url_sets = parse_url(filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_STRIPPED));
            $path = (isset($url_sets['path'])) ? substr($url_sets['path'], strlen($root)) : false;
            return ($path === false) ? '' : $path;
	}

	public static function getWebSiteRoot()
	{
            $req_dirs = array_diff(explode('/', trim(filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_STRIPPED), '/')), array(''));
            $scp_dirs = explode('/', filter_input(INPUT_SERVER, 'SCRIPT_FILENAME', FILTER_SANITIZE_STRIPPED));
            $result = '/';
            $i = 0;

            if (count($req_dirs) > 0) {
                    while (isset($req_dirs[$i]) === true && $dir = $req_dirs[$i++]) {
                            if (in_array($dir, $scp_dirs) === true) {
                                $result .= $dir.'/';
                            } else {
                                break;
                            }
                    }
            }
            return $result;
	}

	public static function jumpto($destination)
	{
            $root = self::getWebSiteRoot();
            $path = (strpos($destination, $root) === 0) ? $destination : $root.$destination;
            View::$http_status = 303;
            header("Location: ".$path);
            exit;
	}

	public static function getRequestMethod()
	{
            return filter_input(INPUT_SERVER, 'REQUEST_METHOD', FILTER_SANITIZE_STRIPPED);
	}

	public static function getRequests()
	{
            $requests = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRIPPED);
            if (isset($requests[self::$query_key])) {
                    unset($requests[self::$query_key]);
            }

            if (self::getRequestMethod() === 'POST') {
                    $posts = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRIPPED);
                    $requests = (empty($requests) === false) ? $requests + $posts : $posts;
            }
            return $requests;
	}

}
