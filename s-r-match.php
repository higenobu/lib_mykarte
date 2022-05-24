<?php // -*- mode: php; coding: euc-japan -*-
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/common.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/mykarte/lib.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/mykarte/model.php';

class mykarte_s_r_match extends mykarte_edit {

	var $debug = 1;

	function mykarte_s_r_match($prefix, $u) {

		$this->prefix = $prefix;
		$this->sid = $_REQUEST['sid'];
		$sfac = new mykarte_s();
		$this->s = $sfac->fetch_one($this->sid);
		$this->rlist = $this->s->r_in_same_area_unmatched();
		$cfg = array('ECOLS' => array());

		$n = $this->prefix . 'recommend-';
		$l = strlen($n);
		$a = array();
		foreach ($_REQUEST as $k => $v) {
			if (strncmp($k, $n, $l))
				continue;
			$v = substr($k, $l);
			$a[$v] = 1;
		}
		$this->associate = $a;
		mykarte_edit::mykarte_edit($prefix, $u, $cfg);
	}

	function draw_body() {

		$n = $this->prefix . 'recommend-';
		foreach ($this->rlist as $r) {
			$rid = $r->data['id'];
			$x = array_key_exists($rid, $this->associate);
			mx_formi_checkbox($n . $rid, array('Caption' => $x));
			$r->draw_detailed();
			$first = 0;
			print "<hr />";
		}

	}

	function create_one($db, $data) {
		$m = new mykarte_user($this->user);
		$me = $m->data['mykarte_user'];
		$sid = $this->sid;
		$bad = 0;
		foreach ($this->associate as $rid => $v) {

			$stmt = ("INSERT INTO mykarte_r_to_s " .
				 "(s_id, r_id, created_at, createdby) " .
				 "VALUES (" .
				 mx_db_sql_quote($sid) . ", " .
				 mx_db_sql_quote($rid) . ", " .
				 "now(), " .
				 mx_db_sql_quote($me) . ")");
			if (!pg_query($db, $stmt))
				$bad = 1;
		}
		if (!$bad && !pg_query($db, 'commit')) {
			$this->err(pg_last_error($db));
			$bad = 1;
		}
		if ($bad) {
			$this->err("登録できません");
			return 'failure';
		}
		return 'ok';
	}


}

?>
