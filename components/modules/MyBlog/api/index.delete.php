<?php
/**
 * @package		MyBlog
 * @category	modules
 * @author		Nazar Mokrynskyi <nazar@mokrynskyi.com>
 * @copyright	Copyright (c) 2013, Nazar Mokrynskyi
 * @license		MIT License, see license.txt
 */
namespace	cs\modules\MyBlog;
use			cs\Config,
			cs\User;
if (!isset($rc[0], $_POST['title'], $_POST['text'])) {
	define('ERROR_CODE', 400);
	return;
}
$post	= Posts::instance()->get($rc[0]);
if (!$post) {
	define('ERROR_CODE', 404);
	return;
}
$User	= User::instance();
if ($post['user'] != $User->id && !$User->admin()) {
	define('ERROR_CODE', 403);
	return;
}
if (!Posts::instance()->del($post['id'])) {
	define('ERROR_CODE', 500);
	return;
}