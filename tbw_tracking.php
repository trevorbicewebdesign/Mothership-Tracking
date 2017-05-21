<?php
/*
Plugin Name:    TBWorks - Mothership Tracking
Plugin URI:     https://webdesign.trevorbice.com/
Description:    Provide tracking information to Trevor Bice Webdesign
Version:        1.1
Author:         Trevor Bice
Author URI:     https://webdesign.trevorbice.com/
*/ 
if( defined('JPATH_BASE') ) {
	class plgSystemTbw_tracking extends JPlugin
	{
		function __construct(&$subject, $config) {
			parent::__construct($subject, $config);
		}
		function onAfterInitialise()	{
			$doc 		= JFactory::getDocument();
			$mainframe 	= JFactory::getApplication();	
			$jinput 		= JFactory::getApplication()->input;
			require_once(JPATH_ROOT."/libraries/cms/version/version.php");
			$version = new JVersion;
			$siteVersion['platform'] 	= "Joomla";
			$siteVersion['release'] 		= $version->RELEASE;
			$siteVersion['build']	 	= $version->RELEASE;
			$siteVersion['dev_level']	= $version->DEV_LEVEL;
			$siteVersion['dev_status']	= $version->DEV_STATUS;
			$siteVersion['version']		= $version->RELEASE.".".$version->DEV_LEVEL." ".$version->DEV_STATUS;
			
			// Not sure why this sometimes doesnt return an actual value, but just in case 
			$task 	= $jinput->get('task', 	$_REQUEST['task'], 		'RAW');
			$key		= $jinput->get('key', 	$_REQUEST['key'], 		'RAW');
			
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select($db->quoteName(array('name', 'manifest_cache')))
				 ->from($db->quoteName('#__extensions'));
			$db->setQuery($query);
			$results = $db->loadObjectList();
			foreach ($results as $extension)
			{
				//$index = 
				
				 $decode = json_decode($extension->manifest_cache);
				//  print_r($decode);
				//print_r($index);
				$abbrv[$extension->name]['title'] 		= $extension->name;
				$abbrv[$extension->name]['version'] 	= $decode->version;
				// $abbrv[$index]['update']		=
				//$i++;
			
			   
			    //echo $extension->name . ' : ' . $decode->version . '<br>';
			}
			
			$siteVersion['plugins']	= $abbrv;
			
			$secret = $this->params->get('secret');
			if($task == 'tbw_tracking.returnTracking' ){
				if($key==md5($secret)){
					echo json_encode($siteVersion);
					die();
				}
				echo "Invalid tracking code, exiting";
				die();
			}
			if($mainframe->isSite()) {	
				$doc->setMetadata("tbw_tracking", "website Active"); 
				$doc->setMetadata("tbw_tracking_version", "1.0"); 
			}
		}
	}
}
else if($wp_version) {
	$plugin_dir = basename(dirname(__FILE__));
	require( 'plugin-update-checker/plugin-update-checker.php');
	$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
		'https://github.com/trevorbicewebdesign/Mothership-Tracking/',
		__FILE__,
		'tbw_tracking'
	);
	$myUpdateChecker->setAuthentication('413f01f41ebff897ba6c3e5c80d173c0e59782e5');
	global $wl_options;
	if(is_admin()) {
	    load_plugin_textdomain('tbw-mothership-tracking', false, dirname( plugin_basename( __FILE__ ) ) . '/i18n');
	    add_action('admin_menu', 'add_TBW_MothershipTracking_admin_page');
	}
	else {
		add_action('wp_head', 'tbw_tracking_metadata');
	}
	function add_TBW_MothershipTracking_admin_page()
	{
	    if ( function_exists('add_submenu_page') ) {
		   add_submenu_page('plugins.php', __('TBW Mothership Tracking', 'tbw-mothership-tracking'), __('TBW Mothership Tracking'), 'manage_options', 'tbw-mothershop-tracking', 'print_MothershipTracking_management');
	    }
	}
	// Prints the admin menu where it is possible to add the tracking code
	function print_MothershipTracking_management() {
	    if (isset($_POST['submit'])) {
		   if (!current_user_can('manage_options')) {
			  wp_die(__('You do not have sufficient permissions to manage options for this blog.'));
		   }
		   $code = trim($_POST['tbw_mothership_tracking_secret']);
		   $cache = get_option('tbw_mothership_tracking_secret');
		   if (empty($code)) {
			  delete_option('tbw_mothership_tracking_secret');
		   } else {
			  if ($cache != $code) {
				 update_option('tbw_mothership_tracking_secret', $code);
			  }
		   }
		   // Clear WP-Super Cache on POST
		   if (function_exists('wp_cache_clear_cache')) {
			  wp_cache_clear_cache();
		   }
	?>
	<div id="message" class="updated fade"><p><strong><?php esc_attr_e('Options saved.'); ?></strong></p></div>
	<?php
	    }
	// wp_enqueue_style("tbw-mothership-tracking", plugin_dir_url("/", __FILE__) . trim(dirname(plugin_basename(__FILE__)), '/') . "/pingdom.css");
	?>
	<div class="wrap">
		<h4><?php esc_attr_e('TBWorks - Mothership Tracking ', 'tbw-mothership-tracking'); ?></h4>
		<h2><?php esc_attr_e('Mothership Tracking', 'tbw-mothership-tracking'); ?></h2>
		<p><?php _e('This plugin provides jSON output for detailed website monitoring. ', 'tbw-mothership-tracking'); ?></p>
	    <form method="post" action="" class="mothership-form">
		   <strong><?php esc_attr_e('Secret', 'tbw-mothership-tracking'); ?>: </strong>
		   <input name="tbw_mothership_tracking_secret" type="text" id="tbw_mothership_tracking_secret" value="<?php echo get_option('tbw_mothership_tracking_secret'); ?>" class="mothership-input" maxlength="24" placeholder="e.g. 1a2b3c4e5f6a7b8c9d0e1f2a" />
		   <input type="submit" name="submit" class="pingdom-button" value="<?php esc_attr_e('Save Changes') ?>" />
	    </form>
	    <p>Brought to you by</p>
		<img src="<?php echo plugin_dir_url("/", __FILE__) . trim(dirname(plugin_basename(__FILE__)), '/'); ?>/tbwebdesignlogo.png" alt="Brought to you by Trevor Bice Webdesign" />
	</div>
	<?php
	}
	add_filter( 'query_vars', 'add_custom_query_var' );
	function add_custom_query_var( $vars ){
	  $vars[] = "task";
	  $vars[] = "key";
	  return $vars;
	}
	function tbw_tracking_metadata() { 
		$hmd_output  = '';
		$hmd_output .= "\t\t" .'<meta name="tbw_tracking" 		content="website Active"/>';
		$hmd_output .= "\t\t" .'<meta name="tbw_tracking_version" 	content="1.0"/>';
		echo $hmd_output;
	}
	add_action('template_redirect', 'mothership_tracking');
	function mothership_tracking(){
		$task 	= get_query_var('task');
		$key 	= get_query_var('key');
		if($task == 'tbw_tracking.returnTracking' ){
			$version = get_bloginfo('version');
			$plugins = get_plugins();
			$update_plugins = get_site_transient( 'update_plugins' );
			$i = 0;
			foreach($plugins as $index=>$val) {
				//print_r($index);
				$abbrv[$index]['title'] 		= $val['Title'];
				$abbrv[$index]['version'] 	= $val['Version'];
				// $abbrv[$index]['update']		=
				$i++;
			}
			foreach($abbrv as $index=>$val){
				if($update_plugins->response[$index]->new_version) {
					$abbrv[$index]['update'] 		= true;
					$abbrv[$index]['update_version'] 	= $update_plugins->response[$index]->new_version;
				}
				else {
					$abbrv[$index]['update'] 		= false;	
				}
			}
			$siteVersion['platform'] 	= "Wordpress";
			$siteVersion['release'] 		= $version;
			$siteVersion['build']	 	= $version;
			$siteVersion['dev_level']	= $version->DEV_LEVEL;
			$siteVersion['dev_status']	= $version->DEV_STATUS;
			$siteVersion['version']		= $version.".".$version->DEV_LEVEL." ".$version->DEV_STATUS;
			$siteVersion['plugins']		= $abbrv;
			$secret = get_option('tbw_mothership_tracking_secret');
			if($task == 'tbw_tracking.returnTracking' && $key==md5($secret)){
				echo json_encode($siteVersion);
				die();
			}
			if (!is_admin()){
				//$doc->setMetadata("tbw_tracking", "website Active"); 
				//$doc->setMetadata("tbw_tracking_version", "1.0"); 
				add_metadata( $meta_type, $object_id, $meta_key, $meta_value, $unique );
			}
		}
	}
}
?>