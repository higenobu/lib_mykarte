<?php // -*- mode: php; coding: euc-japan -*-
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/common.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/mykarte/lib.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/mykarte/model.php';

$_mykarte_r_edit_cfg = array
(
	'ECOLS' => array(array('Column' => 'recommender',
			       'Label' => '¿äÁ¦¼Ô',
			       'Option' => array('validate' => 'nonnull',
						 'size' => '50')),
			 array('Column' => 'recommender_code',
			       'Label' => '´Ø·¸',
			       'Draw' => 'Enum',
			       'Enum' => $__lib_mykarte_model_r_recommender_enum),
			 array('Column' => 'recommendee_name',
			       'Label' => '°å»Õ',
			       'Option' => array('validate' => 'nonnull',
						 'size' => '50')),
			 array('Column' => 'recommendee_org',
			       'Label' => '½êÂ°',
			       'Option' => array('validate' => 'nonnull',
						 'size' => '50')),
			 array('Column' => 'recommendee_zip',
			       'Label' => '¢©',
			       'Option' => array('validate' => 'nonnull,len',
						 'validate-maxlen' => 7,
						 'validate-minlen' => 7,
						 'size' => '7')),
			 array('Column' => 'reason_codes',
			       'Label' => "ÍýÍ³",
			       'Draw' => 'reason_codes',
			       'Option' => $__lib_mykarte_model_r_reasons),
			 array('Column' => "note",
			       'Label' => "¥á¥Ã¥»¡¼¥¸",
			       'Draw' => 'textarea',
			       'Option' => array('validate' => 'nonnull,len',
						 'validate-maxlen' => 200,
						 'cols' => 50,
						 'rows' => 4))),
);

class r_edit_edit extends mykarte_edit {

	var $debug = 1;

	function r_edit_edit($prefix, $u, $cfg=NULL) {
		global $_mykarte_r_edit_cfg;
		if (is_null($cfg))
			$cfg = $_mykarte_r_edit_cfg;
		$this->r_against = NULL;
		if (array_key_exists('sid', $_REQUEST))
			$this->r_against = $_REQUEST['sid'];
		mykarte_edit::mykarte_edit($prefix, $u, $cfg);
		if (trim($this->data['recommender']) == '') {
			$m = new mykarte_user($this->user);
			$this->data['recommender'] = $m->data['handle'];
			$this->data['recommender_code'] = 'A';
		}
	}

	function create_one(&$db, $d) {
		$m = new mykarte_user($this->user);
		$me = $m->data['mykarte_user'];
		$stmt = "SELECT nextval('mykarte_record_seq') as id";
		$id = mx_db_fetch_single($db, $stmt);
		$id = $id['id'];

		$stmt = ("INSERT INTO mykarte_r " .
			 "(id, created_at, createdby, " .
			 "recommender_code, recommender, reason_codes, " .
			 "recommendee_name, recommendee_org, " .
			 "recommendee_zip, recommendee_pref, " .
			 "recommendee_city, note) VALUES (" .
			 $id . ", " .
			 "now(), " .
			 mx_db_sql_quote($me) . ", " .
			 mx_db_sql_quote($d['recommender_code']) . ", " .
			 mx_db_sql_quote($d['recommender']) . ", " .
			 mx_db_sql_quote($d['reason_codes']) . ", " .
			 mx_db_sql_quote($d['recommendee_name']) . ", " .
			 mx_db_sql_quote($d['recommendee_org']) . ", " .
			 mx_db_sql_quote($d['recommendee_zip']) . ", " .
			 mx_db_sql_quote($d['recommendee_pref']) . ", " .
			 mx_db_sql_quote($d['recommendee_city']) . ", " .
			 mx_db_sql_quote($d['note']) . ")");
		if (!pg_query($db, $stmt)) {
			$this->err("ÅÐÏ¿¤Ç¤­¤Þ¤»¤ó");
			return 'failure';
		}

		if ($this->r_against) {
			$stmt = ("INSERT INTO mykarte_r_to_s " .
				 "(s_id, r_id, created_at, createdby) " .
				 "VALUES (" .
				 mx_db_sql_quote($this->r_against) . ", " .
				 mx_db_sql_quote($id) . ", " .
				 "now(), " .
				 mx_db_sql_quote($me) . ")");
			if (!pg_query($db, $stmt)) {
				$this->err("ÅÐÏ¿¤Ç¤­¤Þ¤»¤ó");
				return 'failure';
			}
		}

		if (! pg_query($db, 'commit')) {
			$this->err(pg_last_error($db));
			return 'failure';
		}
		return 'ok';
	}
}

?>
