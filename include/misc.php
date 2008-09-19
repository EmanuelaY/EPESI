<?php

/**
 * This file defines all other base functionality.
 *
 * @author Paul Bukowski <pbukowski@telaxus.com>
 * @copyright Copyright &copy; 2006, Telaxus LLC
 * @version 1.0
 * @package epesi-base
 * @license SPL
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

/**
 * Generates random string of specified length.
 *
 * @param integer length
 * @return string random string
 */
function generate_password($length = 8) {
	// start with a blank password
	$password = "";

	// define possible characters
	$possible = "0123456789bcdfghjkmnpqrstvwxyz";

	// set up a counter
	$i = 0;

	// add random characters to $password until $length is reached
	while ($i < $length) {
		// pick a random character from the possible ones
		$char = substr($possible, mt_rand(0, strlen($possible) - 1), 1);

		// we don't want this character if it's already in the password
		if (!strstr($password, $char)) {
			$password .= $char;
			$i++;
		}
	}
	// done!
	return $password;
}

/**
 * Redirects to specified url. First parameter is array of variables to pass with redirection.
 * If no argument is specified returns saved redirect url.
 *
 * @param array
 * @return string saved url
 */
function location($u = null,$ret = false, $clear = true) {
	static $variables = false;

	if($ret) {
		$ret = $variables;
		if($clear)
			$variables = false;
		return $ret;
	}

	if($variables==false) $variables=array();

	if (is_array($u))
		$variables = array_merge($variables, $u);
}

/**
 * Requests css loading.
 *
 * @param string css file path and name
 */
function load_css($u,$loader=null) {
	return Epesi::load_css($u,$loader);
}

/**
 * Adds js to load.
 *
 * @param string javascript file
 * @param boolean append contents of js file instead of use src tag?
 */
function load_js($u,$loader=null) {
	return Epesi::load_js($u,$loader);
}

/**
 * Adds js block to eval. If no argument is specified returns saved jses.
 *
 * @param string javascrpit code
 */
function eval_js($u,$del_on_loc=true) {
	Epesi::js($u,$del_on_loc);
}
/**
 * Adds js block to eval. Given js will be evaluated only once.
 *
 * @param string javascrpit code
 * @return bool true on success, false otherwise
 */
function eval_js_once($u,$del_on_loc=false) {
	if(!is_string($u) || strlen($u)==0) return false;
	$md5 = md5($u);
	if (!isset($_SESSION['client']['__evaled_jses__'][$md5])) {
		Epesi::js($u,$del_on_loc);
		$_SESSION['client']['__evaled_jses__'][$md5] = true;
		return true;
	}
	return false;
}

/**
 * Adds method to call on exit.
 *
 * @param mixed function to call
 * @param mixed list of arguments
 * @param bool if set to false the function will be called only once, location() doesn't affect with double call
 * @param bool if set to true the function will return currently hold list of functions (don't use it in modules)
 * @return mixed returns function list if requested, true if function was added to list, false otherwise
 */
function on_exit($u = null, $args = null, $stable=true, $ret = false) {
	static $headers = array ();

	if($ret) {
		$ret = $headers;
		$headers = array ();
		foreach($ret as $v)
			if($v['stable']) $headers[] = $v;
		return $ret;
	}

	if ($u != false) {
		$headers[] = array('func'=>$u,'args'=>$args, 'stable'=>$stable);
		return true;
	}
	return false;
}
/**
 * Adds method to call on init.
 *
 * @param mixed function to call
 * @param mixed list of arguments
 * @param bool if set to false the function will be called only once, location() doesn't affect with double call
 * @param bool if set to true the function will return currently hold list of functions (don't use it in modules)
 * @return mixed function list if requested, true if function was added to list, false otherwise
 */
function on_init($u = null, $args = null, $stable=true, $ret = false) {
	static $headers = array ();

	if($ret) {
		$ret = $headers;
		$headers = array ();
		foreach($ret as $v)
			if($v['stable']) $headers[] = $v;
		return $ret;
	}

	if ($u != false)
		$headers[] = array('func'=>$u,'args'=>$args, 'stable'=>$stable);
}

if (STRIP_OUTPUT) {
	function strip_html($data) {
		// strip unecessary comments and characters from a webpages text
		// all line comments, multi-line comments \\r \\n \\t multi-spaces that make a script readable.
		// it also safeguards enquoted values and values within textareas, as these are required

		$data = preg_replace_callback("/>[^<]*<\\/textarea/i", "harden_characters", $data);
		$data = preg_replace_callback("/\"[^\"<>]+\"/", "harden_characters", $data);

		$data = preg_replace("/(\\t|\\r|\\n)/", "", $data); // remove new lines \\n, tabs and \\r

		$data = preg_replace_callback("/\"[^\"<>]+\"/", "unharden_characters", $data);
		$data = preg_replace_callback("/>[^<]*<\\/textarea/", "unharden_characters", $data);

		return $data;
	}

	function harden_characters($array) {
		$safe = $array[0];
		$safe = preg_replace('/\\n/', "%0A", $safe);
		$safe = preg_replace('/\\t/', "%09", $safe);
		return $safe;
	}

	function unharden_characters($array) {
		$safe = $array[0];
		$safe = preg_replace('/%0A/', "\\n", $safe);
		$safe = preg_replace('/%09/', "\\t", $safe);
		return $safe;
	}

	function strip_js($input) {
		$stripPregs = array (
			'/^\s*$/',
			'/^\s*\/\/.*$/'
		);
		$blockStart = '/^\s*\/\/\*/';
		$blockEnd = '/\*\/\s*(.*)$/';
		$inlineComment = '/\/\*.*\*\//';
		$out = '';

		$lines = explode("\n", $input);
		$inblock = false;
		foreach ($lines as $line) {
			$keep = true;
			if ($inblock) {
				if (preg_match($blockEnd, $line)) {
					$inblock = false;
					$line = preg_match($blockEnd, '$1', $line);
					$keep = strlen($line) > 0;
				}
			} else
				if (preg_match($inlineComment, $line)) {
					$keep = true;
				} else
					if (preg_match($blockStart, $line)) {
						$inblock = true;
						$keep = false;
					}

			if (!$inblock) {
				foreach ($stripPregs as $preg) {
					if (preg_match($preg, $line)) {
						$keep = false;
						break;
					}
				}
			}

			if ($keep && !$inblock) {
				$out .= trim($line) . "\n";
			}
		}
		return $out;
	}
}
/**
 * Returns directory tree starting at given directory.
 *
 * @param string starting directory
 * @param integer maximum depth of the tree
 * @param integer depth counter, for internal use
 * @return array directory tree
 */
function dir_tree($path, $hidden=false, $maxdepth = -1, $d = 0) {
	if (substr($path, strlen($path) - 1) != '/') {
		$path .= '/';
	}
	$dirlist = array ();
	$dirlist[] = $path;
	if ($handle = opendir($path)) {
		while (false !== ($file = readdir($handle))) {
			if ($file == '.' || $file == '..' || (!$hidden && ereg('^\.',$file))) 
				continue;
			$file = $path . $file;
			if (is_dir($file) && $d >= 0 && ($d < $maxdepth || $maxdepth < 0)) {
				$result = dir_tree($file . '/', $hidden, $maxdepth, $d +1);
				$dirlist = array_merge($dirlist, $result);
			}
		}
		closedir($handle);
	}
	if ($d == 0) {
		natcasesort($dirlist);
	}
	return ($dirlist);
}

/**
 * Returns files tree matching pattern starting at given directory.
 *
 * @param string starting directory
 * @param string glob pattern
 * @param mixed glob flags
 * @param integer maximum depth of the tree
 * @param integer depth counter, for internal use
 * @return array directory tree
 */
function ereg_tree($path, $pattern, $maxdepth = -1, $d = 0) {
	if (substr($path, strlen($path) - 1) != '/') {
		$path .= '/';
	}
	$list = array();
	if ($handle = opendir($path)) {
		while (false !== ($file = readdir($handle))) {
			if ($file != '.' && $file != '..') {
				$filep = $path . $file;
				if(ereg($pattern,$file)) $list[] = $filep;
				if (is_dir($filep) && $d >= 0 && ($d < $maxdepth || $maxdepth < 0))
					$list = array_merge($list,ereg_tree($filep . '/', $pattern, $maxdepth, $d +1));
			}
		}
		closedir($handle);
	}
	if ($d == 0) {
		natcasesort($list);
	}
	return $list;
}

/**
 * Removes directory recursively, deleteing all files stored under this directory
 *
 * @param string directory to remove
 */
function recursive_rmdir($path) {
	if (!is_dir($path)) {
		unlink($path);
		return;
	}
	$path = rtrim($path, '/');
	$content = scandir($path);
	foreach ($content as $name) {
		if ($name == '.' || $name == '..')
			continue;
		$name = $path . '/' . $name;
		if (is_dir($name)) {
			recursive_rmdir($name);
		} else
			unlink($name);
	}
	rmdir($path);
}
/**
 * Copies directory recursively, along with all files stored under source directory.
 * If destination directory doesn't exist it will be created.
 *
 * @param string source directory
 * @param string destination directory
 */
function recursive_copy($src, $dest) {
	if (!is_dir($src)) {
		copy($src, $dest);
		return;
	}
	$src = rtrim($src, '/');
	$dest = rtrim($dest, '/');
	if (!is_dir($dest))
		mkdir($dest);
	$content = scandir($src);
	foreach ($content as $name) {
		if ($name == '.' || $name == '..')
			continue;
		$src_name = $src . '/' . $name;
		$dest_name = $dest . '/' . $name;
		if (is_dir($src_name)) {
			if (!is_dir($dest_name)) mkdir($dest_name);
			recursive_copy($src_name, $dest_name);
		} else
			copy($src_name, $dest_name);
	}
}

function escapeJS($str,$double=true,$single=true) {return Epesi::escapeJS($str,$double,$single);}

function get_epesi_url() {
	$protocol = (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS'])!== "off") ? 'https://' : 'http://';
	return $protocol.$_SERVER['HTTP_HOST'].str_replace('\\','/',dirname($_SERVER['PHP_SELF']));
}

function filesize_hr($size) {
	if(!is_numeric($size)) $size = filesize($size);
	$bytes = array('B','KB','MB','GB','TB');
	foreach($bytes as $val) {
		if($size > 1024){
			$size = $size / 1024;
		}else{
			break;
		}
  	}
	return number_format($size, 2)." ".$val;
}

if ( !function_exists('json_decode') ){
	function json_decode($content, $assoc=false){
		if((@include_once('Services/JSON.php'))===false)
			trigger_error('Please install PEAR Services/JSON or upgrade PHP to version 5.2 or higher',E_USER_ERROR);
		if ( $assoc ){
			$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
		} else {
			$json = new Services_JSON;
		}
		return $json->decode($content);
	}
}
if ( !function_exists('json_encode') ){
	function json_encode($content){
		if((@include_once('Services/JSON.php'))===false)
			trigger_error('Please install PEAR Services/JSON or upgrade PHP to version 5.2 or higher',E_USER_ERROR);
		$json = new Services_JSON;
		return $json->encode($content);
	}
}


////////////////////////////////////////////////////
// mobile devices

function detect_mobile_device(){
  
  // check if the user agent value claims to be windows but not windows mobile
  if(stristr($_SERVER['HTTP_USER_AGENT'],'windows')&&!(stristr($_SERVER['HTTP_USER_AGENT'],'windows ce')||stristr($_SERVER['HTTP_USER_AGENT'],'palm'))){
    return false;
  }
  // check if the user agent gives away any tell tale signs it's a mobile browser
  if(eregi('up.browser|up.link|windows ce|iemobile|mini|mmp|symbian|midp|wap|phone|pocket|mobile|pda|psp',$_SERVER['HTTP_USER_AGENT'])){
    return true;
  }
  // check the http accept header to see if wap.wml or wap.xhtml support is claimed
  if(stristr($_SERVER['HTTP_ACCEPT'],'text/vnd.wap.wml')||stristr($_SERVER['HTTP_ACCEPT'],'application/vnd.wap.xhtml+xml')){
    return true;
  }
  // check if there are any tell tales signs it's a mobile device from the _server headers
  if(isset($_SERVER['HTTP_X_WAP_PROFILE'])||isset($_SERVER['HTTP_PROFILE'])||isset($_SERVER['X-OperaMini-Features'])||isset($_SERVER['UA-pixels'])){
    return true;
  }
  // build an array with the first four characters from the most common mobile user agents
  $a = array(
                    'acs-'=>'acs-',
                    'alav'=>'alav',
                    'alca'=>'alca',
                    'amoi'=>'amoi',
                    'audi'=>'audi',
                    'aste'=>'aste',
                    'avan'=>'avan',
                    'benq'=>'benq',
                    'bird'=>'bird',
                    'blac'=>'blac',
                    'blaz'=>'blaz',
                    'brew'=>'brew',
                    'cell'=>'cell',
                    'cldc'=>'cldc',
                    'cmd-'=>'cmd-',
                    'dang'=>'dang',
                    'doco'=>'doco',
                    'eric'=>'eric',
                    'hipt'=>'hipt',
                    'inno'=>'inno',
                    'ipaq'=>'ipaq',
                    'java'=>'java',
                    'jigs'=>'jigs',
                    'kddi'=>'kddi',
                    'keji'=>'keji',
                    'leno'=>'leno',
                    'lg-c'=>'lg-c',
                    'lg-d'=>'lg-d',
                    'lg-g'=>'lg-g',
                    'lge-'=>'lge-',
                    'maui'=>'maui',
                    'maxo'=>'maxo',
                    'midp'=>'midp',
                    'mits'=>'mits',
                    'mmef'=>'mmef',
                    'mobi'=>'mobi',
                    'mot-'=>'mot-',
                    'moto'=>'moto',
                    'mwbp'=>'mwbp',
                    'nec-'=>'nec-',
                    'newt'=>'newt',
                    'noki'=>'noki',
                    'opwv'=>'opwv',
                    'palm'=>'palm',
                    'pana'=>'pana',
                    'pant'=>'pant',
                    'pdxg'=>'pdxg',
                    'phil'=>'phil',
                    'play'=>'play',
                    'pluc'=>'pluc',
                    'port'=>'port',
                    'prox'=>'prox',
                    'qtek'=>'qtek',
                    'qwap'=>'qwap',
                    'sage'=>'sage',
                    'sams'=>'sams',
                    'sany'=>'sany',
                    'sch-'=>'sch-',
                    'sec-'=>'sec-',
                    'send'=>'send',
                    'seri'=>'seri',
                    'sgh-'=>'sgh-',
                    'shar'=>'shar',
                    'sie-'=>'sie-',
                    'siem'=>'siem',
                    'smal'=>'smal',
                    'smar'=>'smar',
                    'sony'=>'sony',
                    'sph-'=>'sph-',
                    'symb'=>'symb',
                    't-mo'=>'t-mo',
                    'teli'=>'teli',
                    'tim-'=>'tim-',
                    'tosh'=>'tosh',
                    'treo'=>'treo',
                    'tsm-'=>'tsm-',
                    'upg1'=>'upg1',
                    'upsi'=>'upsi',
                    'vk-v'=>'vk-v',
                    'voda'=>'voda',
                    'wap-'=>'wap-',
                    'wapa'=>'wapa',
                    'wapi'=>'wapi',
                    'wapp'=>'wapp',
                    'wapr'=>'wapr',
                    'webc'=>'webc',
                    'winw'=>'winw',
                    'winw'=>'winw',
                    'xda-'=>'xda-'
                  );
  // check if the first four characters of the current user agent are set as a key in the array
  if(isset($a[substr($_SERVER['HTTP_USER_AGENT'],0,4)])){
    return true;
  }
}

function detect_iphone(){
  if(eregi('iphone',$_SERVER['HTTP_USER_AGENT'])||eregi('ipod',$_SERVER['HTTP_USER_AGENT'])){
    return true;
  }
}

?>
