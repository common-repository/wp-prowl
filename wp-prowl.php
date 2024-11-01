<?php
/*
PLUGIN NAME: wp-prowl
PLUGIN URI: http://blog.milkandtang.com/projects/wp-prowl
DESCRIPTION: A plugin for interfacing your Wordpress with Prowl, an application for receiving custom push notifications on your iPhone.
AUTHOR: Nathan Wittstock
AUTHOR URI: http://milkandtang.com/
VERSION: 0.8.5
*/

/*
    Copyright 2009 Nathan Wittstock (email: nate at milkandtang dot com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * TODO (Important):
 * Improve delay code so we're checking vs. revisions, and not just original post date. will be some work.
 * Rewrite comment/trackback code to newer, smarter code i wrote for pages. actually, combine the two. yeah.
 * WPMU Support. I have no idea where to start. Will look into it.
 *
 * Best Idea ever: serious overhaul allowing individual notifications to be customized for
 * individual API keys, each with their own format, allowing new notification types and events
 * to be added easily. Oh man best idea ever. Also, get less lazy. Yes that first.
 **
 * TODO (Low Priority):
 * Daily Blog Stats?
 **/
	
global $wpp_options;
global $wpp_curl_present;
global $wpp_apikey;
global $wpp_version;

function wpprowl_init_translation()
{	
	load_plugin_textdomain('wp-prowl', PLUGINDIR.'/wp-prowl/translation');
}

function wpprowl() {
	global $wpp_curl_present;
	global $wpp_apikey;
	global $wpp_version;
	
	add_action('init', 'wpprowl_init_translation');

	// Add settings menu
	add_action('admin_menu', 'wpprowl_menu');
	
	// Add my other actions
	//add_action('publish_post', 'wpprowl_post', 99);
	//add_action('publish_page', 'wpprowl_post', 99);
	add_action('save_post', 'wpprowl_post', 99);
	add_action('comment_post', 'wpprowl_comment', 99);
	
	// add / remove settings on activation/deactivation
	register_deactivation_hook(__FILE__, 'wpprowl_deactivation');
	register_activation_hook(__FILE__, 'wpprowl_activation');
	
	$wpp_version  = "0.8.5";
	$wpp_curl_present = wpprowl_checkCurl();
	$wpp_apikey = get_option('wpprowl_apikey');
	
	$stored_version = get_option('wpprowl_version');
	if($stored_version != $wpp_version && !empty($stored_version)) { //need to run an upgrade
		$spam = get_option('wpprowl_commentsendspam');
		if($spam == 'yes') update_option('wpprowl_commentsendspam', '2');
		else if (empty($spam)) update_option('wpprowl_commentsendspam', '1');
		
		update_option('wpprowl_version', $wpp_version);
		wpprowl_activation();
	}
}

function wpprowl_menu() {
	add_options_page(__('WP-Prowl Configuration', 'wp-prowl'), 'WP-Prowl', 8, 'wp-prowl', 'wpprowl_options_panel');
}

function wpprowl_activation() {
	global $wpp_version;
	
	add_option('wpprowl_apikey', '');
	add_option('wpprowl_oncomment', 'yes');
	add_option('wpprowl_commentformat', __('From: %a(%e)\nRe: %t\n%c', 'wp-prowl'));
	add_option('wpprowl_commentpriority', '0');
	add_option('wpprowl_commentsendspam', '1');
	add_option('wpprowl_ontrackback', 'yes');
	add_option('wpprowl_trackbackformat', __('From: %a(%s)\nRe: %t\n%c', 'wp-prowl'));
	add_option('wpprowl_trackbackpriority', '0');
	add_option('wpprowl_onpost', 'yes');
	add_option('wpprowl_postformat', __('Author: %a\n%t', 'wp-prowl'));
	add_option('wpprowl_postpriority', '0');
	add_option('wpprowl_onpage', 'yes');
	add_option('wpprowl_pageformat', __('Author: %a\n%t', 'wp-prowl'));
	add_option('wpprowl_pagepriority', '0');
	add_option('wpprowl_onreview', 'yes');
	add_option('wpprowl_reviewformat', __('Author: %a\n%t', 'wp-prowl'));
	add_option('wpprowl_reviewpriority', '0');
	add_option('wpprowl_pagedelay', '5');
	add_option('wpprowl_version', $wpp_version);
}

function wpprowl_deactivation() {
	delete_option('wpprowl_apikey');
	delete_option('wpprowl_oncomment');
	delete_option('wpprowl_commentformat');
	delete_option('wpprowl_commentpriority');
	delete_option('wpprowl_commentsendspam');
	delete_option('wpprowl_ontrackback');
	delete_option('wpprowl_trackbackformat');
	delete_option('wpprowl_trackbackpriority');
	delete_option('wpprowl_onpost');
	delete_option('wpprowl_postformat');
	delete_option('wpprowl_postpriority');
	delete_option('wpprowl_onpage');
	delete_option('wpprowl_pageformat');
	delete_option('wpprowl_pagepriority');
	delete_option('wpprowl_onreview');
	delete_option('wpprowl_reviewformat');
	delete_option('wpprowl_reviewpriority');
	delete_option('wpprowl_pagedelay');
	delete_option('wpprowl_version');
}

function wpprowl_post($post_id) {
	global $wpp_options;
	
	$post = get_post($post_id);
    
	if($post->post_status == 'pending' || $post->post_status == 'publish') {
		
		$delay = (int)get_option('wpprowl_pagedelay');
		$post_date = strtotime($post->post_date);
		$post_modified = strtotime($post->post_modified);
		$debug = "Delay time: ".($delay * 60)." :: Posted Time: $post_date :: Modified Time: $post_modified";
		
		if(($post_date+$delay*60) > $post_modified && $post_date != $post_modified) return;
		
		if($post->post_status == 'pending' && get_option('wpprowl_onreview') == 'yes') {
			switch($post->post_type) {
				case 'page':
					$action = __('Page Pending Review', 'wp-prowl');
					$format = get_option('wpprowl_pageformat');
					$priority = get_option('wpprowl_reviewpriority');
					break;
				case 'post':
					$action = __('Post Pending Reivew', 'wp-prowl');
					$format = get_option('wpprowl_postformat');
					break;
				default:
					return;
			}
		}
		else if($post->post_status == 'publish' && $post->post_type == 'page' && get_option('wpprowl_onpage') == 'yes') {
			$action = __('New Page', 'wp-prowl');
			$format = get_option('wpprowl_pageformat');
			$priority = get_option('wpprowl_pagepriority');
		}
		else if($post->post_status == 'publish' && $post->post_type == 'post' && get_option('wpprowl_onpost') == 'yes') {
			$action = __('New Post', 'wp-prowl');
			$format = get_option('wpprowl_postformat');
			$priority = get_option('wpprowl_postpriority');
		}
		else return;

		$author = get_userdata($post->post_author);
		
		$search = array(
			'%a',
			'%e',
			'%t',
			'%f',
			'%l',
			'%h',
			'%u',
			'%d');
		$replace = array(
			$author->user_nicename,
			$author->user_email,
			$post->post_title,
			$author->first_name,
			$author->last_name,
			$author->nickname,
			get_permalink($post->ID),
			$debug);
		
		if(empty($format)) $format = __('Author: %a\n%t', 'wp-prowl');
		wpprowl_sendprowl($action, str_replace($search, $replace, $format), $priority);
	}
	
}

function wpprowl_comment($comment_id) {
	global $wpp_options;
	global $recaptcha_saved_error;
	
	//The following is to split the first 4 words...
	/* $titleSnipped = preg_match('/^([^.!?\s]*[\.!?\s]+){0,4}/', strip_tags($news[0]), $title);
	$title = substr($title[0], 0, strlen($title[0])-1);
	if($titleSnipped) $title = $title."...";*/
	
	if($recaptcha_saved_error) return; //ignore failed reCAPTCHA comments
	
	$comment = get_comment($comment_id);
	$post = get_post($comment->comment_post_ID);
			
	$approval = $comment->comment_approved;
	if(strpos($comment->comment_approved, 'spam') === 0) { $approval = 2; }
		
	switch($approval) {
		case 0:
			$approval = __('Pending', 'wp-prowl');
			$comment->is_approved = 0;
			break;
		case 1:
			$approval = __('Approved', 'wp-prowl');
			$comment->is_approved = 2;
			break;
		case 2:
			$approval = __('Spam', 'wp-prowl');
			$comment->is_approved = 1;
			break;
		default:
			$approval = __('Status Unknown', 'wp-prowl');
			$comment->is_approved = 3;
			break;
	}
	
	//Send all notifications: 2
	//Send all, but not spam: 1
	//Send all, but not unapproved and spam: 0
	
	$spam = (int) get_option('wpprowl_commentsendspam');
	if($spam == 1 && $comment->is_approved == 1) return;
	if($spam == 0 && $comment->is_approved < 2) return;
	
	$search = array(
		'%a',
		'%e',
		'%t',
		'%c',
		'%s',
		'%n',
		'%i',
		'%p',
		'%u');
	$replace = array(
		$comment->comment_author,
		$comment->comment_author_email,
		$post->post_title,
		$comment->comment_content,
		$comment->comment_author_url,
		$post->comment_count,
		$comment->comment_author_IP,
		$approval,
		get_permalink($post->ID));
	
	if ($comment->comment_type == 'trackback' || $comment->comment_type == 'pingback') {
		if(get_option('wpprowl_ontrackback') != 'yes') return;
		$format = get_option('wpprowl_trackbackformat');
		if(empty($format)) $format = __('From: %a(%s)\nRe: %t\n%c', 'wp-prowl');
		wpprowl_sendprowl(__('New Ping/Trackback', 'wp-prowl'), str_replace($search, $replace, $format), get_option('wpprowl_trackbackpriority'));		
	} 
	else {
		if(get_option('wpprowl_oncomment') != 'yes') return;
		$format = get_option('wpprowl_commentformat');
		if(empty($format)) $format = __('From: %a(%e)\nRe: %t\n%c', 'wp-prowl');
		wpprowl_sendprowl(__('New Comment', 'wp-prowl'), str_replace($search, $replace, $format), get_option('wpprowl_commentpriority'));
	}
}

function wpprowl_sendprowl($event, $description, $priority = 0, $application = "") {
	global $wpp_options;
	global $wpp_curl_present;
	global $wpp_apikey;
	
	if(!$wpp_curl_present) return;
	if(empty($wpp_apikey)) return;
	if(empty($application)) $application = get_bloginfo('name');
	
	require_once('ProwlPHP.php');
	$apikeys = explode(';', $wpp_apikey);
	foreach ($apikeys as $apikey) {
		$prowl = new Prowl($apikey);
		$prowl->push(array(
	            'application'=>$application,
	            'event'=>$event,
	            'description'=>wpprowl_cleanupmsg($description),
	            'priority'=>$priority,
			),true);
	}
	      
}

function wpprowl_cleanupmsg($string) {
	return strip_tags(str_replace("\r","\n", str_replace("\r\n","\n", $string)));
}

function wpprowl_options_panel() { 
	global $wpp_options;
	global $wpp_curl_present;
	global $wpp_apikey;
	global $wpp_version;
	
	if($_GET['updated']=="true" && !empty($wpp_apikey)) {
		$verification = "";
		$error = false;
		require_once('ProwlPHP.php');
		$apikeys = explode(';', $wpp_apikey);
		foreach ($apikeys as $apikey) {
			$prowl = new Prowl();
			if($prowl->verify($apikey, null)) 
				$verification .= sprintf(__('%s Verified Successfully!', 'wp-prowl'), $apikey).'<br/>';
			else {
				$verification .= sprintf(__('%s <strong>DID NOT</strong> verify successfully...', 'wp-prowl'), $apikey).'<br/>';
				$error = true;
			}
		}
		$updated = "updated";
		if ($error) $updated = "error"; 
		echo "<div class=\"$updated\"><strong>".__('API Key Status', 'wp-prowl').":</strong><br/>$verification</div>";
	}
	
	?>
	<link rel="stylesheet" type="text/css" href="../<?php echo PLUGINDIR;?>/wp-prowl/style.css" />
	<div class="wrap">
	<h2>WP-Prowl</h2>
	<?php 
		if(!$wpp_curl_present) { ?>
		
		<div class="error">
			<b><?php _e('WARNING','wp-prowl'); ?>:</b>
			<p><?php _e('There\'s a problem with your webserver configuration that will stop WP-Prowl from functioning.<br/>
			The cURL library is missing vital functions or does not support SSL. cURL w/SSL is required to execute ProwlPHP.<br/>
			You\'ll need to enable cURL w/SSL support on your webserver. Speak to your hosting provider if this is confusing.', 'wp-prowl'); ?></p>
		</div>
		<?php } ?>
	
	<div id="wpp_donate">
		<?php _e('<strong>You seem like a nice person...</strong> If you like WP-Prowl, please cosider making a donation. I accept monies, kind words, blog mentions, happy thoughts&mdash;whatever you can manage!','wp-prowl'); ?>
		<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
			<input type="hidden" name="cmd" value="_s-xclick">
			<input type="hidden" name="hosted_button_id" value="7648647">
			<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
			<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
		</form>
	</div>	
	<?php _e('<p>A super-duper <a href="http://prowl.weks.net" target="_blank">Prowl</a> integration plugin for your Wordpress.</p>
	<p>To get started, you\'ll need to enter your Prowl API key, which can be obtained in the &ldquo;Settings&rdquo; Section of the <a href="http://prowl.weks.net">Prowl Website</a>, after you log in with your username and password.</p>
	<p>Once you have your API key entered, select the checkboxes for the notifications you\'d like to receive. The notification text can be customized  by entering your own format in the &ldquo;Format&rdquo; section. Insert the approprate code into the format where you\'d like that information to occur (e.g. <strong>%a</strong> for <em>author</em>). All notifications will automatically be prepended with their type (e.g. &ldquo;New Comment&rdquo;), so you can leave that part off.</p>
	<p>Please send all questions, comments, translations, and bug reports to <a href="mailto:nate@milkandtang.com">nate@milkandtang.com</a>.</p>', 'wp-prowl'); ?>
	<hr/>
	<form method="post" action="./options.php">
	
	<?php wp_nonce_field('update-options'); ?>
	
	<table class="form-table">
	
	<tr valign="top">
		<th scope="row"><?php _e('API key:', 'wp-prowl'); ?></th>
		<td><input size="40" type="text" name="wpprowl_apikey" value="<?php echo $wpp_apikey; ?>" /><br/>
		<?php _e('Separate Multiple API Keys with a semicolon (;)', 'wp-prowl'); ?></td>
	</tr>
	<tr valign="top"><th scope="row">Notifications:
	<div class="wpp_key">
		<h4><?php _e('All Types', 'wp-prowl'); ?></h4>
		<ul>
			<li>%a = <?php _e('Author', 'wp-prowl'); ?></li>
			<li>%e = <?php _e('Author Email', 'wp-prowl'); ?></li>
			<li>%t = <?php _e('Post Title', 'wp-prowl'); ?></li>
			<li>%u = <?php _e('Link to Post', 'wp-prowl'); ?></li>
			<li>\n = <?php _e('Line break', 'wp-prowl'); ?></li>
		</ul>
		<h4><?php _e('Comments', 'wp-prowl'); ?></h4>
		<ul>
			<li>%c = <?php _e('Comment Content', 'wp-prowl'); ?></li>
			<li>%p = <?php _e('Approval Status', 'wp-prowl'); ?></li>
		</ul>
		<h4><?php _e('Comments/Trackbacks', 'wp-prowl'); ?></h4>
		<ul>
			<li>%s = <?php _e('Author URL', 'wp-prowl'); ?></li>
			<li>%n = <?php _e('Number of Comments', 'wp-prowl'); ?></li>
			<li>%i = <?php _e('Author IP', 'wp-prowl'); ?></li>
		</ul>
		<h4><?php _e('Posts/Pages', 'wp-prowl'); ?></h4>
		<ul>
			<li>%f = <?php _e('First Name', 'wp-prowl'); ?></li>
			<li>%l = <?php _e('Last Name', 'wp-prowl'); ?></li>
			<li>%h = <?php _e('Author Nickname', 'wp-prowl'); ?></li>
		</ul>
	</div></th><td>
	<table class="wpp_table">
	<tr><th scope="col">&nbsp;</th><th scope="col">On</th><th scope="col"><?php _e('Format', 'wp-prowl'); ?></th><th scope="col"><?php _e('Priority', 'wp-prowl'); ?></th></tr>
	<tr class="wpp_odd">
		<th scope="row"><?php _e('Comments', 'wp-prowl'); ?></th>
		<td><input type="checkbox" name="wpprowl_oncomment" value="yes" <?php if(get_option('wpprowl_oncomment') == "yes") { echo 'checked="checked"'; }?> /></td>
		<td><input size="30" type="text" name="wpprowl_commentformat" value="<?php echo get_option('wpprowl_commentformat'); ?>" /></td>
		<td><?php echo wpprowl_prioritySelect("wpprowl_commentpriority", get_option('wpprowl_commentpriority')); ?></td>
	</tr>
	<tr class="wpp_even">
		<th scope="row"><?php _e('Trackback/Pings', 'wp-prowl'); ?></th>
		<td><input type="checkbox" name="wpprowl_ontrackback" value="yes" <?php if(get_option('wpprowl_ontrackback') == "yes") { echo 'checked="checked"'; }?> /></td>
		<td><input size="30" type="text" name="wpprowl_trackbackformat" value="<?php echo get_option('wpprowl_trackbackformat'); ?>" /></td>
		<td><?php echo wpprowl_prioritySelect("wpprowl_trackbackpriority", get_option('wpprowl_trackbackpriority')); ?></td>
	</tr>
	<tr class="wpp_odd">
		<th scope="row"><?php _e('Posts', 'wp-prowl'); ?></th>
		<td><input type="checkbox" name="wpprowl_onpost" value="yes" <?php if(get_option('wpprowl_onpost') == "yes") { echo 'checked="checked"'; }?> /></td>
		<td><input size="30" type="text" name="wpprowl_postformat" value="<?php echo get_option('wpprowl_postformat'); ?>" /></td>
		<td><?php echo wpprowl_prioritySelect("wpprowl_postpriority", get_option('wpprowl_postpriority')); ?></td>
	</tr>
	<tr class="wpp_even">
		<th scope="row"><?php _e('Pages', 'wp-prowl'); ?></th>
		<td><input type="checkbox" name="wpprowl_onpage" value="yes" <?php if(get_option('wpprowl_onpage') == "yes") { echo 'checked="checked"'; }?> /></td>
		<td><input size="30" type="text" name="wpprowl_pageformat" value="<?php echo get_option('wpprowl_pageformat'); ?>" /></td>
		<td><?php echo wpprowl_prioritySelect("wpprowl_pagepriority", get_option('wpprowl_pagepriority')); ?></td>
	</tr>
	<tr class="wpp_even">
		<th scope="row"><?php _e('Pending Review', 'wp-prowl'); ?></th>
		<td><input type="checkbox" name="wpprowl_onreview" value="yes" <?php if(get_option('wpprowl_onreview') == "yes") { echo 'checked="checked"'; }?> /></td>
		<td><input size="30" type="text" name="wpprowl_reviewformat" value="<?php echo get_option('wpprowl_reviewformat'); ?>" /></td>
		<td><?php echo wpprowl_prioritySelect("wpprowl_reviewpriority", get_option('wpprowl_reviewpriority')); ?></td>
	</tr>
	</table></td></tr>
	<tr valign="top">
		<th scope="row"><?php _e('Spam Comments:', 'wp-prowl'); ?></th>
		<td>
			<?php $spam = get_option('wpprowl_commentsendspam'); ?>
			<input type="radio" name="wpprowl_commentsendspam" value="2" <?php if($spam == "2") { echo 'checked="checked"'; }?> />
			<?php _e('Send Notifications for all comments', 'wp-prowl'); ?><br/> 
			<input type="radio" name="wpprowl_commentsendspam" value="1" <?php if($spam == "1") { echo 'checked="checked"'; }?> />
			<?php _e('Ignore &ldquo;Spam&rdquo; Comments, but not &ldquo;Unapproved&rdquo; Comments', 'wp-prowl'); ?><br/>
			<input type="radio" name="wpprowl_commentsendspam" value="0" <?php if($spam == "0") { echo 'checked="checked"'; }?> />
			<?php _e('Ignore &ldquo;Spam&rdquo; and &ldquo;Unapproved&rdquo; Comments', 'wp-prowl'); ?>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row"><?php _e('Delay between Page Edits:', 'wp-prowl'); ?></th>
		<td>
			<input size="2" type="text" name="wpprowl_pagedelay" value="<?php echo get_option('wpprowl_pagedelay') ?>" /> <?php _e('minutes', 'wp-prowl'); ?><br/>
			<?php _e('The amount of time between notifications for page/post edits, to avoid notification spamming for multiple page edits.', 'wp-prowl'); ?>
		</td>
	</tr>
	</table>
	
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="page_options" value="wpprowl_apikey,wpprowl_oncomment,wpprowl_commentformat,wpprowl_commentpriority,wpprowl_commentsendspam,wpprowl_ontrackback,wpprowl_trackbackformat,wpprowl_trackbackpriority,wpprowl_onpost,wpprowl_postformat,wpprowl_postpriority,wpprowl_onpage,wpprowl_pageformat,wpprowl_pagepriority,wpprowl_onreview,wpprowl_reviewformat,wpprowl_reviewpriority,wpprowl_pagedelay" />
	
	<p class="submit">
		<input type="submit" class="button-primary" value="Update Settings" />
	</p>
	
	</form>
	<div id="wpp_footer">WP-Prowl v<?php echo get_option('wpprowl_version'); ?> &copy;2009 <a href="http://milkandtang.com">milkandtang</a>. <?php _e('Released under the <a href="http://www.gnu.org/licenses/gpl-2.0.html">GNU General Public Licence</a>. Huzzah.', 'wp-prowl'); ?></div>
	</div>
	<?php 
}

function wpprowl_checkCurl() {
	$curl_info = curl_version();	// Checks for cURL function and SSL version. Thanks Adrian Rollett!
	if(!function_exists('curl_exec') || empty($curl_info['ssl_version'])) return false;
	return true;
}

function wpprowl_prioritySelect($className, $selectedOption) {
	$select = "<select name='$className'>";
	for($i = -2;$i <= 2; $i++) {
		$label = $i;
		switch($i) {
			case -2: $label = __('Very Low', 'wp-prowl'); break;
			case -1: $label = __('Moderate', 'wp-prowl'); break;
			case 0:	 $label = __('Normal', 'wp-prowl'); break;
			case 1:  $label = __('High', 'wp-prowl'); break;
			case 2:  $label = __('Emergency', 'wp-prowl'); break;
			default: $label = __('Error', 'wp-prowl');
		}
		$select .= "<option value='$i'";
		if($i == $selectedOption) $select .= " selected='selected'";
		$select .= ">$label</option>";
	}
	$select .= "</select>";
	return $select;
}

wpprowl();

?>