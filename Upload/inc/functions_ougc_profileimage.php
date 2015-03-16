<?php

/***************************************************************************
 *
 *   OUGC Profile Image plugin (/inc/function_ougc_profileimage.php)
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

/**
 * Remove any matching profile pic for a specific user ID
 *
 * @param int The user ID
 * @param string A file name to be excluded from the removal
 */
function remove_ougc_profileimage($uid, $exclude="")
{
	global $mybb;

	if(defined('IN_ADMINCP'))
	{
		$uploadpath = '../'.$mybb->settings['ougc_profileimage_uploadpath'];
	}
	else
	{
		$uploadpath = $mybb->settings['ougc_profileimage_uploadpath'];
	}

	$dir = opendir($uploadpath);
	if($dir)
	{
		while($file = @readdir($dir))
		{
			if(preg_match("#ougc_profileimage_".$uid."\.#", $file) && is_file($uploadpath."/".$file) && $file != $exclude)
			{
				@unlink($uploadpath."/".$file);
			}
		}

		@closedir($dir);
	}
}

/**
 * Upload a new profile pic in to the file system
 *
 * @param srray incoming FILE array, if we have one - otherwise takes $_FILES['ougc_profileimageupload']
 * @param string User ID this profile pic is being uploaded for, if not the current user
 * @return array Array of errors if any, otherwise filename of successful.
 */
function upload_ougc_profileimage($image=array(), $uid=0)
{
	global $db, $mybb, $lang;

	if(!$uid)
	{
		$uid = $mybb->user['uid'];
	}

	if(!$image['name'] || !$image['tmp_name'])
	{
		$image = $_FILES['ougc_profileimageupload'];
	}

	if(!is_uploaded_file($image['tmp_name']))
	{
		$ret['error'] = $lang->error_uploadfailed;
		return $ret;
	}

	// Check we have a valid extension
	$ext = get_extension(my_strtolower($image['name']));
	if(!preg_match("#^(gif|jpg|jpeg|jpe|bmp|png)$#i", $ext)) 
	{
		$ret['error'] = $lang->error_ougc_profileimagetype;
		return $ret;
	}

	if(defined('IN_ADMINCP'))
	{
		$uploadpath = '../'.$mybb->settings['ougc_profileimage_uploadpath'];
		$lang->load("messages", true);
	}
	else
	{
		$uploadpath = $mybb->settings['ougc_profileimage_uploadpath'];
	}

	$filename = "ougc_profileimage_".$uid.".".$ext;
	$file = upload_ougc_profileimagefile($image, $uploadpath, $filename);
	if($file['error'])
	{
		@unlink($uploadpath."/".$filename);		
		$ret['error'] = $lang->error_uploadfailed;
		return $ret;
	}	

	// Lets just double check that it exists
	if(!file_exists($uploadpath."/".$filename))
	{
		$ret['error'] = $lang->error_uploadfailed;
		@unlink($uploadpath."/".$filename);
		return $ret;
	}

	// Check if this is a valid image or not
	$img_dimensions = @getimagesize($uploadpath."/".$filename);
	if(!is_array($img_dimensions))
	{
		@unlink($uploadpath."/".$filename);
		$ret['error'] = $lang->error_uploadfailed;
		return $ret;
	}

	// Check profile image dimensions
	if($mybb->usergroup['ougc_profileimage_maxdimensions'] != '')
	{
		list($maxwidth, $maxheight) = @explode("x", $mybb->usergroup['ougc_profileimage_maxdimensions']);
		if(($maxwidth && $img_dimensions[0] > $maxwidth) || ($maxheight && $img_dimensions[1] > $maxheight))
		{
			// Automatic resizing enabled?
			if($mybb->settings['ougc_profileimage_resizing'] == "auto" || ($mybb->settings['ougc_profileimage_resizing'] == "user" && $mybb->input['auto_resize'] == 1))
			{
				require_once MYBB_ROOT."inc/functions_image.php";
				$thumbnail = generate_thumbnail($uploadpath."/".$filename, $uploadpath, $filename, $maxheight, $maxwidth);
				if(!$thumbnail['filename'])
				{
					$ret['error'] = $lang->sprintf($lang->error_ougc_profileimagetoobig, $maxwidth, $maxheight);
					$ret['error'] .= "<br /><br />".$lang->error_ougc_profileimageresizefailed;
					@unlink($uploadpath."/".$filename);
					return $ret;				
				}
				else
				{
					// Reset filesize
					$image['size'] = filesize($uploadpath."/".$filename);
					// Reset dimensions
					$img_dimensions = @getimagesize($uploadpath."/".$filename);
				}
			}
			else
			{
				$ret['error'] = $lang->sprintf($lang->error_ougc_profileimagetoobig, $maxwidth, $maxheight);
				if($mybb->settings['ougc_profileimage_resizing'] == "user")
				{
					$ret['error'] .= "<br /><br />".$lang->error_ougc_profileimageuserresize;
				}
				@unlink($uploadpath."/".$filename);
				return $ret;
			}			
		}
	}

	// Next check the file size
	if($image['size'] > ($mybb->usergroup['ougc_profileimage_maxsize']*1024) && $mybb->usergroup['ougc_profileimage_maxsize'] > 0)
	{
		@unlink($uploadpath."/".$filename);
		$ret['error'] = $lang->error_uploadsize;
		return $ret;
	}	

	// Check a list of known MIME types to establish what kind of profile image we're uploading
	switch(my_strtolower($image['type']))
	{
		case "image/gif":
			$img_type =  1;
			break;
		case "image/jpeg":
		case "image/x-jpg":
		case "image/x-jpeg":
		case "image/pjpeg":
		case "image/jpg":
			$img_type = 2;
			break;
		case "image/png":
		case "image/x-png":
			$img_type = 3;
			break;
		default:
			$img_type = 0;
	}

	// Check if the uploaded file type matches the correct image type (returned by getimagesize)
	if($img_dimensions[2] != $img_type || $img_type == 0)
	{
		$ret['error'] = $lang->error_uploadfailed;
		@unlink($uploadpath."/".$filename);
		return $ret;		
	}
	// Everything is okay so lets delete old profile image for this user
	remove_ougc_profileimage($uid, $filename);

	$ret = array(
		"image" => $mybb->settings['ougc_profileimage_uploadpath']."/".$filename,
		"width" => intval($img_dimensions[0]),
		"height" => intval($img_dimensions[1])
	);
	return $ret;
}

/**
 * Actually move a file to the uploads directory
 *
 * @param array The PHP $_FILE array for the file
 * @param string The path to save the file in
 * @param string The filename for the file (if blank, current is used)
 */
function upload_ougc_profileimagefile($file, $path, $filename="")
{
	if(empty($file['name']) || $file['name'] == "none" || $file['size'] < 1)
	{
		$upload['error'] = 1;
		return $upload;
	}

	if(!$filename)
	{
		$filename = $file['name'];
	}

	$upload['original_filename'] = preg_replace("#/$#", "", $file['name']); // Make the filename safe
	$filename = preg_replace("#/$#", "", $filename); // Make the filename safe
	$moved = @move_uploaded_file($file['tmp_name'], $path."/".$filename);

	if(!$moved)
	{
		$upload['error'] = 2;
		return $upload;
	}
	@my_chmod($path."/".$filename, '0644');
	$upload['filename'] = $filename;
	$upload['path'] = $path;
	$upload['type'] = $file['type'];
	$upload['size'] = $file['size'];
	return $upload;
}

/**
 * Formats a profile image to a certain dimension
 *
 * @param string The profile image file name
 * @param string Dimensions of the profile image, width x height (e.g. 44|44)
 * @param string The maximum dimensions of the formatted profile image
 * @return array Information for the formatted profile image
 */
function format_profile_image($image, $dimensions = '', $max_dimensions = '')
{
	global $mybb;
	static $images;

	if(!isset($images))
	{
		$images = array();
	}

	if(!$image)
	{
		// Default profile image
		$image = '';
		$dimensions = '';
	}

	if(isset($images[$image]))
	{
		return $images[$image];
	}

	if(!$max_dimensions)
	{
		$max_dimensions = $mybb->usergroup['ougc_profileimage_maxdimensions'];
	}

	if($dimensions)
	{
		$dimensions = explode("|", $dimensions);

		if($dimensions[0] && $dimensions[1])
		{
			list($max_width, $max_height) = explode('x', $max_dimensions);

			if($dimensions[0] > $max_width || $dimensions[1] > $max_height)
			{
				require_once MYBB_ROOT."inc/functions_image.php";
				$scaled_dimensions = scale_image($dimensions[0], $dimensions[1], $max_width, $max_height);
				$width = (int)$scaled_dimensions['width'];
				$height = (int)$scaled_dimensions['height'];
			}
			else
			{
				$width = (int)$dimensions[0];
				$height = (int)$dimensions[1];
			}
		}
	}

	$images[$image] = array(
		'image' => $mybb->get_asset_url($image),
		'width' => $width,
		'height' => $height
	);

	return $images[$image];
}