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
			cs\Page;
$rc		= Config::instance()->route;
$Posts	= Posts::instance();
$Page	= Page::instance();
/**
 * If id not specified - get posts list (10 posts per request limitation)
 */
if (!isset($rc[0])) {
	$Page->json([
		'posts'			=> $Posts->posts(isset($_GET['page']) ? $_GET['page'] : 1),
		'total_posts'	=> $Posts->total_count()
	]);
	return;
}
$post	= Posts::instance()->get($rc[0]);
if (!$post) {
	define('ERROR_CODE', 404);
	return;
}
$Page->json($post);