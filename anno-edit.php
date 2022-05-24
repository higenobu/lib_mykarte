<?php // -*- mode: php; coding: euc-japan -*-
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/common.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/mykarte/lib.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/mykarte/model.php';

$_mykarte_anno_edit_cfg = array
(
	'ECOLS' => array(array('Column' => "note",
			       'Label' => "メッセージ",
			       'Draw' => 'textarea',
			       'Option' => array('validate' => 'nonnull,len',
						 'validate-maxlen' => 200,
						 'cols' => 50,
						 'rows' => 4))),
);

class mykarte_anno_edit extends mykarte_edit {

	var $debug = 1;
	var $default_empty_after_commit = 0;

	function mykarte_anno_edit($prefix, $u, $anno, $cfg=NULL) {
		global $_mykarte_anno_edit_cfg;
		if (is_null($cfg))
			$cfg = $_mykarte_anno_edit_cfg;
		$this->anno = $anno;
		mykarte_edit::mykarte_edit($prefix, $u, $cfg);
	}

	function create_one(&$db, $d) {
		$m = new mykarte_user($this->user);
		$me = $m->data['mykarte_user'];
		$anno = $this->anno->data['id'];
		$stmt = ("INSERT INTO " .
			 mx_db_sql_quote_name($this->table) .
			 " (created_at, createdby, " .
			 "annotates, note) VALUES (" .
			 "now(), " .
			 mx_db_sql_quote($me) . ", " .
			 mx_db_sql_quote($anno) . ", " .
			 mx_db_sql_quote($d['note']) . ")");
		if (!pg_query($db, $stmt)) {
			$this->err("登録できません");
			return 'failure';
		}
		if (! pg_query($db, 'commit')) {
			$this->err(pg_last_error($db));
			return 'failure';
		}
		return 'ok';
	}
}

class mykarte_a_s_edit extends mykarte_anno_edit {
	var $table = 'mykarte_a_s';
}
class mykarte_a_r_edit extends mykarte_anno_edit {
	var $table = 'mykarte_a_r';
}
class mykarte_a_t_edit extends mykarte_anno_edit {
	var $table = 'mykarte_a_t';
}
?>
