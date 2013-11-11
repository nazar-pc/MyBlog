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
			cs\CRUD,
			cs\Singleton;

/**
 * Class Posts for posts manipulation
 *
 * @method static \cs\modules\MyBlog\Posts instance($check = false)
 */
class Posts {
	use	CRUD,
		Singleton;

	/**
	 * Cache object instance
	 *
	 * @var Prefix
	 */
	protected $cache;

	protected $table		= '[prefix]myblog_posts';

	protected $data_model	= [
		'id'	=> 'int',
		'user'	=> 'int',
		'title'	=> 'text',
		'text'	=> 'html',
		'date'	=> 'int'
	];

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
			$data = $this->read(
				$this->table,
				$this->data_model,
				[$id]
			);
			if ($data) {
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
		$id	= $this->create(
			$this->table,
			$this->data_model,
			[
				User::instance()->id,
				$title,
				$text,
				TIME
			]
		);
		if ($id) {
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
		$data			= $this->get($id);
		$data['title']	= $title;
		$data['text']	= $text;
		if ($this->update($this->table, $this->data_model, $data)) {
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
		if ($this->delete($this->table, $this->data_model, [$id])) {
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