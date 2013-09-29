<?php
/**
 * @package		CleverStyle CMS
 * @subpackage	System module
 * @category	modules
 * @author		Nazar Mokrynskyi <nazar@mokrynskyi.com>
 * @copyright	Copyright (c) 2011-2013, Nazar Mokrynskyi
 * @license		MIT License, see license.txt
 */
/**
 * Provides next triggers:<br>
 *  System/profile/info<code>
 *  [
 *   'id'	=> <i>user_id</i><br>
 *  ]</code>
 */
namespace	cs;
use			h;
$L		= Language::instance();
$Page	= Page::instance();
$User	= User::instance();
$rc		= Config::instance()->route;
if (!isset($rc[1], $rc[2]) || !($id = $User->get_id(hash('sha224', $rc[2])))) {
	error_code(404);
	$Page->error();
	return;
}
$data	= $User->get(
	[
		'username',
		'login',
		'reg_date',
		'status',
		'block_until',
		'gender',
		'birthday',
		'avatar',
		'website',
		'skype',
		'about'
	],
	$id
);
if ($data['status'] == -1) {
	error_code(404);
	$Page->error();
	return;
} elseif ($data['status'] == 0) {
	$Page->warning(
		h::tr([
			$L->account_disabled
		])
	);
	return;
} elseif ($data['block_until'] > TIME) {
	$Page->warning(
		h::tr([
			$L->account_temporarily_blocked
		])
	);
}
$name	= $data['username'] ? $data['username'].($data['username'] != $data['login'] ? ' aka '.$data['login'] : '') : $data['login'];
$Page->title($L->profile_of_user($name));
$Page->og(
	'type',
	'profile'
)->og(
	'username',
	$name,
	'profile:'
)->og(
	'gender',
	$data['gender'] == 0 ? 'male' : ($data['gender'] == 1 ? 'female' : false)
)->og(
	'description',
	$data['about'] ?: false
);
$Page->content(
	h::{'table.cs-table-borderless.cs-profile-table tr'}([
		h::{'td.cs-profile-avatar[rowspan=2] img'}([
			'src'	=> $User->avatar(128, $id),
			'alt'	=> $name,
			'title'	=> $name
		]).
		h::{'td h1'}(
			$L->profile_of_user($name)
		),

		h::{'td table.cs-right-odd.cs-left-even tr'}([
			($data['birthday'] ? h::td([
				h::h2($L->birth_date.':'),
				h::h2($L->to_locale(date($L->birth_date_format, $data['birthday'])))
			])  : false),

			($data['gender'] != -1 ? h::td([
				h::h2($L->gender.':'),
				h::h2($L->{$data['gender'] == 0 ? 'male' : 'female'})
			]) : false),

			($data['website'] ? h::td([
				h::h2($L->website.':'),
				h::{'h2 a'}(
					$data['website'],
					[
						'href'	=> (substr($data['website'], 0, 4) != 'http' ? 'http://' : '').$data['website']
					]
				)
			]) : false),

			($data['skype'] ? h::td([
				h::h2($L->skype.':'),
				h::{'h2 a'}(
					$data['skype'],
					[
						'href'	=> 'skype:'.$data['skype']
					]
				)
			]) : false),

			($data['reg_date'] ? h::td([
				h::h2($L->reg_date.':'),
				h::h2($L->to_locale(date($L->reg_date_format, $data['reg_date'])))
			])  : false),

			($data['about'] ? h::td([
				h::h2($L->about_me.':'),
				h::h2(str_replace("\n", h::br(), $data['about']))
			]) : false)
		])
	])
);
Trigger::instance()->run(
	'System/profile/info',
	[
		'id'	=> $id
	]
);