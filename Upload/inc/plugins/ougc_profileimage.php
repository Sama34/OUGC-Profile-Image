<?php

/***************************************************************************
 *
 *   OUGC Profile Image plugin (/inc/plugins/ougc_profileimage.php)
 *	 Author: Omar Gonzalez
 *   Copyright: © 2015 Omar Gonzalez
 *   
 *   Based on: Profile Picture plugin
 *	 By: Starpaul20 (PaulBender)
 *   
 *   Website: http://omarg.me
 *
 *   Allows your users to upload a secondary avatar to show along their avatar.
 *
 ***************************************************************************
 
****************************************************************************
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// PLUGINLIBRARY
defined('PLUGINLIBRARY') or define('PLUGINLIBRARY', MYBB_ROOT.'inc/plugins/pluginlibrary.php');

// Tell MyBB when to run the hooks
if(defined('IN_ADMINCP'))
{
	$plugins->add_hook('admin_style_templates_set', create_function('&$args', 'global $lang;	isset($lang->setting_group_ougc_profileimage) or $lang->load("ougc_profileimage", true);'));

	$plugins->add_hook("admin_user_users_delete_commit", "ougc_profileimage_user_delete");
	$plugins->add_hook("admin_formcontainer_end", "ougc_profileimage_usergroup_permission");
	$plugins->add_hook("admin_user_groups_edit_commit", "ougc_profileimage_usergroup_permission_commit");
	$plugins->add_hook("admin_tools_system_health_output_chmod_list", "ougc_profileimage_chmod");
}
else
{
	$plugins->add_hook("usercp_start", "ougc_profileimage_run");
	$plugins->add_hook("usercp_menu_built", "ougc_profileimage_nav");
	$plugins->add_hook("fetch_wol_activity_end", "ougc_profileimage_online_activity");
	$plugins->add_hook("build_friendly_wol_location_end", "ougc_profileimage_online_location");
	$plugins->add_hook("modcp_do_editprofile_start", "ougc_profileimage_removal");
	$plugins->add_hook("modcp_editprofile_start", "ougc_profileimage_removal_lang");
	$plugins->add_hook("postbit_prev", "ougc_profileimage_postbit");
	$plugins->add_hook("postbit_pm", "ougc_profileimage_postbit");
	$plugins->add_hook("postbit_announcement", "ougc_profileimage_postbit");
	$plugins->add_hook("postbit", "ougc_profileimage_postbit");
	$plugins->add_hook("member_profile_end", "ougc_profileimage_profile");
	$plugins->add_hook("portal_start", "ougc_profileimage_portal");
	$plugins->add_hook("portal_announcement", "ougc_profileimage_portal_announcement");
	$plugins->add_hook("memberlist_user", "ougc_profileimage_memberlist");
	$plugins->add_hook("usercp_end", "ougc_profileimage_usercp");
	$plugins->add_hook("misc_buddypopup_start", "ougc_profileimage_buddy");

	// Neat trick for caching our custom template(s)
	global $templatelist;

	if(isset($templatelist))
	{
		$templatelist .= ',';
	}
	else
	{
		$templatelist = '';
	}

	if(THIS_SCRIPT == 'usercp.php')
	{
		$templatelist .= 'ougcprofileimage_usercp,ougcprofileimage_usercp_auto_resize_auto,ougcprofileimage_usercp_auto_resize_user,ougcprofileimage_usercp_current,ougcprofileimage_usercp_description,ougcprofileimage_usercp_nav,ougcprofileimage_usercp_remove,ougcprofileimage_usercp_upload,ougc_profileimage_usercp';
	}

	if(THIS_SCRIPT == 'private.php')
	{
		$templatelist .= 'ougcprofileimage_usercp_nav';
	}

	if(THIS_SCRIPT == 'member.php')
	{
		$templatelist .= 'ougcprofileimage_profile,ougcprofileimage_profile_description,ougcprofileimage_profile_img,ougc_profileimage_profile';
	}

	if(THIS_SCRIPT == 'modcp.php')
	{
		$templatelist .= 'ougcprofileimage_modcp,ougcprofileimage_modcp_description';
	}

	if(THIS_SCRIPT == 'showthread.php')
	{
		$templatelist .= 'ougc_profileimage_postbit';
	}

	if(THIS_SCRIPT == 'portal.php')
	{
		$templatelist .= 'ougc_profileimage_portal';
	}

	if(THIS_SCRIPT == 'memberlist.php')
	{
		$templatelist .= 'ougc_profileimage_memberlist';
	}

	if(THIS_SCRIPT == 'misc.php')
	{
		$templatelist .= 'ougc_profileimage_buddy';
	}
}

// The information that shows up on the plugin manager
function ougc_profileimage_info()
{
	global $lang;
	isset($lang->setting_group_ougc_profileimage) or $lang->load("ougc_profileimage", true);

	return array(
		"name"				=> 'OUGC Profile Image',
		"description"		=> $lang->setting_group_ougc_profileimage_desc,
		"website"			=> "http://galaxiesrealm.com/index.php",
		"author"			=> "Starpaul20",
		"authorsite"		=> "http://galaxiesrealm.com/index.php",
		"version"			=> "1.0",
		"codename"			=> "ougc_profileimage",
		"compatibility"		=> "18*"
	);
}

// This function runs when the plugin is installed.
function ougc_profileimage_install()
{
	global $db, $cache;
	ougc_profileimage_uninstall();

	$db->add_column("users", "ougc_profileimage", "varchar(200) NOT NULL default ''");
	$db->add_column("users", "ougc_profileimage_dimensions", "varchar(10) NOT NULL default ''");
	$db->add_column("users", "ougc_profileimage_type", "varchar(10) NOT NULL default ''");
	$db->add_column("users", "ougc_profileimage_description", "varchar(255) NOT NULL default ''");

	$db->add_column("usergroups", "ougc_profileimage_canuse", "tinyint(1) NOT NULL default '1'");
	$db->add_column("usergroups", "ougc_profileimage_canupload", "tinyint(1) NOT NULL default '1'");
	$db->add_column("usergroups", "ougc_profileimage_maxsize", "int unsigned NOT NULL default '40'");
	$db->add_column("usergroups", "ougc_profileimage_maxdimensions", "varchar(10) NOT NULL default '200x200'");

	$cache->update_usergroups();
}

// Checks to make sure plugin is installed
function ougc_profileimage_is_installed()
{
	global $db;
	if($db->field_exists("ougc_profileimage", "users"))
	{
		return true;
	}
	return false;
}

// This function runs when the plugin is uninstalled.
function ougc_profileimage_uninstall()
{
	global $db, $cache;
	$PL or require_once PLUGINLIBRARY;

	if($db->field_exists("ougc_profileimage", "users"))
	{
		$db->drop_column("users", "ougc_profileimage");
	}

	if($db->field_exists("ougc_profileimage_dimensions", "users"))
	{
		$db->drop_column("users", "ougc_profileimage_dimensions");
	}

	if($db->field_exists("ougc_profileimage_type", "users"))
	{
		$db->drop_column("users", "ougc_profileimage_type");
	}

	if($db->field_exists("ougc_profileimage_description", "users"))
	{
		$db->drop_column("users", "ougc_profileimage_description");
	}

	if($db->field_exists("ougc_profileimage_canuse", "usergroups"))
	{
		$db->drop_column("usergroups", "ougc_profileimage_canuse");
	}

	if($db->field_exists("ougc_profileimage_canupload", "usergroups"))
	{
		$db->drop_column("usergroups", "ougc_profileimage_canupload");
	}

	if($db->field_exists("ougc_profileimage_maxsize", "usergroups"))
	{
		$db->drop_column("usergroups", "ougc_profileimage_maxsize");
	}

	if($db->field_exists("ougc_profileimage_maxdimensions", "usergroups"))
	{
		$db->drop_column("usergroups", "ougc_profileimage_maxdimensions");
	}

	$cache->update_usergroups();

	$PL->settings_delete('ougc_profileimage');
	$PL->templates_delete('ougcprofileimage');

	// Delete version from cache
	$plugins = (array)$cache->read('ougc_plugins');

	if(isset($plugins['profileimage']))
	{
		unset($plugins['profileimage']);
	}

	if(!empty($plugins))
	{
		$cache->update('ougc_plugins', $plugins);
	}
	else
	{
		$cache->delete('ougc_plugins');
	}
}

// This function runs when the plugin is activated.
function ougc_profileimage_activate()
{
	global $db, $PL, $cache, $lang;
	isset($lang->setting_group_ougc_profileimage) or $lang->load("ougc_profileimage", true);
	$PL or require_once PLUGINLIBRARY;

	// Add settings group
	$PL->settings('ougc_profileimage', $lang->setting_group_ougc_profileimage, $lang->setting_group_ougc_profileimage_desc, array(
		'uploadpath'		=> array(
		   'title'			=> 'Profile Images Upload Path',
		   'description'	=> 'This is the path where profile images will be uploaded to. This directory <strong>must be chmod 777</strong> (writable) for uploads to work.',
		   'optionscode'	=> 'text',
		   'value'			=> './uploads/ougc_profileimages'
		),
		'resizing'		=> array(
		   'title'			=> 'Profile Images Resizing Mode',
		   'description'	=> 'If you wish to automatically resize all large profile images, provide users the option of resizing their profile image, or not resize profile images at all you can change this setting.',
		   'optionscode'	=> 'select
auto=Automatically resize large profile images
user=Give users the choice of resizing large profile images
disabled=Disable this feature',
		   'value'			=> 'auto'
		),
		'description'		=> array(
		   'title'			=> 'Profile Images Description',
		   'description'	=> 'If you wish allow your users to enter an optional description for their profile image, set this option to yes.',
		   'optionscode'	=> 'yesno',
		   'value'			=> 1
		),
		'rating'		=> array(
		   'title'			=> 'Gravatar Rating',
		   'description'	=> 'Allows you to set the maximum rating for Gravatars if a user chooses to use one. If a user profile image is higher than this rating no profile image will be used. The ratings are:
<ul>
<li><strong>G</strong>: suitable for display on all websites with any audience type</li>
<li><strong>PG</strong>: may contain rude gestures, provocatively dressed individuals, the lesser swear words or mild violence</li>
<li><strong>R</strong>: may contain such things as harsh profanity, intense violence, nudity or hard drug use</li>
<li><strong>X</strong>: may contain hardcore sexual imagery or extremely disturbing violence</li>
</ul>',
		   'optionscode'	=> 'select
g=G
pg=PG
r=R
x=X',
		   'value'			=> 'g'
		),
	));

	// Add template group
	$PL->templates('ougcprofileimage', '<lang:setting_group_ougc_profileimage>', array(
		'usercp' => '<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->change_ougc_profileimageture}</title>
{$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
<tr>
	{$usercpnav}
	<td valign="top">
		{$ougc_profileimage_error}
		<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
			<tr>
				<td class="thead" colspan="2"><strong>{$lang->change_ougc_profileimageture}</strong></td>
			</tr>
			<tr>
				<td class="trow1" colspan="2">
					<table cellspacing="0" cellpadding="0" width="100%">
						<tr>
							<td>{$lang->ougc_profileimage_note}{$ougc_profileimagemsg}
							{$current_image}
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td class="tcat" colspan="2"><strong>{$lang->custom_profile_pic}</strong></td>
			</tr>
			<form enctype="multipart/form-data" action="usercp.php" method="post">
			<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
			<input type="hidden" name="action" value="ougc_profileimage" />
			{$ougc_profileimageupload}
			<tr>
				<td class="trow2" width="40%">
					<strong>{$lang->ougc_profileimage_url}</strong>
					<br /><span class="smalltext">{$lang->ougc_profileimage_url_note}</span>
				</td>
				<td class="trow2" width="60%">
					<input type="text" class="textbox" name="ougc_profileimageurl" size="45" value="{$ougc_profileimageurl}" />
					<br /><span class="smalltext">{$lang->ougc_profileimage_url_gravatar}</span>
				</td>
			</tr>
			{$ougc_profileimage_description}
		</table>
		<br />
		<div align="center">
			<input type="submit" class="button" name="submit" value="{$lang->change_image}" />
			{$removeougc_profileimageture}
		</div>
	</td>
</tr>
</table>
</form>
{$footer}
</body>
</html>',
		'usercp_auto_resize_auto' => '<br /><span class="smalltext">{$lang->ougc_profileimage_auto_resize_note}</span>',
		'usercp_auto_resize_user' => '<br /><span class="smalltext"><input type="checkbox" name="auto_resize" value="1" checked="checked" id="auto_resize" /> <label for="auto_resize">{$lang->ougc_profileimage_auto_resize_option}</label></span>',
		'usercp_current' => '<td width="150" align="right"><img src="{$image[\'image\']}" alt="{$lang->profile_image_mine}" title="{$lang->profile_image_mine}" width="{$image[\'width\']}" height="{$image[\'height\']}" /></td>',
		'usercp_remove' => '<input type="submit" class="button" name="remove" value="{$lang->remove_image}" />',
		'profile' => '<img src="{$image[\'image\']}" alt="" width="{$image[\'width\']}" height="{$image[\'height\']}" />',
		'portal' => '<img src="{$image[\'image\']}" alt="" width="{$image[\'width\']}" height="{$image[\'height\']}" />',
		'postbit' => '<img src="{$image[\'image\']}" alt="" width="{$image[\'width\']}" height="{$image[\'height\']}" />',
		'memberlist' => '<img src="{$image[\'image\']}" alt="" width="{$image[\'width\']}" height="{$image[\'height\']}" />',
		'usercp_avatar' => '<img src="{$image[\'image\']}" alt="" width="{$image[\'width\']}" height="{$image[\'height\']}" />',
		'buddy' => '<img src="{$image[\'image\']}" alt="" width="{$image[\'width\']}" height="{$image[\'height\']}" />',

		'usercp_description' => '<tr>
	<td class="trow1" width="40%">
		<strong>{$lang->ougc_profileimage_description}</strong>
		<br /><span class="smalltext">{$lang->ougc_profileimage_description_note}</span>
	</td>
	<td class="trow1" width="60%">
		<input type="text" class="textbox" name="ougc_profileimage_description" size="100" value="{$description}" />
	</td>
</tr>',
		'usercp_upload' => '<tr>
	<td class="trow1" width="40%">
		<strong>{$lang->ougc_profileimage_upload}</strong>
		<br /><span class="smalltext">{$lang->ougc_profileimage_upload_note}</span>
	</td>
	<td class="trow1" width="60%">
		<input type="file" name="ougc_profileimageupload" size="25" class="fileupload" />
		{$auto_resize}
	</td>
</tr>',
		'usercp_nav' => '<div><a href="usercp.php?action=ougc_profileimage" class="usercp_nav_item" style="padding-left:40px; background:url(\'images/ougc_profileimage.png\') no-repeat left center;">{$lang->ucp_nav_change_ougc_profileimage}</a></div>',
		'modcp' => '<tr><td colspan="3"><span class="smalltext"><label><input type="checkbox" class="checkbox" name="remove_ougc_profileimage" value="1" /> {$lang->remove_profile_image}</label></span></td></tr>{$ougc_profileimage_description}',
		'modcp_description' => '<tr>
<td colspan="3"><span class="smalltext">{$lang->ougc_profileimage_description}</span></td>
</tr>
<tr>
<td colspan="3"><textarea name="ougc_profileimage_description" id="ougc_profileimage_description" rows="4" cols="30">{$user[\'ougc_profileimage_description\']}</textarea></td>
</tr>',
	));

	// Insert/update version into cache
	$plugins = $cache->read('ougc_plugins');
	if(!$plugins)
	{
		$plugins = array();
	}

	$info = ougc_profileimage_info();

	if(!isset($plugins['profileimage']))
	{
		$plugins['profileimage'] = $info['versioncode'];
	}

	/*~*~* RUN UPDATES START *~*~*/

	/*~*~* RUN UPDATES END *~*~*/

	$plugins['profileimage'] = $info['versioncode'];
	$cache->update('ougc_plugins', $plugins);

	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("usercp_nav_profile", "#".preg_quote('{$changesigop}')."#i", '{$changesigop}<!-- ougc_profileimage -->');
	find_replace_templatesets("modcp_editprofile", "#".preg_quote('{$lang->remove_avatar}</label></span></td>
										</tr>')."#i", '{$lang->remove_avatar}</label></span></td>
										</tr>{$ougc_profileimage}');
}

// This function runs when the plugin is deactivated.
function ougc_profileimage_deactivate()
{
	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("usercp_nav_profile", "#".preg_quote('<!-- ougc_profileimage -->')."#i", '', 0);
	find_replace_templatesets("modcp_editprofile", "#".preg_quote('{$ougc_profileimage}')."#i", '', 0);
}

// User CP Nav link
function ougc_profileimage_nav()
{
	global $db, $mybb, $lang, $templates, $usercpnav;
	isset($lang->setting_group_ougc_profileimage) or $lang->load("ougc_profileimage");

	if($mybb->usergroup['ougc_profileimage_canuse'] == 1)
	{
		eval("\$ougc_profileimage_nav = \"".$templates->get("ougcprofileimage_usercp_nav")."\";");
		$usercpnav = str_replace("<!-- ougc_profileimage -->", $ougc_profileimage_nav, $usercpnav);
	}
}

// The UserCP profile image page
function ougc_profileimage_run()
{
	global $mybb;

	if($mybb->input['action'] == "ougc_profileimage")
	{
		global $db, $lang, $templates, $theme, $headerinclude, $usercpnav, $header, $ougc_profileimage, $footer;
		isset($lang->setting_group_ougc_profileimage) or $lang->load("ougc_profileimage");
		require_once MYBB_ROOT."inc/functions_ougc_profileimage.php";

		if($mybb->request_method == "post")
		{
			// Verify incoming POST request
			verify_post_check($mybb->get_input('my_post_key'));

			if($mybb->usergroup['ougc_profileimage_canuse'] == 0)
			{
				error_no_permission();
			}

			$ougc_profileimage_error = "";

			if(!empty($mybb->input['remove'])) // remove profile image
			{
				$updated_ougc_profileimage = array(
					"ougc_profileimage" => "",
					"ougc_profileimage_dimensions" => "",
					"ougc_profileimage_type" => "",
					"ougc_profileimage_description" => ""
				);
				$db->update_query("users", $updated_ougc_profileimage, "uid='{$mybb->user['uid']}'");
				remove_ougc_profileimage($mybb->user['uid']);
			}
			elseif($_FILES['ougc_profileimageupload']['name']) // upload profile image
			{
				if($mybb->usergroup['ougc_profileimage_canupload'] == 0)
				{
					error_no_permission();
				}

				// See if profile image description is too long
				if(my_strlen($mybb->input['ougc_profileimage_description']) > 255)
				{
					$ougc_profileimage_error = $lang->error_descriptiontoobig;
				}

				$ougc_profileimage = upload_ougc_profileimage();
				if($ougc_profileimage['error'])
				{
					$ougc_profileimage_error = $ougc_profileimage['error'];
				}
				else
				{
					if($ougc_profileimage['width'] > 0 && $ougc_profileimage['height'] > 0)
					{
						$ougc_profileimage_dimensions = $ougc_profileimage['width']."|".$ougc_profileimage['height'];
					}
					$updated_ougc_profileimage = array(
						"ougc_profileimage" => $ougc_profileimage['image'].'?dateline='.TIME_NOW,
						"ougc_profileimage_dimensions" => $ougc_profileimage_dimensions,
						"ougc_profileimage_type" => "upload",
						"ougc_profileimage_description" => $db->escape_string($mybb->input['ougc_profileimage_description'])
					);
					$db->update_query("users", $updated_ougc_profileimage, "uid='{$mybb->user['uid']}'");
				}
			}
			elseif($mybb->input['ougc_profileimageurl']) // remote profile image
			{
				$mybb->input['ougc_profileimageurl'] = trim($mybb->get_input('ougc_profileimageurl'));
				if(validate_email_format($mybb->input['ougc_profileimageurl']) != false)
				{
					// Gravatar
					$mybb->input['ougc_profileimageurl'] = my_strtolower($mybb->input['ougc_profileimageurl']);

					// If user image does not exist, or is a higher rating, use the mystery man
					$email = md5($mybb->input['ougc_profileimageurl']);

					$s = '';
					if(!$mybb->usergroup['ougc_profileimage_maxdimensions'])
					{
						$mybb->usergroup['ougc_profileimage_maxdimensions'] = '200x200'; // Hard limit of 200 if there are no limits
					}

					// Because Gravatars are square, hijack the width
					list($maxwidth, $maxheight) = explode("x", my_strtolower($mybb->usergroup['ougc_profileimage_maxdimensions']));
					$maxheight = (int)$maxwidth;

					// Rating?
					$types = array('g', 'pg', 'r', 'x');
					$rating = $mybb->settings['ougc_profileimage_rating'];

					if(!in_array($rating, $types))
					{
						$rating = 'g';
					}

					$s = "?s={$maxheight}&r={$rating}&d=mm";

					// See if profile image description is too long
					if(my_strlen($mybb->input['ougc_profileimage_description']) > 255)
					{
						$ougc_profileimage_error = $lang->error_descriptiontoobig;
					}

					$updated_avatar = array(
						"ougc_profileimage" => "http://www.gravatar.com/avatar/{$email}{$s}.jpg",
						"ougc_profileimage_dimensions" => "{$maxheight}|{$maxheight}",
						"ougc_profileimage_type" => "gravatar",
						"ougc_profileimage_description" => $db->escape_string($mybb->input['ougc_profileimage_description'])
					);

					$db->update_query("users", $updated_avatar, "uid = '{$mybb->user['uid']}'");
				}
				else
				{
					$mybb->input['ougc_profileimageurl'] = preg_replace("#script:#i", "", $mybb->input['ougc_profileimageurl']);
					$ext = get_extension($mybb->input['ougc_profileimageurl']);

					// Copy the profile image to the local server (work around remote URL access disabled for getimagesize)
					$file = fetch_remote_file($mybb->input['ougc_profileimageurl']);
					if(!$file)
					{
						$ougc_profileimage_error = $lang->error_invalidougc_profileimageurl;
					}
					else
					{
						$tmp_name = $mybb->settings['ougc_profileimage_uploadpath']."/remote_".md5(uniqid(rand(), true));
						$fp = @fopen($tmp_name, "wb");
						if(!$fp)
						{
							$ougc_profileimage_error = $lang->error_invalidougc_profileimageurl;
						}
						else
						{
							fwrite($fp, $file);
							fclose($fp);
							list($width, $height, $type) = @getimagesize($tmp_name);
							@unlink($tmp_name);
							if(!$type)
							{
								$ougc_profileimage_error = $lang->error_invalidougc_profileimageurl;
							}
						}
					}

					// See if profile image description is too long
					if(my_strlen($mybb->input['ougc_profileimage_description']) > 255)
					{
						$ougc_profileimage_error = $lang->error_descriptiontoobig;
					}

					if(empty($ougc_profileimage_error))
					{
						if($width && $height && $mybb->usergroup['ougc_profileimage_maxdimensions'] != "")
						{
							list($maxwidth, $maxheight) = explode("x", my_strtolower($mybb->usergroup['ougc_profileimage_maxdimensions']));
							if(($maxwidth && $width > $maxwidth) || ($maxheight && $height > $maxheight))
							{
								$lang->error_ougc_profileimagetoobig = $lang->sprintf($lang->error_ougc_profileimagetoobig, $maxwidth, $maxheight);
								$ougc_profileimage_error = $lang->error_ougc_profileimagetoobig;
							}
						}
					}

					if(empty($ougc_profileimage_error))
					{
						if($width > 0 && $height > 0)
						{
							$ougc_profileimage_dimensions = (int)$width."|".(int)$height;
						}
						$updated_ougc_profileimage = array(
							"ougc_profileimage" => $db->escape_string($mybb->input['ougc_profileimageurl'].'?dateline='.TIME_NOW),
							"ougc_profileimage_dimensions" => $ougc_profileimage_dimensions,
							"ougc_profileimage_type" => "remote",
							"ougc_profileimage_description" => $db->escape_string($mybb->input['ougc_profileimage_description'])
						);
						$db->update_query("users", $updated_ougc_profileimage, "uid='{$mybb->user['uid']}'");
						remove_ougc_profileimage($mybb->user['uid']);
					}
				}
			}
			else // just updating profile image description
			{
				// See if profile image description is too long
				if(my_strlen($mybb->input['ougc_profileimage_description']) > 255)
				{
					$ougc_profileimage_error = $lang->error_descriptiontoobig;
				}

				if(empty($ougc_profileimage_error))
				{
					$updated_ougc_profileimage = array(
						"ougc_profileimage_description" => $db->escape_string($mybb->input['ougc_profileimage_description'])
					);
					$db->update_query("users", $updated_ougc_profileimage, "uid='{$mybb->user['uid']}'");
				}
			}

			if(empty($ougc_profileimage_error))
			{
				redirect("usercp.php?action=ougc_profileimage", $lang->redirect_ougc_profileimageupdated);
			}
			else
			{
				$mybb->input['action'] = "ougc_profileimage";
				$ougc_profileimage_error = inline_error($ougc_profileimage_error);
			}
		}
		add_breadcrumb($lang->nav_usercp, "usercp.php");
		add_breadcrumb($lang->change_ougc_profileimageture, "usercp.php?action=ougc_profileimage");

		// Show main profile image page
		if($mybb->usergroup['ougc_profileimage_canuse'] == 0)
		{
			error_no_permission();
		}

		$ougc_profileimagemsg = $ougc_profileimageurl = '';

		if($mybb->user['ougc_profileimage_type'] == "upload" || stristr($mybb->user['ougc_profileimage'], $mybb->settings['ougc_profileimage_uploadpath']))
		{
			$ougc_profileimagemsg = "<br /><strong>".$lang->already_uploaded_ougc_profileimage."</strong>";
		}
		elseif($mybb->user['ougc_profileimage_type'] == "remote" || my_strpos(my_strtolower($mybb->user['ougc_profileimage']), "http://") !== false)
		{
			$ougc_profileimagemsg = "<br /><strong>".$lang->using_remote_ougc_profileimage."</strong>";
			$ougc_profileimageurl = htmlspecialchars_uni($mybb->user['ougc_profileimage']);
		}

		if(!empty($mybb->user['ougc_profileimage']))
		{
			$image = format_profile_image(htmlspecialchars_uni($mybb->user['ougc_profileimage']), $mybb->user['ougc_profileimage_dimensions'], '200x200');
			eval("\$current_image = \"".$templates->get("ougcprofileimage_usercp_current")."\";");
		}

		if($mybb->usergroup['ougc_profileimage_maxdimensions'] != "")
		{
			list($maxwidth, $maxheight) = explode("x", my_strtolower($mybb->usergroup['ougc_profileimage_maxdimensions']));
			$lang->ougc_profileimage_note .= "<br />".$lang->sprintf($lang->ougc_profileimage_note_dimensions, $maxwidth, $maxheight);
		}
		if($mybb->usergroup['ougc_profileimage_maxsize'])
		{
			$maxsize = get_friendly_size($mybb->usergroup['ougc_profileimage_maxsize']*1024);
			$lang->ougc_profileimage_note .= "<br />".$lang->sprintf($lang->ougc_profileimage_note_size, $maxsize);
		}

		$auto_resize = '';
		if($mybb->settings['ougc_profileimage_resizing'] == "auto")
		{
			eval("\$auto_resize = \"".$templates->get("ougcprofileimage_usercp_auto_resize_auto")."\";");
		}
		else if($mybb->settings['ougc_profileimage_resizing'] == "user")
		{
			eval("\$auto_resize = \"".$templates->get("ougcprofileimage_usercp_auto_resize_user")."\";");
		}

		$ougc_profileimageupload = '';
		if($mybb->usergroup['ougc_profileimage_canupload'] == 1)
		{
			eval("\$ougc_profileimageupload = \"".$templates->get("ougcprofileimage_usercp_upload")."\";");
		}

		$description = htmlspecialchars_uni($mybb->user['ougc_profileimage_description']);

		$ougc_profileimage_description = '';
		if($mybb->settings['ougc_profileimage_description'] == 1)
		{
			eval("\$ougc_profileimage_description = \"".$templates->get("ougcprofileimage_usercp_description")."\";");
		}

		$removeougc_profileimageture = '';
		if(!empty($mybb->user['ougc_profileimage']))
		{
			eval("\$removeougc_profileimageture = \"".$templates->get("ougcprofileimage_usercp_remove")."\";");
		}

		if(!isset($ougc_profileimage_error))
		{
			$ougc_profileimage_error = '';
		}

		eval("\$ougc_profileimageture = \"".$templates->get("ougcprofileimage_usercp")."\";");
		output_page($ougc_profileimageture);
	}
}

// Format postbit profile image
function ougc_profileimage_postbit(&$post)
{
	ougc_profileimage_format($post, $post['ougc_profileimage'], 'postbit');
}

// Format profile image
function ougc_profileimage_profile()
{
	global $memprofile;

	ougc_profileimage_format($memprofile, $memprofile['ougc_profileimage'], 'profile');
}

// Hijack the portal query
function ougc_profileimage_portal()
{
	control_object($GLOBALS['db'], '
		function query($string, $hide_errors=0, $write_query=0)
		{
			static $done = false;
			if(!$done && !$write_query && my_strpos($string, \'t.*, t.username AS threadusername, u.username, u.avatar, u.avatardimensions\') !== false)
			{
				$done = true;
				$string = strtr($string, array(
					\'avatardimensions\' => \'avatardimensions, u.ougc_profileimage, u.ougc_profileimage_dimensions\'
				));
			}
			return parent::query($string, $hide_errors, $write_query);
		}
	');
}

// Format portal profile image
function ougc_profileimage_portal_announcement()
{
	global $announcement;

	ougc_profileimage_format($announcement, $announcement['ougc_profileimage'], 'portal');
}

// Format member list profile image
function ougc_profileimage_memberlist(&$user)
{
	ougc_profileimage_format($user, $user['ougc_profileimage'], 'memberlist');
}

// Format usercp profile image
function ougc_profileimage_usercp()
{
	global $mybb, $ougc_profileimage;

	ougc_profileimage_format($mybb->user, $ougc_profileimage, 'usercp_avatar');
}

// Format buddy list profile image
function ougc_profileimage_buddy()
{
	control_object($GLOBALS['templates'], '
		function get($title, $eslashes=1, $htmlcomments=1)
		{
			if($title == \'misc_buddypopup_user_online\' || $title == \'misc_buddypopup_user_offline\')
			{
				global $buddy;

				ougc_profileimage_format($buddy, $buddy[\\\'ougc_profileimage\\\'], \\\'buddy\\\');
			}

			return parent::get($title, $eslashes, $htmlcomments);
		}
	');
}

// Format a profile image
function ougc_profileimage_format(&$user, &$var, $tmpl)
{
	global $mybb;

	if(!$mybb->user['uid'] || isset($mybb->user['showavatars']) && $mybb->user['showavatars'])
	{
		global $templates;
		require_once MYBB_ROOT.'inc/functions_ougc_profileimage.php';

		$image = format_profile_image($user['ougc_profileimage'], $user['ougc_profileimage_dimensions']);
		eval('$var = "'.$templates->get('ougcprofileimage_'.$tmpl).'";');
	}
	else
	{
		$var = '';
	}
}

// Online location support
function ougc_profileimage_online_activity($user_activity)
{
	global $user;
	if(my_strpos($user['location'], "usercp.php?action=ougc_profileimage") !== false)
	{
		$user_activity['activity'] = "usercp_ougc_profileimage";
	}

	return $user_activity;
}

function ougc_profileimage_online_location($plugin_array)
{
	global $db, $mybb, $lang, $parameters;
	isset($lang->setting_group_ougc_profileimage) or $lang->load("ougc_profileimage");

	if($plugin_array['user_activity']['activity'] == "usercp_ougc_profileimage")
	{
		$plugin_array['location_name'] = $lang->changing_ougc_profileimage;
	}

	return $plugin_array;
}

// Mod CP removal function
function ougc_profileimage_removal()
{
	global $mybb, $db, $user;
	require_once MYBB_ROOT."inc/functions_ougc_profileimage.php";

	if($mybb->input['remove_ougc_profileimage'])
	{
		$updated_ougc_profileimage = array(
			"ougc_profileimage" => "",
			"ougc_profileimage_dimensions" => "",
			"ougc_profileimage_type" => ""
		);
		remove_ougc_profileimage($user['uid']);

		$db->update_query("users", $updated_ougc_profileimage, "uid='{$user['uid']}'");
	}

	// Update description if active
	if($mybb->settings['ougc_profileimage_description'] == 1)
	{
		$updated_ougc_profileimage = array(
			"ougc_profileimage_description" => $db->escape_string($mybb->input['ougc_profileimage_description'])
		);
		$db->update_query("users", $updated_ougc_profileimage, "uid='{$user['uid']}'");
	}
}

// Mod CP language
function ougc_profileimage_removal_lang()
{
	global $mybb, $lang, $user, $templates, $ougc_profileimage_description, $ougc_profileimage;
	isset($lang->setting_group_ougc_profileimage) or $lang->load("ougc_profileimage");

	$user['ougc_profileimage_description'] = htmlspecialchars_uni($user['ougc_profileimage_description']);

	if($mybb->settings['ougc_profileimage_description'] == 1)
	{
		eval("\$ougc_profileimage_description = \"".$templates->get("ougcprofileimage_modcp_description")."\";");
	}

	eval("\$ougc_profileimage = \"".$templates->get("ougcprofileimage_modcp")."\";");
}

// Delete profile image if user is deleted
function ougc_profileimage_user_delete()
{
	global $db, $mybb, $user;

	if($user['ougc_profileimage_type'] == "upload")
	{
		// Removes the ./ at the beginning the timestamp on the end...
		@unlink("../".substr($user['ougc_profileimage'], 2, -20));
	}
}

// Admin CP permission control
function ougc_profileimage_usergroup_permission()
{
	global $mybb, $lang, $form, $form_container, $run_module;
	isset($lang->setting_group_ougc_profileimage) or $lang->load("ougc_profileimage", true);

	if($run_module == 'user' && !empty($form_container->_title) & !empty($lang->misc) & $form_container->_title == $lang->misc)
	{
		$ougc_profileimage_options = array(
	 		$form->generate_check_box('ougc_profileimage_canuse', 1, $lang->can_use_ougc_profileimage, array("checked" => $mybb->input['ougc_profileimage_canuse'])),
			$form->generate_check_box('ougc_profileimage_canupload', 1, $lang->can_upload_ougc_profileimage, array("checked" => $mybb->input['ougc_profileimage_canupload'])),
			"{$lang->profile_pic_size}<br /><small>{$lang->profile_pic_size_desc}</small><br />".$form->generate_text_box('ougc_profileimage_maxsize', $mybb->input['ougc_profileimage_maxsize'], array('id' => 'ougc_profileimage_maxsize', 'class' => 'field50')). "KB",
			"{$lang->profile_pic_dims}<br /><small>{$lang->profile_pic_dims_desc}</small><br />".$form->generate_text_box('ougc_profileimage_maxdimensions', $mybb->input['ougc_profileimage_maxdimensions'], array('id' => 'ougc_profileimage_maxdimensions', 'class' => 'field'))
		);
		$form_container->output_row($lang->profile_image, "", "<div class=\"group_settings_bit\">".implode("</div><div class=\"group_settings_bit\">", $ougc_profileimage_options)."</div>");
	}
}

function ougc_profileimage_usergroup_permission_commit()
{
	global $db, $mybb, $updated_group;
	$updated_group['ougc_profileimage_canuse'] = (int)$mybb->input['ougc_profileimage_canuse'];
	$updated_group['ougc_profileimage_canupload'] = (int)$mybb->input['ougc_profileimage_canupload'];
	$updated_group['ougc_profileimage_maxsize'] = (int)$mybb->input['ougc_profileimage_maxsize'];
	$updated_group['ougc_profileimage_maxdimensions'] = $db->escape_string($mybb->input['ougc_profileimage_maxdimensions']);
}

// Check to see if CHMOD for profile images is writable
function ougc_profileimage_chmod()
{
	global $mybb, $lang, $table, $message_profile_image;
	isset($lang->setting_group_ougc_profileimage) or $lang->load("ougc_profileimage", true);

	if(is_writable('../'.$mybb->settings['ougc_profileimage_uploadpath']))
	{
		$message_profile_image = "<span style=\"color: green;\">{$lang->writable}</span>";
	}
	else
	{
		$message_profile_image = "<strong><span style=\"color: #C00\">{$lang->not_writable}</span></strong><br />{$lang->please_chmod_777}";
		++$errors;
	}

	$table->construct_cell("<strong>{$lang->profile_image_upload_dir}</strong>");
	$table->construct_cell($mybb->settings['ougc_profileimage_uploadpath']);
	$table->construct_cell($message_profile_image);
	$table->construct_row();
}