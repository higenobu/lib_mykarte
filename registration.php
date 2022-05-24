<?php // -*- mode: php; coding: euc-japan -*-
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/common.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/soe.php';
//07012016
$_mykarte_registration_cfg = array
(
	'ECOLS' => array(array('Column' => 'handle',
			       'Label' => 'スクリーン名/ID',
			       'Option' => array('validate' => 'nonnull,len',
						 'validate-minlen' => 6,
						 'validate-maxlen' => 16)),

			 array('Column' => "住所0",
			       'Label' => "〒/zip",'Draw'=>'text'),
/*
			       'Draw' => 'post_code',
			       'Option' => array('ime' => 'disabled',
						 'zip' => '住所0',
						 'prefecture' => '住所1',
						 'city' => '住所2',
						 'block' => '住所3',
						 'add_id' => 1,
						 'validate' => 'nonnull,len',
						 'validate-minlen' => 7,
						 'validate-maxlen' => 7)),\*/

			 array('Column' => "住所1",
			       'Label' => "都道府県/state",
			       'Option' => array('add_id' => 1,
						 'validate' => 'len',
						 'validate-maxlen' => 20)),
			 array('Column' => "住所2",
			       'Label' => "市町村/city.street",
			       'Option' => array('add_id' => 1,
						 'validate' => 'len',
						 'validate-maxlen' => 20)),
			 array('Column' => "住所3",
			       'Label' => "番地/no",
			       'Option' => array('add_id' => 1,
						 'validate' => 'len',
						 'validate-maxlen' => 60)),

			 array('Column' => '姓',
			       'Label' => '姓/Last name',
			       'Option' => array('validate' => 'nonnull,len',
						 'validate-maxlen' => 30)),
			 array('Column' => '名',
			       'Label' => '名/First name',
			       'Option' => array('validate' => 'nonnull,len',
						 'validate-maxlen' => 30)),
			 array('Column' => 'フリガナ',
			       'Label' => '名前ふりがな/nick name',
			       'Option' => array('validate' => 'len',
						 'validate-maxlen' => 60)),
			 array('Column' => 'anonymous',
			       'Label' => '氏名を公開しない/not open',
			       'Draw' => 'check'),
			 array('Column' => '性別', 'Label' => 'Gender',
			       'Draw' => 'enum',
			       'Enum' => array('' => '',
					       'M' => '男/male', 'F' => '女/female'),
			       'Option' => array('validate' => 'nonnull')),
			 array('Column' => 'email',
			       'Label' => 'メールアドレス/mail address',
			       'Option' => array('validate' => 'nonnull,len',
						 'validate-minlen' => 0,
						 'validate-maxlen' => 128)),
			 array('Column' => '生年月日',
			       'Label' => '生年月日/birth date',
			       'Draw' => 'date',
			       'Option' => array('validate' => 'date,nonnull'))
		),
);

function email_valid($e) {
	$e = trim($e);
	return preg_match('/^[-.\w]+@(?:[-\w]+\.)+[\w]+$/', $e);
}

class registration_data_edit extends simple_object_edit {

	function registration_data_edit($prefix, $cfg=NULL) {
		global $_mykarte_registration_cfg;
		if (is_null($cfg))
			$cfg = $_mykarte_registration_cfg;
		simple_object_edit::simple_object_edit($prefix, $cfg);
	}

	// We do not do insert at all.
	function precompute_insert_stmt_head() {}
	function resync() {}

	function create_one(&$db, $d) {
		$this->confirm = NULL;

		// Create a new mx_authenticate object.
		$qid = mx_db_sql_quote($d['handle']);
		$result = mx_db_fetch_single($db,
					     "SELECT max(userid) FROM ".
					     "mx_authenticate");
		$uid = $result['max'] + 1;
		$this->log('UID is ' . $uid . "\n");
		$this->log('QID is ' . $qid . "\n");
		pg_query($db,
			 "INSERT INTO mx_authenticate ".
			 "(userid, username, passhash) ".
			 "VALUES ($uid, $qid, NULL)");
		$result = mx_db_fetch_single($db,
					     "SELECT userid FROM " .
					     "mx_authenticate " .
					     "WHERE username = $qid");
		if (! (is_array($result) && $result['userid'] == $uid) ) {
			$this->err("スクリーン名がすでに使われています(1)");
			$this->log('RESULT: ' . mx_var_dump($result));
			return 'failure';
		}

		$stmt = ("SELECT ".
			 "nextval('\"患者台帳_ID_seq\"') AS pid," .
			 "nextval('\"職員台帳_ID_seq\"') AS eid");

		$sth = pg_query($db, $stmt);
		if (!$sth) {
			$this->err("ユーザ登録できません(1)");
			return 'failure';
		}
		$data = pg_fetch_all($sth);
		$pid = $data[0]['pid'];
		$eid = $data[0]['eid'];
$email=$d['email'];
		$v = array($pid, $pid,
			   mx_db_sql_quote(sprintf("%07d", $pid)),
			   mx_db_sql_quote($d['姓']),
			   mx_db_sql_quote($d['名']),
			   mx_db_sql_quote($d['フリガナ']),
			   mx_db_sql_quote($d['性別']),
			   mx_db_sql_quote($d['生年月日']),

			   mx_db_sql_quote($d['住所0']),
			   mx_db_sql_quote($d['住所1']),
			   mx_db_sql_quote($d['住所2']),
			   mx_db_sql_quote($d['住所3']));
		$stmt = ('INSERT INTO "患者台帳" (' .
			 '"ID", "ObjectID", ' .
		 '"患者ID", "姓", "名", "フリガナ", "性別", "生年月日", ' .
			 '"住所0", "住所1", "住所2", "住所3","メールアドレス") VALUES (' .
			 implode(', ', $v) .','."'".$email."'". ')');
		if (!pg_query($db, $stmt)) {
			$this->err("ユーザ登録できません(2)");
			return 'failure';
		}

		$v[0] = $v[1] = $eid;

		// Customer log-in; see psql/Customize-MYKARTE.sql
//07022016 changed mykarte syukusyu etc
		$edata = array(35, 9, 1);

		$stmt = ('INSERT INTO "職員台帳" (' .
			 '"ID", "ObjectID", ' .
		 '"職員ID", "姓", "名", "フリガナ", "性別", "生年月日", ' .
			 '"住所0", "住所1", "住所2", "住所3", ' .
			 '"職種", "職位", "部署", userid) VALUES (' .
			 implode(', ', $v) . ', ' .
			 implode(', ', $edata) . ', ' .
			 $uid .
			 ')');
		if (!pg_query($db, $stmt)) {
			$this->err("ユーザ登録できません(3)");
			return 'failure';
		}
/*
		$stmt = ('INSERT INTO "患者担当職員" ("患者") '.
			 'VALUES (' . $pid . ')');
		if (!pg_query($db, $stmt)) {
			$this->err("ユーザ登録できません(4)");
			return 'failure';
		}
		$stmt = ('SELECT "ObjectID" FROM "患者担当職員" WHERE '.
			 '"Superseded" IS NULL AND "患者" = ' . $pid);
		$curr = mx_db_fetch_single($db, $stmt);
		$rid = $curr['ObjectID'];

		$v = array($rid, $eid, 1);
		$stmt = ('INSERT INTO "患者担当職員データ" (' .
			 '"患者担当職員", "職員", "担当役割") VALUES (' .
			 implode(', ', $v) . ')');
		if (!pg_query($db, $stmt)) {
			$this->err("ユーザ登録できません(5)");
			return 'failure';
		}
*/
		$cookie = mx_random_cookie(24, $d['email']);

		$v = array($eid,
			   $pid,
			   mx_db_sql_quote($d['handle']),
			   mx_db_sql_quote($d['email']),
			   mx_db_sql_quote($d['anonymous']),
			   mx_db_sql_quote($cookie));
//print_r($v);


		$stmt = ('INSERT INTO mykarte_users (' .
			 'mx_employee, mx_patient, handle, ' .
			 'email, anonymous, confirm_cookie' .
			 ') VALUES (' .
			 implode(', ', $v) . ')');

		if (!pg_query($db, $stmt)) {
			$this->err("スクリーン名がすでに使われています(2)");
			return 'failure';
		}



		if (! pg_query($db, 'commit')) {
			$this->err(pg_last_error($db));
			return 'failure';
		}
		$this->confirm = array('email' => $d['email'],
				       'eid' => $eid,
				       'handle' => $d['handle'],
				       'cookie' => $cookie);

		return 'ok';
	}

	// this is inside "begin"
	function try_commit(&$db) {
		$this->change_nature = 'create';
		return $this->create_one($db, $this->data);
	}

	function _validate($force=NULL) {
		$status = simple_object_edit::_validate($force);
		if (!email_valid($this->data['email'])) {
			$this->err("メールアドレスが不正です\n");
			$status = 'bad';
		}
		return $status;
	}

	function send_confirm_mail() {
		global $_mx_site_url;
//	$_mx_site_url='medex.from-mn.com/';
$_mx_site_url='www.mykarte.us/';
		$email = $this->confirm['email'];
		$eid = $this->confirm['eid'];
		$cookie = $this->confirm['cookie'];

		$target =$_mx_site_url."mykarte/confirm.php";
		$subject = 'MyKARTE registration';
		
	$aa="$target/$eid/$cookie";

//mx_insert_url($eid,$aa);

$bb="Please use this code for registration. ".$eid." \n\n and use the following URL for confirm your ID and Password.\n\n";

//print "<br>".$bb."</br>";
//print "<br>".$aa."emailaddr=".$email."</br>";

//for amazon site 07012016
mx_send_amzmail($email,$aa);
print "<br>"."send mail to you"."</br>";
//		mx_send_mail($email, $subject, $msg);
	}
}

?>
