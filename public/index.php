<?php

/* setup a native class object to act as a service locator */
$mvc = new stdclass();

/* get the run code from the htaccess file */
$mvc->run_code = getenv('RUNCODE');

/* Defaults to no errors displayed */
error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED ^ E_STRICT);
ini_set('display_errors', 0);

/* if it's DEBUG then turn the error display on */
if ($mvc->run_code == 'DEBUG') {
	error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
	ini_set('display_errors', 1);
}

/*
start session - if needed
since this hits the hard drive hard
it can really slow speed down.
*/
//session_start();

/* Where is this bootstrap file */
$mvc->path = __DIR__;

/* app path */
$mvc->app = realpath($mvc->path.'/../app');

/* register the autoloader */
spl_autoload_register('mvc_autoloader');

/* is this a ajax request? */
$mvc->is_ajax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ? 'Ajax' : false;

/* with http:// and with trailing slash */
$mvc->base_url = trim('http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']), '/');

/* The GET method is default so controller methods look like openAction, others are handled directly openPostAction, openPutAction, openDeleteAction, etc... */
$mvc->raw_request = ucfirst(strtolower($_SERVER['REQUEST_METHOD']));
$mvc->request     = ($mvc->raw_request == 'Get') ? '' : $mvc->raw_request;

/* Put ANY (POST, PUT, DELETE) posted into into $_POST */
parse_str(file_get_contents('php://input'), $_POST);

/* get the uri (uniform resource identifier) */
$mvc->uri = trim(urldecode(substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), strlen(dirname($_SERVER['SCRIPT_NAME'])))), '/');

/* get the uri pieces */
$mvc->segs = $mvc->raw_segs = explode('/', $mvc->uri);

/* If they didn't include a controller and method use the defaults  main & index */
$mvc->controller = (!@empty($mvc->segs[0])) ? strtolower(array_shift($mvc->segs)) : 'main';
$mvc->method     = (!@empty($mvc->segs[0])) ? strtolower(array_shift($mvc->segs)) : 'index';

/* try to auto load the controller - will throw an error you must catch if it's not there */
$mvc->classname = $mvc->controller.'Controller';

/* instantiate it */
$controller = new $mvc->classname();

/* what method are we going to try to call? */
$method = $mvc->method.$mvc->request.$mvc->is_ajax.'Action';

/* does that method even exist? */
if (!method_exists($controller, $method)) {
	/* Method not found */
	mvc_error('Method '.$method.' Not Found');
}

/* call the method and echo what's returned */
echo call_user_func_array([$controller, $method], $mvc->segs);

/* all done */

/* give me a reference to the global service locator */
function mvc() {
	global $mvc;

	return $mvc;
}

/* class autoloader */
function mvc_autoloader($name,$load = true) {
	/* autoload controllers or libraries */
	$filename = ($name{0} == '/') ? $name : mvc()->app.'/'.((substr($name, -10) != 'Controller') ? 'libraries' : 'controllers').'/'.$name.'.php';

	/* is the file there? */
	if (!file_exists($filename)) {
		/* simple error and exit */
		mvc_error('File '.$filename.' Not Found');
	}

	/* then let's load it if we need to */
	if ($load) {
		require_once $filename;
	}
}

/* auto load view and extract view data */
function view($_mvc_view_name, $_mvc_view_data = []) {
	/* what file are we looking for? */
	$_mvc_view_file = mvc()->app.'/views/'.$_mvc_view_name.'.php';
	
	/* just find out if it's there don't load it */
	mvc_autoloader($_mvc_view_file,false);

	/* extract out view data and make it in scope */
	extract($_mvc_view_data);

	/* start output cache */
	ob_start();

	/* load in view (which now has access to the in scope view data */
	require $_mvc_view_file;

	/* capture cache and return */
	return ob_get_clean();
}

function config($filename) {
	/* empty config array */
	$config = [];
	
	/* what config are we trying to load? */
	$file = mvc()->app.'/config/'.$filename.'.php';

	/* just find out if it's there but don't load it */
	mvc_autoloader($file,false);
	
	/* return the array it contains */
	return $file;
}

/* single die method */
function mvc_error($string) {
	/* save errors in a simple log */
	file_put_contents('../app/var/logs/error.log', date('Y-m-d H:i:s ').$string.chr(10), FILE_APPEND);

	/* don't show to much unless env var = DEBUG */
	echo (mvc()->run_code != 'DEBUG') ? 'Sorry a fatal error has occurred' : $string;

	/* exit clean */
	exit(1);
}