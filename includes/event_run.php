<?php
// don't load directly 
if ( !defined('ABSPATH') )  {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( class_exists( 'wpsellerevents_event_run' ) ) return;

class wpsellerevents_event_run {
	public $cfg			   = array();
	public $event_id	   = 0;  // $post_id of event
	public $event	   = array();

	public function __construct($event_id) {
		global $wpdb,$event_log_message, $jobwarnings, $joberrors;
		$jobwarnings=0;
		$joberrors=0;
		@ini_set('safe_mode','Off');			//disable safe mode
		@ini_set('ignore_user_abort','Off');//Set PHP ini setting
		ignore_user_abort(true);				//user can't abort script (close windows or so.)
		@set_time_limit(0);

		$this->event_id	= $event_id;		//set event id
		$this->event		= WPSellerEvents :: get_event($this->event_id);
		$this->cfg = get_option(WPSellerEvents :: OPTION_KEY);

		//set function for PHP user defined error handling
		if (defined(WP_DEBUG) and WP_DEBUG)
			set_error_handler('wpse_joberrorhandler',E_ALL | E_STRICT);
		else
			set_error_handler('wpse_joberrorhandler',E_ALL & ~E_NOTICE);
		
		//Set job start settings
		$this->event['starttime'] = current_time('timestamp'); //set start time for job
		$mensaje = "\n";
		$mensaje .= "event = ". print_r($this->event	,1);
		$mensaje .= "\n";
		$mensaje .= "event_id = ". $event_id;
		$mensaje .= "\n";
		$mensaje .= "cronnextrun = ". $cronnextrun;
		$mensaje .= "\n";
		$mensaje .= "current_time = ". current_time('timestamp');
		$mensaje .= "\n";
		$mensaje .= "event['runtime'] = ". $this->event	['runtime'];
		$mensaje .= "\n";
		$mensaje .= "event['starttime'] = ". $this->event	['starttime'];
		//wp_mail('etruel@gmail.com', 'Automatic email EVENT RUN', $mensaje);

		//check max script execution tme
		if (ini_get('safe_mode') or strtolower(ini_get('safe_mode'))=='on' or ini_get('safe_mode')=='1')
			trigger_error(sprintf(__('PHP Safe Mode is on!!! Max exec time is %1$d sec.', WPSellerEvents :: TEXTDOMAIN ),ini_get('max_execution_time')),E_USER_WARNING);
		// check function for memorylimit
		if (!function_exists('memory_get_usage')) {
			ini_set('memory_limit', apply_filters( 'admin_memory_limit', '256M' )); //Wordpress default
			trigger_error(sprintf(__('Memory limit set to %1$s ,because can not use PHP: memory_get_usage() function to dynamically increase the Memory!', WPSellerEvents :: TEXTDOMAIN ),ini_get('memory_limit')),E_USER_WARNING);
		}
		//run job parts

		$this->send_alarm_mail(); // if everything ok call send_alarm_mail  and end class
	}

	/**
	* This function will connect wp_mail to your authenticated
	* SMTP server. This improves reliability of wp_mail, and 
	* avoids many potential problems.
	*
	*/
	function prepare_email( $phpmailer ) {
		// Define that we are sending with SMTP
		$phpmailer->isSMTP();

		// The hostname of the mail server
		$phpmailer->Host = $this->cfg['mailhost'];

		// Use SMTP authentication (true|false)
		// Force it to use Username and Password to authenticate
		$phpmailer->SMTPAuth = true;

		// SMTP port number - likely to be 25, 465 or 587
		$phpmailer->Port = $this->cfg['mailport'];

		// Username to use for SMTP authentication
		$phpmailer->Username = $this->cfg['mailuser'];

		// Password to use for SMTP authentication
		$phpmailer->Password = base64_decode($this->cfg['mailpass']);

		// Encryption system to use - ssl or tls
		$phpmailer->SMTPSecure = $this->cfg['mailsecure'];

		$phpmailer->From = $this->cfg['mailsndemail'];
		$phpmailer->FromName = $this->cfg['mailsndname'];
	}

	/**
	 * function send_alarm_mail
	 * 
	 */
	private function send_alarm_mail() {
		$sendmail=false;
		$seller_id = $this->event['seller_id'];
		if(isset($seller_id) && ($seller_id>0) ) {
			$seller = get_userdata( $seller_id );
			$seller_mail = esc_html( $seller->display_name ). " <".esc_html( $seller->user_email ).">";
		}
		
		if (!empty($seller_mail) && $this->event['activated'])
			$sendmail=true;

		if ($sendmail) {	
			switch($this->cfg['mailmethod']) {
			case 'SMTP':
				add_action( 'phpmailer_init', array(__CLASS__,'prepare_email' ) );
				break;
			default:
				$headers[] = 'From: '.$this->cfg['mailsndname'].' <'.$this->cfg['mailsndemail'].'>';
				//$headers[] = 'Cc: John Q Codex <jqc@wordpress.org>';
				//$headers[] = 'Cc: iluvwp@wordpress.org'; // note you can just use a simple email address
				break;
			}
			$headers[] = 'Content-Type: text/html; charset=UTF-8';
			//add_filter('wp_mail_content_type', function(){ return 'text/html'; }); //function wpe_change_content_type(){ return 'text/html'; } 

			$to_seller_mail = $seller_mail;
			
			$title = get_the_title($this->event_id);
			$subject = __('Event Alarm', WPSellerEvents :: TEXTDOMAIN ).' '.  current_time('Y-m-d H:i').': '.$title;
			
			$mailbody.= __("Seller Event Alarm", WPSellerEvents :: TEXTDOMAIN )." ".$title."<br /><br />";
			$mailbody.= __("Event Name:", WPSellerEvents :: TEXTDOMAIN )." ".$title."<br /><br />";
			$mailbody.= get_post_field('post_content', $this->event_id, 'display');
			$mailbody.= "<br /><br /><br /><hr>";
			$mailbody.= __("WP-Seller Events by <a href='http://etruel.com/wp-seller-events'>etruel</a>", WPSellerEvents :: TEXTDOMAIN ). "<br />";;
			
			wp_mail($to_seller_mail, $subject, $mailbody,$headers,'');
		}
		
		$this->event['starttime'] = 0;
		$this->event['runtime'] 	= current_time('timestamp');
		WPSellerEvents :: update_event($this->event_id, $this->event);  //Save Event new data
	}

	public function __destruct() {
		global $event_log_message, $joberrors;
		  
		$Suss = sprintf(__('Event run in %1s sec.', WPSellerEvents :: TEXTDOMAIN ),$this->event['runtime']);
		$message = '<p>'. $Suss.'  <a href="JavaScript:void(0);" style="font-weight: bold; text-decoration:none; display:inline;" onclick="jQuery(\'#log_message_'.$this->event_id.'\').fadeToggle();">' . __('Show detailed Log', WPSellerEvents :: TEXTDOMAIN ) . '.</a></p>';
		$event_log_message = $message .'<div id="log_message_'.$this->event_id.'" style="display:none;" class="error fade">'.$event_log_message.'</div><span id="ret_runtime" style="display:none;">'.$this->event["runtime"].'</span>';

		return;
	}
}

//function wpe_change_content_type(){ return 'text/html'; }

//function for PHP error handling
function wpse_joberrorhandler($errno, $errstr, $errfile, $errline) {
	global $event_log_message, $jobwarnings, $joberrors;
    
	//generate timestamp
	if (function_exists('memory_get_usage') and (defined(WP_DEBUG) and WP_DEBUG) ) { // test if memory functions compiled in
		$timestamp="<span style=\"background-color:c3c3c3;\" title=\"[Line: ".$errline."|File: ".basename($errfile)."|Mem: ". WPSellerEvents :: formatBytes(@memory_get_usage(true))."|Mem Max: ". WPSellerEvents :: formatBytes( @memory_get_peak_usage(true))."|Mem Limit: ".ini_get('memory_limit')."]\">".current_time('Y-m-d H:i.s').":</span> ";
	} else  {
		$timestamp="<span style=\"background-color:c3c3c3;\" title=\"[Line: ".$errline."|File: ".basename($errfile)."\">".current_time('Y-m-d H:i.s').":</span> ";
	}

	switch ($errno) {
    case E_NOTICE:
	case E_USER_NOTICE:
		$massage=$timestamp."<span>".$errstr."</span>";
        break;
    case E_WARNING:
    case E_USER_WARNING:
		$jobwarnings += 1;
		$massage=$timestamp."<span style=\"background-color:yellow;\">".__('[WARNING]', WPSellerEvents :: TEXTDOMAIN )." ".$errstr."</span>";
        break;
	case E_ERROR: 
    case E_USER_ERROR:
		$joberrors += 1;
		$massage=$timestamp."<span style=\"background-color:red;\">".__('[ERROR]', WPSellerEvents :: TEXTDOMAIN )." ".$errstr."</span>";
        break;
	case E_DEPRECATED:
	case E_USER_DEPRECATED:
		$massage=$timestamp."<span>".__('[DEPRECATED]', WPSellerEvents :: TEXTDOMAIN )." ".$errstr."</span>";
		break;
	case E_STRICT:
		$massage=$timestamp."<span>".__('[STRICT NOTICE]', WPSellerEvents :: TEXTDOMAIN )." ".$errstr."</span>";
		break;
	case E_RECOVERABLE_ERROR:
		$massage=$timestamp."<span>".__('[RECOVERABLE ERROR]', WPSellerEvents :: TEXTDOMAIN )." ".$errstr."</span>";
		break;
	default:
		$massage=$timestamp."<span>[".$errno."] ".$errstr."</span>";
        break;
    }

	if (!empty($massage)) {

		$event_log_message .= $massage."<br />\n";

		if ($errno==E_ERROR or $errno==E_CORE_ERROR or $errno==E_COMPILE_ERROR) {//Die on fatal php errors.
			die("Fatal Error:" . $errno);
		}
		//300 is most webserver time limit. 0= max time! Give script 5 min. more to work.
		@set_time_limit(300); 
		//true for no more php error hadling.
		return true;
	} else {
		return false;
	}

	
}
