<?php // -*- mode: php; coding: euc-japan -*-
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/common.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/mykarte/lib.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/mykarte/model.php';

$_mykarte_t_edit_cfg = array
(
	'ECOLS' => array(array('Column' => "note",
			       'Label' => "メッセージ",
			       'Draw' => 'textarea',
			       'Option' => array('validate' => 'nonnull,len',
						 'validate-maxlen' => 200,
						 'cols' => 50,
						 'rows' => 4))),
);

class t_edit_edit extends mykarte_edit {

	var $debug = 1;

	function t_edit_edit($prefix, $u, $cfg=NULL) {
		global $_mykarte_t_edit_cfg;
		if (is_null($cfg))
			$cfg = $_mykarte_t_edit_cfg;
		$this->thank_r = NULL;
		$this->thankee = NULL;
		if (array_key_exists('rid', $_REQUEST))
			$this->thank_r = $_REQUEST['rid'];
		if (array_key_exists('tid', $_REQUEST))
			$this->thankee = $_REQUEST['tid'];
		mykarte_edit::mykarte_edit($prefix, $u, $cfg);
	}

	function create_one(&$db, $d) {
		$m = new mykarte_user($this->user);
		$me = $m->data['mykarte_user'];

		$thank_r = $this->thank_r;
		$thankee = $this->thankee;

		$stmt = ("INSERT INTO mykarte_t " .
			 "(created_at, createdby, " .
			 "thank_r, thankee, note) VALUES (" .
			 "now(), " . mx_db_sql_quote($me) . ", " .
			 mx_db_sql_quote($thank_r) . ", " .
			 mx_db_sql_quote($thankee) . ", " .
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

?>