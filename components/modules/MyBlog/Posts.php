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
			cs\Cache\Prefix,
			cs\DB\Accessor,
			cs\Language,
			cs\User,
			cs\Singleton;

/**
 * Class Posts for posts manipulation
 *
 * @method static \cs\modules\MyBlog\Posts instance($check = false)
 */
class Posts extends Accessor {
	use Singleton;

	/**
	 * Cache object instance
	 *
	 * @var Prefix
	 */
	protected $cache;

	protected function construct () {
		/**
		 * Save instance of cache object with prefix MyBlog (will be added to every item)
		 */
		$this->cache	= new Prefix('MyBlog');
	}
	/**
	 * Required by abstract Accessor class
	 *
	 * @return int	Database index
	 */
	protected function cdb () {
		return Config::instance()->module('MyBlog')->db('posts');
	}
	/**
	 * Get post
	 *
	 * @param int|int[]		$id
	 *
	 * @return array|bool
	 */
	function get ($id) {
		if (is_array($id)) {
			foreach ($id as &$i) {
				$i	= $this->get($i);
			}
			return $id;
		}
		$id	= (int)$id;
		/**
		 * Try to get item from cache, if not found - get it from database and save in cache
		 */
		return $this->cache->get("posts/$id", function () use ($id) {
			if ($data = $this->db()->qf([	//Readable database, Query, Fetch
				"SELECT
					`id`,
					`user`,
					`title`,
					`text`,
					`date`
				FROM `[prefix]myblog_posts`
				WHERE `id` = '%d'
				LIMIT 1",
				$id
			])) {
				$L					= Language::instance();
				$data['datetime']	= $L->to_locale(date($L->_datetime_long, $data['date']));
				$data['username']	= User::instance()->username($data['user']);
			}
			return $data;
		});
	}
	/**
	 * Add post
	 *
	 * @param string	$title
	 * @param string	$text
	 *
	 * @return bool|int			Id of created post or <b>false</b> on failure
	 */
	function add ($title, $text) {
		$user	= User::instance()->id;	//User id
		$title	= xap($title);			//XSS filter
		$text	= xap($text, true);		//XSS filter, allow html tags
		$date	= TIME;					//Current timestamp
		if ($this->db_prime()->q(		//Writable database, Query
			"INSERT INTO `[prefix]myblog_posts`
				(
					`user`,
					`title`,
					`text`,
					`date`
				) VALUES (
					'%d',
					'%s',
					'%s',
					'%d'
				)",
			$user,
			$title,
			$text,
			$date
		)) {
			/**
			 * Delete total count of posts
			 */
			unset($this->cache->total_count);
			return $this->db_prime()->id();
		}
		return false;
	}
	/**
	 * Edit post
	 *
	 * @param int		$id
	 * @param string	$title
	 * @param string	$text
	 *
	 * @return bool
	 */
	function set ($id, $title, $text) {
		$id		= (int)$id;
		$title	= xap($title);			//XSS filter
		$text	= xap($text, true);		//XSS filter, allow html tags
		if ($this->db_prime()->q(		//Writable database, Query
			"UPDATE `[prefix]myblog_posts`
			SET
				`title`	= '%s',
				`text`	= '%s'
			WHERE `id` = '%d'
			LIMIT 1",
			$title,
			$text,
			$id
		)) {
			/**
			 * Delete cached item if any
			 */
			unset($this->cache->{"posts/$id"});
			return true;
		}
		return false;
	}
	/**
	 * Delete post
	 *
	 * @param int	$id
	 *
	 * @return bool
	 */
	function del ($id) {
		$id	= (int)$id;
		if ($this->db_prime()->q(
			"DELETE FROM `[prefix]myblog_posts`
			WHERE `id` = '%d'
			LIMIT 1"
		)) {
			/**
			 * Delete cached item if any, and total count of posts
			 */
			unset(
				$this->cache->{"posts/$id"},
				$this->cache->total_count
			);
			return true;
		}
		return false;
	}
	/**
	 * Get posts
	 *
	 * @param $page
	 *
	 * @return int[]
	 */
	function posts ($page = 1) {
		$from	= ($page - 1) * 10 ?: 0;
		return $this->db()->qfas(	//Readable database, Query, Fetch, Single, Array
			"SELECT `id`
			FROM `[prefix]myblog_posts`
			ORDER BY `id` DESC
			LIMIT $from, 10"
		) ?: [];
	}
	/**
	 * Get total count of posts
	 *
	 * @return int
	 */
	function total_count () {
		return $this->cache->get('total_count', function () {
			return $this->db()->qfs(	//Readable database, Query, Fetch, Single
				"SELECT COUNT(`id`)
				FROM `[prefix]myblog_posts`"
			) ?: 0;
		});
	}
}