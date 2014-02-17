<?PHP
abstract class Model
{
	public static $view = false;
	
	public function __call($name, $arguments)
	{
		if (method_exists(self::$view, $name) === true) {
			return call_user_func_array(array(self::$view, $name), $arguments);
		}
	}
	
	public function __set($key, $value)
	{
		if (property_exists(self::$view, $key) === true) {
			self::$view->$key = $value;
		}
		$this->$key = $value;
	}

	public function __get($key)
	{
		if (property_exists(self::$view, $key)) {
			$value = self::$view->$key;
			if (is_array($value) === true) {
				$value = new ArrayObject($value);
			}
			self::$view->$key = &$value;
			return $value;
		}
	}

}