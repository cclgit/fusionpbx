<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2008-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Lewis Hallam <lewishallam80@gmail.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('call_flow_add') || permission_exists('call_flow_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$call_flow_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_POST) > 0) {
		//set the variables from the http values
			$call_flow_name = check_str($_POST["call_flow_name"]);
			$call_flow_extension = check_str($_POST["call_flow_extension"]);
			$call_flow_feature_code = check_str($_POST["call_flow_feature_code"]);
			$call_flow_context = check_str($_POST["call_flow_context"]);
			$call_flow_status = check_str($_POST["call_flow_status"]);
			$call_flow_pin_number = check_str($_POST["call_flow_pin_number"]);
			$call_flow_label = check_str($_POST["call_flow_label"]);
			$call_flow_sound = check_str($_POST["call_flow_sound"]);
			$call_flow_destination = check_str($_POST["call_flow_destination"]);
			$call_flow_alternate_label = check_str($_POST["call_flow_alternate_label"]);
			$call_flow_alternate_sound = check_str($_POST["call_flow_alternate_sound"]);
			$call_flow_alternate_destination = check_str($_POST["call_flow_alternate_destination"]);
			$call_flow_description = check_str($_POST["call_flow_description"]);
			$dialplan_uuid = check_str($_POST["dialplan_uuid"]);

		//seperate the action and the param
			$destination_array = explode(":", $call_flow_destination);
			$call_flow_app = array_shift($destination_array);
			$call_flow_data = join(':', $destination_array);

		//seperate the action and the param call_flow_alternate_app
			$alternate_destination_array = explode(":", $call_flow_alternate_destination);
			$call_flow_alternate_app = array_shift($alternate_destination_array);
			$call_flow_alternate_data = join(':', $alternate_destination_array);

		//set the context for users that are not in the superadmin group
			if (!if_group("superadmin")) {
				$call_flow_context = $_SESSION['domain_name'];
			}

	}

if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$call_flow_uuid = check_str($_POST["call_flow_uuid"]);
	}

	//check for all required data
		if (strlen($call_flow_name) == 0) { $msg .= $text['message-required'].$text['label-name']."<br>\n"; }
		if (strlen($call_flow_extension) == 0) { $msg .= $text['message-required'].$text['label-extension']."<br>\n"; }
		//if (strlen($call_flow_feature_code) == 0) { $msg .= $text['message-required'].$text['label-feature_code']."<br>\n"; }
		if (strlen($call_flow_context) == 0) { $msg .= $text['message-required'].$text['label-context']."<br>\n"; }
		//if (strlen($call_flow_status) == 0) { $msg .= $text['message-required'].$text['label-status']."<br>\n"; }
		//if (strlen($call_flow_pin_number) == 0) { $msg .= $text['message-required'].$text['label-pin_number']."<br>\n"; }
		//if (strlen($call_flow_status) == 0) { $msg .= $text['message-required'].$text['label-status']."<br>\n"; }
		//if (strlen($call_flow_label) == 0) { $msg .= $text['message-required'].$text['label-destination_label']."<br>\n"; }
		//if (strlen($call_flow_app) == 0) { $msg .= $text['message-required'].$text['label-destination']."<br>\n"; }
		//if (strlen($call_flow_data) == 0) { $msg .= $text['message-required'].$text['label-destination']."<br>\n"; }
		//if (strlen($call_flow_alternate_label) == 0) { $msg .= $text['message-required'].$text['label-alternate_label']."<br>\n"; }
		//if (strlen($call_flow_alternate_app) == 0) { $msg .= $text['message-required'].$text['label-alternate_destination']."<br>\n"; }
		//if (strlen($call_flow_alternate_data) == 0) { $msg .= $text['message-required'].$text['label-alternate_destination']."<br>\n"; }
		//if (strlen($call_flow_description) == 0) { $msg .= $text['message-required'].$text['label-description']."<br>\n"; }
		if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
			require_once "resources/header.php";
			require_once "resources/persist_form_var.php";
			echo "<div align='center'>\n";
			echo "<table><tr><td>\n";
			echo $msg."<br />";
			echo "</td></tr></table>\n";
			persistformvar($_POST);
			echo "</div>\n";
			require_once "resources/footer.php";
			return;
		}

	//add or update the database
		if ($_POST["persistformvar"] != "true") {
			if ($action == "add" && permission_exists('call_flow_add')) {
				//prepare the uuids
					$call_flow_uuid = uuid();
					$dialplan_uuid = uuid();
				//add the call flow
					$sql = "insert into v_call_flows ";
					$sql .= "(";
					$sql .= "domain_uuid, ";
					$sql .= "call_flow_uuid, ";
					$sql .= "dialplan_uuid, ";
					$sql .= "call_flow_name, ";
					$sql .= "call_flow_extension, ";
					$sql .= "call_flow_feature_code, ";
					$sql .= "call_flow_context, ";
					$sql .= "call_flow_status, ";
					$sql .= "call_flow_pin_number, ";
					$sql .= "call_flow_label, ";
					$sql .= "call_flow_sound, ";
					$sql .= "call_flow_app, ";
					$sql .= "call_flow_data, ";
					$sql .= "call_flow_alternate_label, ";
					$sql .= "call_flow_alternate_sound, ";
					$sql .= "call_flow_alternate_app, ";
					$sql .= "call_flow_alternate_data, ";
					$sql .= "call_flow_description ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'$domain_uuid', ";
					$sql .= "'".$call_flow_uuid."', ";
					$sql .= "'".$dialplan_uuid."', ";
					$sql .= "'$call_flow_name', ";
					$sql .= "'$call_flow_extension', ";
					$sql .= "'$call_flow_feature_code', ";
					$sql .= "'$call_flow_context', ";
					$sql .= "'$call_flow_status', ";
					$sql .= "'$call_flow_pin_number', ";
					$sql .= "'$call_flow_label', ";
					$sql .= "'$call_flow_sound', ";
					$sql .= "'$call_flow_app', ";
					$sql .= "'$call_flow_data', ";
					$sql .= "'$call_flow_alternate_label', ";
					$sql .= "'$call_flow_alternate_sound', ";
					$sql .= "'$call_flow_alternate_app', ";
					$sql .= "'$call_flow_alternate_data', ";
					$sql .= "'$call_flow_description' ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);
			} //if ($action == "add")

			if ($action == "update" && permission_exists('call_flow_edit')) {
				//prepare the uuids
					if (strlen($dialplan_uuid) == 0) {
						$dialplan_uuid = uuid();
					}
				//add the call flow
					$sql = "update v_call_flows set ";
					$sql .= "dialplan_uuid = '$dialplan_uuid', ";
					$sql .= "call_flow_name = '$call_flow_name', ";
					$sql .= "call_flow_extension = '$call_flow_extension', ";
					$sql .= "call_flow_feature_code = '$call_flow_feature_code', ";
					$sql .= "call_flow_context = '$call_flow_context', ";
					$sql .= "call_flow_status = '$call_flow_status', ";
					$sql .= "call_flow_pin_number = '$call_flow_pin_number', ";
					$sql .= "call_flow_label = '$call_flow_label', ";
					$sql .= "call_flow_sound = '$call_flow_sound', ";
					$sql .= "call_flow_app = '$call_flow_app', ";
					$sql .= "call_flow_data = '$call_flow_data', ";
					$sql .= "call_flow_alternate_label = '$call_flow_alternate_label', ";
					$sql .= "call_flow_alternate_sound = '$call_flow_alternate_sound', ";
					$sql .= "call_flow_alternate_app = '$call_flow_alternate_app', ";
					$sql .= "call_flow_alternate_data = '$call_flow_alternate_data', ";
					$sql .= "call_flow_description = '$call_flow_description' ";
					$sql .= "where domain_uuid = '$domain_uuid' ";
					$sql .= "and call_flow_uuid = '$call_flow_uuid'";
					$db->exec(check_sql($sql));
					unset($sql);
			} //if ($action == "update")

			if ($action == "add" || $action == "update") {

				//delete the dialplan
					$sql = "delete from v_dialplans ";
					$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
					$sql .= "and dialplan_uuid = '".$dialplan_uuid."' ";
					$db->query(check_sql($sql));

				//delete the dialplan details
					$sql = "delete from v_dialplan_details ";
					$sql .= "where domain_uuid = '$domain_uuid' ";
					$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
					$db->query($sql);
					unset($sql);

				//add the dialplan entry
					$dialplan_name = $call_flow_name;
					$dialplan_order ='333';
					$dialplan_context = $call_flow_context;
					$dialplan_enabled = 'true';
					$dialplan_description = $call_flow_description;
					$app_uuid = 'b1b70f85-6b42-429b-8c5a-60c8b02b7d14';
					dialplan_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_name, $dialplan_order, $dialplan_context, $dialplan_enabled, $dialplan_description, $app_uuid);

					//<condition destination_number="300" break="on-true"/>
					$dialplan = new dialplan;
					$dialplan->domain_uuid = $domain_uuid;
					$dialplan->dialplan_uuid = $dialplan_uuid;
					$dialplan->dialplan_detail_tag = 'condition'; //condition, action, antiaction
					$dialplan->dialplan_detail_type = 'destination_number';
					$dialplan->dialplan_detail_data = '^'.str_replace('+', '\+', str_replace('*', '\*', $call_flow_feature_code)).'$';
					$dialplan->dialplan_detail_break = 'on-true';
					//$dialplan->dialplan_detail_inline = '';
					$dialplan->dialplan_detail_group = '1';
					$dialplan->dialplan_detail_order = '000';
					$dialplan->dialplan_detail_add();
					unset($dialplan);

					//<action application="set" data="call_flow_uuid="/>
					$dialplan = new dialplan;
					$dialplan->domain_uuid = $domain_uuid;
					$dialplan->dialplan_uuid = $dialplan_uuid;
					$dialplan->dialplan_detail_tag = 'action'; //condition, action, antiaction
					$dialplan->dialplan_detail_type = 'set';
					$dialplan->dialplan_detail_data = 'call_flow_uuid='.$call_flow_uuid;
					//$dialplan->dialplan_detail_break = '';
					//$dialplan->dialplan_detail_inline = '';
					$dialplan->dialplan_detail_group = '1';
					$dialplan->dialplan_detail_order = '010';
					$dialplan->dialplan_detail_add();
					unset($dialplan);

					//<action application="set" data="feature_code=true"/>
					$dialplan = new dialplan;
					$dialplan->domain_uuid = $domain_uuid;
					$dialplan->dialplan_uuid = $dialplan_uuid;
					$dialplan->dialplan_detail_tag = 'action'; //condition, action, antiaction
					$dialplan->dialplan_detail_type = 'set';
					$dialplan->dialplan_detail_data = 'feature_code=true';
					//$dialplan->dialplan_detail_break = '';
					//$dialplan->dialplan_detail_inline = '';
					$dialplan->dialplan_detail_group = '1';
					$dialplan->dialplan_detail_order = '020';
					$dialplan->dialplan_detail_add();
					unset($dialplan);

					//<action application="lua" data="call_flow.lua"/>
					$dialplan = new dialplan;
					$dialplan->domain_uuid = $domain_uuid;
					$dialplan->dialplan_uuid = $dialplan_uuid;
					$dialplan->dialplan_detail_tag = 'action'; //condition, action, antiaction
					$dialplan->dialplan_detail_type = 'lua';
					$dialplan->dialplan_detail_data = 'call_flow.lua';
					//$dialplan->dialplan_detail_break = '';
					//$dialplan->dialplan_detail_inline = '';
					$dialplan->dialplan_detail_group = '1';
					$dialplan->dialplan_detail_order = '030';
					$dialplan->dialplan_detail_add();
					unset($dialplan);

				//dialplan group 2
					//<condition destination_number="301"/>
					$dialplan = new dialplan;
					$dialplan->domain_uuid = $domain_uuid;
					$dialplan->dialplan_uuid = $dialplan_uuid;
					$dialplan->dialplan_detail_tag = 'condition'; //condition, action, antiaction
					$dialplan->dialplan_detail_type = 'destination_number';
					$dialplan->dialplan_detail_data = '^'.str_replace('+', '\+', str_replace('*', '\*', $call_flow_extension)).'$';
					//$dialplan->dialplan_detail_break = '';
					//$dialplan->dialplan_detail_inline = '';
					$dialplan->dialplan_detail_group = '2';
					$dialplan->dialplan_detail_order = '000';
					$dialplan->dialplan_detail_add();
					unset($dialplan);

					//<action application="set" data="call_flow_uuid="/>
					$dialplan = new dialplan;
					$dialplan->domain_uuid = $domain_uuid;
					$dialplan->dialplan_uuid = $dialplan_uuid;
					$dialplan->dialplan_detail_tag = 'action'; //condition, action, antiaction
					$dialplan->dialplan_detail_type = 'set';
					$dialplan->dialplan_detail_data = 'call_flow_uuid='.$call_flow_uuid;
					//$dialplan->dialplan_detail_break = '';
					//$dialplan->dialplan_detail_inline = '';
					$dialplan->dialplan_detail_group = '2';
					$dialplan->dialplan_detail_order = '010';
					$dialplan->dialplan_detail_add();
					unset($dialplan);

					//<action application="set" data="ringback=${us-ring}"/>
					//$dialplan = new dialplan;
					//$dialplan->domain_uuid = $domain_uuid;
					//$dialplan->dialplan_uuid = $dialplan_uuid;
					//$dialplan->dialplan_detail_tag = 'action'; //condition, action, antiaction
					//$dialplan->dialplan_detail_type = 'set';
					//$dialplan->dialplan_detail_data = 'ringback=${us-ring}';
					//$dialplan->dialplan_detail_break = '';
					//$dialplan->dialplan_detail_inline = '';
					//$dialplan->dialplan_detail_group = '2';
					//$dialplan->dialplan_detail_order = '020';
					//$dialplan->dialplan_detail_add();
					//unset($dialplan);

					//<action application="lua" data="call_flow.lua"/>
					$dialplan = new dialplan;
					$dialplan->domain_uuid = $domain_uuid;
					$dialplan->dialplan_uuid = $dialplan_uuid;
					$dialplan->dialplan_detail_tag = 'action'; //condition, action, antiaction
					$dialplan->dialplan_detail_type = 'lua';
					//$dialplan->dialplan_detail_data = $call_flow_extension . ' LUA call_flow.lua';
					$dialplan->dialplan_detail_data = 'call_flow.lua';
					//$dialplan->dialplan_detail_break = '';
					//$dialplan->dialplan_detail_inline = '';
					$dialplan->dialplan_detail_group = '2';
					$dialplan->dialplan_detail_order = '030';
					$dialplan->dialplan_detail_add();
					unset($dialplan);

				//save the xml
					save_dialplan_xml();

				//apply settings reminder
					$_SESSION["reload_xml"] = true;

				//clear the cache
					$cache = new cache;
					$cache->delete("dialplan:".$call_flow_context);

				//set the message
					if ($action == "add") {
						$_SESSION["message"] = $text['message-add'];
					}
					if ($action == "update") {
						$_SESSION["message"] = $text['message-update'];
					}

				//redirect the browser
					header("Location: call_flows.php");
					return;
			}
		} //if ($_POST["persistformvar"] != "true")
} //(count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0)

//initialize the destinations object
	$destination = new destinations;

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$call_flow_uuid = check_str($_GET["id"]);
		$sql = "select * from v_call_flows ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and call_flow_uuid = '$call_flow_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll();
		foreach ($result as &$row) {
			//set the php variables
				$call_flow_name = $row["call_flow_name"];
				$call_flow_extension = $row["call_flow_extension"];
				$call_flow_feature_code = $row["call_flow_feature_code"];
				$call_flow_context = $row["call_flow_context"];
				$call_flow_status = $row["call_flow_status"];
				$call_flow_label = $row["call_flow_label"];
				$call_flow_sound = $row["call_flow_sound"];
				$call_flow_app = $row["call_flow_app"];
				$call_flow_pin_number = $row["call_flow_pin_number"];
				$call_flow_data = $row["call_flow_data"];
				$call_flow_alternate_label = $row["call_flow_alternate_label"];
				$call_flow_alternate_sound = $row["call_flow_alternate_sound"];
				$call_flow_alternate_app = $row["call_flow_alternate_app"];
				$call_flow_alternate_data = $row["call_flow_alternate_data"];
				$call_flow_description = $row["call_flow_description"];
				$dialplan_uuid = $row["dialplan_uuid"];

			//if superadmin show both the app and data
				if (if_group("superadmin")) {
					$destination_label = $call_flow_app.':'.$call_flow_data;
				}
				else {
					$destination_label = $call_flow_data;
				}

			//if superadmin show both the app and data
				if (if_group("superadmin")) {
					$alternate_destination_label = $call_flow_alternate_app.':'.$call_flow_alternate_data;
				}
				else {
					$alternate_destination_label = $call_flow_alternate_data;
				}
		}
		unset ($prep_statement);
	}

	//set the context for users that are not in the superadmin group
		if (strlen($call_flow_context) == 0) {
			$call_flow_context = $_SESSION['domain_name'];
		}

//show the header
	require_once "resources/header.php";
	if ($action == "update") {
		$document['title'] = $text['title-call_flow-edit'];
	}
	if ($action == "add") {
		$document['title'] = $text['title-call_flow-add'];
	}

//get the recordings
	$sql = "select recording_name, recording_filename from v_recordings ";
	$sql .= "where domain_uuid = '".$_SESSION["domain_uuid"]."' ";
	$sql .= "order by recording_name asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$recordings = $prep_statement->fetchAll(PDO::FETCH_ASSOC);

	if (if_group("superadmin")) {
		echo "<script>\n";
		echo "var Objs;\n";
		echo "\n";
		echo "function changeToInput(obj){\n";
		echo "	tb=document.createElement('INPUT');\n";
		echo "	tb.type='text';\n";
		echo "	tb.name=obj.name;\n";
		echo "	tb.setAttribute('class', 'formfld');\n";
		//echo "	tb.setAttribute('style', 'width: 380px;');\n";
		echo "	tb.value=obj.options[obj.selectedIndex].value;\n";
		echo "	tbb=document.createElement('INPUT');\n";
		echo "	tbb.setAttribute('class', 'btn');\n";
		echo "	tbb.setAttribute('style', 'margin-left: 4px;');\n";
		echo "	tbb.type='button';\n";
		echo "	tbb.value=$('<div />').html('&#9665;').text();\n";
		echo "	tbb.objs=[obj,tb,tbb];\n";
		echo "	tbb.onclick=function(){ Replace(this.objs); }\n";
		echo "	obj.parentNode.insertBefore(tb,obj);\n";
		echo "	obj.parentNode.insertBefore(tbb,obj);\n";
		echo "	obj.parentNode.removeChild(obj);\n";
		echo "}\n";
		echo "\n";
		echo "function Replace(obj){\n";
		echo "	obj[2].parentNode.insertBefore(obj[0],obj[2]);\n";
		echo "	obj[0].parentNode.removeChild(obj[1]);\n";
		echo "	obj[0].parentNode.removeChild(obj[2]);\n";
		echo "}\n";
		echo "</script>\n";
		echo "\n";
	}

	function sound_select_list($var, $name, $description_name, $load_sound=false) {
		global $text, $recordings, $db;

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-' . $description_name]."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";

		echo "<select name='$name' class='formfld' ".((if_group("superadmin")) ? "onchange='changeToInput(this);'" : null).">\n";
		echo "	<option value=''></option>\n";
		//misc optgroup
			if (if_group("superadmin")) {
				echo "<optgroup label=".$text['miscellaneous'].">\n";
				echo "	<option value='say:'>say:</option>\n";
				echo "	<option value='tone_stream:'>tone_stream:</option>\n";
				echo "</optgroup>\n";
			}
		//recordings
			$tmp_selected = false;
			if (count($recordings) > 0) {
				echo "<optgroup label=".$text['recordings'].">\n";
				foreach ($recordings as &$row) {
					$recording_name = $row["recording_name"];
					$recording_filename = $row["recording_filename"];
					if ($var == $_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$recording_filename && strlen($var) > 0) {
						$tmp_selected = true;
						echo "	<option value='".$_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$recording_filename."' selected='selected'>".$recording_name."</option>\n";
					}
					else if ($var == $recording_filename && strlen($var) > 0) {
						$tmp_selected = true;
						echo "	<option value='".$recording_filename."' selected='selected'>".$recording_name."</option>\n";
					}
					else {
						echo "	<option value='".$recording_filename."'>".$recording_name."</option>\n";
					}
				}
				echo "</optgroup>\n";
			}
		//phrases
			$sql = "select * from v_phrases where domain_uuid = '".$domain_uuid."' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			if (count($result) > 0) {
				echo "<optgroup label='Phrases'>\n";
				foreach ($result as &$row) {
					if ($var == "phrase:".$row["phrase_uuid"]) {
						$tmp_selected = true;
						echo "	<option value='phrase:".$row["phrase_uuid"]."' selected='selected'>".$row["phrase_name"]."</option>\n";
					}
					else {
						echo "	<option value='phrase:".$row["phrase_uuid"]."'>".$row["phrase_name"]."</option>\n";
					}
				}
				unset ($prep_statement);
				echo "</optgroup>\n";
			}
		//sounds
			if ($load_sound) {
				$file = new file;
				$sound_files = $file->sounds();
				if (is_array($sound_files)) {
					echo "<optgroup label=".$text["sounds"].">\n";
					foreach ($sound_files as $value) {
						if (strlen($value) > 0) {
							if (substr($var, 0, 71) == "\$\${sounds_dir}/\${default_language}/\${default_dialect}/\${default_voice}/") {
								$var = substr($var, 71);
							}
							if ($var == $value) {
								$tmp_selected = true;
								echo "	<option value='$value' selected='selected'>$value</option>\n";
							}
							else {
								echo "	<option value='$value'>$value</option>\n";
							}
						}
					}
					echo "</optgroup>\n";
				}
			}
		//select
			if (if_group("superadmin")) {
				if (!$tmp_selected && strlen($var) > 0) {
					echo "<optgroup label='Selected'>\n";
					if (file_exists($_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$var)) {
						echo "	<option value='".$_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$var."' selected='selected'>".$var."</option>\n";
					}
					else if (substr($var, -3) == "wav" || substr($var, -3) == "mp3") {
						echo "	<option value='".$var."' selected='selected'>".$var."</option>\n";
					}
					else {
						echo "	<option value='".$var."' selected='selected'>".$var."</option>\n";
					}
					echo "</optgroup>\n";
				}
				unset($tmp_selected);
			}
		echo "	</select>\n";
		echo "	<br />\n";
		echo $text['description-' . $description_name]."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

//show the content
	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap'><b>";
	if ($action == "update") {
		echo $text['header-call_flow-edit'];
	}
	if ($action == "add") {
		echo $text['header-call_flow-add'];
	}
	echo "</b></td>\n";
	echo "<td width='70%' align='right'>";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='call_flows.php'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_flow_name' maxlength='255' value=\"$call_flow_name\">\n";
	echo "<br />\n";
	echo $text['description-name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-extension']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_flow_extension' maxlength='255' value=\"$call_flow_extension\">\n";
	echo "<br />\n";
	echo $text['description-extension']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-feature_code']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_flow_feature_code' maxlength='255' value=\"$call_flow_feature_code\">\n";
	echo "<br />\n";
	echo $text['description-feature_code']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-context']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_flow_context' maxlength='255' value=\"$call_flow_context\">\n";
	echo "<br />\n";
	echo $text['description-context']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-status']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='call_flow_status'>\n";
	echo "	<option value=''></option>\n";
	if ($call_flow_status == "true") {
		if (strlen($call_flow_label) > 0) {
			echo "	<option value='true' selected='selected'>$call_flow_label</option>\n";
		}
		else {
			echo "	<option value='true' selected='selected'>".$text['label-true']."</option>\n";
		}
	}
	else {
		if (strlen($call_flow_label) > 0) {
			echo "	<option value='true'>$call_flow_label</option>\n";
		}
		else {
			echo "	<option value='true'>".$text['label-true']."</option>\n";
		}
	}
	if ($call_flow_status == "false") {
		if (strlen($call_flow_alternate_label) > 0) {
			echo "	<option value='false' selected='selected'>$call_flow_alternate_label</option>\n";
		}
		else {
			echo "	<option value='false' selected='selected'>".$text['label-false']."</option>\n";
		}
	}
	else {
		if (strlen($call_flow_alternate_label) > 0) {
			echo "	<option value='false'>$call_flow_alternate_label</option>\n";
		}
		else {
			echo "	<option value='false'>".$text['label-false']."</option>\n";
		}
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-status']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-pin_number']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_flow_pin_number' maxlength='255' value=\"$call_flow_pin_number\">\n";
	echo "<br />\n";
	echo $text['description-pin_number']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-destination_label']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_flow_label' maxlength='255' value=\"$call_flow_label\">\n";
	echo "<br />\n";
	echo $text['description-destination_label']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	sound_select_list($call_flow_sound, 'call_flow_sound', 'sound', true);

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-destination']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$select_value = '';
	//set the selected value
	if (strlen($call_flow_app.$call_flow_data) > 0) {
		$select_value = $call_flow_app.':'.$call_flow_data;
	}
	//show the destination list
	echo $destination->select('dialplan', 'call_flow_destination', $select_value);
	unset($select_value);
	echo "<br />\n";
	echo $text['description-destination']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-alternate_label']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_flow_alternate_label' maxlength='255' value=\"$call_flow_alternate_label\">\n";
	echo "<br />\n";
	echo $text['description-alternate_label']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	sound_select_list($call_flow_alternate_sound, 'call_flow_alternate_sound', 'alternate_sound', true);

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-alternate_destination']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$select_value = '';
	if (strlen($call_flow_alternate_app.$call_flow_alternate_data) > 0) {
		$select_value = $call_flow_alternate_app.':'.$call_flow_alternate_data;
	}
	echo $destination->select('dialplan', 'call_flow_alternate_destination', $select_value);
	unset($select_value);
	echo "<br />\n";
	echo $text['description-alternate_destination']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_flow_description' maxlength='255' value=\"$call_flow_description\">\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "		<input type='hidden' name='call_flow_uuid' value='$call_flow_uuid'>\n";
		echo "		<input type='hidden' name='dialplan_uuid' value='$dialplan_uuid'>\n";
	}
	echo "			<br>";
	echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

//include the footer
	require_once "resources/footer.php";
?>
