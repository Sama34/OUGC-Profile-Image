<?php

/***************************************************************************
 *
 *   OUGC Profile Image plugin (/inc/languages/english/ougc_profileimage.php)
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

$l['setting_group_ougc_profileimage'] = "OUGC Profile Image";
$l['setting_group_ougc_profileimage_desc'] = "Allows your users to upload a secondary avatar to show along their avatar.";

$l['profile_image'] = "Profile Image";
$l['users_ougc_profileimage'] = "{1}'s Profile Image";
$l['changing_ougc_profileimage'] = "<a href=\"usercp.php?action=ougc_profileimage\">Changing profile Image</a>";
$l['remove_profile_image'] = "Remove user's profile image?";
$l['can_use_ougc_profileimage'] = "Can use profile image?";
$l['can_upload_ougc_profileimage'] = "Can upload profile image?";
$l['profile_pic_size'] = "Maximum File Size:";
$l['profile_pic_size_desc'] = "Maximum file size of an uploaded profile image in kilobytes. If set to 0, there is no limit.";
$l['profile_pic_dims'] = "Maximum Dimensions:";
$l['profile_pic_dims_desc'] = "Maximum dimensions a profile image can be, in the format of width<strong>x</strong>height. If this is left blank then there will be no dimension restriction.";

$l['profile_image_upload_dir'] = "Profile Image Uploads Directory";

$l['nav_usercp'] = "User Control Panel";
$l['ucp_nav_change_ougc_profileimage'] = "Change Profile Image";
$l['change_ougc_profileimageture'] = "Change Profile Image";
$l['change_image'] = "Change Image";
$l['remove_image'] = "Remove Image";
$l['ougc_profileimage_url'] = "Profile Image URL:";
$l['ougc_profileimage_url_note'] = "Enter the URL of a profile image on the internet.";
$l['ougc_profileimage_url_gravatar'] = "To use a <a href=\"http://gravatar.com\" target=\"_blank\">Gravatar</a> enter your Gravatar email.";
$l['ougc_profileimage_description'] = "Profile Image Description:";
$l['ougc_profileimage_description_note'] = "(Optional) Add a brief description of your profile image.";
$l['ougc_profileimage_upload'] = "Upload Profile Image:";
$l['ougc_profileimage_upload_note'] = "Choose a profile image on your local computer to upload.";
$l['ougc_profileimage_note'] = "A profile image is a small identifying image shown in a user's profile.";
$l['ougc_profileimage_note_dimensions'] = "The maximum dimensions for profile images are: {1}x{2} pixels.";
$l['ougc_profileimage_note_size'] = "The maximum file size for profile images is {1}.";
$l['custom_profile_pic'] = "Custom Profile Image";
$l['already_uploaded_ougc_profileimage'] = "You are currently using an uploaded profile image. If you upload another one, your old one will be deleted.";
$l['ougc_profileimage_auto_resize_note'] = "If your profile image is too large, it will automatically be resized.";
$l['ougc_profileimage_auto_resize_option'] = "Try to resize my profile image if it is too large.";
$l['redirect_ougc_profileimageupdated'] = "Your profile image has been changed successfully.<br />You will now be returned to your User CP.";
$l['using_remote_ougc_profileimage'] = "You are currently using an remote profile image.";
$l['profile_image_mine'] = "This is your Profile Image";

$l['error_uploadfailed'] = "The file upload failed. Please choose a valid file and try again. ";
$l['error_ougc_profileimagetype'] = "Invalid file type. An uploaded profile image must be in GIF, JPEG, or PNG format.";
$l['error_invalidougc_profileimageurl'] = "The URL you entered for your profile image does not appear to be valid. Please ensure you enter a valid URL.";
$l['error_ougc_profileimagetoobig'] = "Sorry but we cannot change your profile image as the new image you specified is too big. The maximum dimensions are {1}x{2} (width x height)";
$l['error_ougc_profileimageresizefailed'] = "Your profile image was unable to be resized so that it is within the required dimensions.";
$l['error_ougc_profileimageuserresize'] = "You can also try checking the 'attempt to resize my profile image' check box and uploading the same image again.";
$l['error_uploadsize'] = "The size of the uploaded file is too large.";
$l['error_descriptiontoobig'] = "Your profile image description is too long. The maximum length for descriptions is 255 characters.";