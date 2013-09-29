<?php
/**
 * @package		MyBlog
 * @category	modules
 * @author		Nazar Mokrynskyi <nazar@mokrynskyi.com>
 * @copyright	Copyright (c) 2013, Nazar Mokrynskyi
 * @license		MIT License, see license.txt
 */
namespace	cs;
$rc	= Config::instance()->route;
if (!isset($rc[1]) || $rc[1] == 'view' || is_numeric($rc[1])) {
	Index::instance()->title_auto	= false;
}