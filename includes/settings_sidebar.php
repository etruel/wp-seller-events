<?php
// don't load directly 
if ( !defined('ABSPATH') || !defined('WP_ADMIN')) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}
?>
		<div id="side-info-column" class="inner-sidebar">
			<div id="side-sortables" class="meta-box-sortables ui-sortable">
				<div class="postbox inside">
					<h3 class="handle"><?php _e( 'About', self :: TEXTDOMAIN );?></h3>
					<div class="inside">
						<p><strong><?php echo '► '.  self::$name. ' '. self::$version. ' ◄' ; ?></strong></p>
<?php /*						<p><?php _e( 'Thanks for test, use and enjoy this plugin.', self :: TEXTDOMAIN );?></p>
						<p><?php _e( 'If you like it, I really appreciate a donation.', self :: TEXTDOMAIN );?></p>
						<p>
						<input type="button" class="button-primary" name="donate" value="<?php _e( 'Click for Donate', self :: TEXTDOMAIN );?>" onclick="javascript:window.open('https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=B8V39NWK3NFQU');return false;"/>
						</p>
						<p><?php // _e('Help', self :: TEXTDOMAIN ); ?><a href="#" onclick="javascript:window.open('https://www.paypal.com/ar/cgi-bin/webscr?cmd=xpt/Marketing/general/WIPaypal-outside','WIPaypal','toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=700, height=600');"><img  src="https://www.paypal.com/es_XC/Marketing/i/logo/bnr_airlines1_205x67.gif" border="0" alt="Paypal Help"></a>
						</p>
						<p></p>
						<p>
						<input type="button" class="button-primary" name="buypro" value="<?php _e( 'Buy PRO version online', self :: TEXTDOMAIN );?>" onclick="javascript:window.open('http://www.wpematico.com/wpematico/');return false;"/>
						</p>
*/ ?>					<p></p>
					</div>
				</div>
				<div class="postbox">
					<h3 class="handle"><?php _e( 'Sending e-Mails', self :: TEXTDOMAIN );?></h3>
					<div class="inside">
						<p><b><?php _e('Sender Email:', self :: TEXTDOMAIN ); ?></b><br /><input name="mailsndemail" type="text" value="<?php echo $wpsecfg['mailsndemail'];?>" class="large-text" /></p>
						<p><b><?php _e('Sender Name:', self :: TEXTDOMAIN ); ?></b><br /><input name="mailsndname" type="text" value="<?php echo $wpsecfg['mailsndname'];?>" class="large-text" /></p>
						<p><b><?php _e('Send mail method:', self :: TEXTDOMAIN ); ?></b><br />
						<?php 
						echo '<select id="mailmethod" name="mailmethod">';
						echo '<option value="mail"'.selected('mail',$wpsecfg['mailmethod'],false).'>'.__('PHP: mail()', self :: TEXTDOMAIN ).'</option>';
						//echo '<option value="Sendmail"'.selected('Sendmail',$wpsecfg['mailmethod'],false).'>'.__('Sendmail', self :: TEXTDOMAIN ).'</option>';
						echo '<option value="SMTP"'.selected('SMTP',$wpsecfg['mailmethod'],false).'>'.__('SMTP', self :: TEXTDOMAIN ).'</option>';
						echo '</select>';
						?></p>
						<label id="mailsendmail" <?php if ($wpsecfg['mailmethod']!='Sendmail') echo 'style="display:none;"';?>>
							<b><?php _e('Sendmail Path:', self :: TEXTDOMAIN ); ?></b><br />
							<input name="mailsendmail" type="text" value="<?php echo $wpsecfg['mailsendmail'];?>" class="large-text" /><br />
						</label>
						<label id="mailsmtp" <?php if ($wpsecfg['mailmethod']!='SMTP') echo 'style="display:none;"';?>>
							<b><?php _e('SMTP Hostname:', self :: TEXTDOMAIN ); ?></b><br /><input name="mailhost" type="text" value="<?php echo $wpsecfg['mailhost'];?>" class="large-text" /><br />
							<b><?php _e('SMTP Port:', self :: TEXTDOMAIN ); ?></b><br /><input name="mailport" type="text" value="<?php echo $wpsecfg['mailport'];?>" class="small-text" /><br />
							<b><?php _e('SMTP Secure Connection:', self :: TEXTDOMAIN ); ?></b><br />
							<select name="mailsecure">
								<option value=""<?php selected('',$wpsecfg['mailsecure'],true); ?>><?php _e('none', self :: TEXTDOMAIN ); ?></option>
								<option value="ssl"<?php selected('ssl',$wpsecfg['mailsecure'],true); ?>>SSL</option>
								<option value="tls"<?php selected('tls',$wpsecfg['mailsecure'],true); ?>>TLS</option>
							</select><br />
							<b><?php _e('SMTP Username:', self :: TEXTDOMAIN ); ?></b><br /><input name="mailuser" type="text" autocomplete="off" value="<?php echo $wpsecfg['mailuser'];?>" class="user large-text" /><br />
							<b><?php _e('SMTP Password:', self :: TEXTDOMAIN ); ?></b><br /><input name="mailpass" type="password" value="<?php echo base64_decode($wpsecfg['mailpass']);?>" class="password large-text" /><br />
						</label>
						<script type="text/javascript">
							jQuery('#mailmethod').change(function() {
								if ( true == jQuery('#mailmethod > option[value="SMTP"]').prop("selected") ) {
									jQuery('#mailsendmail').fadeOut();
									jQuery('#mailsmtp').fadeIn();
								} else if ( true == jQuery('#mailmethod > option[value="Sendmail"]').prop("selected") ) {
									jQuery('#mailsendmail').fadeIn();
									jQuery('#mailsmtp').fadeOut();
								} else {
									jQuery('#mailsendmail').fadeOut();
									jQuery('#mailsmtp').fadeOut();
								}
							});
						</script>
					</div>
				</div>

				<div class="postbox inside">
					<div class="inside">
						<p>
						<input type="submit" class="button-primary" name="submit" value="<?php _e( 'Save settings', self :: TEXTDOMAIN );?>" />
						</p>
					</div>
				</div>

				<div id="enabledashboard" class="postbox">
				<h3 class="hndle"><span><?php _e('Dashboard widget', self :: TEXTDOMAIN ); ?></span> <span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['disabledashboard']; ?>"></span></h3>
				<div class="inside">
					<label><input class="checkbox" value="1" type="checkbox" <?php checked($wpsecfg['disabledashboard'],true); ?> name="disabledashboard" id="disabledashboard" /> <?php _e('Disable <b><i>Dashboard Widget</i></b>', self :: TEXTDOMAIN ); ?></label><br />
					<div id="roles" <?php echo ($wpsecfg['disabledashboard']) ? 'style="display:none;"' : ''; ?>>
						<label id="roleslabel"><?php _e('User roles that can see dashboard widget:', self :: TEXTDOMAIN ); ?></label>
					<?php 
						global $wp_roles;
						if(!isset($wpsecfg['roles_widget'])) $wpsecfg['roles_widget'] = array( "administrator" => "administrator" );
						$role_select = '<input type="hidden" name="role_name[]" value="administrator" />';
						foreach( $wp_roles->role_names as $role => $name ) {			
							$name = _x($name, self :: TEXTDOMAIN );
							if ( $role != 'administrator' ) {
								if ( array_search($role, $wpsecfg['roles_widget']) ) {
									$checked = 'checked="checked"';
								}else{
									$checked = '';
								}
							  $role_select .= '<label style="margin:5px;"><input style="margin:0 5px;" ' . $checked . ' type="checkbox" name="role_name[]" value="'.$role .'" />'. $name . '</label>';
							}	
						}
						echo $role_select;
					?>
					</div>
				</div>
			</div>
				
			</div>
			<?php // include( self :: $dir . 'myplugins.php');	?>
		</div>
