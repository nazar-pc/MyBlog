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
	/**
	 * Extracting new versions of files
	 */
	copy(
		$_POST['package'],
		$tmp_file = TEMP.'/'.md5($_POST['package'].MICROTIME).'.phar.php'
	);
	$tmp_dir	= "phar://$tmp_file";
	$module		= file_get_contents("$tmp_dir/dir");
	$module_dir	= MODULES."/$module";
	if (file_exists("$module_dir/fs_old.json")) {
		$Page->content(1);
	}
	copy("$module_dir/fs.json",		"$module_dir/fs_old.json");
	$fs			= _json_decode(file_get_contents("$tmp_dir/fs.json"));
	$extract	= array_product(
		array_map(
			function ($index, $file) use ($tmp_dir, $module_dir) {
				if (
					!file_exists(pathinfo("$module_dir/$file", PATHINFO_DIRNAME)) &&
					!mkdir(pathinfo("$module_dir/$file", PATHINFO_DIRNAME), 0700, true)
				) {
					return 0;
				}
				return (int)copy("$tmp_dir/fs/$index", "$module_dir/$file");
			},
			$fs,
			array_keys($fs)
		)
	);
	file_put_contents(MODULES.'/'.$module.'/fs.json', _json_encode($fs = array_keys($fs)));
	/**
	 * Removing of old unnecessary files and directories
	 */
	foreach (array_diff(_json_decode(file_get_contents($module_dir.'/fs_old.json')), $fs) as $file) {
		$file	= "$module_dir/$file";
		if (file_exists($file) && is_writable($file)) {
			unlink($file);
			if (!get_files_list($dir = pathinfo($file, PATHINFO_DIRNAME))) {
				rmdir($dir);
			}
		}
	}
	unset($fs, $file, $dir);
	$Page->content((int)(bool)$extract);
} else {
	$Page->content(0);
}