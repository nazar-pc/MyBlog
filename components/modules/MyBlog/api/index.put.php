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
	error_code(400);
	return;
}
$post	= Posts::instance()->get($rc[0]);
if (!$post) {
	error_code(404);
	return;
}
$User	= User::instance();
if ($post['user'] != $User->id && !$User->admin()) {
	error_code(403);
	return;
}
if (!Posts::instance()->set($post['id'], $_POST['title'], $_POST['text'])) {
	error_code(500);
	return;
}