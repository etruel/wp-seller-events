<?php
// don't load directly 
if ( !defined('ABSPATH') || !defined('WP_ADMIN') ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}
$cfg = $this->check_options($this->options);  

$helptip = array(
 'dateformat' 	=> __('The format that dates must be entered in the date fields. All strings will be displayed following the date format on Wordpress Settings.', self :: TEXTDOMAIN ),
 'disabledashboard'	=> __('Check this if you don\'t want to display the widget dashboard.  Anyway, only admins will see it.', self :: TEXTDOMAIN ) ,	
);
foreach($helptip as $key => $value){
	$helptip[$key] = htmlentities($value);
}

?>
<div class="wrap">
	<h2><?php _e( 'Seller Events settings', self :: TEXTDOMAIN );?></h2>
	<div id="poststuff" class="metabox-holder has-right-sidebar">
		<form method="post" action="">
		<?php  wp_nonce_field('wpsellerevents-settings'); ?>
			
		<?php include( 'settings_sidebar.php');	?>
			
		<div id="post-body">
			<div id="post-body-content">
				<div id="normal-sortables" class="meta-box-sortables ui-sortable">
		
			<div id="enablefeatures" class="postbox">
				<h3 class="hndle"><span><?php _e('Global Settings', self :: TEXTDOMAIN ); ?></span></h3>
				<div class="inside"> 
					<p><b><?php _e('Time format:', self :: TEXTDOMAIN ); ?></b> <span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['dateformat']; ?>"></span><br />
					<label><input class="checkbox" value="d/m/Y" type="radio" <?php checked($cfg['dateformat'],"d/m/Y"); ?> name="dateformat" id="dateformat1" />dd/mm/YYYY </label><br />
					<label><input class="checkbox" value="m/d/Y" type="radio" <?php checked($cfg['dateformat'],"m/d/Y"); ?> name="dateformat" id="dateformat1" />mm/dd/YYYY </label>
					</p>
					<p></p>
				</div>
			</div>
		
		
			<div id="disablewpcron" class="postbox">
				<h3 class="hndle"><span><?php _e('Disable WP-Cron', self :: TEXTDOMAIN ); ?></span></h3>
				<div class="inside">
					<input class="checkbox" id="disablewpcron" type="checkbox"<?php checked($cfg['disablewpcron'],true);?> name="disablewpcron" value="1"/> <?php _e('Use Cron job of Hoster and disable WP_Cron', self :: TEXTDOMAIN ); ?><br />
					<div id="hlpcron" style="padding-left:20px;">
					<strong><?php _e('NOTE:', self :: TEXTDOMAIN ); ?></strong> <?php _e('Checking this, deactivate all Wordpress cron schedules.', self :: TEXTDOMAIN ); ?><br /><br />
					<?php _e('You must set up a cron job that calls:', self :: TEXTDOMAIN ); ?><br />
					<span class="coderr b"><i> php -q <?php echo self :: $dir . "app/wpe-cron.php"; ?></i></span><br />
					<?php _e('or URL:', self :: TEXTDOMAIN ); ?> &nbsp;&nbsp;&nbsp;<span class="coderr b"><i><?php echo self :: $uri . "app/wpe-cron.php"; ?></i></span>
					<br /><br />
					<?php _e('If also want to run the wordpress cron with external cron you can set up a cron job that calls:', self :: TEXTDOMAIN ); ?><br />
					<span class="coderr b"><i> php -q <?php echo ABSPATH.'wp-cron.php'; ?></i></span><br /> 
					<?php _e('or URL:', self :: TEXTDOMAIN ); ?> &nbsp;&nbsp;&nbsp;<span class="coderr b"><i><?php echo trailingslashit(get_option('siteurl')).'wp-cron.php'; ?></i></span></div><br /> 
				</div>
			</div>		

			<div id="dias_considerar" class="postbox">
				<h3 class="hndle">Event Settings</h3>
				<div class="inside">
					<strong><?php _e('Limit days to consider report (1-999) (1-999)', self :: TEXTDOMAIN); ?></strong>
					<br>
					<input value="<?php print($cfg['consideration_days']); ?>" type="text" name="consideration_days">
				</div>
				<div class="inside">
					<strong>Editor Type:</strong>
					Basic:<input type="radio" <?php if($cfg['editor_type']=='Basic') print("checked"); ?>  name="editor_type" value="Basic">
					<br>
					Advanced:<input <?php if($cfg['editor_type']=='Advanced') print("checked"); ?> type="radio" name="editor_type" value="Advanced">
				</div>
			</div>			
				
			<div class="postbox inside">
				<div class="inside">
					<p>
					<input type="submit" class="button-primary" name="submit" value="<?php _e( 'Save settings', self :: TEXTDOMAIN );?>" />
					</p>
				</div>
			</div>
			</div>
			</div>
		</div>
		</form>
	</div>
</div>
<script type="text/javascript" language="javascript">
//jQuery(document).ready(function($){
	jQuery('#disabledashboard').click(function() {
		if ( true == jQuery('#disabledashboard').is(':checked')) {
			jQuery('#roles').fadeOut();
			jQuery('#roleslabel').fadeOut();
		} else {
			jQuery('#roles').fadeIn();
			jQuery('#roleslabel').fadeIn();
		}
	});
	jQuery(function(){
		jQuery(".help_tip").tipTip({maxWidth: "300px", edgeOffset: 5,fadeIn:50,fadeOut:50, keepAlive:true, defaultPosition: "right"});
	});
//}
</script>