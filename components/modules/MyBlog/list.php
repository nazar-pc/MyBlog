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
			h;
$rc				= Config::instance()->route;
$page			= 1;
if (isset($rc[1]) && $rc[1]) {
	$page	= (int)$rc[1];
}
$Page			= Page::instance();
$Posts			= Posts::instance();
$total_count	= $Posts->total_count();
$Page->content(
	h::{'a.cs-button-compact'}(
		h::icon('plus').' Добавить пост',
		[
			'href'	=> 'MyBlog/post/add'
		]
	)
);
if (!$total_count) {
	$Page->content(
		h::{'p.cs-center.uk-text-info'}('Пока нет постов')
	);
	return;
}
$Page->title('Мой блог');
if ($page > 1) {
	$Page->title("Страница $page");
}
$Page->content(
	h::{'section article.cs-myblog-posts'}(
		h::{'h1 a[href=MyBlog/post/$i[id]]'}('$i[title]').
		h::div('$i[text]').
		h::footer('$i[datetime], $i[username]'),
		[
			'insert'	=> $Posts->get($Posts->posts($page))
		]
	).
	(
		$total_count > 10 ? h::{'div.cs-center'}(pages($page, ceil($total_count / 10), function ($page) {
			return $page < 2 ? 'MyBlog' : "MyBlog/list/$page";
		})) : ''
	)
);