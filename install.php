<?php
  // ======================================== \

	// Package: Mihalism Multi Forum Host 
	// Version: 3.0.0
	// Copyright (c) 2007, 2008 Mihalism, Inc.
	// License: http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt GNU Public License
	// ======================================== /
	
	require_once "./source/includes/data.php";
	
	$mfhclass->templ->page_title = "Multi Forum Hosting Script &raquo; Instalador";


	if ($mfhclass->info->site_installed == true) {
		$mfhclass->templ->error("El Instalador ha sido desactivado usted ya posee una instalacion de Multi Forum Hosting Script (MHFS).", true);
	}

	switch ($mfhclass->input->get_vars['act']) {
		case "install":
			$mfhclass->templ->templ_vars[] = array(
				"SERVER_ADMIN" => $mfhclass->input->server_vars['server_admin'],
			);
			
			$mfhclass->templ->output("install", "install_form_page");
			break;
		case "install-d":
			if ($mfhclass->funcs->is_null($mfhclass->input->post_vars['username']) == true || $mfhclass->funcs->is_null($mfhclass->input->post_vars['password']) == true || $mfhclass->funcs->is_null($mfhclass->input->post_vars['password-c']) == true || $mfhclass->funcs->is_null($mfhclass->input->post_vars['email_address']) == true || $mfhclass->funcs->is_null($mfhclass->input->post_vars['sql_host']) == true || $mfhclass->funcs->is_null($mfhclass->input->post_vars['sql_database']) == true || $mfhclass->funcs->is_null($mfhclass->input->post_vars['sql_username']) == true) {
				$mfhclass->templ->error("Please ensure that all required fields of the form on the previous page had been filled in correctly.", true);
			} elseif ($mfhclass->funcs->valid_email($mfhclass->input->post_vars['email_address']) == false) {
				$mfhclass->templ->error("The entered administrator email address appears to be invalid.", true);
			} elseif ($mfhclass->input->post_vars['password'] !== $mfhclass->input->post_vars['password-c']) {
				$mfhclass->templ->error("Please ensure that the administrator passwords you have entered match each other.", true);
			} elseif (strlen($mfhclass->input->post_vars['password']) < 6 || strlen($mfhclass->input->post_vars['password']) > 30) {
				$mfhclass->templ->error("Please ensure you have entered a valid administrator password.", true);
			} elseif ($mfhclass->funcs->valid_string($mfhclass->input->post_vars['username']) == false || strlen($mfhclass->input->post_vars['username']) < 3 || strlen($mfhclass->input->post_vars['username']) > 30) {
				$mfhclass->templ->error("Please ensure you have entered a valid administrator username.", true);
			} elseif (is_writable("{$mfhclass->info->root_path}source/includes/config.php") == false) {
				$mfhclass->templ->error("Please ensure the file <b>{$mfhclass->info->root_path}source/includes/config.php</b> has the ability to be written to. A good permission level is 0777.", true);
			} elseif (is_writable("{$mfhclass->info->root_path}phpBB3/files/") == false || is_readable("{$mfhclass->info->root_path}phpBB3/files/") == false) {
				$mfhclass->templ->error("Please ensure the folder <b>{$mfhclass->info->root_path}phpBB3/files/</b> has the ability to be read and written to. A good permission level is 0777.", true);
			} else {
				$mfhclass->db->connect($mfhclass->input->post_vars['sql_host'], $mfhclass->input->post_vars['sql_username'], $mfhclass->input->post_vars['sql_password'], $mfhclass->input->post_vars['sql_database']);
				
				$mfhclass->db->install_queries = array();

				$mfhclass->db->install_queries[] = "DROP TABLE IF EXISTS `mfh_admin_accounts`;";
				$mfhclass->db->install_queries[] = "DROP TABLE IF EXISTS `mfh_admin_sessions`;";
				$mfhclass->db->install_queries[] = "DROP TABLE IF EXISTS `mfh_directory_categories`;";
				$mfhclass->db->install_queries[] = "DROP TABLE IF EXISTS `mfh_forum_databases`;";
				$mfhclass->db->install_queries[] = "DROP TABLE IF EXISTS `mfh_hosted_forums`;";
				$mfhclass->db->install_queries[] = "DROP TABLE IF EXISTS `mfh_site_settings`;";
	
				$mfhclass->db->install_queries[] = "CREATE TABLE `mfh_admin_accounts` (
				  `admin_id` int(25) NOT NULL auto_increment,
				  `username` varchar(30) NOT NULL default '',
				  `password` varchar(32) NOT NULL default '',
				  `email_address` varchar(255) NOT NULL default '',
				  `ip_address` varchar(15) NOT NULL default '',
				  PRIMARY KEY  (`admin_id`),
				  UNIQUE KEY `username` (`username`)
				) ENGINE=MyISAM;";

				$mfhclass->db->install_queries[] = "CREATE TABLE `mfh_admin_sessions` (
				  `admin_id` int(25) NOT NULL default '0',
				  `session_id` varchar(32) NOT NULL,
				  `session_start` int(10) NOT NULL default '0',
				  `ip_address` varchar(15) NOT NULL,
				  `user_agent` varchar(255) NOT NULL default '',
				  PRIMARY KEY  (`session_id`)
				) ENGINE=MyISAM;";

				$mfhclass->db->install_queries[] = "CREATE TABLE `mfh_directory_categories` (
				  `category_id` int(25) NOT NULL auto_increment,
				  `category_name` varchar(255) NOT NULL default '',
				  PRIMARY KEY  (`category_id`)
				) ENGINE=MyISAM;";

				$mfhclass->db->install_queries[] = "CREATE TABLE `mfh_forum_databases` (
				  `database_id` int(25) NOT NULL auto_increment,
				  `sql_host` varchar(255) NOT NULL default '',
				  `sql_database` varchar(255) NOT NULL,
				  `sql_username` varchar(255) NOT NULL,
				  `sql_password` varchar(255) NOT NULL,
				  `allow_signups` tinyint(1) NOT NULL default '0',
				  PRIMARY KEY  (`database_id`)
				) ENGINE=MyISAM;";

				$mfhclass->db->install_queries[] = "CREATE TABLE `mfh_hosted_forums` (
				  `forum_id` int(25) NOT NULL auto_increment,
				  `database_id` int(25) NOT NULL default '1',
				  `access_name` varchar(30) NOT NULL,
				  `time_started` int(10) NOT NULL default '0',
				  `total_hits` int(30) NOT NULL default '0',
				  `category_id` int(5) NOT NULL default '0',
				  `ip_address` varchar(15) NOT NULL,
				  `contact_address` varchar(255) NOT NULL,
				  PRIMARY KEY  (`forum_id`),
				  UNIQUE KEY `access_name` (`access_name`)
				) ENGINE=MyISAM;";

				$mfhclass->db->install_queries[] = "CREATE TABLE `mfh_site_settings` (

				  `config_key` varchar(70) NOT NULL default '',

				  `config_value` text NOT NULL,

				  PRIMARY KEY  (`config_key`)

				) ENGINE=MyISAM;";
				
				$mfhclass->db->install_queries[] = "INSERT INTO `mfh_admin_accounts` (`admin_id`, `username`, `password`, `email_address`, `ip_address`) VALUES ('1', '{$mfhclass->input->post_vars['username']}', '".md5($mfhclass->input->post_vars['password'])."', '{$mfhclass->input->post_vars['email_address']}', '{$mfhclass->input->server_vars['remote_addr']}');";

				$mfhclass->db->install_queries[] = "INSERT INTO `mfh_site_settings` (`config_key`, `config_value`) VALUES ('date_format', 'F j, Y, g:i:s a');";
				$mfhclass->db->install_queries[] = "INSERT INTO `mfh_site_settings` (`config_key`, `config_value`) VALUES ('max_results', '15');";
				$mfhclass->db->install_queries[] = "INSERT INTO `mfh_site_settings` (`config_key`, `config_value`) VALUES ('site_name', 'Tu Sitio | MHFS');";
				$mfhclass->db->install_queries[] = "INSERT INTO `mfh_site_settings` (`config_key`, `config_value`) VALUES ('email_out', '{$mfhclass->input->post_vars['email_address']}');";
				$mfhclass->db->install_queries[] = "INSERT INTO `mfh_site_settings` (`config_key`, `config_value`) VALUES ('blocked_access_names', 'access_name,example,test,mfh,help,support,phpbb,phpbb3,forum,forums');";

				$mfhclass->db->install_queries[] = "INSERT INTO `mfh_forum_databases` (`database_id`, `sql_host`, `sql_database`, `sql_password`, `sql_username`, `allow_signups`) VALUES (1, '{$mfhclass->input->post_vars['sql_host']}', '{$mfhclass->input->post_vars['sql_database']}', '{$mfhclass->input->post_vars['sql_password']}', '{$mfhclass->input->post_vars['sql_username']}', 1); ";

				$mfhclass->db->install_queries[] = "INSERT INTO `mfh_directory_categories` (`category_id`, `category_name`) VALUES (1, 'Art & Literature');";
				$mfhclass->db->install_queries[] = "INSERT INTO `mfh_directory_categories` (`category_id`, `category_name`) VALUES (2, 'Cars');";
				$mfhclass->db->install_queries[] = "INSERT INTO `mfh_directory_categories` (`category_id`, `category_name`) VALUES (3, 'Clans');";
				$mfhclass->db->install_queries[] = "INSERT INTO `mfh_directory_categories` (`category_id`, `category_name`) VALUES (4, 'Computers & Internet');";
				$mfhclass->db->install_queries[] = "INSERT INTO `mfh_directory_categories` (`category_id`, `category_name`) VALUES (5, 'Education');";
				$mfhclass->db->install_queries[] = "INSERT INTO `mfh_directory_categories` (`category_id`, `category_name`) VALUES (6, 'Family & Parents');";
				$mfhclass->db->install_queries[] = "INSERT INTO `mfh_directory_categories` (`category_id`, `category_name`) VALUES (7, 'Gaming');";
				$mfhclass->db->install_queries[] = "INSERT INTO `mfh_directory_categories` (`category_id`, `category_name`) VALUES (8, 'Graphics & Design');";
				$mfhclass->db->install_queries[] = "INSERT INTO `mfh_directory_categories` (`category_id`, `category_name`) VALUES (9, 'Health & Medical');";
				$mfhclass->db->install_queries[] = "INSERT INTO `mfh_directory_categories` (`category_id`, `category_name`) VALUES (10, 'Hobbies');";
				$mfhclass->db->install_queries[] = "INSERT INTO `mfh_directory_categories` (`category_id`, `category_name`) VALUES (11, 'Music');";
				$mfhclass->db->install_queries[] = "INSERT INTO `mfh_directory_categories` (`category_id`, `category_name`) VALUES (12, 'News & Politics');";
				$mfhclass->db->install_queries[] = "INSERT INTO `mfh_directory_categories` (`category_id`, `category_name`) VALUES (13, 'Online Communites');";
				$mfhclass->db->install_queries[] = "INSERT INTO `mfh_directory_categories` (`category_id`, `category_name`) VALUES (14, 'Outdoors & Nature');";
				$mfhclass->db->install_queries[] = "INSERT INTO `mfh_directory_categories` (`category_id`, `category_name`) VALUES (15, 'Religious');";
				$mfhclass->db->install_queries[] = "INSERT INTO `mfh_directory_categories` (`category_id`, `category_name`) VALUES (16, 'Sports');";
				$mfhclass->db->install_queries[] = "INSERT INTO `mfh_directory_categories` (`category_id`, `category_name`) VALUES (17, 'Teens & Kids');";
				$mfhclass->db->install_queries[] = "INSERT INTO `mfh_directory_categories` (`category_id`, `category_name`) VALUES (18, 'TV & Movies');";
				$mfhclass->db->install_queries[] = "INSERT INTO `mfh_directory_categories` (`category_id`, `category_name`) VALUES (19, 'Other');";

				for ($i = 0; $i < count($mfhclass->db->install_queries); $i++) {
					$mfhclass->db->query($mfhclass->db->install_queries[$i]);
				}
			
				if ($htaccess = fopen("{$mfhclass->info->root_path}.htaccess", "w")) {
					$file_string  = "\n#Mihalism Multi Forum Host auto generated .htaccess file\n";
					$file_string .= "RewriteEngine On\n";
					$file_string .= "RewriteBase {$mfhclass->info->script_path}\n";
					$file_string .= "RewriteRule ^forums$|^forums/$ index.php [R=301,L]\n";
					$file_string .= "RewriteRule ^forums/([-_a-zA-Z0-9]{3,30})$ forums/$1/ [R=301,L]\n";
					$file_string .= "RewriteRule ^forums/([-_a-zA-Z0-9]{3,30})/(.*)$ phpBB3/$2?access_name=$1 [QSA,L]\n";
					if (fwrite($htaccess, $file_string) == false) {
						$mfhclass->templ->error("Failed to write to file <b>{$mfhclass->info->root_path}.htaccess</b>. Please ensure the script has permission to write to it. A good permission level is 0777.", true);
					}
				} else {
					$mfhclass->templ->error("Failed to open file <b>{$mfhclass->info->root_path}.htaccess</b> for writing. Please ensure the script has permission to write to it. A good permission level is 0777.", true);
				}
			if ($htaccess = fopen("{$mfhclass->info->root_path} creditos.txt", "w")) {
					$file_string  = "\n#Creditos /// No Borrar\n";
					$file_string .= "Ideador del Proyecto : Zero";
					$file_string .= "Version 0.2 : Zero\n";
					$file_string .= "Antiguo Proyecto (Dueño) : Mishalim \n";
					if (fwrite($htaccess, $file_string) == false) {
						$mfhclass->templ->error("Failed to write to file <b>{$mfhclass->info->root_path}Creditos.txt </b>. .", true);
					}
				} else {
					$mfhclass->templ->error("Failed to open file <b>{$mfhclass->info->root_path}Creditos.txt</b> .", true);
				}
				if ($config = fopen("{$mfhclass->info->root_path}source/includes/config.php", "w")) {
					$file_string = "<"."?php\n\n";
					$file_string .= "\t\t\t\t\t$"."mfhclass->info->config                 = array();
					$"."mfhclass->info->site_installed         = true;\n
					/"."* DATABASE INFORMATION *"."/ \n
					$"."mfhclass->info->config['sql_host']       = \"{$mfhclass->input->post_vars['sql_host']}\";
					$"."mfhclass->info->config['sql_username']   = \"{$mfhclass->input->post_vars['sql_username']}\";
					$"."mfhclass->info->config['sql_password']   = \"{$mfhclass->input->post_vars['sql_password']}\";
					$"."mfhclass->info->config['sql_database']   = \"{$mfhclass->input->post_vars['sql_database']}\";
					//$"."mfhclass->info->config['sql_tbl_prefix'] = \"mfh_\";"; 
					$file_string .= "\n\n?".">";
					if (fwrite($config, $file_string) == false) {
						$mfhclass->templ->error("Failed to write to file <b>{$mfhclass->info->root_path}source/includes/config.php</b>. Please ensure the script has permission to write to it. A good permission level is 0777.", true);
					}
				} else {
					$mfhclass->templ->error("Failed to open file <b>{$mfhclass->info->root_path}source/includes/config.php</b> for writing. Please ensure the script has permission to write to it. A good permission level is 0777.", true);
				}

				$mfhclass->templ->success("El Sitio ha Sido Intalado Correctamente. <br /><br /> <a href=\"index.php\">Inicio</a><br /><a href=\"admin.php\">Panel de Administrador</a>", true);
			}
			break;
		default:
			$mfhclass->templ->templ_vars[] = array(
				"PACKAGED_PHPBB_VERSION" => $mfhclass->info->phpbb_version,
			); 
		
			$mfhclass->templ->output("install", "installer_intro_page");
	}
				$mfhclass->templ->output("install", "install_form_page");
			break;
		
?>
