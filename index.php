<?php
  // ======================================== \
	// Package: Mihalism Multi Forum Host 
	// Version: 3.0.0
	// Copyright (c) 2007, 2008 Mihalism, Inc.
	// License: http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt GNU Public License
	// ======================================== /
	
	require_once "./source/includes/data.php";
    
    echo $template;
	switch ($mfhclass->input->get_vars['act']) {
		case "signup":
			$mfhclass->templ->page_title = "{$mfhclass->info->config['site_name']} &raquo; phpBB Forum Signup";
			 
			$sql = $mfhclass->db->query("SELECT * FROM `mfh_directory_categories` ORDER BY `category_name` ASC;");
			while ($row = $mfhclass->db->fetch_array($sql)) {
				$mfhclass->templ->templ_globals['get_whileloop'] = true;
			
				$mfhclass->templ->templ_vars[] = array(
					"CATEGORY_ID"   => $row['category_id'],
					"CATEGORY_NAME" => $row['category_name'],
				);
			
				$mfhclass->templ->templ_globals['directory_categories_whileloop'] .= $mfhclass->templ->parse_template("home", "phpbb_signup_page");
			
				unset($mfhclass->templ->templ_vars, $mfhclass->templ->templ_globals['get_whileloop']);
			}
		
			$mfhclass->templ->templ_vars[] = array(
				"PACKAGED_PHPBB_VERSION" => $mfhclass->info->phpbb_version,
			); 
		
			$mfhclass->templ->output("home", "phpbb_signup_page");
			break;
		case "signup-p":
			$mfhclass->templ->page_title = "{$mfhclass->info->config['site_name']} &raquo; phpBB Forum Signup &raquo; Singup Complete";
			$mfhclass->input->post_vars['access_name'] = str_replace("-", "_", strtolower($mfhclass->input->post_vars['access_name']));

			require_once "{$mfhclass->info->root_path}phpBB3/includes/install/install_queries.php";

			if ($mfhclass->funcs->is_null($mfhclass->input->post_vars['iagree']) == true || $mfhclass->funcs->is_null($mfhclass->input->post_vars['access_name']) == true || $mfhclass->funcs->is_null($mfhclass->input->post_vars['username']) == true || $mfhclass->funcs->is_null($mfhclass->input->post_vars['password']) == true || $mfhclass->funcs->is_null($mfhclass->input->post_vars['password-c']) == true || $mfhclass->funcs->is_null($mfhclass->input->post_vars['email_address']) == true || $mfhclass->funcs->is_null($mfhclass->input->post_vars['forum_category']) == true) {
				$mfhclass->templ->error("Please ensure that all required fields of the form on the previous page had been filled in correctly.", true);
			} elseif ($mfhclass->funcs->valid_email($mfhclass->input->post_vars['email_address']) == false) {
				$mfhclass->templ->error("The entered administrator email address appears to be invalid.", true);
			} elseif ($mfhclass->input->post_vars['password'] !== $mfhclass->input->post_vars['password-c']) {
				$mfhclass->templ->error("Please ensure that the administrator passwords you have entered match each other. ", true);
			} elseif (strlen($mfhclass->input->post_vars['password']) < 6 || strlen($mfhclass->input->post_vars['password']) > 30) {
				$mfhclass->templ->error("Please ensure you have entered a valid administrator password.", true);
			} elseif ($mfhclass->funcs->valid_string($mfhclass->input->post_vars['username']) == false || strlen($mfhclass->input->post_vars['username']) < 3 || strlen($mfhclass->input->post_vars['username']) > 30) {
				$mfhclass->templ->error("Please ensure you have entered a valid administrator username.", true);
			} elseif ($mfhclass->funcs->valid_string($mfhclass->input->post_vars['access_name']) == false || strlen($mfhclass->input->post_vars['access_name']) < 3 || strlen($mfhclass->input->post_vars['access_name']) > 30) {
				$mfhclass->templ->error("Please ensure you have entered a valid access name", true);
			} elseif ($mfhclass->funcs->forum_exists($mfhclass->input->post_vars['access_name']) == true || in_array($mfhclass->input->post_vars['access_name'], preg_split("/,/", trim($mfhclass->info->config['blocked_access_names']))) == true) {
				$mfhclass->templ->error("Sorry but the requested access name is already in use.", true);
			} elseif ($mfhclass->db->total_rows($mfhclass->db->query("SELECT * FROM `mfh_forum_databases` WHERE `allow_signups` = 1 ORDER BY RAND() LIMIT 1;")) < 1) {
				$mfhclass->templ->error("Sorry but signups are disabled.", true);
			} else {
				if (mkdir("{$mfhclass->info->root_path}phpBB3/files/{$mfhclass->input->post_vars['access_name']}/", 0777) == false) {
					$mfhclass->templ->error("Failed to create upload folder <b>{$mfhclass->info->root_path}phpBB3/files/{$mfhclass->input->post_vars['access_name']}/</b>.");
				}
				
				$database_info = $mfhclass->db->fetch_array($mfhclass->db->query("SELECT * FROM `mfh_forum_databases` WHERE `allow_signups` = 1 ORDER BY RAND() LIMIT 1;"));				
				$mfhclass->db->query("INSERT INTO `mfh_hosted_forums` (`database_id`, `access_name`, `time_started`, `total_hits`, `category_id`, `ip_address`, `contact_address`) VALUES ('{$database_info['database_id']}', '{$mfhclass->input->post_vars['access_name']}', ".time().", 1, {$mfhclass->input->post_vars['forum_category']}, '{$mfhclass->input->server_vars['ip_address']}', '{$mfhclass->input->post_vars['email_address']}');");

				for ($i = 0; $i < count($mfhclass->db->install_queries); $i++) {
					$mfhclass->db->query($mfhclass->db->install_queries[$i], $database_info['database_id']);
				}

				$mfhclass->templ->templ_vars[] = array(
					"BASE_URL"            => $mfhclass->info->base_url,
					"ACCESS_NAME"         => $mfhclass->input->post_vars['access_name'],
					"FORUM_NAME"          => (($mfhclass->funcs->is_null($mfhclass->input->post_vars['forum_name']) == true) ? "<i>No Information</i>" : $mfhclass->input->post_vars['forum_name']),
					"ADMIN_USERNAME"      => $mfhclass->input->post_vars['username'],
					"ADMIN_PASSWORD"      => $mfhclass->input->post_vars['password'],
					"ADMIN_EMAIL_ADDRESS" => $mfhclass->input->post_vars['email_address'],
				);
				
				$mfhclass->templ->output("home", "forum_created_page");
			}
			break;
		case "rules":
			$mfhclass->templ->page_title = "{$mfhclass->info->config['site_name']} &raquo; Terms of Service";

			$mfhclass->templ->templ_vars[] = array(
				"MODIFICATION_TIME" => date($mfhclass->info->config['date_format'], filemtime("{$mfhclass->info->root_path}index.php")),
				"SITE_NAME"         => $mfhclass->info->config['site_name'],
				"EMAIL_OUT"         => $mfhclass->info->config['email_out'],
			); 
		
			$mfhclass->templ->output("home", "terms_of_service_page");
			break;
		case "directory":
			$mfhclass->templ->page_title = "{$mfhclass->info->config['site_name']} &raquo; Forum Directory";

			$sql = $mfhclass->db->query("SELECT * FROM `mfh_directory_categories` ORDER BY `category_name` ASC;");
			while ($row = $mfhclass->db->fetch_array($sql)) {
				$mfhclass->templ->templ_globals['get_whileloop'] = true;
			
				$mfhclass->templ->templ_vars[] = array(
					"TRCLASS"       => $trclass = (($trclass == "row1") ? "row2" : "row1"),
					"CATEGORY_ID"   => $row['category_id'],
					"CATEGORY_NAME" => $row['category_name'],
					"TOTAL_FORUMS"  => $mfhclass->funcs->format_number($mfhclass->db->total_rows($mfhclass->db->query("SELECT * FROM `mfh_hosted_forums` WHERE `category_id` = '{$row['category_id']}';"))),
				);
			
				$mfhclass->templ->templ_globals['directory_category_whileloop'] .= $mfhclass->templ->parse_template("home", "directory_index_page");
			
				unset($mfhclass->templ->templ_vars, $mfhclass->templ->templ_globals['get_whileloop']);
			}
			
			$mfhclass->templ->output("home", "directory_index_page");
			break;
		case "directory-vc":
			$mfhclass->templ->page_title = "{$mfhclass->info->config['site_name']} &raquo; Forum Directory &raquo; Viewing Category";

			$sql = $mfhclass->db->query("SELECT * FROM `mfh_hosted_forums` WHERE `category_id` = '{$mfhclass->input->get_vars['cat']}' ORDER BY `total_hits` DESC LIMIT <# QUERY_LIMIT #>;");
			if ($mfhclass->db->total_rows($sql) < 1 || $mfhclass->input->get_vars['cat'] == -1) {
				$mfhclass->templ->error("Category is empty or doesn't exist.", true);
			} else {
				while ($row = $mfhclass->db->fetch_array($sql)) {
					$forum_name = $mfhclass->db->fetch_array($mfhclass->db->query("SELECT * FROM `{$row['access_name']}_config` WHERE `config_name` = 'sitename';", $row['database_id']));

					$mfhclass->templ->templ_globals['get_whileloop'] = true;
			
					$mfhclass->templ->templ_vars[] = array(
						"TRCLASS"            => $trclass = (($trclass == "row1") ? "row2" : "row1"),
						"FORUM_NAME"         => $forum_name['config_value'],
						"TOTAL_HITS"         => $row['total_hits'],
						"HUMAN_TOTAL_HITS"   => $mfhclass->funcs->format_number($row['total_hits']),
						"ACCESS_NAME"        => $row['access_name'],
						"BASE_URL"           => $mfhclass->info->base_url,
						"DATE_CREATED"       => date($mfhclass->info->config['date_format'], $row['time_started']),
						"TOTAL_MEMBERS"      => $mfhclass->funcs->format_number(($mfhclass->db->total_rows($mfhclass->db->query("SELECT * FROM `{$row['access_name']}_users`;", $row['database_id'])) - $mfhclass->db->total_rows($mfhclass->db->query("SELECT * FROM `{$row['access_name']}_bots`;", $row['database_id']))) - 1),
					);
			
					$mfhclass->templ->templ_globals['category_listing_whileloop'] .= $mfhclass->templ->parse_template("home", "directory_view_category_page");
			
					unset($mfhclass->templ->templ_vars, $mfhclass->templ->templ_globals['get_whileloop']);
				}
			}
			
			$mfhclass->templ->templ_vars[] = array(
				"PAGINATION_LINKS"   => $mfhclass->templ->pagelinks("index.php?act=directory-vc&cat={$mfhclass->input->get_vars['cat']}", $mfhclass->db->total_rows($mfhclass->db->query("SELECT * FROM `mfh_hosted_forums` WHERE `category_id` = '{$mfhclass->input->get_vars['cat']}' ORDER BY `total_hits` DESC;"))),
			);
			
			$mfhclass->templ->output("home", "directory_view_category_page");
			break;
		case "contact_us":
			$mfhclass->templ->page_title = "{$mfhclass->info->config['site_name']} &raquo; Contact {$mfhclass->info->config['site_name']}";
			
			$mfhclass->templ->templ_vars[] = array(
				"SITE_NAME" => $mfhclass->info->config['site_name'],
			); 
		
			$mfhclass->templ->output("home", "contact_us_page");
		case "contact_us-s":
			$mfhclass->templ->page_title = "{$mfhclass->info->config['site_name']} &raquo; Contact {$mfhclass->info->config['site_name']}";
			
			if ($mfhclass->funcs->is_null($mfhclass->input->post_vars['email_address']) == true || $mfhclass->funcs->is_null($mfhclass->input->post_vars['message_body']) == true || $mfhclass->funcs->is_null($mfhclass->input->post_vars['full_name']) == true) {
				$mfhclass->templ->error("Please ensure that all required fields of the form on the previous page had been filled in correctly.", true);
			} elseif ($mfhclass->funcs->valid_email($mfhclass->input->post_vars['email_address']) == false) {
				$mfhclass->templ->error("Please ensure that the email address you entered is valid.", true);
			} else {
				$mfhclass->templ->templ_vars[] = array(
					"SITE_NAME"     => $mfhclass->info->config['site_name'],
					"FULL_NAME"     => $mfhclass->input->post_vars['full_name'],
					"EMAIL_ADDRESS" => $mfhclass->input->post_vars['email_address'],
					"EMAIL_BODY"    => strip_tags(str_replace("<br />", "\n", $mfhclass->input->post_vars['message_body'])),
				);
				
				$message_body = $mfhclass->templ->parse_template("home", "contact_us_email"); 
	
				if (mail($mfhclass->info->config['email_out'], "Site Contact ({$mfhclass->info->config['site_name']})", $message_body, "From: {$mfhclass->info->config['site_name']} <{$mfhclass->info->config['email_out']}>") == true) {
					$mfhclass->templ->success("The {$mfhclass->info->config['site_name']} team has been successfully contacted. <br /><br /> <a href=\"index.php\">Site Index</a>", true);
				} else {
					$mfhclass->templ->error("Failed to send email due to an unknown problem.", true);
				}
			}
		default:
			$mfhclass->templ->templ_vars[] = array(
				"SITE_NAME" => $mfhclass->info->config['site_name'],
			); 
		
			$mfhclass->templ->output("home", "index_intro_page");
	}

	$mfhclass->templ->output();	

?>
