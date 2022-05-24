<?php // -*- mode: php; coding: euc-japan -*-
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/common.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/mykarte/lib.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/mykarte/model.php';

$_mykarte_s_edit_cfg = array
(
	'ECOLS' => array(array('Column' => 'seeker',
			       'Label' => 'さがしている人',
			       'Option' => array('validate' => 'nonnull',
						 'size' => '50')),
			 array('Column' => 'anonymous',
			       'Label' => '匿名',
			       'Draw' => 'checkbox'),
			 array('Column' => "reason_codes",
			       'Label' => "理由",
			       'Draw' => 'reason_codes',
			       'Option' => $__lib_mykarte_model_s_reasons),
			 array('Column' => "note",
			       'Label' => "メッセージ",
			       'Draw' => 'textarea',
			       'Option' => array('validate' => 'nonnull,len',
						 'validate-maxlen' => 200,
						 'cols' => 50,
						 'rows' => 4))),
);

class s_edit_edit extends mykarte_edit {

	var $debug = 1;

	function s_edit_edit($prefix, $u, $cfg=NULL) {
		global $_mykarte_s_edit_cfg;
		if (is_null($cfg))
			$cfg = $_mykarte_s_edit_cfg;
		mykarte_edit::mykarte_edit($prefix, $u, $cfg);
		if (trim($this->data['seeker']) == '') {
			$m = new mykarte_user($this->user);
			$this->data['seeker'] = $m->data['handle'];
		}
	}

	function create_one(&$db, $d) {
		$m = new mykarte_user($this->user);
		$me = $m->data['mykarte_user'];
		$my_zip = $m->data['zip'];
		$an = ($d['anonymous'] ? 'Y' : 'N');
		$stmt = ("INSERT INTO mykarte_s " .
			 "(created_at, createdby, seeker_zip, " .
			 "seeker, anonymous, reason_codes, note) VALUES (" .
			 "now(), " .
			 mx_db_sql_quote($me) . ", " .
			 mx_db_sql_quote($my_zip) . ", " .
			 mx_db_sql_quote($d['seeker']) . ", " .
			 mx_db_sql_quote($an) . ", " .
			 mx_db_sql_quote($d['reason_codes']) . ", " .
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
