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
			h;
if (isset($_POST['title'], $_POST['text'])) {
	if ($post = Posts::instance()->add($_POST['title'], $_POST['text'])) {
		header("Location: /MyBlog/post/$post");
		return;
	} else {
		Page::instance()->warning('Добавление поста неудачно, ошибка на сервере');
	}
}
$Index						= Index::instance();
$Index->form				= true;
$Index->apply_button		= false;
$Index->cancel_button_back	= true;
$Index->content(
	h::{'h3.cs-center'}('Новый пост').
	h::label('Название').
	h::{'input[name=title][required]'}().
	h::label('Текст').
	h::{'textarea[name=text][rows=10][required]'}().
	h::br(2)
);