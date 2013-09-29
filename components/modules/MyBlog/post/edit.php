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
			cs\Index,
			cs\Page,
			cs\User,
			h;
$rc		= Config::instance()->route;
if (!isset($rc[2])) {
	define('ERROR_CODE', 404);
	return;
}
$post	= Posts::instance()->get($rc[2]);
if (!$post) {
	define('ERROR_CODE', 404);
	return;
}
$User	= User::instance();
if ($post['user'] != $User->id && !$User->admin()) {
	define('ERROR_CODE', 403);
	return;
}
if (isset($_POST['title'], $_POST['text'])) {
	if (Posts::instance()->set($post['id'], $_POST['title'], $_POST['text'])) {
		header("Location: /MyBlog/post/$post[id]");
		return;
	} else {
		Page::instance()->warning('Редактирование поста неудачно, ошибка на сервере');
	}
}
$Index						= Index::instance();
$Index->form				= true;
$Index->action				.= "/$post[id]";
$Index->apply_button		= false;
$Index->cancel_button_back	= true;
$Index->content(
	h::{'h3.cs-center'}('Редактировать пост').
	h::label('Название').
	h::{'input[name=title][required]'}([
		'value'	=> $post['title']
	]).
	h::label('Текст').
	h::{'textarea[name=text][rows=10][required]'}($post['text']).
	h::br(2)
);