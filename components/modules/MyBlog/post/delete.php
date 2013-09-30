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
			cs\Page,
			cs\User,
			h;
$rc		= Config::instance()->route;
if (!isset($rc[2])) {
	error_code(404);
	return;
}
$post	= Posts::instance()->get($rc[2]);
if (!$post) {
	error_code(404);
	return;
}
$User	= User::instance();
if ($post['user'] != $User->id && !$User->admin()) {
	error_code(403);
	return;
}
if (Posts::instance()->del($post['id'])) {
	header("Location: /MyBlog");
	return;
} else {
	Page::instance()->warning('Удаление поста неудачно, ошибка на сервере');
}