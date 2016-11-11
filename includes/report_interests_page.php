<?php
// don't load directly 
if ( !defined('ABSPATH') || !defined('WP_ADMIN') ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

//functions 
//formulated  days passed
	function days_passed($date_1,$date_2) {
		$days	= (strtotime($date_1)-strtotime($date_2))/86400;
		$days 	= abs($days); $days = floor($days);		
		return $days;
	}
?><?php 	
	//formulated for events triggered by days to consider 
	$wpsecfg = $this->check_options($this->options);
	$consideration_days = $wpsecfg['consideration_days'];
	$date_now = date('d/m/Y h:i A');

	//Query Arguments	
	$args=array(
		'order' => 'ASC', 
		'orderby' => 'title', 
		'post_type' => 'wpsellerevents',
		'post_status' => 'publish'
	);
	$my_query = null;
	$my_query = new WP_Query($args);

?>

<!--VIEW TEMPLATE-->
<div class="wrap">
<h1>Report Interests</h1>
<table class="wp-list-table widefat fixed striped pages table-report-interest">
<thead>
	<tr>
		<th>Event</th>
		<th>Status</th>
		<th>Date</th>
		<th>Seller</th>
		<th>Client  == ></th>
		<th>Interests</th>
	</tr>
</thead>
<tbody>
<?php 
	if( $my_query->have_posts() ) {
		while ($my_query->have_posts()) : $my_query->the_post(); 
			//get info events
			$event_data = WPSellerEvents :: get_event (get_the_id()); 
			//date event
			$fromdate = date_i18n($wpsecfg['dateformat'] .' '.get_option( 'time_format' ), $event_data['fromdate']);

			//get info clients
			$client_data = sellerevents_clients :: get_client_data($event_data['customer_id']);	
			
			//get how many days have passed since the firing of the alarm until the current date
			$event_show_days = days_passed($fromdate,$date_now);

			//corresponding conditions to see if we show the event in the list
			if($client_data['user-null-interests']!="yes"){
				//display the list of events that have not been successful
				if($event_data['event_status']!="success"){
					//display the list of events that the stipulated days have elapsed after activating the alarm
					if($event_show_days>=$consideration_days){
?>
						<tr>
							<td><?php the_title(); ?></td>
							<td><?php print($event_data['event_status']); ?></td>
							<td><?php the_time('Y/m/d'); ?></td>
							<td><?php print(get_post_meta(get_the_id(), 'seller',TRUE)); ?></td>
							<td><?php print(get_post_meta(get_the_id(), 'client',TRUE)); ?></td>
							<td class="td_interest">
								<ol class="resp-interests-user">
									<?php 
										//interest Taxonomy
										$term_list = wp_get_post_terms($event_data['customer_id'], 'interest', array("fields" => "all"));
									 	foreach($term_list as $term_single) {
									 ?>	
									 			<li><?php echo $term_single->slug; ?></li>	
									 <?php } ?>
									
								</ol>
							</td>
						</tr>
<?php  
					}//if closing events as the days of consideration
				}//closed if event estatus
			}//closed if user-null-interest
		endwhile;
	}
	wp_reset_query();  // Restore global post data stomped by the_post().
?>
</tbody>
<tfoot>
	<tr>
		<th>Event</th>
		<th>Status</th>
		<th>Date</th>
		<th>Seller</th>
		<th>Client  == ></th>
		<th>Interests</th>
	</tr>
</tfoot>
</table>
</div>
<style type="text/css">
	.table-report-interest thead tr th{font-weight: bold; color: white !important; background-color: #0073AA; border-left: 1px solid white;}
	.table-report-interest tfoot tr th{font-weight: bold; color: #999999 !important;  border: 1px solid #ccc; border-top: none; }
	.table-report-interest tr td{border-bottom: 1px solid #ccc;}
	ol.resp-interests-user li{font-weight: bold; list-style:square; padding-bottom: 10px; }
	td.td_interest{border-left: 1px solid #ccc;}
</style>