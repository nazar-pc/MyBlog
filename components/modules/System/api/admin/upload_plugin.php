<?php
/**
 * @package		CleverStyle CMS
 * @subpackage	System module
 * @category	modules
 * @author		Nazar Mokrynskyi <nazar@mokrynskyi.com>
 * @copyright	Copyright (c) 2011-2013, Nazar Mokrynskyi
 * @license		MIT License, see license.txt
 */
namespace	cs;
$Page	= Page::instance();
if (User::instance()->system()) {
	copy(
		$_POST['package'],
		$tmp_file = TEMP.'/'.md5($_POST['package'].MICROTIME).'.phar.php'
	);
	$tmp_dir		= 'phar://'.$tmp_file;
	$plugin			= file_get_contents($tmp_dir.'/dir');
	$fs				= _json_decode(file_get_contents($tmp_dir.'/fs.json'));
	$extract		= array_product(
		array_map(
			function ($index, $file) use ($tmp_dir, $plugin) {
				if (
					!file_exists(pathinfo(PLUGINS.'/'.$plugin.'/'.$file, PATHINFO_DIRNAME)) &&
					!mkdir(pathinfo(PLUGINS.'/'.$plugin.'/'.$file, PATHINFO_DIRNAME), 0700, true)
				) {
					return 0;
				}
				return (int)copy($tmp_dir.'/fs/'.$index, MODULES.'/'.$plugin.'/'.$file);
			},
			$fs,
			array_keys($fs)
		)
	);
	file_put_contents(PLUGINS.'/'.$plugin.'/fs.json', _json_encode(array_keys($fs)));
	$Page->content((int)(bool)$extract);
} else {
	$Page->content(0);
}