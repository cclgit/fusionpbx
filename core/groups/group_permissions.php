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
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permisions
	if (permission_exists('group_permissions') || if_group("superadmin")) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//include the header
	$document['title'] = $text['title-group_permissions'];
	require_once "resources/header.php";

//include paging
	require_once "resources/paging.php";

//get the list of installed apps from the core and mod directories
	$config_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/app_config.php");
	$x=0;
	foreach ($config_list as &$config_path) {
		include($config_path);
		$x++;
	}

//if there are no permissions listed in v_group_permissions then set the default permissions
	$sql = "select count(*) from v_group_permissions ";
	$database = new database;
	$group_permission_count = $database->select($sql, null, 'column');
	unset($sql);

	if ($group_permission_count == 0) {
		//no permissions found add the defaults
		foreach($apps as $app) {
			foreach ($app['permissions'] as $row) {
				foreach ($row['groups'] as $index => $group) {
					//add the record
					$array['group_permissions'][$index]['group_permission_uuid'] = uuid();
					$array['group_permissions'][$index]['permission_name'] = $row['name'];
					$array['group_permissions'][$index]['group_name'] = $group;
				}
				if (is_array($array) && sizeof($array) != 0) {
					$database = new database;
					$database->app_name = 'groups';
					$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
					$database->save($array);
					unset($array);
				}
			}
		}
	}

//get the group uuid, lookup domain uuid (if any) and name
	$group_uuid = $_REQUEST['group_uuid'];
	$sql = "select domain_uuid, group_name from v_groups ";
	$sql .= "where group_uuid = :group_uuid ";
	$parameters['group_uuid'] = $group_uuid;
	$database = new database;
	$row = $database->select($sql, $parameters, 'row');
	if (is_array($row) && sizeof($row) != 0) {
		$domain_uuid = $row["domain_uuid"];
		$group_name = $row["group_name"];
	}
	unset($sql, $parameters, $row);

//get the permissions assigned to this group
	$sql = "select * from v_group_permissions ";
	$sql .= "where group_name = :group_name ";
	if (is_uuid($domain_uuid)) {
		$sql .= "and domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	else {
		$sql .= "and domain_uuid is null ";
	}
	$parameters['group_name'] = $group_name;
	$database = new database;
	$result = $database->select($sql, $parameters, 'all');
	if (is_array($result) && sizeof($result) != 0) {
		foreach ($result as &$row) {
			$permissions_db[$row["permission_name"]] = "true";
		}
	}
	unset($sql, $parameters, $result, $row);

//show the db checklist
	//echo "<pre>";
	//print_r($permissions_db);
	//echo "</pre>";

//list all the permissions in the database
	foreach($apps as $app) {
		if (isset($app['permissions'])) foreach ($app['permissions'] as $row) {
			if ($permissions_db[$row['name']] == "true") {
				$permissions_db_checklist[$row['name']] = "true";
			}
			else {
				$permissions_db_checklist[$row['name']] = "false";
			}
		}
	}

//show the db checklist
	//echo "<pre>";
	//print_r($permissions_db_checklist);
	//echo "</pre>";

//process the http post
	if (count($_POST)>0) {

		foreach($_POST['permissions_form'] as $permission) {
			$permissions_form[$permission] = "true";
		}

		//list all the permissions
			foreach($apps as $app) {
				foreach ($app['permissions'] as $row) {
					if ($permissions_form[$row['name']] == "true") {
						$permissions_form_checklist[$row['name']] = "true";
					}
					else {
						$permissions_form_checklist[$row['name']] = "false";
					}
				}
			}
		//show the form db checklist
			//echo "<pre>";
			//print_r($permissions_form_checklist);
			//echo "</pre>";

		//list all the permissions
			foreach($apps as $app) {
				foreach ($app['permissions'] as $row) {
					$permission = $row['name'];
					if ($permissions_db_checklist[$permission] == "true" && $permissions_form_checklist[$permission] == "true") {
						//matched do nothing
					}
					if ($permissions_db_checklist[$permission] == "false" && $permissions_form_checklist[$permission] == "false") {
						//matched do nothing
					}
					if ($permissions_db_checklist[$permission] == "true" && $permissions_form_checklist[$permission] == "false") {
						//delete the record
							$array['group_permissions'][0]['group_name'] = $group_name;
							$array['group_permissions'][0]['permission_name'] = $permission;
							$database = new database;
							$database->app_name = 'groups';
							$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
							$database->delete($array);
							unset($array);

						foreach($apps as $app) {
							foreach ($app['permissions'] as $row) {
								if ($row['name'] == $permission) {

									$array['menu_item_groups'][0]['menu_item_uuid'] = $row['menu']['uuid'];
									$array['menu_item_groups'][0]['group_name'] = $group_name;
									$array['menu_item_groups'][0]['menu_uuid'] = 'b4750c3f-2a86-b00d-b7d0-345c14eca286';

									$p = new permissions;
									$p->add('menu_item_group_delete', 'temp');

									$database = new database;
									$database->app_name = 'groups';
									$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
									$database->delete($array);
									unset($array);

									$p->delete('menu_item_group_delete', 'temp');

									$sql = "select menu_item_parent_uuid from v_menu_items ";
									$sql .= "where menu_item_uuid = :menu_item_uuid ";
									$sql .= "and menu_uuid = 'b4750c3f-2a86-b00d-b7d0-345c14eca286' ";
									$parameters['menu_item_uuid'] = $row['menu']['uuid'];
									$database = new database;
									$menu_item_parent_uuid = $database->select($sql, $parameters, 'column');
									unset($sql, $parameters);

									$sql = "select count(*) from v_menu_items as i, v_menu_item_groups as g  ";
									$sql .= "where i.menu_item_uuid = g.menu_item_uuid ";
									$sql .= "and i.menu_uuid = 'b4750c3f-2a86-b00d-b7d0-345c14eca286' ";
									$sql .= "and i.menu_item_parent_uuid = :menu_item_parent_uuid ";
									$sql .= "and g.group_name = :group_name ";
									$parameters['menu_item_parent_uuid'] = $menu_item_parent_uuid;
									$parameters['group_name'] = $group_name;
									$database = new database;
									$result_count = $database->select($sql, $parameters, 'column');

									if ($result_count == 0) {
										$array['menu_item_groups'][0]['menu_item_uuid'] = $menu_item_parent_uuid;
										$array['menu_item_groups'][0]['group_name'] = $group_name;
										$array['menu_item_groups'][0]['menu_uuid'] = 'b4750c3f-2a86-b00d-b7d0-345c14eca286';

										$p = new permissions;
										$p->add('menu_item_group_delete', 'temp');

										$database = new database;
										$database->app_name = 'groups';
										$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
										$database->delete($array);
										unset($array);

										$p->delete('menu_item_group_delete', 'temp');
									}
									unset($sql, $parameters, $result_count);
								}
							}
						}
						//set the permission to false in the permissions_db_checklist
							$permissions_db_checklist[$permission] = "false";
					}
					if ($permissions_db_checklist[$permission] == "false" && $permissions_form_checklist[$permission] == "true") {
						//add the record
							$array['group_permissions'][0]['group_permission_uuid'] = uuid();
							if (is_uuid($domain_uuid)) {
								$array['group_permissions'][0]['domain_uuid'] = $domain_uuid;
							}
							$array['group_permissions'][0]['permission_name'] = $permission;
							$array['group_permissions'][0]['group_name'] = $group_name;
							$database = new database;
							$database->app_name = 'groups';
							$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
							$database->save($array);
							unset($array);

						foreach($apps as $app) {
							foreach ($app['permissions'] as $row) {
								if ($row['name'] == $permission) {

									$array['menu_item_groups'][0]['menu_uuid'] = 'b4750c3f-2a86-b00d-b7d0-345c14eca286';
									$array['menu_item_groups'][0]['menu_item_uuid'] = $row['menu']['uuid'];
									$array['menu_item_groups'][0]['group_name'] = $group_name;

									$p = new permissions;
									$p->add('menu_item_group_add', 'temp');

									$database = new database;
									$database->app_name = 'groups';
									$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
									$database->save($array);
									unset($array);

									$p->delete('menu_item_group_add', 'temp');

									$sql = "select menu_item_parent_uuid from v_menu_items ";
									$sql .= "where menu_item_uuid = :menu_item_uuid ";
									$sql .= "and menu_uuid = 'b4750c3f-2a86-b00d-b7d0-345c14eca286' ";
									$parameters['menu_item_uuid'] = $row['menu']['uuid'];
									$database = new database;
									$menu_item_parent_uuid = $database->select($sql, $parameters, 'column');
									unset($sql, $parameters);

									$sql = "select count(*) from v_menu_item_groups ";
									$sql .= "where menu_item_uuid = :menu_item_uuid ";
									$sql .= "and group_name = :group_name ";
									$sql .= "and menu_uuid = 'b4750c3f-2a86-b00d-b7d0-345c14eca286' ";
									$parameters['menu_item_uuid'] = $menu_item_parent_uuid;
									$parameters['group_name'] = $group_name;
									$database = new database;
									$result_count = $database->select($sql, $parameters, 'column');

									if ($result_count == 0) {
										$array['menu_item_groups'][0]['menu_uuid'] = 'b4750c3f-2a86-b00d-b7d0-345c14eca286';
										$array['menu_item_groups'][0]['menu_item_uuid'] = $menu_item_parent_uuid;
										$array['menu_item_groups'][0]['group_name'] = $group_name;

										$p = new permissions;
										$p->add('menu_item_group_add', 'temp');

										$database = new database;
										$database->app_name = 'groups';
										$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
										$database->save($array);
										unset($array);

										$p->delete('menu_item_group_add', 'temp');
									}

									unset($sql, $parameters, $result_count);
								}
							}
						}
						//set the permission to true in the permissions_db_checklist
							$permissions_db_checklist[$permission] = "true";
					}
				}
			}

		message::add($text['message-update']);
		header("Location: groups.php");
		return;
	}

//copy group javascript
	echo "<script language='javascript' type='text/javascript'>\n";
	echo "	function copy_group() {\n";
	echo "		var new_group_name;\n";
	echo "		var new_group_desc;\n";
	echo "		new_group_name = prompt('".$text['message-new_group_name']."');\n";
	echo "		if (new_group_name != null) {\n";
	echo "			new_group_desc = prompt('".$text['message-new_group_description']."');\n";
	echo "			if (new_group_desc != null) {\n";
	echo "				window.location = 'permissions_copy.php?id=".escape($group_uuid)."&new_group_name=' + new_group_name + '&new_group_desc=' + new_group_desc;\n";
	echo "			}\n";
	echo "		}\n";
	echo "	}\n";
	echo "\n";
	echo "	$( document ).ready(function() {\n";
	echo "		$('#group_permission_search').trigger('focus');\n";
	echo "	});\n";
	echo "</script>\n";

//prevent enter key submit on search field
	echo "<script language='javascript' type='text/javascript'>\n";
	echo "	$(document).ready(function() {\n";
	echo "		$('#group_permission_search').keydown(function(event){\n";
	echo "			if (event.keyCode == 13) {\n";
	echo "				event.preventDefault();\n";
	echo "				return false;\n";
	echo "			}\n";
	echo "		});\n";
	echo "	});\n";
	echo "</script>\n";

//show the content
	echo "<form method='post' name='frm' action=''>\n";
	echo "<input type='hidden' name='domain_uuid' value='".escape($domain_uuid)."'>\n";
	echo "<table cellpadding='0' cellspacing='0' width='100%' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align=\"left\" nowrap=\"nowrap\" valign='top'>";
	echo "			<b>".$text['header-group_permissions'].escape($group_name)."</b>";
	echo "			<br><br>";
	echo "		</td>\n";
	echo "		<td width='50%' align=\"right\" valign='top'>\n";
	echo "			<input type='button' class='btn' alt='".$text['button-back']."' onclick=\"window.location='groups.php'\" value='".$text['button-back']."'> ";
	echo "			<input type='text' id='group_permission_search' class='formfld' style='min-width: 150px; width:150px; max-width: 150px;' placeholder=\"".$text['label-search']."\" onkeyup='permission_search();'>\n";
	echo "			<input type='button' class='btn' alt='".$text['button-copy']."' onclick='copy_group();' value='".$text['button-copy']."'>";
	echo "			<input type='submit' class='btn' name='submit' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align=\"left\" colspan='2'>\n";
	echo "			".$text['description-group_permissions']."\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br /><br />\n";

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	//list all the permissions
		foreach($apps as $app_index => $app) {
			//hide apps for which there are no permissions
			if (!is_array($app['permissions']) || sizeof($app['permissions']) == 0) { continue; }

			$app_name = $app['name'];
			$description = $app['description']['en-us'];

			//used to hide apps, even if permissions don't exist
			$array_apps_unique[] = str_replace(' ','_',strtolower($app['name']));

			echo "<div id='app_".str_replace(' ','_',strtolower($app['name']))."'>";
			echo "<b>".$app_name."</b><br />\n";
			if ($description != '') { echo $description."<br />\n"; }
			echo "<br>";

			echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
			echo "	<tr>\n";
			echo "		<th style='text-align: center; padding: 0 7px;'><input type='checkbox' id='check_toggle_".$app_index."' onclick=\"check_toggle('".$app_index."', this.checked);\"></th>\n";
			echo "		<th width='30%'>".$text['label-permission_permissions']."</th>\n";
			echo "		<th width='70%'>".$text['label-permission_description']."</th>\n";
			echo "	<tr>\n";

			foreach ($app['permissions'] as $permission_index => $row) {
				$checked = ($permissions_db_checklist[$row['name']] == "true") ? "checked='checked'" : null;
				echo "<tr id='permission_".$row['name']."'>\n";
				echo "	<td valign='top' class='".$row_style[$c]."' style='text-align: center; padding: 3px 0px 0px 0px;'><input type='checkbox' name='permissions_form[]' id='perm_".$app_index."_".$permission_index."' ".$checked." value='".escape($row['name'])."'></td>\n";
				echo "	<td valign='top' nowrap='nowrap' class='".$row_style[$c]."' onclick=\"(document.getElementById('perm_".$app_index."_".$permission_index."').checked) ? document.getElementById('perm_".$app_index."_".$permission_index."').checked = false : document.getElementById('perm_".$app_index."_".$permission_index."').checked = true;\">".escape($row['name'])."</td>\n";
				echo "	<td valign='top' class='row_stylebg' onclick=\"(document.getElementById('perm_".$app_index."_".$permission_index."').checked) ? document.getElementById('perm_".$app_index."_".$permission_index."').checked = false : document.getElementById('perm_".$app_index."_".$permission_index."').checked = true;\">".escape($row['description'])."&nbsp;</td>\n";
				echo "</tr>\n";
				$c = ($c == 0) ? 1 : 0;

				//populate search/filter arrays
				$array_apps[] = str_replace(' ','_',strtolower($app['name']));
				$array_apps_original[] = $app['name'];
				$array_permissions[] = $row['name'];
				$array_descriptions[] = str_replace('"','\"',$row['description']);

				$app_permissions[$app_index][] = "perm_".$app_index."_".$permission_index;
			}

			echo "	<tr>\n";
			echo "		<td colspan='3' align='right' style='padding-top: 15px;'><input type='submit' name='submit' class='btn' value='".$text['button-save']."'></td>\n";
			echo "	</tr>\n";
			echo "</table>";
			echo "</div>\n\n";

		} //end foreach
		echo "<br>";

	echo "</form>\n";

//check or uncheck all category checkboxes
	echo "<script>\n";
	echo "function check_toggle(app_index, toggle_state) {\n";
	echo "	switch (app_index) {\n";
	foreach ($app_permissions as $app_index => $app_permission_ids) {
		echo "	case '".$app_index."':\n";
		foreach ($app_permission_ids as $app_permission_id) {
			echo "	document.getElementById('".$app_permission_id."').checked = toggle_state;\n";
		}
		echo "	break;\n";
	}
	echo "	}\n";
	echo "}\n";
	echo "</script>\n";

//setting search script
	echo "<script>\n";
	echo "	var apps_unique = new Array(\"".implode('","', $array_apps_unique)."\");\n";
	echo "	var apps = new Array(\"".implode('","', $array_apps)."\");\n";
	echo "	var apps_original = new Array(\"".implode('","', $array_apps_original)."\");\n";
	echo "	var permissions = new Array(\"".implode('","', $array_permissions)."\");\n";
	echo "	var descriptions = new Array(\"".implode('","', $array_descriptions)."\");\n";
	echo "\n";
	echo "	function permission_search() {\n";
	echo "		var criteria = $('#group_permission_search').val();\n";
	echo "		if (criteria.length >= 2) {\n";
	echo "			for (var x = 0; x < apps_unique.length; x++) {\n";
	echo "				document.getElementById('app_'+apps_unique[x]).style.display = 'none';\n";
	echo "			}\n";
	echo "			for (var x = 0; x < permissions.length; x++) {\n";
	echo "				if (\n";
	echo "					apps_original[x].toLowerCase().match(criteria.toLowerCase()) ||\n";
	echo "					permissions[x].toLowerCase().match(criteria.toLowerCase()) ||\n";
	echo "					descriptions[x].toLowerCase().match(criteria.toLowerCase())\n";
	echo "					) {\n";
	echo "					document.getElementById('app_'+apps[x]).style.display = '';\n";
	echo "					document.getElementById('permission_'+permissions[x]).style.display = '';\n";
	echo "				}\n";
	echo "				else {\n";
	echo "					document.getElementById('permission_'+permissions[x]).style.display = 'none';\n";
	echo "				}\n";
	echo "			}\n";
	echo "		}\n";
	echo "		else {\n";
	echo "			for (var x = 0; x < permissions.length; x++) {\n";
	echo "				document.getElementById('app_'+apps[x]).style.display = '';\n";
	echo "				document.getElementById('permission_'+permissions[x]).style.display = '';\n";
	echo "			}\n";
	echo "		}\n";
	echo "	}\n";
	echo "\n";
	echo "</script>\n";

//show the footer
	require_once "resources/footer.php";

?>
