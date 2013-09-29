<?php
/**
 * @package		MyBlog
 * @category	modules
 * @author		Nazar Mokrynskyi <nazar@mokrynskyi.com>
 * @copyright	Copyright (c) 2013, Nazar Mokrynskyi
 * @license		MIT License, see license.txt
 */
namespace	cs\modules\MyBlog;
use			cs\Index,
			cs\Page,
			cs\User,
			h;
$Index				= Index::instance();
if (!isset($Index->route_ids[0])) {
	define('ERROR_CODE', 404);
	return;
}
$post				= Posts::instance()->get($Index->route_ids[0]);
if (!$post) {
	define('ERROR_CODE', 404);
	return;
}
$Index->title_auto	= false;
$Page				= Page::instance();
$User				= User::instance();
if ($post['user'] == $User->id || $User->admin()) {
	$Page->content(
		h::{'a.cs-button-compact'}(
			h::icon('edit').' Редактировать пост',
			[
				'href'	=> "MyBlog/post/edit/$post[id]"
			]
		).
		h::{'a.cs-button-compact'}(
			h::icon('trash').' Удалить пост',
			[
				'href'	=> "MyBlog/post/delete/$post[id]"
			]
		)
	);
}
$Page
	->title('Мой блог')
	->title($post['title'])
	->content(
		h::{'section article.cs-myblog-post'}(
			h::h1($post['title']).
			h::div($post['text']).
			h::footer("$post[datetime], $post[username]")
		)
	);