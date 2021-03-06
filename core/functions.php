<?php
/**
 * @package		CleverStyle CMS
 * @author		Nazar Mokrynskyi <nazar@mokrynskyi.com>
 * @copyright	Copyright (c) 2011-2013, Nazar Mokrynskyi
 * @license		MIT License, see license.txt
 */
/**
 * Base system functions, do not edit this file, or make it very carefully
 * otherwise system workability may be broken
 */
use	cs\Cache,
	cs\Config,
	cs\DB,
	cs\Error,
	cs\Index,
	cs\Key,
	cs\Language,
	cs\Page,
	cs\Text,
	cs\Trigger,
	cs\User;
/**
 * Auto Loading of classes
 */
spl_autoload_register(function ($class) {
	if (substr($class, 0, 3) == 'cs\\') {
		$class	= substr($class, 3);
	}
	$class	= explode('\\', $class);
	$class	= [
		'namespace'	=> count($class) > 1 ? implode('/', array_slice($class, 0, -1)) : '',
		'name'		=> array_pop($class)
	];
	return	_require_once(CLASSES."/$class[namespace]/$class[name].php", false) ||
			_require_once(TRAITS."/$class[namespace]/$class[name].php", false) ||
			_require_once(ENGINES."/$class[namespace]/$class[name].php", false) ||
			(
				mb_strpos($class['namespace'], "modules/") === 0 && _require_once(MODULES."/../$class[namespace]/$class[name].php", false)
			) ||
			(
				mb_strpos($class['namespace'], "plugins/") === 0 && _require_once(PLUGINS."/../$class[namespace]/$class[name].php", false)
			);
}, true, true);
/**
 * Correct termination
 */
register_shutdown_function(function () {
	if (!class_exists('\\cs\\Core', false)) {
		return;
	}
	Index::instance(true)->__finish();
	Page::instance()->__finish();
	User::instance(true)->__finish();
});
/**
 * Enable of errors processing
 */
function errors_on () {
	error_reporting(defined('DEBUG') && DEBUG ? E_ALL : E_ERROR | E_WARNING | E_PARSE);
	if (defined('CS_ERROR_HANDLER') && CS_ERROR_HANDLER && class_exists('\\cs\\Error', false)) {
		Error::instance()->error = true;
	}
}
/**
 * Disabling of errors processing
 */
function errors_off () {
	error_reporting(0);
	if (defined('CS_ERROR_HANDLER') && CS_ERROR_HANDLER && class_exists('\\cs\\Error', false)) {
		Error::instance()->error = false;
	}
}
/**
 * Enabling of page interface
 */
function interface_on () {
	Page::instance()->interface	= true;
}
/**
 * Disabling of page interface
 */
function interface_off () {
	Page::instance()->interface	= false;
}
/**
 * Get file url by it's destination in file system
 *
 * @param string		$source
 *
 * @return bool|string
 */
function url_by_source ($source) {
	$Config	= Config::instance(true);
	if (!$Config) {
		return false;
	}
	$source = realpath($source);
	if (mb_strpos($source, DIR) === 0) {
		return $Config->base_url().mb_substr($source, mb_strlen(DIR));
	}
	return false;
}
/**
 * Get file destination in file system by it's url
 *
 * @param string		$url
 *
 * @return bool|string
 */
function source_by_url ($url) {
	$Config	= Config::instance(true);
	if (!$Config) {
		return false;
	}
	if (mb_strpos($url, $Config->base_url()) === 0) {
		return DIR.mb_substr($url, mb_strlen($Config->base_url()));
	}
	return false;
}
/**
 * Public cache cleaning
 *
 * @return bool
 */
function clean_pcache () {
	$ok = true;
	$list = get_files_list(PCACHE, false, 'fd', true, true, 'name|desc');
	foreach ($list as $item) {
		if (is_writable($item)) {
			is_dir($item) ? @rmdir($item) : @unlink($item);
		} else {
			$ok = false;
		}
	}
	unset($list, $item);
	return $ok;
}
/**
 * Formatting of time in seconds to human-readable form
 *
 * @param int		$time	Time in seconds
 *
 * @return string
 */
function format_time ($time) {
	$L		= Language::instance();
	$res	= [];
	if ($time >= 31536000) {
		$time_x = round($time / 31536000);
		$time -= $time_x * 31536000;
		$res[] = $L->time($time_x, 'y');
	}
	if ($time >= 2592000) {
		$time_x = round($time / 2592000);
		$time -= $time_x * 2592000;
		$res[] = $L->time($time_x, 'M');
	}
	if($time >= 86400) {
		$time_x = round($time / 86400);
		$time -= $time_x * 86400;
		$res[] = $L->time($time_x, 'd');
	}
	if($time >= 3600) {
		$time_x = round($time / 3600);
		$time -= $time_x * 3600;
		$res[] = $L->time($time_x, 'h');
	}
	if ($time >= 60) {
		$time_x = round($time / 60);
		$time -= $time_x * 60;
		$res[] = $L->time($time_x, 'm');
	}
	if ($time > 0 || empty($res)) {
		$res[] = $L->time($time, 's');
	}
	return implode(' ', $res);
}
/**
 * Formatting of data size in bytes to human-readable form
 *
 * @param int		$size
 * @param bool|int	$round
 *
 * @return float|string
 */
function format_filesize ($size, $round = false) {
	$L		= Language::instance();
	$unit	= '';
	if($size >= 1099511627776) {
		$size = $size / 1099511627776;
		$unit = " $L->TB";
	} elseif($size >= 1073741824) {
		$size = $size / 1073741824;
		$unit = " $L->GB";
	} elseif ($size >= 1048576) {
		$size = $size / 1048576;
		$unit = " $L->MB";
	} elseif ($size >= 1024) {
		$size = $size / 1024;
		$unit = " $L->KB";
	} else {
		$size = "$size $L->Bytes";
	}
	return $round ? round($size, $round).$unit : $size;
}
/**
 * Function for setting cookies on all mirrors and taking into account cookies prefix. Parameters like in system function, but $path, $domain and $secure
 * are skipped, they are detected automatically, and $api parameter added in the end.
 *
 * @param string     $name
 * @param string     $value
 * @param int        $expire
 * @param bool       $httponly
 * @param bool       $api		Is this cookie setting during api request (in most cases it is not necessary to change this parameter)
 *
 * @return bool
 */
function _setcookie ($name, $value, $expire = 0, $httponly = false, $api = false) {
	static $path, $domain, $prefix, $secure;
	$Config					= Config::instance(true);
	if (!isset($prefix) && $Config) {
		$prefix		= $Config->core['cookie_prefix'];
		$secure		= $Config->server['protocol'] == 'https';
		if (
			$Config->server['mirror_index'] == -1 ||
			!isset(
				$Config->core['mirrors_cookie_domain'][$Config->server['mirror_index']],
				$Config->core['mirrors_cookie_path'][$Config->server['mirror_index']]
			)
		) {
			$domain	= $Config->core['cookie_domain'];
			$path	= $Config->core['cookie_path'];
		} else {
			$domain	= $Config->core['mirrors_cookie_domain'][$Config->server['mirror_index']];
			$path	= $Config->core['mirrors_cookie_path'][$Config->server['mirror_index']];
		}
	}
	if (!isset($prefix)) {
		$prefix	= '';
	}
	$_COOKIE[$prefix.$name] = $value;
	if (!$api && $Config->core['cookie_sync']) {
		$data = [
			'name'		=> $name,
			'value'		=> $value,
			'expire'	=> $expire,
			'httponly'	=> $httponly
		];
		Trigger::instance()->register(
			'System/Index/preload',
			function () use ($prefix, $data, $domain) {
				$Config	= Config::instance();
				$Key	= Key::instance();
				$User	= User::instance();
				if (count($Config->core['mirrors_cookie_domain'])) {
					$mirrors_url			= $Config->core['mirrors_url'];
					$mirrors_cookie_domain	= $Config->core['mirrors_cookie_domain'];
					$database				= DB::instance()->{$Config->module('System')->db('keys')}();
					$data['check']			= md5($User->ip.$User->forwarded_for.$User->client_ip.$User->user_agent._json_encode($data));
					$urls					= [];
					if ($Config->server['mirror_index'] != -1 && $domain != $Config->core['cookie_domain']) {
						$url	= $Config->core_url();
						if ($Key->add($database, $key = $Key->generate($database), $data)) {
							$urls[] = $url."/api/System/user/setcookie/$key";
						}
						unset($url);
					}
					foreach ($mirrors_cookie_domain as $i => $d) {
						$mirrors_url[$i] = explode(';', $mirrors_url[$i], 2)[0];
						if ($d && $d != $domain) {
							if ($Key->add($database, $key = $Key->generate($database), $data)) {
								$urls[]	= $mirrors_url[$i]."/api/System/user/setcookie/$key";
							}
						}
					}
					if (!empty($urls)) {
						$setcookie	= isset($_COOKIE[$prefix.'setcookie']) ? (_json_decode($_COOKIE[$prefix.'setcookie']) ?: []) : [];
						$setcookie	= array_merge($setcookie, $urls);
						setcookie($prefix.'setcookie', $_COOKIE[$prefix.'setcookie'] = _json_encode($setcookie));
					}
				}
			}
		);
	}
	if (isset($domain)) {
		return setcookie(
			$prefix.$name,
			$value,
			$expire,
			$path,
			$domain,
			$secure,
			$httponly
		);
	} else {
		return setcookie(
			$prefix.$name,
			$value,
			$expire,
			'/',
			$_SERVER['HTTP_HOST'],
			false,
			$httponly
		);
	}
}
/**
 * Function for getting of cookies, taking into account cookies prefix
 *
 * @param $name
 *
 * @return bool
 */
function _getcookie ($name) {
	static $prefix;
	if (!isset($prefix)) {
		$Config	= Config::instance(true);
		$prefix	= $Config->core['cookie_prefix'] ? $Config->core['cookie_prefix'].'_' : '';
	}
	return isset($_COOKIE[$prefix.$name]) ? $_COOKIE[$prefix.$name] : false;
}
/**
 * Get list of timezones
 *
 * @return array
 */
function get_timezones_list () {
	if (
		!class_exists('\\cs\\Cache', false) ||
		!($Cache = Cache::instance(true)) ||
		($timezones = $Cache->timezones) === false
	) {
		$tzs = timezone_identifiers_list();
		$timezones_ = $timezones = [];
		foreach ($tzs as $tz) {
			$offset		= (new DateTimeZone($tz))->getOffset(new DateTime);
			$offset_	=	($offset < 0 ? '-' : '+').
							str_pad(floor(abs($offset / 3600)), 2, 0, STR_PAD_LEFT).':'.
							str_pad(abs(($offset % 3600) / 60), 2, 0, STR_PAD_LEFT);
			$timezones_[(39600 + $offset).$tz] = [
				'key'	=> strtr($tz, '_', ' ')." ($offset_)",
				'value'	=> $tz
			];
		}
		unset($tzs, $tz, $offset);
		ksort($timezones_, SORT_NATURAL);
		/**
		 * @var array $offset
		 */
		foreach ($timezones_ as $tz) {
			$timezones[$tz['key']] = $tz['value'];
		}
		unset($timezones_, $tz);
		if (class_exists('\\cs\\Cache', false) && isset($Cache) && $Cache) {
			$Cache->timezones = $timezones;
		}
	}
	return $timezones;
}
/**
 * Check existence of mcrypt
 *
 * @return bool
 */
function check_mcrypt () {
	return preg_match(
		'/mcrypt support<\/th><th>enabled/',
		ob_wrapper(function () {
			phpinfo(INFO_MODULES);
		})
	);
}
/**
 * Check existence of zlib library
 *
 * @return bool
 */
function zlib () {
	return extension_loaded('zlib');
}
/**
 * Check autocompression state of zlib library
 *
 * @return bool
 */
function zlib_compression () {
	return zlib() && strtolower(ini_get('zlib.output_compression')) != 'off';
}
/**
 * Check existence of curl library
 *
 * @return bool
 */
function curl () {
	return extension_loaded('curl');
}
/**
 * Check existence of apc module
 *
 * @return bool
 */
function apc () {
	return extension_loaded('apc');
}
/**
 * Check existence of memcache module
 *
 * @return bool
 */
function memcached () {
	return extension_loaded('memcached');
}
/**
 * Get multilingual value from $Config->core array
 *
 * @param string $item
 *
 * @return bool|string
 */
function get_core_ml_text ($item) {
	$Config	= Config::instance(true);
	if (!$Config) {
		return false;
	}
	return Text::instance()->process($Config->module('System')->db('texts'), $Config->core[$item], true, true);
}
/**
 * Pages navigation based on links
 *
 * @param int				$page		Current page
 * @param int				$total		Total pages number
 * @param Closure|string	$url		if string - it will be formatted with sprintf with one parameter - page number<br>
 * 										if Closure - one parameter will be given, Closure should return url string
 * @param bool				$head_links	If <b>true</b> - links with rel="prev" and rel="next" will be added
 *
 * @return bool|string					<b>false</b> if single page, otherwise string, set of navigation links
 */
function pages ($page, $total, $url, $head_links = false) {
	if ($total == 1) {
		return false;
	}
	$Page	= Page::instance();
	$output	= [];
	if ($total <= 11) {
		for ($i = 1; $i <= $total; ++$i) {
			$output[]	= [
				$i,
				[
					'href'	=> $i == $page ? false : ($url instanceof Closure ? $url($i) : sprintf($url, $i)),
					'class'	=> $i == $page ? 'cs-button uk-button-primary uk-frozen' : 'cs-button'
				]
			];
			if ($head_links && ($i == $page - 1 || $i == $page + 1)) {
				$Page->link([
					'href'	=> $url instanceof Closure ? $url($i) : sprintf($url, $i),
					'rel'	=> $i == $page - 1 ? 'prev' : ($i == $page + 1 ? 'next' : false)
				]);
			}
		}
	} else {
		if ($page <= 5) {
			for ($i = 1; $i <= 7; ++$i) {
				$output[]	= [
					$i,
					[
						'href'	=> $i == $page ? false : ($url instanceof Closure ? $url($i) : sprintf($url, $i)),
						'class'	=> $i == $page ? 'cs-button uk-button-primary uk-frozen' : 'cs-button'
					]
				];
				if ($head_links&& ($i == $page - 1 || $i == $page + 1)) {
					$Page->link([
						'href'	=> $url instanceof Closure ? $url($i) : sprintf($url, $i),
						'rel'	=> $i == $page - 1 ? 'prev' : ($i == $page + 1 ? 'next' : false)
					]);
				}
			}
			$output[]	= [
				'...',
				[
					'class'	=> 'cs-button uk-frozen'
				]
			];
			for ($i = $total - 2; $i <= $total; ++$i) {
				$output[]	= [
					$i,
					[
						'href'	=> $url instanceof Closure ? $url($i) : sprintf($url, $i),
						'class'	=> 'cs-button'
					]
				];
			}
		} elseif ($page >= $total - 4) {
			for ($i = 1; $i <= 3; ++$i) {
				$output[]	= [
					$i,
					[
						'href'	=> $url instanceof Closure ? $url($i) : sprintf($url, $i),
						'class'	=> 'cs-button'
					]
				];
			}
			$output[]	= [
				'...',
				[
					'class'	=> 'cs-button uk-frozen'
				]
			];
			for ($i = $total - 6; $i <= $total; ++$i) {
				$output[]	= [
					$i,
					[
						'href'	=> $i == $page ? false : ($url instanceof Closure ? $url($i) : sprintf($url, $i)),
						'class'	=> $i == $page ? 'cs-button uk-button-primary uk-frozen' : 'cs-button'
					]
				];
				if ($head_links && ($i == $page - 1 || $i == $page + 1)) {
					$Page->link([
						'href'	=> $url instanceof Closure ? $url($i) : sprintf($url, $i),
						'rel'	=> $i == $page - 1 ? 'prev' : ($i == $page + 1 ? 'next' : false)
					]);
				}
			}
		} else {
			for ($i = 1; $i <= 2; ++$i) {
				$output[]	= [
					$i,
					[
						'href'	=> $url instanceof Closure ? $url($i) : sprintf($url, $i),
						'class'	=> 'cs-button'
					]
				];
			}
			$output[]	= [
				'...',
				[
					'class'	=> 'cs-button uk-frozen'
				]
			];
			for ($i = $page - 1; $i <= $page + 3; ++$i) {
				$output[]	= [
					$i,
					[
						'href'	=> $i == $page ? false : ($url instanceof Closure ? $url($i) : sprintf($url, $i)),
						'class'	=> $i == $page ? 'cs-button uk-button-primary uk-frozen' : 'cs-button'
					]
				];
				if ($head_links && ($i == $page - 1 || $i == $page + 1)) {
					$Page->link([
						'href'	=> $url instanceof Closure ? $url($i) : sprintf($url, $i),
						'rel'	=> $i == $page - 1 ? 'prev' : ($i == $page + 1 ? 'next' : false)
					]);
				}
			}
			$output[]	= [
				'...',
				[
					'class'	=> 'cs-button uk-frozen'
				]
			];
			for ($i = $total - 1; $i <= $total; ++$i) {
				$output[]	= [
					$i,
					[
						'href'	=> $url instanceof Closure ? $url($i) : sprintf($url, $i),
						'class'	=> 'cs-button'
					]
				];
			}
		}
	}
	return h::a($output);
}
/**
 * Pages navigation based on buttons (for search forms, etc.)
 *
 * @param int					$page		Current page
 * @param int					$total		Total pages number
 * @param bool|Closure|string	$url		Adds <i>formaction</i> parameter to every button<br>
 * 											if <b>false</b> - only form parameter <i>page</i> will we added<br>
 * 											if string - it will be formatted with sprintf with one parameter - page number<br>
 * 											if Closure - one parameter will be given, Closure should return url string
 *
 * @return bool|string						<b>false</b> if single page, otherwise string, set of navigation buttons
 */
function pages_buttons ($page, $total, $url = false) {
	if ($total == 1) {
		return false;
	}
	$output	= [];
	if ($total <= 11) {
		for ($i = 1; $i <= $total; ++$i) {
			$output[]	= [
				$i,
				[
					'formaction'	=> $i == $page || $url === false ? false : ($url instanceof Closure ? $url($i) : sprintf($url, $i)),
					'value'			=> $i,
					'type'			=> $i == $page ? 'button' : 'submit',
					'class'			=> $i == $page ? 'uk-button-primary uk-frozen' : false
				]
			];
		}
	} else {
		if ($page <= 5) {
			for ($i = 1; $i <= 7; ++$i) {
				$output[]	= [
					$i,
					[
						'formaction'	=> $i == $page || $url === false ? false : ($url instanceof Closure ? $url($i) : sprintf($url, $i)),
						'value'			=> $i == $page ? false : $i,
						'type'			=> $i == $page ? 'button' : 'submit',
						'class'			=> $i == $page ? 'uk-button-primary uk-frozen' : false
					]
				];
			}
			$output[]	= [
				'...',
				[
					'type'			=> 'button',
					'disabled'
				]
			];
			for ($i = $total - 2; $i <= $total; ++$i) {
				$output[]	= [
					$i,
					[
						'formaction'	=> $url instanceof Closure ? $url($i) : sprintf($url, $i),
						'value'			=> $i,
						'type'			=> 'submit'
					]
				];
			}
		} elseif ($page >= $total - 4) {
			for ($i = 1; $i <= 3; ++$i) {
				$output[]	= [
					$i,
					[
						'formaction'	=> $url instanceof Closure ? $url($i) : sprintf($url, $i),
						'value'			=> $i,
						'type'			=> 'submit'
					]
				];
			}
			$output[]	= [
				'...',
				[
					'type'			=> 'button',
					'disabled'
				]
			];
			for ($i = $total - 6; $i <= $total; ++$i) {
				$output[]	= [
					$i,
					[
						'formaction'	=> $i == $page || $url === false ? false : ($url instanceof Closure ? $url($i) : sprintf($url, $i)),
						'value'			=> $i == $page ? false : $i,
						'type'			=> $i == $page ? 'button' : 'submit',
						'class'			=> $i == $page ? 'uk-button-primary uk-frozen' : false
					]
				];
			}
		} else {
			for ($i = 1; $i <= 2; ++$i) {
				$output[]	= [
					$i,
					[
						'formaction'	=> $url instanceof Closure ? $url($i) : sprintf($url, $i),
						'value'			=> $i,
						'type'			=> 'submit'
					]
				];
			}
			$output[]	= [
				'...',
				[
					'type'			=> 'button',
					'disabled'
				]
			];
			for ($i = $page - 1; $i <= $page + 3; ++$i) {
				$output[]	= [
					$i,
					[
						'formaction'	=> $i == $page || $url === false ? false : ($url instanceof Closure ? $url($i) : sprintf($url, $i)),
						'value'			=> $i == $page ? false : $i,
						'type'			=> $i == $page ? 'button' : 'submit',
						'class'			=> $i == $page ? 'uk-button-primary uk-frozen' : false
					]
				];
			}
			$output[]	= [
				'...',
				[
					'type'			=> 'button',
					'disabled'
				]
			];
			for ($i = $total - 1; $i <= $total; ++$i) {
				$output[]	= [
					$i,
					[
						'formaction'	=> $url instanceof Closure ? $url($i) : sprintf($url, $i),
						'value'			=> $i,
						'type'			=> 'submit'
					]
				];
			}
		}
	}
	return h::{'button[name=page]'}($output);
}
/**
 * Simple wrapper for defining constant ERROR_CODE
 *
 * @param int	$code
 */
function error_code ($code) {
	!defined('ERROR_CODE') && define('ERROR_CODE', $code);
}

/**
 * Checks whether specified functionality available or not
 *
 * @param string|string[]	$functionality	One functionality or array of them
 *
 * @return bool								<i>true</i> if all functionality available, <i>false</i> otherwise
 */
function functionality ($functionality) {
	if (is_array($functionality)) {
		$result	= true;
		foreach ($functionality as $f) {
			$result	= $result && functionality($f);
		}
		return $result;
	}
	$all	= Cache::instance()->get("functionality", function () {
		$functionality	= [];
		$components		= Config::instance()->components;
		foreach ($components['modules'] as $module => $module_data) {
			if ($module_data['active'] != 1 || !file_exists(MODULES."/$module/meta.json")) {
				continue;
			}
			$meta			= _json_decode(file_get_contents(MODULES."/$module/meta.json"));
			if (!isset($meta['provide'])) {
				continue;
			}
			$functionality	= array_merge(
				$functionality,
				(array)$meta['provide']
			);
		}
		unset($module, $module_data, $meta);
		foreach ($components['plugins'] as $plugin) {
			if (!file_exists(PLUGINS."/$plugin/meta.json")) {
				continue;
			}
			$meta			= _json_decode(file_get_contents(PLUGINS."/$plugin/meta.json"));
			if (!isset($meta['provide'])) {
				continue;
			}
			$functionality	= array_merge(
				$functionality,
				(array)$meta['provide']
			);
		}
		return $functionality;
	});
	return array_search($functionality, $all) !== false;
}