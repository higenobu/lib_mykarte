<?php // -*- mode: php; coding: euc-japan -*-
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/common.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/soe.php';

$_mykarte_confirm_cfg = array
(
	'ECOLS' => array(array('Column' => 'handle',
			       'Label' => 'スクリーン名',
			       'Option' => array('validate' => 'nonnull,len',
						 'validate-minlen' => 6,
						 'validate-maxlen' => 16)),
			 array('Column' => 'pass0',
			       'Label' => 'パスワード',
			       'Draw' => 'password',
			       'Option' => array('validate' => 'nonnull,len',
						 'validate-minlen' => 6)),
			 array('Column' => 'pass1',
			       'Label' => 'パスワード（もう一度）',
			       'Draw' => 'password',
			       'Option' => array('validate' => 'nonnull'))),
);

class mykarte_confirm_edit extends simple_object_edit {

	function mykarte_confirm_edit($prefix, $cfg=NULL) {
		global $_mykarte_confirm_cfg;
		if (is_null($cfg))
			$cfg = $_mykarte_confirm_cfg;
		if (!array_key_exists('PATH_INFO', $_SERVER))
			$this->bogus_url = 1;
		else {
			$pathinfo = $_SERVER['PATH_INFO'];
			$m = array();
			if (!preg_match('/^\/(\d+)\/(.*)$/', $pathinfo, &$m))
				$this->bogus_url = 1;
			else {
				$this->eid = $m[1];
				$this->cookie = $m[2];
			}
		}
		simple_object_edit::simple_object_edit($prefix, $cfg);
	}

	function draw() {
		if ($this->bogus_url) {
			print "Heh, what are you talking about!";
			return;
		}
		simple_object_edit::draw();
	}

	function dx_password($desc, $name, $value) {
		mx_formi_password($name, $value);
	}

	// We do not do insert at all.
	function precompute_insert_stmt_head() {}
	function resync() {}

	// this is inside "begin"
	function try_commit(&$db) {
		$this->change_nature = 'updated';

		$stmt = ('SELECT U.handle AS handle, U.mx_employee as eid, '.
			 'U.confirm_cookie as cookie, E.userid as userid '.
			 'FROM mykarte_users AS U '.
			 'LEFT JOIN "職員台帳" AS E ' .
			 'ON U.mx_employee = E."ObjectID" ' .
			 'WHERE U.mx_employee = ' . $this->eid .
			 ' AND U.confirm_cookie = ' .
			 mx_db_sql_quote($this->cookie) .
			 ' AND U.handle = ' .
			 mx_db_sql_quote($this->data['handle']));
		$sth = pg_query($db, $stmt);
		if (!$sth) {
			$this->err("登録データがありません(1)");
			return 'bad';
		}
		$d = pg_fetch_all($sth);
		if (!$d || count($d) != 1) {
			$this->err("登録データがありません(2)");
			return 'bad';
		}
		$h = $d[0]['handle'];
		$p = $this->data['pass0'];
		$qph = mx_db_sql_quote(mx_authenticate_hmac($h . ':' . $p));

		$stmt = ('UPDATE mx_authenticate SET passhash = ' .
			 $qph .
			 ' WHERE userid = ' . $d[0]['userid']);
		if (!pg_query($db, $stmt)) {
			$this->err("パスワードを設定できません(1)");
			return 'bad';
		}

		$stmt = ('UPDATE mykarte_users SET confirm_cookie = NULL,' .
			 'sha1_password = ' . mx_db_sql_quote(sha1($p)) .
			 ' WHERE mx_employee = ' . $this->eid .
			 ' AND confirm_cookie = ' .
			 mx_db_sql_quote($this->cookie) .
			 ' AND handle = ' .
			 mx_db_sql_quote($this->data['handle']));
		if (!pg_query($db, $stmt)) {
			$this->err("パスワードを設定できません(2)");
			return 'bad';
		}

		if (! pg_query($db, 'commit')) {
			$this->err(pg_last_error($db));
			return 'failure';
		}
		return 'ok';
	}

	function _validate($force=NULL) {
		if ($this->bogus_url) {
			$this->err("ページが存在しません。");
			return 'bad';
		}
		$status = simple_object_edit::_validate($force);
		if ($this->data['pass0'] != '' &&
		    $this->data['pass1'] != '' &&
		    $this->data['pass0'] != $this->data['pass1']) {
			$this->err("２つのパスワードが一致しません\n");
			$status = 'bad';
		}
		return $status;
	}
}
