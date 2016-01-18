<?php

header("Content-Type: text/html; charset=utf-8");
require_once dirname(dirname(__FILE__)) . '/wp-config.php';

class EkonerApi {
	var $action = 'list';
	var $id = 28; // Рубрика "Статьи"

	function run() {
		$this->getAction();
		$this->connect();
		$res = $this->select();
		echo $this->output($res);
		$this->close();
	}

	function connect() {
		mysql_connect(DB_HOST, DB_USER, DB_PASSWORD) or $this->error(mysql_error());
		mysql_select_db(DB_NAME) or $this->error(mysql_error());
	}

	function select() {
		if (isset($_GET['downloaded'])) {
			$offset = (int) $_GET['downloaded'];
		} else {
			$offset = 0;
		}
		switch ($this->action) {
			case 'page':
				$SQL = "SELECT wp_posts.*, image.guid as image
						FROM wp_posts 
						LEFT JOIN wp_postmeta AS thumb_id ON (thumb_id.post_id = wp_posts.ID AND thumb_id.meta_key = '_thumbnail_id')
						LEFT JOIN wp_posts AS image ON image.ID = thumb_id.meta_value
						WHERE wp_posts.ID = $this->id";
				break;
			case 'list':
				$SQL = "SELECT wp_posts.*, image.guid as image
						FROM wp_posts
						LEFT JOIN wp_term_relationships as post_cat ON (post_cat.object_id = wp_posts.ID)
						LEFT JOIN wp_postmeta AS thumb_id ON (thumb_id.post_id = wp_posts.ID AND thumb_id.meta_key = '_thumbnail_id')
						LEFT JOIN wp_posts AS image ON image.ID = thumb_id.meta_value
						WHERE post_cat.term_taxonomy_id = $this->id AND wp_posts.post_status = 'publish'
						ORDER BY  wp_posts.post_date DESC 
						LIMIT $offset , 10";
				break;
			default:
				$this->error('Wrong Action');
				break;
		}
		$rs = mysql_query($SQL);
		$res = array();
		while($row = mysql_fetch_array($rs)) {
			if ($row['image'] == null) {
				$row['image'] = 'http://ekoner.ru/wp-content/uploads/2016/01/noimage.png';
			}
			$res[] = array(
					'id' => $row['ID'],
					'title' => $row['post_title'],
					'image' => $row['image'],
					'date' => date('d.m.Y г.', strtotime($row['post_date'])),
					'description' => $this->getDescr($row['post_content']),
					'content' => $this->clearText($row['post_content'])
				);
		}
		return $res;
	}

	function clearText($text) {
		$text = str_replace("\r", "", $text);
		$text = str_replace("\n", "<br>", $text);
		$text = preg_replace('#\[.*\]#sUi', '', $text);
		return $text;
	}

	function getDescr($string) {
		$string = $this->clearText($string);
		$string = strip_tags($string);
		$string = substr($string, 0, 350);
		$string = rtrim($string, "!,.-");
		$string = substr($string, 0, strrpos($string, ' ')) . '...';
		return $string;
	}

	function output($res) {
		if (!is_array($res)) {$this->error('Res is not an Array');}
		if (count($res) === 0) {
			$res[] = array(
					'title' => 'Страница не найдена',
					'image' => 'http://ekoner.ru/wp-content/uploads/2016/01/noimage.png',
					'content' => ''
				);
		}
		if (count($res) === 1) {
			$res = array_shift($res);
		} else {
			foreach ($res as $k => $v) {
				unset($res[$k]['content']);
			}
		}
		$res = json_encode($res);
		if ($_GET['debug'] == 1) {
			$res = '<html><head><style>body { white-space: pre; font-family: monospace; }</style></head>
			<body><script>var obj = '.$res.'; document.body.innerHTML = ""; document.body.appendChild(document.createTextNode(JSON.stringify(obj, null, 4)));</script></body></html>';
		}
		return $res;
	}

	function close() {
		mysql_close();
		die();
	}

	function getAction() {
		if (!isset($_GET['action'])) {
			$this->error('Action not specified');
		}
		switch ($_GET['action']) { 
			case 'about':
				$this->action = 'page';
				$this->id = 2;
				break;
			case 'contacts':
				$this->action = 'page';
				$this->id = 31;
				break;
			case 'articles':
				$this->action = 'list';
				$this->id = 28;
				break;
			case 'events':
				$this->action = 'list';
				$this->id = 25;
				break;
			case 'page':
				$this->action = 'page';
				$id = (int) $_GET['id'];
				if (!$id) {$this->error('ID not specified');}
				$this->id = $id;
				break;
			case 'list':
				$this->action = 'list';
				$id = (int) $_GET['id'];
				if (!$id) {$this->error('ID not specified');}
				$this->id = $id;
				break;
			default:
				$this->error('Action not found');
				break;
		}
	}

	function error ($msg) {
		die('{"error":"'.$msg.'"}');
	}
}
$api = new EkonerApi;
$api->run();
?>
