<?PHP
abstract class DOMprocessor
{
	public $encode;
	public $request_method;
	public $requests = array();
	public $content_type = 'text/html';

	private $_html;
	private $_meta_tags=array();
	private	$_prefix = 'php-';
	protected $_opener = ':;';
	protected $_closer = ';:';
	protected $_compilers = array('if'=>'phpIf','elseif'=>'phpElse','else'=>'phpElse','foreach'=>'phpClause','for'=>'phpClause','while'=>'phpClause');

	
	protected function __construct($prefix, $opener, $closer)
	{
		if(empty($prefix)===false) $this->_prefix = $prefix;
		if(empty($opener)===false) $this->_opener = $opener;
		if(empty($closer)===false) $this->_closer = $closer;

		$this->encode = mb_internal_encoding();
		$this->requests = (class_exists('Controller')) ? Controller::getRequests(): $_REQUEST;
		$this->request_method = (class_exists('Controller')) ? Controller::getRequestMethod(): $_SERVER['REQUEST_METHOD'];
	}
	
	protected function process($src, $processor)
	{
		$this->_html = new DOMDocument('1.0', $this->encode);
		$this->_html->formatOutput = false;
		$this->_html->preserveWhiteSpace = false;
		$this->_html->strictErrorChecking = false;
		libxml_use_internal_errors(true);

		$this->load($src);
		call_user_func($processor);
		$html = $this->save();
		return $html;
	}
	
	private function load($src)
	{
		$src = preg_replace_callback('/(?<=<script)(\s*[^>]+?(?==)=\s*([\'"]).+?(?=\2)\2)(?=>)/uis', create_function('$m','return htmlspecialchars($m[1],ENT_NOQUOTES);'),$src);
		$src = preg_replace_callback('/(<script[^>]*>(?!\<))(.+?)(?=<\/script>)(<\/script>)/uis', create_function('$m','return $m[1].htmlspecialchars($m[2]).$m[3];'), $src);
		
		if (substr($this->content_type,-3) === 'xml') {
			$src = preg_replace('/(?<=<)(\w+[^>]+'.$this->_prefix.'[^\=\s]+?(?=>))>/uis', '\1="">', $src);
			$this->_html->loadXML($src,LIBXML_NOCDATA);
		} else {
			if (preg_match('/<meta.*\s+charset\s*=\s*[\'"]([^\'"]+)[\'"]>/is',$src,$matches)) {
				$this->_meta_tags = array($matches[0], '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">');
				$src = str_replace($this->_meta_tags[0], $this->_meta_tags[1], $src);
			}
			
			if (empty($matches) === true && stripos($this->encode,'utf-8') !== false) {
				$src = mb_convert_encoding($src, 'HTML-ENTITIES', $this->encode);
			}
			
			$src = preg_replace('/<(\/*)(\w+):(\w+)/uis','<$1:$2:$3', $src);
			$this->_html->loadHTML($src);
		}

	}

	private function save()
	{
		if (substr($this->content_type,-3) === 'xml') {
			$html = $this->_html->saveXML();
		} else {
			$temp = $this->_html->saveHTML();
			$html = preg_replace('/<(\/*):(\w+):(\w+)/uis','<$1$2:$3', $temp);
		}
	
		unset($this->_html);

		$html = preg_replace_callback('/(<script[^>]*>(?!<))(.+?)(?=<\/script>)(<\/script>)/uis', create_function('$m','return $m[1].htmlspecialchars_decode($m[2]).$m[3];'), $html);	
		
		if (empty($this->_meta_tags) === false) {
			$html = str_replace($this->_meta_tags[1], $this->_meta_tags[0], $html);
		}
		return $html;
	}

	protected function run($code, $variables)
	{
		if (empty($variables) === false) {
			extract($variables);
		}	
		ob_start();
		eval('?>'.$code);
		$result = ob_get_contents();
		ob_end_clean();
		return $result;
	}

	protected function compile($src)
	{
		if (substr($this->content_type,-4) === 'html' || substr($this->content_type,-3) === 'xml') {
			$src = $this->process($src, array($this,'convert'), false);
			$src = str_replace('-&gt;','->', rawurldecode($src));
			$src = preg_replace('/(<\?xml.*?(?=\?>)\?>)/ui',"<?PHP echo<<<PHP_EOS\n$1\nPHP_EOS;\n?>", $src);
			$src = preg_replace('/(<script[^>]*?(?=type[=\s\'"]+?php)[^>]*>)(.*?)(?=<\/script>)(<\/script>)/uis', '<?PHP $2 ?>', $src);
		}
		$src = preg_replace_callback('/'.$this->_opener.'(.+?)'.$this->_closer.'/uis', array($this, 'replacer'), $src);
		return $src;
	}

	private function replacer($matches)
	{
		if (empty($matches[1]) === true) {
			return $matches[0];
		} else {
			$context = $matches[1]; 
			$context = str_replace('&amp;', '&', $context);
			$context = (mb_substr($context, 0, 1, $this->encode) === '=')
				? '<?PHP echo ('.ltrim($context, '=').');?>'
				: '<?PHP echo htmlspecialchars('.$context.', ENT_COMPAT,"'.$this->encode.'", false);?>';
			return $context;
		}
	}
	
	protected function compress($src)
	{
		$src = str_replace(array("\n","\r","\t"), '', $src);
		$src = trim($src);
		return $src;
        }

	private function convert()
	{
		$xPath = new DOMXPath($this->_html);
		foreach ($this->_compilers as $clause=>$method) {
			$result = $xPath->query('//*[@*[starts-with(name(), "'.$this->_prefix.$clause.'")]]');
			if ($result === false) {
				continue;
			}

                        $i=0;
			while ($elem = $result->item($i++)) {
				$condition = $elem->getAttribute($this->_prefix.$clause);
				call_user_func(array($this, $method), $elem, $clause, $condition);
				$elem->removeAttribute($this->_prefix.$clause);
			}
		}
	}

	private function phpIf($elem, $clause, $condition)
	{
		$context = ($condition) ? $clause.'('.$condition.')' : $clause;				
		$openCdata = $this->_html->createCDATASection('<?PHP '.$context.'{?>');
		$closeCdata = $this->_html->createCDATASection('<?PHP }?>');
		$elem->parentNode->insertBefore($openCdata, $elem);				
		$this->insertAfter($closeCdata, $elem);
	}

	private function phpElse($elem, $clause, $condition)
	{
		$context = ($condition) ? $clause.'('.$condition.')' : $clause;				
		$openCdata = $this->_html->createCDATASection('<?PHP }'.$context.'{?>');
		$closeCdata = $this->_html->createCDATASection('<?PHP }?>');
		$previousElem = $elem->previousSibling; 
		while ($previousElem->previousSibling && $previousElem->nodeType != 4) {
			$previousElem = $previousElem->previousSibling;
		}
		$elem->parentNode->replaceChild($openCdata, $previousElem);
		$this->insertAfter($closeCdata, $elem);
	}

	private function phpClause($elem, $clause, $condition)
	{
		$context = ($condition) ? $clause.'('.$condition.')' : $clause;				
		$openCdata = $this->_html->createCDATASection('<?PHP '.$context.'{?>');
		$closeCdata = $this->_html->createCDATASection('<?PHP }?>');
		$this->prependChild($openCdata, $elem);
		$elem->appendChild($closeCdata);
	}

	protected function form()
	{
		$forms = $this->_html->getElementsByTagName('form');
		foreach ($forms as $form) {
			$formname = $form->getAttribute('name');
			$method = strtoupper($form->getAttribute('method'));
			$isSent = (strcasecmp($this->request_method, $method) === 0 && isset($this->requests[$formname]));
			
			if ($isSent === true) {
				$this->setFormValues($this->requests, $form);
			}
		}
	}

	private function setFormValues($values, $form, $prefix='')
	{
		$xPath = new DOMXPath($this->_html);
		foreach ($values as $key => $value) {
			$elem = false;
			$name = (empty($prefix)) ? $key : $prefix.'['.$key.']';
			
			if (is_array($value)) {
				$this->setFormValues($value, $form, $name);
			} else {
				$formname = $form->getAttribute('name');
				$res = $xPath->query('.//*[starts-with(@name, "'.$name.'")]', $form);
				if ($res->length == 1) {
					$elem = $res->item(0);
					if ($elem->tagName == 'select' && $xPath->query('.//option[@value="'.$value.'"]', $elem)->length === 1) {
						$elem = $xPath->query('.//option[@value="'.$value.'"]', $elem)->item(0);
					}
				} elseif ($res->length == 0 && empty($prefix) === false) {
					$query = './/*[starts-with(@name, "'.$prefix.'")]';
					$res = $xPath->query($query, $form);
					if ($res->length > 0) {
						$query_value = htmlentities($value, ENT_COMPAT, $this->encode);
						if (($elem = $xPath->query($query.'[@value="'.$query_value.'"]', $form)) && $elem->length == 1) {
							$elem = $elem->item(0);
						} elseif (($elem = $xPath->query($query.'/*[@value="'.$query_value.'"]', $form)) && $elem->length == 1) {
							$elem = $elem->item(0);
						} elseif ( is_numeric($key) === true && $res->item($key)) {
							$elem = $res->item($key);
						}
					}
				}

				if (isset($elem) && $elem !== false) {
					$this->setElementValue($elem, $value);					
				}
			}
		}
	}

	protected function setElementValue($elem, $value)
	{
		$type = ($elem->hasAttribute('type')) ? strtolower($elem->getAttribute('type')) : false;
		$tag = strtolower($elem->nodeName);

		if ($elem->hasAttribute('value')) {
			if ($elem->hasAttribute('name')) {
				$ref = $elem->getAttribute('value');
				
				if ($ref !== $value) {
					$elem->setAttribute('value', $value);
				} elseif ( $type == 'checkbox' || $type == 'radio' ) {
					$elem->setAttribute('checked', true);
				}
			} elseif ($tag == 'option') {
				$elem->setAttribute('selected', true);
			}
		} elseif ($tag == 'textarea') {
			$text = $this->_html->createTextNode($value);
			$elem->appendChild($text);
		} else {
			$elem->setAttribute('value', $value);
		}
	}

	protected function insertAfter($newnode, $ref)
	{
		if ($ref->nextSibling) {
			return $ref->parentNode->insertBefore($newnode, $ref->nextSibling); 
		} else {
			return $ref->parentNode->appendChild($newnode);
		}
	}

	protected function prependChild($newnode, $ref)
	{
		if ($ref->hasChildNodes()) {
			return $ref->insertBefore($newnode, $ref->childNodes->item(0)); 
		} else {
			return $ref->appendChild($newnode);
		}
	}
}




class View extends DOMprocessor
{
	private $_timestamp=0;
	private $_lastupdate=0;
	private $_template;
	private $_variables=array();
	private $_registres=array();
	private $_client_cached=false;

	public $root;
	public $path;
	public $caches = array();
	public $compressors=array(__CLASS__,'compress');

	public static $cache_ext = '.txt';
	public static $cache_root;
	public static $client_cache=false;
	public static $template_ext = '.temp';
	public static $template_types = array('html'=>'text/html','js'=>'text/javascript','css'=>'text/css','txt'=>'text/plain'
																	,'xml'=>'application/xml','rss'=>'application/rss+xml','atom'=>' application/atom+xml');
	public static $template_root;
	public static $benchmark;
	public static $cache_chunk_range = 32;
	public static $http_status = 200;
	public static $http_exceptions = array();

	public static $php_prefix;
	public static $php_opener;	
	public static $php_closer;

	const NO_CACHE = 'NO_CACHE';

	public function __construct($template_name='', $path='', $class_path = '')
	{
		$this->_timestamp = microtime(true);

		parent::__construct(self::$php_prefix, self::$php_opener, self::$php_closer);
		
		$this->queryString = $path;
		$this->path = $path;
		$this->root = ($this->path) ? substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], $this->path)) : $_SERVER['REQUEST_URI'];
		
		if (empty($template_name) === false) {
			$this->_template = $this->getTemplate($template_name, $this->_lastupdate, $class_path);
		}

		if (empty($class_path) === false && ($cache = $this->getCache($this->path, $cache_timestamp)) && $this->_lastupdate <= $cache_timestamp) {
			$this->caches = $this->_variables = $cache;
			$this->_lastupdate = $cache_timestamp;
		}

		$this->_client_cached = (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) === true && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) === $this->_lastupdate);

	}

	public function set($val, $option=NULL, $option2=NULL)
	{
		$array=array();
		$flag=false;
		if (is_array($val) === true) {
			$array = $val;
			$flag = $option;
		} elseif ($option !== NULL) {
			$array[$val] = $option;
			$flag = $option2;
		}

		if ($flag === self::NO_CACHE) {
                        $this->_registres = $array+$this->_registres;
                } else {
                        $this->_variables = $array+$this->_variables;
                }
	}

	public function __destruct()
	{
		if (headers_sent() === true) {
		 exit;
		} elseif (self::$http_status === 200
				&& empty($this->_variables) === false
				&& $this->setCache($this->path, $this->_variables, $this->_lastupdate) === false
				&& $this->_client_cached === true
				&& self::$client_cache===true)
		{
			header('Cache-Control: private');
			header('Expires: '.gmdate("D, d M Y H:i:s",strtotime('+ 1 month')).' GMT');
			header('Last-Modified: '.gmdate("D, d M Y H:i:s",$this->_lastupdate).' GMT');
			self::$http_status = 304;
		} elseif (empty($this->_variables) === true && empty($this->_registres) === false) {
			$this->_lastupdate = 0;
		}
		$this->checkException(self::$http_status);

		$output = $this->getOutput($this->_template);
		$this->checkException(self::$http_status, $output);
		if ($this->_lastupdate > 0 && self::$client_cache===true) {
			header('Cache-Control: private');
			header('Expires: '.gmdate("D, d M Y H:i:s",strtotime('+ 1 month')).' GMT');
			header('Last-Modified: '.gmdate("D, d M Y H:i:s",$this->_lastupdate).' GMT');
		}
		header(' ', true, self::$http_status);
		header('Content-type: '.$this->content_type.'; charset='.$this->encode);
		print($output);	
		if (self::$benchmark === true) {
			$text = microtime(true)-$this->_timestamp;
			print('<script>alert("'.$text.'")</script>');
		}
		exit;
	}

	private function checkException($status, &$output='')
	{
		if ($status !== 200) {
			if (isset(self::$http_exceptions[$status])) {
				$this->_template = $this->getTemplate(self::$http_exceptions[$status]);
				if (empty($output) === false) {
					$output = $this->getOutput($this->_template);
				}
			} else {
				header(' ', true, $status);
				exit;
			}
		}
	}
	
	private function getTemplate($name, &$timestamp=0, $class_path='')
	{
		$filename = $this->getTemplateFileName($name);
		$this->content_type = $this->getTemplateType($filename);
		
		if (true === empty($filename)) {
			$template = '';
		} elseif ('text' !== substr($this->content_type, 0, 4) && 'xml' !== substr($this->content_type, -3)) {
			$template = file_get_contents(self::$template_root.DIRECTORY_SEPARATOR.$filename);
		} else {
			$cache = $this->getCache($filename, $timestamp);
			if (false === empty($cache)) {
				foreach ($cache['files'] as $file) {
					if ($timestamp <= filemtime($file)) {
						unlink($this->getCachePath($filename, self::$cache_root));
						$cache=false;
						break;
					}
				}
			}
			
			if (empty($cache) === true) {
				$files = array(__FILE__);
				if (empty($class_path) === false) {
					$file_list = get_included_files();
					$flag = false;
					foreach ($file_list as $file) {
						if ($file === $class_path) {
                                                        $flag = true;
                                                }
						if ($flag === true) {
                                                    $files[] = $file;
                                                }
					}
				}
				$template = $this->makeTemplate(self::$template_root.DIRECTORY_SEPARATOR.$filename, $files, $this->content_type);
				if (empty($template) === false) {
					$cache = array('files'=>$files,'template'=>$template);
					$this->setCache($filename, $cache, $timestamp);
				}
			} else {
				$template = $cache['template'];
			}
		}
		return $template;
	}

	private function makeTemplate($path, &$files, &$type)
	{
		$files[] = $path;
		$template = file_get_contents($path);
		while (preg_match('/<\!\-\-\s*#include\s+file\s*=\s*([\'"])(.+?)(?=\1)[^>]+>/uis', $template, $mathces)) {
			$path = str_replace('\\', '/', $mathces[2]);
			$path = preg_replace_callback('/'.$this->_opener.'(.+?)'.$this->_closer.'/uis', create_function('$matches','return eval("return @".$matches[1].";");'), $path);
			$url = parse_url($path);
			if (isset($url['scheme']) === true && stripos($url['scheme'], 'http') === 0) {
				$context = stream_context_create();
				stream_context_set_option($context, $url['scheme'], 'ignore_errors', true);
				$content = file_get_contents($path, false, $context);
				if (strpos($http_response_header[0], '200') === false) {
					$content = '';
				}
			} else {
				if (is_file($path)=== false) {
					$path = self::$template_root.DIRECTORY_SEPARATOR.$path;
				} 
				if (is_file($path)=== false) {
					$path = self::$template_root.DIRECTORY_SEPARATOR.$mathces[2];
				} 
				
				$files[] = $path;
				$content = file_get_contents($path);
			}
			$template = str_replace($mathces[0], $content, $template);
		}
		
		$template = $this->compile($template);
				
		return $template;
	}

	private function getTemplateFileName($name)
	{
		$dh  = opendir(self::$template_root);
		while (false !== ($filename = readdir($dh))) {
			if (strpos($filename, $name.self::$template_ext) === 0) {
                            break;
                        }
		}
		closedir($dh);
		return $filename;
	}

	private function getTemplateType($filename)
	{
		$type = 'text/html';
		$ext = substr($filename, strpos($filename, self::$template_ext)+strlen(self::$template_ext) );
		if (is_string($ext) === true) {
			$ext = trim($ext,'.');
			if (false == empty(self::$template_types[$ext])) {
                            $type = self::$template_types[$ext];
                        }
		}
		return $type;
	}
	
	private function getOutput($template)
	{
		$output='';
		$this->encode = mb_detect_encoding($template);
		if (empty($template) === false) {
			if ('text' === substr($this->content_type, 0, 4) || 'xml' === substr($this->content_type, -3)) {
				$escapes = array('<?'=>'&?lt;', '?>'=>'&?gt;');
				$variables = $this->getVariables($escapes);
				$output = $this->run($template, $variables);
				$output = str_replace($escapes, array_keys($escapes), $output);
				if ('html' === substr($this->content_type,-4) || 'xml' === substr($this->content_type,-3)) {
					$output = $this->process($output,array($this, 'form'));
				}
			} else {
				$output = $template;
			}
		}
		return $output;
	}
	
	private function getVariables($escapes)
	{
		$serial = serialize($this->_variables);
		$serial = str_replace(array_keys($escapes), $escapes, $serial);
		$variables = unserialize($serial);
		$variables = $variables + $this->_registres;
		return $variables;
	}
	
	public function getCache($path, &$timestamp=0)
	{
		if (empty(self::$cache_root)) {
                    return false;
                }
                
		$result = false;
		$path = $this->getCachePath($path, self::$cache_root);
		if (is_file($path) === true) {
			$timestamp = filemtime($path);
			$file = file_get_contents($path);
			try {
				$result = unserialize($file);
			} catch (Exception $e) {
				$result = false;
			}
		}
		return $result;
	}

	private function getCachePath($path, $root='')
	{
		$dirs = explode('/', $path);
		foreach ($dirs as $i=>$dir) {
			$path = rawurlencode($dir);
			$dir = str_split($path, self::$cache_chunk_range);
			$dirs[$i] = implode(DIRECTORY_SEPARATOR, $dir);
		}
		$path = implode(DIRECTORY_SEPARATOR, $dirs).self::$cache_ext;
		if (empty($root) === false) {
			$path = rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$path;
		}
		return $path;
	}

	public function setCache($path, $data, &$timestamp=0)
	{
		if (empty(self::$cache_root)) {
                    return false;
                } elseif (is_dir(self::$cache_root) === false) {
			mkdir(self::$cache_root);
			@chmod(self::$cache_root, fileperms(self::$cache_root.'/../'));
		}

		$mode = fileperms(self::$cache_root);
		$root = dir(self::$cache_root);
		
		$path = $this->getCachePath($path);
		$dirs = explode(DIRECTORY_SEPARATOR, $path);
		$dest = $root->path;
		
		$serial = serialize($data);		
		$path=$dest.DIRECTORY_SEPARATOR.$path;
		if (is_file($path) === true) {
			$cache = file_get_contents($path);
			if ($cache === $serial) {
				if (empty($timestamp) === false && filemtime($path) < $timestamp) {
                                    touch($path, $timestamp);
                                }
				return false;
			}
		}
		
		try {
			foreach ($dirs as $i=>$dir) {
				$dest .= DIRECTORY_SEPARATOR.$dir;
				if (is_dir($dest) === true) {
					continue;
				} else if(count($dirs) > $i+1) {
					mkdir($dest);
					@chmod($dest, $mode);
				} elseif(file_put_contents($dest, $serial)) {
					@chmod($dest, $mode);
					clearstatcache();
					$timestamp=filemtime($dest);
				}
			}
			return true;
		} catch (Exception $e) {
			return false;
		}
	}	

	public function unsetCache($path)
	{
		if (empty(self::$cache_root)) {
                    return false;
                }
                    
		$path = self::$cache_root.DIRECTORY_SEPARATOR.$this->getCachePath($path);
		if (is_file($path) === true) {
			return unlink($path);
		}
	}			

}
