<?php // -*- mode: php; coding: euc-japan -*-
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/common.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/mykarte/lib.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/mykarte/model.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/mykarte/registration.php';

$_mykarte_p_edit_cfg = array('ECOLS' => array(),
			     'ICOLS' => array());

foreach ($_mykarte_registration_cfg['ECOLS'] as $item) {
	if ($item['Column'] == 'handle')
		$item['Draw'] = 'static';
	$_mykarte_p_edit_cfg['ECOLS'][] = $item;
	$_mykarte_p_edit_cfg['ICOLS'][] = $item['Column'];
}

$__lib_mykarte_p_edit_column_map = array(
	'handle' => array('SOE' => 'handle'),
	'email' => array('SOE' => 'email', 'dst' => 1),
	'zip' => array('SOE' => '住所0', 'dst' => 2),
	'pref' => array('SOE' => '住所1', 'dst' => 2),
	'city' => array('SOE' => '住所2', 'dst' => 2),
	'address' => array('SOE' => '住所3', 'dst' => 2),
	'first_name' => array('SOE' => '名', 'dst' => 2),
	'family_name' => array('SOE' => '姓', 'dst' => 2),
	'anonymous' => array('SOE' => 'anonymous', 'dst' => 1),
	'birthdate' => array('SOE' => '生年月日', 'dst' => 2),
);

class p_edit_edit extends simple_object_edit {

	function p_edit_edit($prefix, $u, $cfg=NULL) {
		global $_mykarte_p_edit_cfg;
		if (is_null($cfg))
			$cfg = $_mykarte_p_edit_cfg;
		$this->user = $u;
		simple_object_edit::simple_object_edit($prefix, $cfg);
		$this->empty_tweak();
	}

	function fetch_original_data() {
		global $__lib_mykarte_p_edit_column_map;
		$m = new mykarte_user($this->user);
		$data = array();

		foreach ($__lib_mykarte_p_edit_column_map as $a => $b)
			$data[$b['SOE']] = $m->data[$a];
		return $data;
	}

	function empty_tweak() {
		foreach ($this->data as $k => $v) {
			if (trim($v) != '' && $k != 'anonymous')
				return;
		}
		$this->resync();
	}

        function precompute_insert_stmt_head() {}
        function resync() {
		# fetch from the view
		$data = $this->fetch_original_data();
		$this->annotate_form_data(&$data);
		$this->data = $data;
	}

	function update_ep($db, $id, $table, $cols) {
		$up = array();
		foreach ($cols as $n => $d) {
			$s = mx_db_sql_quote_name($n) . ' = ';
			$s .= mx_db_sql_quote($d);
			$up[] = $s;
		}
		$up = implode(', ', $up);
		$stmt = ('UPDATE ' . mx_db_sql_quote_name($table) .
			 ' SET ' . $up .
			 ' WHERE "ObjectID" = ' . mx_db_sql_quote($id));
		if (!pg_query($db, $stmt)) {
			$this->err("$table を更新できません");
			return -1;
		}
		return 0;
	}

	function update_m($db, $eid, $cols) {
		$up = array();
		foreach ($cols as $n => $d) {
			$s = mx_db_sql_quote_name($n) . ' = ';
			$s .= mx_db_sql_quote($d);
			$up[] = $s;
		}
		$up = implode(', ', $up);
		$stmt = ('UPDATE mykarte_users ' .
			 ' SET ' . $up .
			 ' WHERE mx_employee = ' . mx_db_sql_quote($eid));
		if (!pg_query($db, $stmt)) {
			$this->err("$table を更新できません");
			return -1;
		}
		return 0;
	}

	function try_commit(&$db) {
		global $__lib_mykarte_p_edit_column_map;

		$this->change_nature = 'updated';

		$orig = $this->fetch_original_data();
		$data = $this->data;
		$m = new mykarte_user($this->user);
		$bad = 0;

		$ep_dst = array();
		$m_dst = array();
		foreach ($__lib_mykarte_p_edit_column_map as $a => $b) {
			$soename = $b['SOE'];
			$d = $data[$soename];
			if ($d == $orig[$soename])
				continue;
			switch ($b['dst']) {
			case 1: $m_dst[$a] = $d; break;
			case 2: $ep_dst[$soename] = $d; break;
			}
		}
		$eid = $m->data['employee_objectid'];
		if (count($ep_dst)) {
			$pid = $m->data['patient_objectid'];
			$bad |= $this->update_ep($db, $eid, "職員台帳", $ep_dst);
			$bad |= $this->update_ep($db, $pid, "患者台帳", $ep_dst);
		}
		if (count($m_dst))
			$bad |= $this->update_m($db, $eid, $m_dst);

		if (!count($ep_dst) && !count($m_dst))
			$this->change_nature = 'nochange';

		if (!$bad) {
			if (!pg_query($db, 'commit')) {
				$this->err(pg_last_error($db));
				$bad = 1;
			}
		}

		if ($bad) {
			$this->err("更新させません");
			return 'bad';
		}
		else {
			return 'ok';
		}
	}

	function _validate($force=NULL) {
		$this->commit_tried = 1;
		$status = simple_object_edit::_validate($force);
		return $status;
	}

}

?>