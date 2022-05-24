<?php // -*- mode: php; coding: euc-japan -*-
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/common.php';

class mykarte_base {

	function fetch_data($id) {
		$stmt = $this->fetch_stmt($id);
		$db = mx_db_connect();
		$this->data = mx_db_fetch_single(&$db, $stmt);
	}

	// implement in subclass
	// function fetch_stmt($id)

	function fetch_allcol_stmt($table, $id, $idcol='id') {
		return 'SELECT * FROM ' .
			mx_db_sql_quote_name($table) .
			' WHERE ' .
			mx_db_sql_quote_name($idcol) .
			' = ' .
			mx_db_sql_quote($id);
	}

}

class mykarte_user extends mykarte_base {

	function mykarte_user($id, $mykarte_user=NULL) {
		if (is_null($mykarte_user))
			$this->fetch_data($id);
		else {
			$stmt = $this->fetch_allcol_stmt('mykarte_user_view',
							 $mykarte_user,
							 'mykarte_user');
			$db = mx_db_connect();
			$this->data = mx_db_fetch_single(&$db, $stmt);
		}
	}

	function fetch_data($id) {
		// $id == 0 is special -- it is the system user
		// on the Medex side.
		if ($id == 0)
			$this->data = $this->system_user();
		else
			mykarte_base::fetch_data($id);
	}

	function fetch_stmt($id) {
		return $this->fetch_allcol_stmt('mykarte_user_view', $id,
						'userid');
	}

	function system_user() {
		print "<!-- SYSTEM -->";
		return array('userid' => 0,
			     'user_name' => 'system user',
			     'password' => NULL,
			     'display_name' => 'システムユーザ',
			     'anonymous' => 'N');
	}

	function get_name() {
		if (($this->data['anonymous'] != 'Y'))
			return $this->data['display_name'];
		return $this->data['handle'];
	}

	function get_t_rank() {
		$mypoint = $this->data['tpoint'];
		if (!$mypoint)
			$mypoint = 0;
		$stmt = ('SELECT count(*)+1 AS rank FROM mykarte_users ' .
			 'WHERE tpoint > ' . mx_db_sql_quote($mypoint));
		$db = mx_db_connect();
		$result = mx_db_fetch_single($db, $stmt);
		if (is_array($result))
			return $result['rank'];
		else
			return 'N/A';
	}

	function update_stats() {
		$u = $this->data['userid'];
		if ($u) {
			$db = mx_db_connect();
			$stmt = ('SELECT mykarte_user_stat_update(' .
				 mx_db_sql_quote($u) . ')');
			pg_query($db, $stmt);
		}
	}

}

class mykarte_srt extends mykarte_base {

	var $table = NULL;
	var $minecol = 'createdby';

	function fetch_stmt($id) {
		return $this->fetch_allcol_stmt($this->table, $id);
	}

	function list_recent_stmt($uid, $zip, $not, $limit) {
		if (!is_null($uid)) {
			$mine = array();
			if (!is_array($this->minecol))
				$minecol = array($this->minecol);
			else
				$minecol = $this->minecol;

			foreach ($minecol as $c) {
				$mine[] = (mx_db_sql_quote_name($c) .
					   ($not ? ' != ' : ' = ') .
					   mx_db_sql_quote($uid));
			}
			if ($not)
				$mine = implode(' AND ', $mine);
			else
				$mine = implode(' OR ', $mine);
		}
		else
			$mine = 'NULL IS NULL';
		return 'SELECT * FROM ' .
			mx_db_sql_quote_name($this->table) .
			" WHERE cancelled = 'N' AND (" . $mine . ')' .
			$this->and_from_the_same_area($zip) .
			' ORDER BY created_at DESC ' .
			' LIMIT ' .
			mx_db_sql_quote($limit);
	}

	function and_from_the_same_area($zip) {
		return ''; // override
	}

	function list_recent($mykarte_user, $limit) {
		$db = mx_db_connect();
		$result = array();
		$obclass = $this->obclass;

		$uid = $mykarte_user->data['userid'];
		$area = $mykarte_user->data['zip'];
		print "<!--\n";
		var_dump($mykarte_user->data);
		print "-->\n";

		$stmt = $this->list_recent_stmt($uid, $area, 0, $limit);

		print "<!-- $stmt -->";

		foreach (mx_db_fetch_all(&$db, $stmt) as $tuple) {
			$result[] = new $obclass($tuple);
		}
		if (count($result) < $limit) {
			$limit -= count($result);
			$stmt = $this->list_recent_stmt($uid, $area, 1, $limit);
			foreach (mx_db_fetch_all(&$db, $stmt) as $tuple) {
				$result[] = new $obclass($tuple);
			}
		}
		return $result;
	}

	function fetch_one($id) {
		$db = mx_db_connect();
		$result = array();
		$obclass = $this->obclass;
		$stmt = $this->fetch_stmt($id);
		foreach (mx_db_fetch_all(&$db, $stmt) as $tuple) {
			$result[] = new $obclass($tuple);
		}
		if (!$result || count($result) != 1)
			return NULL;
		return $result[0];
	}
}

class mykarte_s extends mykarte_srt {
	var $table = 'mykarte_s';
	var $obclass = 'mykarte_s_obj';
	var $use_zip_limit = 0;

	function and_from_the_same_area($zip) {
		if (!$this->use_zip_limit)
			return '';
		return (' AND substring(seeker_zip from 1 for 3) = '.
			mx_db_sql_quote(substr($zip, 0, 3)));
	}
}
class mykarte_r extends mykarte_srt {
	var $table = 'mykarte_r';
	var $obclass = 'mykarte_r_obj';
	var $use_zip_limit = 0;

	function and_from_the_same_area($zip) {
		if (!$this->use_zip_limit)
			return '';
		return (' AND substring(recommendee_zip from 1 for 3) = '.
			mx_db_sql_quote(substr($zip, 0, 3)));
	}
}
class mykarte_t extends mykarte_srt {
	var $table = 'mykarte_t';
	var $obclass = 'mykarte_t_obj';
	var $minecol = array('createdby', 'thankee');
}

class mykarte_anno extends mykarte_srt {
	function mykarte_anno($it) {
		$this->it = $it;
	}

	function list_recent_stmt($uid, $zip, $not, $limit) {
		if (!is_array($this->minecol))
			$minecol = array($this->minecol);
		else
			$minecol = $this->minecol;

		$it = $this->it;
		$mine = array();
		foreach ($minecol as $c) {
			$mine[] = (mx_db_sql_quote_name($c) .
				   ($not ? ' != ' : ' = ') .
				   mx_db_sql_quote($uid));
		}
		if ($not)
			$mine = implode(' AND ', $mine);
		else
			$mine = implode(' OR ', $mine);
		return 'SELECT * FROM ' .
			mx_db_sql_quote_name($this->table) .
			' WHERE annotates = ' . $it .
			' AND (' . $mine .
			') ORDER BY created_at DESC ' .
			' LIMIT ' .
			mx_db_sql_quote($limit);
	}
}
class mykarte_a_s extends mykarte_anno {
	var $table = 'mykarte_a_s';
	var $obclass = 'mykarte_a_s_obj';
}
class mykarte_a_r extends mykarte_anno {
	var $table = 'mykarte_a_r';
	var $obclass = 'mykarte_a_r_obj';
}
class mykarte_a_t extends mykarte_anno {
	var $table = 'mykarte_a_t';
	var $obclass = 'mykarte_a_t_obj';
}

class mykarte_bare_obj {
	var $cols = array('created_at' =>
			  array('Label' => '投稿日',
				'String' => 'date'),
			  'createdby' =>
			  array('Label' => '投稿者',
				'String' => 'user'),
			  'note' =>
			  array('Label' => 'メッセージ',
				'String' => 'abbrev',
				'Abbrev' => 10));

	function mykarte_bare_obj($data) {
		$this->data = $data;
	}
	function draw_header() {
		print '<tr class="header">';
		foreach ($this->cols as $k => $attr) {
			print "<th>";
			print htmlspecialchars($attr['Label']);
			print "</th>";
		}
		print '</tr>';
	}
	function draw_as_row($nth) {
		$eo = ($nth % 2) ? 'o' : 'e';
		$d = $this->data;
		if (count($this->detailed_cols))
			$shnr=' onclick="toggle(this.nextSibling.firstChild.firstChild)"';
		else
			$shnr='';
		$ncols = count($this->cols);
		print "<tr class=\"$eo\"$shnr>";
		foreach ($this->cols as $k => $attr) {
			$v = $d[$k];

			print "<td>";
			$ts = $attr['String'];
			if ($ts) {
				$ts = "str_" . $ts;
				$v = $this->$ts($v, $d, $attr);
			}

			$draw = $attr['Draw'];
			if (!$draw)
				$draw = 'text';
			$draw = "dx_" . $draw;
			$this->$draw($v, $d, $attr);
			print "</td>";
		}
		print "</tr>";
		if ($shnr != "") {
			print "<tr>";
			print "<td colspan=\"$ncols\">";
			print "<div style=\"display:none\">";
			$this->draw_detailed();

			$this->draw_annotate_link();

			print "</div>";
			print "</td></tr>";
		}
	}
	function draw_annotate_link() {
	}
	function draw_detailed() {
		$d = $this->data;
		print "<table class=\"detailed\">";
		foreach ($this->detailed_cols as $k => $attr) {
			$v = $d[$k];
			if ($attr['Option']['OmitIfEmpty'] && !$v)
				continue;
			print "<tr>";
			print "<th>";
			print htmlspecialchars($attr['Label']);
			print "</th>";
			print "<td>";
			$ts = $attr['String'];
			if ($ts) {
				$ts = "str_" . $ts;
				$v = $this->$ts($v, $d, $attr);
			}
			$draw = $attr['Draw'];
			if (!$draw)
				$draw = 'text';
			$draw = "dx_" . $draw;
			$this->$draw($v, $d, $attr);
			print "</td>";
		}
		print "</table>";
	}
	function str_datetime($v, $d, $attr) {
		return mx_format_timestamp($v, 0);
	}
	function str_date($v, $d, $attr) {
		return mx_format_timestamp($v, -1);
	}
	function str_user($v, $d, $attr) {
		$u = new mykarte_user(NULL, $v);
		return $u->get_name();
	}
	function str_abbrev($v, $d, $attr) {
		$abbreviated = '';
		$c = mb_strlen($v);
		if ($c > $attr['Abbrev']) {
			$v = mb_substr($v, 0, $attr['Abbrev']);
			$abbreviated = '...';
		}
		$c = strpos($v, "\n");
		if ($c !== FALSE)
			$v = substr($v, 0, $c);
		return "$v$abbreviated";
	}
	function str_enumset($v, $d, $attr) {
		$r = array();
		$l = strlen($v);
		for ($i = 0; $i < $l; $i++) {
			$c = substr($v, $i, 1);
			if (array_key_exists($c, $attr['Enum']))
				$r[] = $attr['Enum'][$c];
		}
		return implode("\n", $r);
	}
	function dx_text($v, $d, $attr) {
		$s = htmlspecialchars($v);
		print str_replace("\n", "<br />\n", $s);
	}
	function str_userlist($v, $d, $attr) {
		$r = array();
		foreach ($v as $uid) {
			$u = new mykarte_user(NULL, $uid);
			$r[] = $u->get_name();
		}
		return implode(", ", $r);
	}
	function dx_thankable_userlist($v, $d, $attr) {
		global $mx_authenticate_current_user;

		$first = 1;
		$current = $mx_authenticate_current_user;
		foreach ($v as $uid) {
			$u = new mykarte_user(NULL, $uid);
			$n = $u->get_name();

			if (!$first)
				print "&nbsp;";
			$first = 0;
			print htmlspecialchars($n);
			if ($u->data['userid'] != $current)
				$this->draw_thank_it_link(NULL, $uid);
		}
	}
	function draw_thank_it_link($rid, $tid) {
		global $_mx_resource_dir;
		$button = "/$_mx_resource_dir/images/tqbutton.png";
		$b = "<img src=\"$button\" />";
		$h = "ThankQ を送る";
		$p = array();
		if ($rid)
			$p[] = "rid=$rid";
		if ($tid)
			$p[] = "tid=$tid";
		if (count($p))
			$param = '?' . implode('&amp;', $p);
		else
			$param = '';
		print "<a href=\"t-edit.php$param\" title=\"$h\">$b</a>";
	}
}

$__lib_mykarte_model_s_reasons = array(
	'A' => "体調が悪い、悩んでいる",
	'B' => "今は健康ですが何でも相談できるホームドクターが欲しい",
	'C' => "過去にこんな病気をしたので心配",
	'D' => "ドクターにこんな治療を勧められたがどうしよう",
	'E' => "米国で健康診断か治療をうけたい",
	'F' => "歯のインプラント・矯正をしたい",
	'G' => "美容整形をしたい",
	'H' => "自由診療制のドクターを知りたい",
	'I' => "予約制のドクターを知りたい",
	'J' => "その他"
	);

class mykarte_s_obj extends mykarte_bare_obj {
	function mykarte_s_obj($data) {
		global $__lib_mykarte_model_s_reasons;
		mykarte_bare_obj::mykarte_bare_obj($data);
		$this->detailed_cols =
		array('created_at' =>
		      array('Label' => '投稿日',
			    'String' => 'datetime'),
		      'seeker' =>
		      array('Label' => 'さがしている人',
			    'String' => 'seeker_name'),
		      'reason_codes' =>
		      array('Label' => '理由',
			    'String' => 'enumset',
			    'Enum' =>
			    $__lib_mykarte_model_s_reasons),
		      'note' =>
		      array('Label' => 'メッセージ'));
	}
	function str_seeker_name($v, $d, $attr) {
		$str = $v;
		if ($d['anonymous'] == 'Y')
			$str = '非公開';
		if ($d['seeker_zip'])
			$str = "$str (〒 " . $d['seeker_zip'] . ")";
		return $str;
	}
	function str_user($v, $d, $attr) {
		if ($d['anonymous'] == 'Y')
			return '非公開';
		return mykarte_bare_obj::str_user($v, $d, $attr);
	}
	function draw_annotate_link() {
		$id = $this->data['id'];
		print "<a href=\"s-detail.php?id=$id\"";
		print " style=\"float: right\"";
		print ">&gt;&gt;</a>";
	}

	function matching_r() {
		$db = mx_db_connect();
		$id = $this->data['id'];
		$stmt = ("SELECT r_id, created_at, createdby ".
			 "FROM mykarte_r_to_s " .
			 "WHERE s_id = " . mx_db_sql_quote($id) .
			 "ORDER BY created_at DESC");
		$rfac = new mykarte_r();
		$rhash = array();
		foreach (mx_db_fetch_all($db, $stmt) as $tuple) {
			$rid = $tuple['r_id'];
			if (!array_key_exists($rid, $rhash)) {
				$r = $rfac->fetch_one($rid);
				$rhash[$rid] = $r;
			}
			$rhash[$rid]->data['linkers'][] = $tuple['createdby'];
		}
		return $rhash;
	}

	function r_in_same_area_unmatched($limit=NULL) {
		if (is_null($limit))
			$limit = 10;
		$db = mx_db_connect();
		$stmt = ('SELECT zip FROM mykarte_user_view '.
			 'WHERE mykarte_user = ' .
			 mx_db_sql_quote($this->data['createdby']));
		$result = mx_db_fetch_single($db, $stmt);
		if (!is_array($result))
			return array();
		$zip = $result['zip'];
		$rfactory = new mykarte_r();
		$stmt = $rfactory->list_recent_stmt(NULL, $zip, 0, $limit);

		$id = $this->data['id'];
		$by = $this->data['createdby'];
		$un = (' AND (id, ' . mx_db_sql_quote($id) . ')' .
		       'NOT IN (SELECT r_id, s_id FROM mykarte_r_to_s)' .
		       ' AND createdby != ' . mx_db_sql_quote($by));
		$m = array();
		preg_match('/\A(.*)( ORDER BY .*)\Z/', $stmt, &$m);
		$stmt = $m[1] . $un . $m[2];
		$result = array();
		foreach (mx_db_fetch_all(&$db, $stmt) as $tuple) {
			$result[] = new mykarte_r_obj($tuple);
		}
		return $result;
	}
}

$__lib_mykarte_model_r_reasons = array(
	"A" => "やさしい",
	"B" => "的確なアドバイス",
	"C" => "くわしく説明",
	"D" => "話をよく聞いてくれる、相談しやすい",
	"E" => "こちらの気持ちをよくわかってくれる",
	"F" => "治療が的確でじょうず",
	"G" => "知識が豊富",
	"H" => "経験が豊富",
	"I" => "専門医を紹介",
	"J" => "時間を十分取ってくれる",
	"K" => "予約ができるので待ち時間が少ない",
	"L" => "時間外でも急用のときには対応してくれる",
	);

$__lib_mykarte_model_r_recommender_enum = array(
	'A' => '本人',
	'B' => '家族',
	'C' => '友人',
	'D' => '知人',
	);

class mykarte_r_obj extends mykarte_bare_obj {
	function mykarte_r_obj($data) {
		global $__lib_mykarte_model_r_reasons;
		mykarte_bare_obj::mykarte_bare_obj($data);
		$this->detailed_cols =
			array('created_at' =>
			      array('Label' => '投稿日',
				    'String' => 'datetime'),
			      'recommender' =>
			      array('Label' => '推薦者',
				    'String' => 'recommender',
				    'Draw' => 'thankable_recommender'),
			      'recommendee_name' =>
			      array('Label' => '医師',
				    'String' => 'recommendee'),
			      'reason_codes' =>
			      array('Label' => '理由',
				    'String' => 'enumset',
				    'Enum' =>
				    $__lib_mykarte_model_r_reasons),
			      'note' =>
			      array('Label' => 'メッセージ'),
			      'linkers' =>
			      array('Label' => 'マッチしてくれた人',
				    'Draw' => 'thankable_userlist',
				    'Option' => array('OmitIfEmpty' => 1)),
			      );
		$this->data['linkers'] = array();
	}

	function str_recommender($v, $d, $attr) {
		global $__lib_mykarte_model_r_recommender_enum;
		$c = $d['recommender_code'];
		if ($__lib_mykarte_model_r_recommender_enum[$c]) {
			$c = $__lib_mykarte_model_r_recommender_enum[$c];
			$me = " ($c)";
		} else
			$me = '';
		return "$v$me";
	}

	function dx_thankable_recommender($v, $d, $attr) {
		global $mx_authenticate_current_user;
		$current = $mx_authenticate_current_user;

		$rid = $this->data['id'];
		$tid = $this->data['createdby'];
		print htmlspecialchars($v);

		$u = new mykarte_user(NULL, $tid);
		if ($u->data['userid'] != $current)
			$this->draw_thank_it_link($rid, $tid);
	}

	function str_recommendee($v, $d, $attr) {
		$l = array();
		$x = $d['recommendee_zip'];
		if ($x != '')
			$l[] = "〒 $x";
		$x = $d['recommendee_pref'] . $d['recommendee_city'];
		if ($x != '')
			$l[] = $x;
		$x = $d['recommendee_org'];
		if ($x != '')
			$l[] = $x;
		$l = implode(' ', $l);
		if ($l != '')
			$v = "$v ($l)";
		return $v;
	}

	function draw_annotate_link() {
		$id = $this->data['id'];
		print "<a href=\"r-detail.php?id=$id\"";
		print " style=\"float: right\"";
		print ">&gt;&gt;</a>";
	}

	function draw_link_to_me() {
		$id = $this->data['id'];
		print "<a href=\"r-detail.php?id=$id\">";
		$r = $this->str_recommendee($this->data['recommendee_name'],
					    $this->data, array());
		print htmlspecialchars($r);
		print "</a>";
	}
}

class mykarte_t_obj extends mykarte_bare_obj {
	var $cols = array('created_at' =>
			  array('Label' => '投稿日',
				'String' => 'date'),
			  'createdby' =>
			  array('Label' => '投稿者',
				'String' => 'user'),
			  'note' =>
			  array('Label' => 'メッセージ',
				'String' => 'abbrev',
				'Abbrev' => 10));
	var $detailed_cols = array('created_at' =>
				   array('Label' => '投稿日',
					 'String' => 'datetime'),
				   'thankee' =>
				   array('Label' => 'ThankQ',
					 'String' => 'user'),
				   'thank_r' =>
				   array('Label' => 'すいせん',
					 'Option' => array('OmitIfEmpty' => 1),
					 'Draw' => 'link_to_r'),
				   'note' =>
				   array('Label' => 'メッセージ'));

	function dx_link_to_r($v, $d, $attr) {
		$rfac = new mykarte_r();
		$r = $rfac->fetch_one($v);
		$r->draw_link_to_me();
	}

	function draw_annotate_link() {
		$id = $this->data['id'];
		print "<a href=\"t-detail.php?id=$id\"";
		print " style=\"float: right\"";
		print ">&gt;&gt;</a>";
	}
}

class mykarte_anno_obj extends mykarte_bare_obj {
	var $detailed_cols = array('created_at' =>
				   array('Label' => '投稿日',
					 'String' => 'datetime'),
				   'createdby' =>
				   array('Label' => '投稿者',
					 'String' => 'user'),
				   'note' =>
				   array('Label' => 'メッセージ'));

}

class mykarte_a_s_obj extends mykarte_anno_obj {}
class mykarte_a_r_obj extends mykarte_anno_obj {}
class mykarte_a_t_obj extends mykarte_anno_obj {}

?>
