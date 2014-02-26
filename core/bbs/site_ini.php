<?PHP

/*****************************
Definitions
*****************************/
define('SITE_NAME', 'BBS');
define('LIST_ITEM_LIMIT', 10);

/*****************************
Site settings
*****************************/

mb_detect_order('UTF-8,EUC-JP,SJIS,JIS,ASCII');
mb_internal_encoding('UTF-8');
session_start();
session_regenerate_id();

/*****************************
Controller settings
*****************************/

Controller::$query_key 		= 'q';
Controller::$directions 	= array('\d*'=>'Index',
    'topic/.*'=>'Topic',
    'admin'=>'Admin',
    'manage'=>'Manage',
    'search/.*'=>'Search',
    'script'=>'Script',
    'style'=>'Style');

/*****************************
View settings
*****************************/
View::$template_root 	= CORE_SITE_ROOT.DIRECTORY_SEPARATOR.'template';
View::$cache_root       = CORE_SITE_ROOT.DIRECTORY_SEPARATOR.'cache';
View::$http_exceptions	= array(400=>'Badrequest', 401=>'Unauthorized', 403=>'Forbidden', 404=>'Notfound', 500=>'Error');
