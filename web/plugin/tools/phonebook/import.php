<?php
defined('_SECURE_') or die('Forbidden');
if(!valid()){forcenoaccess();};

$uid = $core_config['user']['uid'];

switch ($op) {
	case "list":
		$content .= "
			<h2>"._('Phonebook')."</h2>
			<h3>"._('Import')."</h3>
			<table class=ps_table>
				<tbody>
					<tr>
						<td>
							<form action=\"index.php?app=menu&inc=tools_phonebook&route=import&op=import\" enctype=\"multipart/form-data\" method=\"post\">
							<p>"._('Please select CSV file for phonebook entries')."</p>
							<p><input type=\"file\" size=30 name=\"fnpb\"></p>
							<p class=text-info>"._('format')." : "._('Name').", "._('Mobile').", "._('Email').", "._('Group code')."</p>
							<p><input type=\"submit\" value=\""._('Import')."\" class=\"button\"></p>
							</form>
						</td>
					</tr>
				</tbody>
			</table>
			<p>"._b('index.php?app=menu&inc=tools_phonebook&op=phonebook_list');
		if ($err = $_SESSION['error_string']) {
			echo "<div class=error_string>$err</div>";
		}
		echo $content;
		break;
	case "import":
		$fnpb = $_FILES['fnpb'];
		$fnpb_tmpname = $_FILES['fnpb']['tmp_name'];
		$content = "
			<h2>"._('Phonebook')."</h2>
			<h3>"._('Import confirmation')."</h3>
			<div class=table-responsive>
			<table class=playsms-table-list>
			<thead><tr>
				<th width=\"5%\">*</th>
				<th width=\"25%\">"._('Name')."</th>
				<th width=\"25%\">"._('Mobile')."</th>
				<th width=\"30%\">"._('Email')."</th>
				<th width=\"15%\">"._('Group code')."</th>
			</tr></thead><tbody>";
		if (file_exists($fnpb_tmpname)) {
			$fp = fopen($fnpb_tmpname, "r");
			$file_content = fread($fp, filesize($fnpb_tmpname));
			fclose($fp);
			$parse_phonebook = explode("\n", $file_content);
			$row_num = ( count($parse_phonebook) <= $phonebook_row_limit ? count($parse_phonebook) : $phonebook_row_limit );
			$session_import = 'phonebook_'._PID_;
			unset($_SESSION['tmp'][$session_import]);
			$j = 0;
			for ($i = 0; $i < $row_num; $i++) {
				if (!empty($parse_phonebook) && strlen($parse_phonebook[$i]) > 1) {
					$parse_phonebook[$i] = str_replace(";", ",", $parse_phonebook[$i]);
					$parse_param = explode(",", str_replace("\"", "", $parse_phonebook[$i]));
					$gid = phonebook_groupcode2id($uid, $parse_param[3]);
					if ($parse_param[0] && $parse_param[1] && $parse_param[3] && $gid) {
						$j++;
						$content .= "
							<tr>
							<td>$j.</td>
							<td>$parse_param[0]</td>
							<td>$parse_param[1]</td>
							<td>$parse_param[2]</td>
							<td>$parse_param[3]</td>
							</tr>";
						$k = $j - 1;
						$_SESSION['tmp'][$session_import][$k] = $parse_param;
					}
					unset($parse_param);
				}
			}
			$content .= "
				</tbody></table>
				</div>
				<p>"._('Import above phonebook entries ?')."
				<form action=\"index.php?app=menu&inc=tools_phonebook&route=import&op=import_yes\" method=\"post\">
				<input type=\"hidden\" name=\"number_of_row\" value=\"$j\">
				<input type=\"hidden\" name=\"session_import\" value=\"".$session_import."\">
				<input type=\"submit\" class=\"button\" value=\""._('Import')."\">
				</form>
				<p>"._b('index.php?app=menu&inc=tools_phonebook&route=import&op=list');
			echo $content;
		} else {
			$_SESSION['error_string'] = _('Fail to upload CSV file for phonebook');
			header("Location: index.php?app=menu&inc=tools_phonebook&route=import&op=list");
			exit();
		}
		break;
	case "import_yes":
		set_time_limit(600);
		$num = $_POST['number_of_row'];
		$session_import = $_POST['session_import'];
		$data = $_SESSION['tmp'][$session_import];
		//$i = 0;
		foreach ($data as $d) {
			$name = trim($d[0]);
			$mobile = trim($d[1]);
			$email = trim($d[2]);
			if ($group_code = trim($d[3])) {
				$gpid = phonebook_groupcode2id($uid, $group_code);
			}
			if ($name && $mobile && $gpid) {
				dba_remove(_DB_PREF_.'_toolsPhonebook', array('name' => $name, 'gpid' => $gpid, 'uid' => $uid), 'AND');
				dba_add(_DB_PREF_.'_toolsPhonebook', array('uid' => $uid, 'name' => $name, 'mobile' => $mobile, 'email' => $email, 'gpid' => $gpid));
				//$i++;
				//logger_print("no:".$i." gpid:".$gpid." uid:".$uid." name:".$name." mobile:".$mobile." email:".$email, 3, "phonebook import");
			}
			unset($gpid);
		}
		$_SESSION['error_string'] = _('Contacts has been imported');
		header("Location: index.php?app=menu&inc=tools_phonebook&route=import&op=list");
		exit();
		break;
}

?>