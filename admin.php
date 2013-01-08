<?php
  // ======================================== \

	// Package: Mihalism Multi Forum Host 
	// Version: 3.0.0
	// Copyright (c) 2007, 2008 Mihalism, Inc.
	// License: http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt GNU Public License
	// ======================================== /

	require_once "./source/includes/data.php";

	if (preg_match("/login/i", $mfhclass->input->get_vars['act']) == false) {
		if ($mfhclass->funcs->is_null($mfhclass->input->cookie_vars['mfh_admin_session']) == false) {
			$admin_session = unserialize(stripslashes(str_replace("&quot;", '"', urldecode($mfhclass->input->cookie_vars['mfh_admin_session']))));
			if ($mfhclass->db->total_rows($mfhclass->db->query("SELECT * FROM `mfh_admin_sessions` WHERE `session_id` = '{$admin_session['session_id']}' AND `admin_id` = '{$admin_session['admin_id']}' AND `ip_address` = '{$mfhclass->input->server_vars['remote_addr']}';")) !== 1) {
				$mfhclass->input->get_vars['act'] = "login";
			} else {
				$mfhclass->info->is_admin = true;
				$mfhclass->info->admin_data = $mfhclass->db->fetch_array($mfhclass->db->query("SELECT * FROM `mfh_admin_sessions` WHERE `session_id` = '{$admin_session['session_id']}' AND `admin_id` = '{$admin_session['admin_id']}' AND `ip_address` = '{$mfhclass->input->server_vars['remote_addr']}';"));
				$sql = $mfhclass->db->query("SELECT * FROM `mfh_admin_sessions`;");
				while ($row = $mfhclass->db->fetch_array($sql)) {
					if ($admin_session['admin_id'] == $row['admin_id'] && $row['session_id'] != $admin_session['session_id']) {
						$mfhclass->db->query("DELETE FROM `mfh_admin_sessions` WHERE `session_id` = '{$row['session_id']}';");
					}
				}
			}
		} else {
			$mfhclass->input->get_vars['act'] = "login";
		}
	}
	
	$mfhclass->templ->page_header = $mfhclass->templ->parse_template("admin/page_header");
	$mfhclass->templ->page_footer = $mfhclass->templ->parse_template("admin/page_footer");

	switch ($mfhclass->input->get_vars['act']) {
		case "login":
			$mfhclass->templ->output("admin/admin", "admin_login_page");
			break;
		case "login-f":
			if ($mfhclass->funcs->is_null($mfhclass->input->post_vars['username']) == true || $mfhclass->funcs->is_null($mfhclass->input->post_vars['password'] == true)) {
				$mfhclass->templ->error("Please ensure that all required fields of the form on the previous page had been filled in correctly.", true);
			} elseif ($mfhclass->db->total_rows($mfhclass->db->query("SELECT * FROM `mfh_admin_accounts` WHERE `username` = '{$mfhclass->input->post_vars['username']}' AND `password` = '".md5($mfhclass->input->post_vars['password'])."';")) !== 1) {
				$mfhclass->templ->error("Invalid username and or password.", true);
			} else {
				$session_id = md5($mfhclass->funcs->random_string(30));
				$mfhclass->info->user_data = $mfhclass->db->fetch_array($mfhclass->db->query("SELECT * FROM `mfh_admin_accounts` WHERE `username` = '{$mfhclass->input->post_vars['username']}' AND `password` = '".md5($mfhclass->input->post_vars['password'])."';"));
				$mfhclass->db->query("UPDATE `mfh_admin_accounts` SET `ip_address` = '{$mfhclass->input->server_vars['remote_addr']}' WHERE `admin_id` = '{$mfhclass->info->user_data['admin_id']}';");
				$mfhclass->db->query("INSERT INTO `mfh_admin_sessions` (session_id, session_start, admin_id, user_agent, ip_address) VALUES ('{$session_id}', '".time()."', '{$mfhclass->info->user_data['admin_id']}', '{$mfhclass->input->server_vars['http_user_agent']}', '{$mfhclass->input->server_vars['remote_addr']}');");

				setcookie("mfh_admin_session", "session_delete", (time() - 60000));	

				if (setcookie("mfh_admin_session", serialize(array("session_id" => $session_id, "admin_id" => $mfhclass->info->user_data['admin_id'])), 0, $mfhclass->info->script_path) == true) {
					$mfhclass->templ->success("You have been successfully logged into the admin control panel. <br /><br /> <a href=\"admin.php\">Admin CP Index</a>", true);
				} else {
					$mfhclass->templ->error("Log in failed. <br /><br /> Failed to set cookie <b>mfh_admin_session</b>.", true);
				}
			}
			break;
		case "logout":
			if (setcookie("mfh_admin_session", "session_delete", (time() - 60000)) == true) {
				$mfhclass->templ->success("You have been successfully logged out of the admin control panel. <br /><br /> <a href=\"admin.php?act=login\">Admin CP Login Form</a>", true);
			} else {
				$mfhclass->templ->error("Log out failed. <br /><br /> Failed to unset cookie <b>mfh_admin_session</b>.", true);
			}	
			break;
		case "site_settings":
			$sql = $mfhclass->db->query("SELECT * FROM `mfh_site_settings`;");
			while ($row = $mfhclass->db->fetch_array($sql)) {
				$setting[$row['config_key']] = $row['config_value']; // <-- load unchanged values of settings
			}

			for ($i = 1; $i <= 100; $i++) {
				$mfhclass->templ->templ_globals['get_whileloop'] = true;
				
				$mfhclass->templ->templ_vars[] = array(
					"MAX_RESULTS_SUM"      => $i,
					"MAX_RESULTS_SELECTED" => (($setting['max_results'] == $i) ? "selected=\"selected\"" : NULL),
				);
										
				$mfhclass->templ->templ_globals['max_results_forloop'] .= $mfhclass->templ->parse_template("admin/admin", "site_settings_page");

				unset($mfhclass->templ->templ_vars, $mfhclass->templ->templ_globals['get_whileloop']);
			}
			
			$mfhclass->templ->templ_vars[] = array(
				"SITE_NAME"            => $setting['site_name'],
				"EMAIL_OUT"            => $setting['email_out'],
				"DATE_FORMAT"          => $setting['date_format'],
				"blocked_access_names"  => $setting['blocked_access_names'],
			);
			
			$mfhclass->templ->output("admin/admin", "site_settings_page");
			break;
		case "site_settings-s":
			$new_config_values = array(
				"site_name"            => $mfhclass->input->post_vars['site_name'],
				"email_out"            => (($mfhclass->funcs->valid_email($mfhclass->input->post_vars['email_out']) == false) ? $mfhclass->info->config['email_out'] : $mfhclass->input->post_vars['email_out']),
				"date_format"          => $mfhclass->input->post_vars['date_format'],
				"max_results"          => round($mfhclass->input->post_vars['max_results']),
				"blocked_access_names" => trim($mfhclass->input->post_vars['blocked_access_names']),
			);
			
			foreach ($new_config_values as $config_key => $config_value) {
				$mfhclass->db->query("UPDATE `mfh_site_settings` SET `config_value` = '{$config_value}' WHERE `config_key` = '{$config_key}';");
			}
			
			$mfhclass->templ->success("The site settings have been successfully updated. <br /><br /><a href=\"admin.php?act=site_settings\">Site Settings</a><br /><a href=\"admin.php\">Admin CP Index</a>", true);
			break;
		case "forum_settings":
			if ($mfhclass->funcs->is_null($mfhclass->input->get_vars['access_name']) == true || $mfhclass->funcs->forum_exists($mfhclass->input->get_vars['access_name']) == false) {
				$mfhclass->templ->error("Sorry but the requested forum could not be found.", true);
			} else {
				$forum_info = $mfhclass->db->fetch_array($mfhclass->db->query("SELECT * FROM `mfh_hosted_forums` WHERE `access_name` = '{$mfhclass->input->get_vars['access_name']}';"));
		
				$sql = $mfhclass->db->query("SELECT * FROM `{$mfhclass->input->get_vars['access_name']}_config` ORDER BY `config_name` ASC;", $forum_info['database_id']);
				while ($row = $mfhclass->db->fetch_array($sql)) {
					$mfhclass->templ->templ_globals['get_whileloop'] = true;
				
					$mfhclass->templ->templ_vars[] = array(
						"CONFIG_NAME"  => $row['config_name'],
						"CONFIG_VALUE" => $row['config_value'],
					);
							
					$mfhclass->templ->templ_globals['forum_settings_whileloop'] .= $mfhclass->templ->parse_template("admin/admin", "forum_settings_page");
		
					unset($mfhclass->templ->templ_vars, $mfhclass->templ->templ_globals['get_whileloop']);
				}
			
				$mfhclass->templ->templ_vars[] = array(
					"ACCESS_NAME" => $forum_info['access_name'],
				);
			
				$mfhclass->templ->output("admin/admin", "forum_settings_page");
			}
			break;
		case "forum_settings-s":
			if ($mfhclass->funcs->is_null($mfhclass->input->post_vars['access_name']) == true || $mfhclass->funcs->forum_exists($mfhclass->input->post_vars['access_name']) == false) {
				$mfhclass->templ->error("Sorry but the requested forum could not be found.", true);
			} else {
				$forum_info = $mfhclass->db->fetch_array($mfhclass->db->query("SELECT * FROM `mfh_hosted_forums` WHERE `access_name` = '{$mfhclass->input->post_vars['access_name']}';"));
				$config_keys = array_keys($mfhclass->input->post_vars);
				for ($i = 0; $i < count($mfhclass->input->post_vars); $i++) {
					if ($config_keys[$i] != "access_name") {
						$mfhclass->db->query("UPDATE `{$mfhclass->input->post_vars['access_name']}_config` SET `config_value` = '{$mfhclass->input->post_vars[$config_keys[$i]]}' WHERE `config_name` = '{$config_keys[$i]}';", $forum_info['database_id']);
					}
				}	
				$mfhclass->templ->success("Forum settings have been successfully updated. <br /><br /> <a href=\"admin.php?act=forum_settings&access_name={$mfhclass->input->post_vars['access_name']}\">Edit Settings Again</a><br /><a href=\"admin.php\">Admin CP Index</a>", true);
			}
			break;
		case "remove_forum":
			if ($mfhclass->funcs->is_null($mfhclass->input->get_vars['access_name']) == true || $mfhclass->funcs->forum_exists($mfhclass->input->get_vars['access_name']) == false) {
				$mfhclass->templ->error("Sorry but the requested forum could not be found.", true);
			} else {
				$mfhclass->templ->templ_vars[] = array(
					"ACCESS_NAME" => $mfhclass->input->get_vars['access_name'],
				);
				
				$mfhclass->templ->output("admin/admin", "delete_forum_page");
			}
			break;
		case "remove_forum-d":
			if ($mfhclass->funcs->is_null($mfhclass->input->post_vars['access_name']) == true || $mfhclass->funcs->forum_exists($mfhclass->input->post_vars['access_name']) == false) {
				$mfhclass->templ->error("Sorry but the requested forum could not be found.", true);
			} else {
				$forum_info = $mfhclass->db->fetch_array($mfhclass->db->query("SELECT * FROM `mfh_hosted_forums` WHERE `access_name` = '{$mfhclass->input->post_vars['access_name']}';"));

				require_once "{$mfhclass->info->root_path}phpBB3/includes/install/table_names.php";

				for ($i = 0; $i < count($mfhclass->info->phpbb_tables); $i++) {
					$table_name = preg_replace("#<\# ACCESS_NAME \#>#", $mfhclass->input->post_vars['access_name'], $mfhclass->info->phpbb_tables[$i]);

					$mfhclass->db->query("DROP TABLE IF EXISTS `{$table_name}`;", $forum_info['database_id']);

				}
			
				$uploads_folder = "{$mfhclass->info->root_path}phpBB3/files/{$mfhclass->input->post_vars['access_name']}/";

				if ($handle = opendir($uploads_folder)) {

					while (false !== ($file = readdir($handle))) {

						if ($file != "." && $file != "..") {

							if(unlink($uploads_folder.$file) == false){

								$mfhclass->templ->error("Sorry but we failed to delete the file <b>{$file}</b> from <b>{$uploads_folder}</b>.", true);

							}

						}

					}

					closedir($handle);

				} else {

					$mfhclass->templ->error("Cannot open uploads folder <b>{$uploads_folder}</b>.", true);

				}
			

				if (rmdir($uploads_folder) == false) {

					$mfhclass->templ->error("Cannot delete uploads folder <b>{$uploads_folder}</b>.", true);

				}
			
				$mfhclass->db->query("DELETE FROM `mfh_hosted_forums` WHERE `access_name` = '{$mfhclass->input->post_vars['access_name']}';");
			
				$mfhclass->templ->success("Forum successfully deleted. <br /><br /> <a href=\"admin.php\">Admin CP Index</a>", true);
			}
			break;
		case "categories":
			$sql = $mfhclass->db->query("SELECT * FROM `mfh_directory_categories` ORDER BY `category_name` ASC;");
			while ($row = $mfhclass->db->fetch_array($sql)) {
				$mfhclass->templ->templ_globals['get_whileloop'] = true;
			
				$mfhclass->templ->templ_vars[] = array(
					"TRCLASS"       => $trclass = (($trclass == "row1") ? "row2" : "row1"),
					"CATEGORY_ID"   => $row['category_id'],
					"CATEGORY_NAME" => $row['category_name'],
					"TOTAL_FORUMS"  => $mfhclass->funcs->format_number($mfhclass->db->total_rows($mfhclass->db->query("SELECT * FROM `mfh_hosted_forums` WHERE `category_id` = '{$row['category_id']}';"))),
				);
			
				$mfhclass->templ->templ_globals['directory_category_whileloop'] .= $mfhclass->templ->parse_template("admin/admin", "directory_manager_index_page");
			
				unset($mfhclass->templ->templ_vars, $mfhclass->templ->templ_globals['get_whileloop']);
			}

			$mfhclass->templ->output("admin/admin", "directory_manager_index_page");
			break;
		case "categories-n":
			if ($mfhclass->funcs->is_null($mfhclass->input->post_vars['category_name']) == true) {
				$mfhclass->templ->error("Please ensure that all required fields of the form on the previous page had been filled in correctly.", true);
			} else {
				$mfhclass->db->query("INSERT INTO `mfh_directory_categories` (`category_name`) VALUES ('{$mfhclass->input->post_vars['category_name']}');");
				$mfhclass->templ->success("The category <b>{$mfhclass->input->post_vars['category_name']}</b> has been successfully added. <br /><br /> <a href=\"admin.php?act=categories\">Manage Categories</a> <br /> <a href=\"admin.php\">Admin CP Index</a>", true);
			}
			break;
		case "categories-e":
			if ($mfhclass->funcs->is_null($mfhclass->input->get_vars['cat']) == true || $mfhclass->db->total_rows($mfhclass->db->query("SELECT * FROM `mfh_directory_categories` WHERE `category_id` = '{$mfhclass->input->get_vars['cat']}';")) !== 1) {
				$mfhclass->templ->error("Sorry but the requested category could not be found.", true);
			} else {
				$category_info = $mfhclass->db->fetch_array($mfhclass->db->query("SELECT * FROM `mfh_directory_categories` WHERE `category_id` = '{$mfhclass->input->get_vars['cat']}';"));
				
				$mfhclass->templ->templ_vars[] = array(
					"CATEGORY_ID"   => $category_info['category_id'],
					"CATEGORY_NAME" => $category_info['category_name'],
				);
				
				$mfhclass->templ->output("admin/admin", "edit_directory_category_page");
			}
			break;
		case "categories-e-s":
			if ($mfhclass->db->total_rows($mfhclass->db->query("SELECT * FROM `mfh_directory_categories` WHERE `category_id` = '{$mfhclass->input->post_vars['category_id']}';")) !== 1) {
				$mfhclass->templ->error("Sorry but the requested category could not be found.", true);
			} else { 
				if ($mfhclass->funcs->is_null($mfhclass->input->post_vars['category_name']) == true) {
					$mfhclass->templ->error("Please ensure that all required fields of the form on the previous page had been filled in correctly.", true);
				} else {
					$mfhclass->db->query("UPDATE `mfh_directory_categories` SET `category_name` = '{$mfhclass->input->post_vars['category_name']}' WHERE `category_id` = '{$mfhclass->input->post_vars['category_id']}';");
					$mfhclass->templ->success("The category <b>{$mfhclass->input->post_vars['category_name']}</b> has been successfully updated. <br /><br /><a href=\"admin.php?act=categories\">Manage Categories</a> <br /> <a href=\"admin.php\">Admin CP Index</a>", true);
				}
			}
			break;
		case "categories-r":
			if ($mfhclass->funcs->is_null($mfhclass->input->get_vars['cat']) == true || $mfhclass->db->total_rows($mfhclass->db->query("SELECT * FROM `mfh_directory_categories` WHERE `category_id` = '{$mfhclass->input->get_vars['cat']}';")) !== 1) {
				$mfhclass->templ->error("Sorry but the requested category could not be found.", true);
			} else {
				$mfhclass->templ->templ_vars[] = array(
					"CATEGORY_ID" => $mfhclass->input->get_vars['cat'],
				);
				
				$mfhclass->templ->output("admin/admin", "delete_directory_category_page");
			}
			break;
		case "categories-r-d":
			if ($mfhclass->funcs->is_null($mfhclass->input->post_vars['category_id']) == true || $mfhclass->db->total_rows($mfhclass->db->query("SELECT * FROM `mfh_directory_categories` WHERE `category_id` = '{$mfhclass->input->post_vars['category_id']}';")) !== 1) {
				$mfhclass->templ->error("Sorry but the requested category could not be found.", true);
			} else {
				$mfhclass->db->query("DELETE FROM `mfh_directory_categories` WHERE `category_id` = '{$mfhclass->input->post_vars['category_id']}';");
				$mfhclass->db->query("UPDATE `mfh_hosted_forums` SET `category_id` = '-1' WHERE `category_id` = '{$mfhclass->input->post_vars['category_id']}';");
				$mfhclass->templ->success("Category successfully deleted. <br /><br /><a href=\"admin.php?act=categories\">Manage Categories</a><br /><a href=\"admin.php\">Admin CP Index</a>", true);
			}
			break;
		case "database":
			$sql = $mfhclass->db->query("SELECT * FROM `mfh_forum_databases` ORDER BY `database_id` ASC;");
			while ($row = $mfhclass->db->fetch_array($sql)) {
				$mfhclass->templ->templ_globals['get_whileloop'] = true;
				
				$mfhclass->templ->templ_vars[] = array(
					"TRCLASS"           => $trclass = (($trclass == "row1") ? "row2" : "row1"),
					"DATABASE_ID"       => $row['database_id'],
					"DATABASE_NAME"     => $row['sql_database'],
					"DATABASE_USERNAME" => $row['sql_username'],
					"DATABASE_HOST"     => $row['sql_host'],
					"DATABASE_PASSWORD" => $row['sql_password'],
					"ALLOW_SIGNUPS"     => (($row['allow_signups'] == 1) ? "Yes" : "No"),
				);
							
				$mfhclass->templ->templ_globals['database_manager_listing_whileloop'] .= $mfhclass->templ->parse_template("admin/admin", "database_manager_index_page");
		
				unset($mfhclass->templ->templ_vars, $mfhclass->templ->templ_globals['get_whileloop']);
			}
			
			$mfhclass->templ->output("admin/admin", "database_manager_index_page");
			break;
		case "database-r":
			if ($mfhclass->funcs->is_null($mfhclass->input->get_vars['db_id']) == true || $mfhclass->input->get_vars['db_id'] === 1 || $mfhclass->db->total_rows($mfhclass->db->query("SELECT * FROM `mfh_forum_databases` WHERE `database_id` = '{$mfhclass->input->get_vars['db_id']}';")) !== 1) {		
				$mfhclass->templ->error("Sorry but the requested database could not be found.", true);
			} else {
				$mfhclass->templ->templ_vars[] = array(
					"DATABASE_ID" => $mfhclass->input->get_vars['db_id'],
				);
				
				$mfhclass->templ->output("admin/admin", "delete_database_page");
			}
			break;
		case "database-r-d":
			$mfhclass->db->query("DELETE FROM `mfh_forum_databases` WHERE `database_id` = '{$mfhclass->input->post_vars['db_id']}';");
			$mfhclass->templ->success("Database successfully deleted. <br /><br /><a href=\"admin.php?act=database\">Database Manager</a><br /><a href=\"admin.php\">Admin CP Index</a>", true);
			break;
		case "database-e":
			if ($mfhclass->funcs->is_null($mfhclass->input->get_vars['db_id']) == true || $mfhclass->db->total_rows($mfhclass->db->query("SELECT * FROM `mfh_forum_databases` WHERE `database_id` = '{$mfhclass->input->get_vars['db_id']}';")) !== 1) {		
				$mfhclass->templ->error("Sorry but the requested database could not be found.", true);
			} else {
				$database_info = $mfhclass->db->fetch_array($mfhclass->db->query("SELECT * FROM `mfh_forum_databases` WHERE `database_id` = '{$mfhclass->input->get_vars['db_id']}';"));
				
				$mfhclass->templ->templ_vars[] = array(
					"DATABASE_ID"       => $database_info['database_id'],
					"DATABASE_NAME"     => $database_info['sql_database'],
					"DATABASE_USERNAME" => $database_info['sql_username'],
					"DATABASE_HOST"     => $database_info['sql_host'],
					"DATABASE_PASSWORD" => $database_info['sql_password'],
					"ALLOW_SIGNUPS_YES" => (($database_info['allow_signups'] == 1) ? "checked=\"checked\"" : NULL),
					"ALLOW_SIGNUPS_NO"  => (($database_info['allow_signups'] == 0) ? "checked=\"checked\"" : NULL),
				);
				
				$mfhclass->templ->output("admin/admin", "edit_database_settings_page");
			}
			break;		
		case "database-e-s":
			if ($mfhclass->funcs->is_null($mfhclass->input->post_vars['sql_host']) == true || $mfhclass->funcs->is_null($mfhclass->input->post_vars['sql_database']) == true || $mfhclass->funcs->is_null($mfhclass->input->post_vars['sql_username']) == true) {
				$mfhclass->templ->error("Please ensure that all required fields of the form on the previous page had been filled in correctly.", true);
			} else {
				if ($mfhclass->funcs->is_null($mfhclass->input->post_vars['db_id']) == true || $mfhclass->db->total_rows($mfhclass->db->query("SELECT * FROM `mfh_forum_databases` WHERE `database_id` = '{$mfhclass->input->post_vars['db_id']}';")) !== 1) {		
					$mfhclass->templ->error("Sorry but the requested database could not be found.", true);
				} else {
					if ($mfhclass->input->post_vars['db_id'] == 1) {
						if ($config = fopen("{$mfhclass->info->root_path}source/includes/config.php", "w")) {
							$file_string = "<"."?php\n\n";
							$file_string .= "\t\t\t\t\t$"."mfhclass->info->config                 = array();
							$"."mfhclass->info->site_installed         = true;\n
							/"."* DATABASE INFORMATION *"."/ \n
							$"."mfhclass->info->config['sql_host']       = \"{$mfhclass->input->post_vars['sql_host']}\";
							$"."mfhclass->info->config['sql_username']   = \"{$mfhclass->input->post_vars['sql_username']}\";
							$"."mfhclass->info->config['sql_password']   = \"{$mfhclass->input->post_vars['sql_password']}\";
							$"."mfhclass->info->config['sql_database']   = \"{$mfhclass->input->post_vars['sql_database']}\";
							//$"."mfhclass->info->config['sql_tbl_prefix'] = \"{$mfhclass->info->config['sql_tbl_prefix']}\";"; 
							$file_string .= "\n\n?".">";
							if (fwrite($config, $file_string) == false) {
								$mfhclass->templ->error("Failed to write to file <b>{$mfhclass->info->root_path}source/includes/config.php</b>. Please ensure the script has permission to write to it. A good permission level is 0777.", true);
							}
						} else {
							$mfhclass->templ->error("Failed to open file <b>{$mfhclass->info->root_path}source/includes/config.php</b> for writing. Please ensure the script has permission to write to it. A good permission level is 0777.", true);
						}
					}
			
					$mfhclass->db->query("UPDATE `mfh_forum_databases` SET `sql_username` = '{$mfhclass->input->post_vars['sql_username']}', `sql_database` = '{$mfhclass->input->post_vars['sql_database']}', `sql_password` = '{$mfhclass->input->post_vars['sql_password']}', `sql_host` = '{$mfhclass->input->post_vars['sql_host']}', `allow_signups` = '{$mfhclass->input->post_vars['allow_signups']}' WHERE `database_id` = '{$mfhclass->input->post_vars['db_id']}';");
					
					$mfhclass->db->connect($mfhclass->input->post_vars['sql_host'], $mfhclass->input->post_vars['sql_username'], $mfhclass->input->post_vars['sql_password'], $mfhclass->input->post_vars['sql_database'], rand(1,10));

					$mfhclass->templ->success("Database successfully updated. <br /><br /><a href=\"admin.php?act=database\">Database Manager</a> <br /> <a href=\"admin.php\">Admin CP Index</a>", true);
				}
			}
			break;		
		case "database-n":
			$mfhclass->templ->output("admin/admin", "new_database_page");
			break;		
		case "database-n-s":
			if ($mfhclass->funcs->is_null($mfhclass->input->post_vars['sql_host']) == true || $mfhclass->funcs->is_null($mfhclass->input->post_vars['sql_database']) == true || $mfhclass->funcs->is_null($mfhclass->input->post_vars['sql_username']) == true) {
				$mfhclass->templ->error("Please ensure that all required fields of the form on the previous page had been filled in correctly.", true);
			} else {
				$mfhclass->db->connect($mfhclass->input->post_vars['sql_host'], $mfhclass->input->post_vars['sql_username'], $mfhclass->input->post_vars['sql_password'], $mfhclass->input->post_vars['sql_database'], rand(1,10));
				$mfhclass->db->query("INSERT INTO `mfh_forum_databases` (`sql_host`, `sql_database`, `sql_password`, `sql_username`, `allow_signups`) VALUES ('{$mfhclass->input->post_vars['sql_host']}', '{$mfhclass->input->post_vars['sql_database']}', '{$mfhclass->input->post_vars['sql_password']}', '{$mfhclass->input->post_vars['sql_username']}', '{$mfhclass->input->post_vars['allow_signups']}');");
				$mfhclass->templ->success("Database successfully added. <br /><br /><a href=\"admin.php?act=database\">Database Manager</a> <br /> <a href=\"admin.php\">Admin CP Index</a>", true);
			}
			break;		
		case "admins":
			$sql = $mfhclass->db->query("SELECT * FROM `mfh_admin_accounts` ORDER BY `admin_id` ASC;");
			while ($row = $mfhclass->db->fetch_array($sql)) {
				$mfhclass->templ->templ_globals['get_whileloop'] = true;
				
				$mfhclass->templ->templ_vars[] = array(
					"TRCLASS"       => $trclass = (($trclass == "row1") ? "row2" : "row1"),
					"ADMIN_ID"      => $row['admin_id'],
					"USERNAME"      => $row['username'],
					"EMAIL_ADDRESS" => $row['email_address'],
					"IP_ADDRESS"    => $row['ip_address'],
				);
							
				$mfhclass->templ->templ_globals['administrator_listing_whileloop'] .= $mfhclass->templ->parse_template("admin/admin", "administrator_manager_page");
		
				unset($mfhclass->templ->templ_vars, $mfhclass->templ->templ_globals['get_whileloop']);
			}
			
			$mfhclass->templ->templ_vars[] = array(
				"PAGINATION_LINKS" => $mfhclass->templ->pagelinks("admin.php?act=admins", $mfhclass->db->total_rows($mfhclass->db->query("SELECT * FROM `mfh_admin_accounts` ORDER BY `admin_id` ASC;"))),
			);
			
			$mfhclass->templ->output("admin/admin", "administrator_manager_page");
		case "admins-r":
			if ($mfhclass->funcs->is_null($mfhclass->input->get_vars['admin_id']) == true || $mfhclass->db->total_rows($mfhclass->db->query("SELECT * FROM `mfh_admin_accounts` WHERE `admin_id` = '{$mfhclass->input->get_vars['admin_id']}';")) !== 1) {
				$mfhclass->templ->error("Sorry but the requested administrator could not be found.", true);
			} elseif ($mfhclass->input->get_vars['admin_id'] == 1) {
				$mfhclass->templ->error("The root administrator account is not allowed to be deleted.", true);
			} else {
				$mfhclass->templ->templ_vars[] = array(
					"ADMIN_ID" => $mfhclass->input->get_vars['admin_id'],
				);
				
				$mfhclass->templ->output("admin/admin", "delete_administrator_page");
			}
			break;
		case "admins-r-d":
			if ($mfhclass->funcs->is_null($mfhclass->input->post_vars['admin_id']) == true || $mfhclass->db->total_rows($mfhclass->db->query("SELECT * FROM `mfh_admin_accounts` WHERE `admin_id` = '{$mfhclass->input->post_vars['admin_id']}';")) !== 1) {
				$mfhclass->templ->error("Sorry but the requested administrator could not be found.", true);
			} elseif ($mfhclass->input->post_vars['admin_id'] === 1) {
				$mfhclass->templ->error("The root administrator account is not allowed to be deleted.", true);
			} else {
				$mfhclass->db->query("DELETE FROM `mfh_admin_accounts` WHERE `admin_id` = '{$mfhclass->input->post_vars['admin_id']}';");
				$mfhclass->templ->success("Account successfully deleted. <br /><br /><a href=\"admin.php?act=admins\">Manage Admin Accounts</a><br /><a href=\"admin.php\">Admin CP Index</a>", true);
			}
			break;
		case "admins-ep":
			if ($mfhclass->funcs->is_null($mfhclass->input->get_vars['admin_id']) == true || $mfhclass->db->total_rows($mfhclass->db->query("SELECT * FROM `mfh_admin_accounts` WHERE `admin_id` = '{$mfhclass->input->get_vars['admin_id']}';")) !== 1) {
				$mfhclass->templ->error("Sorry but the requested administrator could not be found.", true);
			} elseif ($mfhclass->info->admin_data['admin_id'] != 1) {
				$mfhclass->templ->error("The root administrator account is only allowed to be edited by him/her self.", true);
			} else {
				$mfhclass->templ->templ_vars[] = array(
					"ADMIN_ID" => $mfhclass->input->get_vars['admin_id'],
				);
				
				$mfhclass->templ->output("admin/admin", "edit_administrator_page");
			}
			break;
		case "admins-ep-s":
			if ($mfhclass->funcs->is_null($mfhclass->input->post_vars['admin_id']) == true || $mfhclass->db->total_rows($mfhclass->db->query("SELECT * FROM `mfh_admin_accounts` WHERE `admin_id` = '{$mfhclass->input->post_vars['admin_id']}';")) !== 1) {
				$mfhclass->templ->error("Sorry but the requested administrator could not be found.", true);
			} elseif ($mfhclass->info->admin_data['admin_id'] != 1) {
				$mfhclass->templ->error("The root administrator account is only allowed to be edited by him/her self.", true);
			} else {
				if ($mfhclass->funcs->is_null($mfhclass->input->post_vars['password']) == true || $mfhclass->funcs->is_null($mfhclass->input->post_vars['new_password']) == true || $mfhclass->funcs->is_null($mfhclass->input->post_vars['new_password-c']) == true) {
					$mfhclass->templ->error("Please ensure that all required fields of the form on the previous page had been filled in correctly.", true);
				} elseif ($mfhclass->db->total_rows($mfhclass->db->query("SELECT * FROM `mfh_admin_accounts` WHERE `admin_id` = '{$mfhclass->input->post_vars['admin_id']}' AND `password` = '".md5($mfhclass->input->post_vars['password'])."';")) !== 1) {
					$mfhclass->templ->error("Failed to find an administrator account with the information entered.", true);
				} elseif ($mfhclass->input->post_vars['new_password'] !== $mfhclass->input->post_vars['new_password-c']) {
					$mfhclass->templ->error("Please ensure that the new passwords you have entered exactly match each other. ", true);
				} elseif (strlen($mfhclass->input->post_vars['new_password']) < 6 || strlen($mfhclass->input->post_vars['new_password']) > 30) {
					$mfhclass->templ->error("Please ensure that the new password you have entered is valid.", true);
				} else {
					$mfhclass->db->query("UPDATE `mfh_admin_accounts` SET `password` = '".md5($mfhclass->input->post_vars['new_password'])."' WHERE `admin_id` = '{$mfhclass->input->post_vars['admin_id']}';");
					$mfhclass->templ->success("Password successfully changed.  <br /><br /><a href=\"admin.php?act=admins\">Manage Admin Accounts</a> <br /> <a href=\"admin.php\">Admin CP Index</a>", true);
				}
			}
			break;
		case "admins-n":
			$mfhclass->templ->output("admin/admin", "new_administrator_page");
			break;
		case "admins-n-s":
			if ($mfhclass->funcs->is_null($mfhclass->input->post_vars['username'] ) == true || $mfhclass->funcs->is_null($mfhclass->input->post_vars['password'] ) == true || $mfhclass->funcs->is_null($mfhclass->input->post_vars['password-c'] ) == true || $mfhclass->funcs->is_null($mfhclass->input->post_vars['email_address'] ) == true) {
				$mfhclass->templ->error("Please ensure that all required fields of the form on the previous page had been filled in correctly.", true);
			} elseif ($mfhclass->funcs->valid_email($mfhclass->input->post_vars['email_address']) == false) {
				$mfhclass->templ->error("The administrator email address entered appears to be invalid.", true);
			} elseif ($mfhclass->input->post_vars['password'] !== $mfhclass->input->post_vars['password-c']) {
				$mfhclass->templ->error("Please ensure that the administrator passwords you have entered exactly match each other. ", true);
			} elseif (strlen($mfhclass->input->post_vars['password']) < 6 || strlen($mfhclass->input->post_vars['password']) > 30) {
				$mfhclass->templ->error("Please ensure you have entered a valid administrator password.", true);
			} elseif ($mfhclass->funcs->valid_string($mfhclass->input->post_vars['username']) == false || strlen($mfhclass->input->post_vars['username']) < 3 || strlen($mfhclass->input->post_vars['username']) > 30) {
				$mfhclass->templ->error("Please ensure you have entered a valid administrator username.", true);
			} else {
				$mfhclass->db->query("INSERT INTO `mfh_admin_accounts` (`admin_id`, `username`, `password`, `email_address`, `ip_address`) VALUES ('', '{$mfhclass->input->post_vars['username']}', '".md5($mfhclass->input->post_vars['password'])."', '{$mfhclass->input->post_vars['email_address']}', '{$mfhclass->input->server_vars['remote_addr']}');");
				$mfhclass->templ->success("Account successfully added.  <br /><br /> <a href=\"admin.php?act=admins\">Manage Admin Accounts</a> <br /> <a href=\"admin.php\">Admin CP Index</a>", true);
			}
			break;
		default:
			$sql = $mfhclass->db->query("SELECT * FROM `mfh_hosted_forums` ORDER BY `time_started` DESC LIMIT <# QUERY_LIMIT #>;");
			if ($mfhclass->db->total_rows($sql) < 1) {
				$mfhclass->templ->error("There are no hosted forums at this time.", true);
			} else {
				while ($row = $mfhclass->db->fetch_array($sql)) {
					$last_post = $mfhclass->db->fetch_array($mfhclass->db->query("SELECT * FROM `{$row['access_name']}_posts` ORDER BY `post_id` DESC LIMIT 1;", $row['database_id']));
			
					$mfhclass->templ->templ_globals['get_whileloop'] = true;
					
					$mfhclass->templ->templ_vars[] = array(
						"TRCLASS"           => $trclass = (($trclass == "row1") ? "row2" : "row1"),
						"BASE_URL"          => $mfhclass->info->base_url,
						"ACCESS_NAME"       => $row['access_name'],
						"TOTAL_HITS"        => $mfhclass->funcs->format_number($row['total_hits']),
						"DATE_CREATED"      => date("F j, Y", $row['time_started']),
						"TOTAL_MEMBERS"     => $mfhclass->funcs->format_number(($mfhclass->db->total_rows($mfhclass->db->query("SELECT * FROM `{$row['access_name']}_users`;", $row['database_id'])) - $mfhclass->db->total_rows($mfhclass->db->query("SELECT * FROM `{$row['access_name']}_bots`;", $row['database_id']))) - 1),
						"DAYS_WITHOUT_POST" => floor((mktime() - $last_post['post_time']) / 86400),
					);
								
					$mfhclass->templ->templ_globals['forum_listing_whileloop'] .= $mfhclass->templ->parse_template("admin/admin", "admin_index_page");
			
					unset($mfhclass->templ->templ_vars, $mfhclass->templ->templ_globals['get_whileloop']);
				}
			}
		
			$mfhclass->templ->templ_vars[] = array(
				"PAGINATION_LINKS" => $mfhclass->templ->pagelinks("admin.php", $mfhclass->db->total_rows($mfhclass->db->query("SELECT * FROM `mfh_hosted_forums` ORDER BY `time_started` DESC;"))),
			);
			
			$mfhclass->templ->output("admin/admin", "admin_index_page");
	}

	$mfhclass->templ->output();

?>
