<?php
/**
 * @package		MyBlog
 * @category	modules
 * @author		Nazar Mokrynskyi <nazar@mokrynskyi.com>
 * @copyright	Copyright (c) 2013, Nazar Mokrynskyi
 * @license		MIT License, see license.txt
 */
namespace	cs\modules\MyBlog;
use			cs\Page;
if (!isset($_POST['title'], $_POST['text'])) {
	error_code(400);
	return;
}
if ($post = Posts::instance()->add($_POST['title'], $_POST['text'])) {
	code_header(201);
	Page::instance()->json([
		'id'	=> $post
	]);
} else {
	error_code(500);
}