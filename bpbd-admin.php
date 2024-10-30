<?php

 // Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


class BP_Bulk_Delete {

	public static function instance() {

		static $instance = null;
		if ( null === $instance ) {
			$instance = new BP_Bulk_Delete;
			$instance->add_menu();
		}
		return $instance;
	}

	private function __construct() { /* Nothing so far*/ }

	public function add_menu() {
		add_action( 'admin_menu',  array( $this, 'bpbd_tool_link' ) );
	}

	public function bpbd_tool_link() {
		add_management_page( 'BP Bulk Delete', 'BP Bulk Delete', 'manage_options', 'bpbd', array( $this, 'bpbd_form' ), 44 );
	}


	public function bpbd_form() {

		$date_dropdown = $this->bpbd_date_dropdown();

		echo "<h1>BuddyPress Bulk Delete</h1>";

		//echo "current_time( 'mysql' ) returns local site time: " . current_time( 'mysql' ) . '<br />';

		echo "We recommend that you make a backup of your database <em>before</em> running any of these operations.<br>&nbsp;<br>";


		if ( bp_is_active( 'activity' ) )
			$this->bpbd_activity_html();

		if ( bp_is_active( 'groups' ) )
			$this->bpbd_activity_groups_html();

		if ( bp_is_active( 'notifications' ) )
			$this->bpbd_notifications_html();

		if ( bp_is_active( 'messages' ) )
			$this->bpbd_messages_html();

	}


	private function bpbd_activity_delete( $date_end ) {
		global $wpdb;

		$bp = buddypress();

		$activities_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT id FROM {$bp->activity->table_name} WHERE date_recorded < %s",
			$date_end
		) );

		$count = count( $activities_ids );

		do_action( 'bpbd_activity_delete_pre', $activities_ids );

		if ( empty ( $activities_ids ) ) {
			echo 'No Activity Entries were found.<br>';
		} else {

			$sql = "DELETE FROM {$bp->activity->table_name} WHERE date_recorded < '$date_end'";
			$wpdb->query($sql);

			$activities_ids = implode( ',', $activities_ids );
			$sql = "DELETE FROM {$bp->activity->table_name_meta} WHERE activity_id IN ($activities_ids)";
			$wpdb->query($sql);

		}

		do_action( 'bpbd_activity_delete_post', $activities_ids );

		return $count;

	}


	private function bpbd_group_activity_delete( $date_end, $group ) {
		global $wpdb;

		$bp = buddypress();

		if ( $group == 'all' ) {

			$activities_ids = $wpdb->get_col( $wpdb->prepare(
				"SELECT id FROM {$bp->activity->table_name} WHERE component = 'groups' AND date_recorded < %s",
				$date_end
			) );

		} else {

			$activities_ids = $wpdb->get_col( $wpdb->prepare(
				"SELECT id FROM {$bp->activity->table_name} WHERE component = 'groups' AND item_id = $group AND date_recorded < %s",
				$date_end
			) );

		}

		$count = count( $activities_ids );

		do_action( 'bpbd_group_activity_delete_pre', $activities_ids );

		if ( empty ( $activities_ids ) ) {
			echo 'No Activity Entries were found.<br>';
		} else {

			$activities_ids = implode( ',', $activities_ids );

			$sql = "DELETE FROM {$bp->activity->table_name} WHERE id IN ($activities_ids)";
			$wpdb->query($sql);

			// delete any comments too
			$sql = "DELETE FROM {$bp->activity->table_name} WHERE item_id IN ($activities_ids)";
			$wpdb->query($sql);

			$sql = "DELETE FROM {$bp->activity->table_name_meta} WHERE activity_id IN ($activities_ids)";
			$wpdb->query($sql);

		}

		do_action( 'bpbd_group_activity_delete_post', $activities_ids );

		return $count;

	}



	private function bpbd_notifications_delete( $date_end ) {
		global $wpdb;

		$bp = buddypress();

		$notifications_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT id FROM {$bp->notifications->table_name} WHERE date_notified < %s",
			$date_end
		) );

		$count = count( $notifications_ids );

		do_action( 'bpbd_notifications_delete_pre', $notifications_ids );

		if ( empty ( $notifications_ids ) ) {
			echo 'No Notifications were found.<br>';
		} else {

			$sql = "DELETE FROM {$bp->notifications->table_name} WHERE date_notified < '$date_end'";
			$wpdb->query($sql);

			$notifications_ids = implode( ',', $notifications_ids );
			$sql = "DELETE FROM {$bp->notifications->table_name_meta} WHERE notification_id IN ($notifications_ids)";
			$wpdb->query($sql);

		}

		do_action( 'bpbd_notifications_delete_post', $notifications_ids );

		return $count;

	}



	private function bpbd_messages_delete( $date_end ) {
		global $wpdb;

		$bp = buddypress();

		$all_messages = $wpdb->get_results( $wpdb->prepare(
			"SELECT id, thread_id FROM {$bp->messages->table_name_messages} WHERE date_sent < %s",
			 $date_end
		) );

		$message_ids = array();
		$thread_ids = array();

		foreach ( $all_messages as $message ) {
			$message_ids[] = $message->id;
			$thread_ids[] = $message->thread_id;
		}

		$thread_ids = array_unique( $thread_ids );

		do_action( 'bpbd_messages_delete_pre', $message_ids, $thread_ids );

		$count = count( $message_ids );

		if ( empty ( $message_ids ) ) {
			echo 'No Messages were found.<br>';
		} else {

			$sql = "DELETE FROM {$bp->messages->table_name_messages} WHERE date_sent < '$date_end'";
			$wpdb->query($sql);

			$thread_ids = implode( ',', $thread_ids );
			$sql = "DELETE FROM {$bp->messages->table_name_recipients} WHERE thread_id IN ($thread_ids)";
			$wpdb->query($sql);

			$message_ids = implode( ',', $message_ids );
			$sql = "DELETE FROM {$bp->messages->table_name_meta} WHERE message_id IN ($message_ids)";
			$wpdb->query($sql);

		}

		do_action( 'bpbd_messages_delete_post', $message_ids, $thread_ids );

		return $count;

	}


	private function bpbd_sanitize_date() {

		$check = true;

		$year =     absint( $_POST['date_year'] );
		$month =    absint( $_POST['date_month'] );
		$day =      absint( $_POST['date_day'] );

		if ( ! $year ) 	$check = false;

		if ( ! $month ) $check = false;
		elseif ( $month < 10 ) $month = zeroise( $month, 2 );

		if ( ! $day ) $check = false;
		elseif ( $day < 10 ) $day = zeroise( $day, 2 );


		if ( ! $check ) {
			return $check;
		} else {
			$date = $year . '-' .  $month . '-' . $day;
			return $date;
		}
	}


	private function bpbd_date_dropdown(){

		$html_output = '';

		/*months*/
		$html_output .= '<select name="date_month" id="month_select" >';
		$months = array("", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
		for ($month = 1; $month <= 12; $month++) {
			$html_output .= '<option value="' . $month . '">' . $months[$month] . '</option>';
		}
		$html_output .= '</select>';


		/*days*/
		$html_output .= '<select name="date_day" id="day_select">';
		for ($day = 1; $day <= 31; $day++) {
			$html_output .= '<option>' . $day . '</option>';
		}
		$html_output .= '</select>';

		/*years*/
		$html_output .= '<select name="date_year" id="year_select">';
		for ($year = 2012; $year < (date("Y") + 1); $year++) {
			$html_output .= '<option>' . $year . '</option>';
		}
		$html_output .= '</select>';


		return $html_output;
	}

	private function bpbd_groups_dropdown() {

		$html_output = '';

		$html_output .= '<select name="bpbd-groups" id="bpbd-groups" >';

		$html_output .= '<option value="all">All</option>';

		$groups = groups_get_groups( array( 'order' => 'ASC', 'orderby' => 'name' ) );

		foreach( $groups['groups'] as $group ) {

			$html_output .= '<option value="' . $group->id .'">' . $group->name . '</option>';

		}

		$html_output .= '</select>';

		return $html_output;
	}

	private function bpbd_activity_html() {

		echo '<hr>';
		echo "<h3>Activity Bulk Delete</h3>";

		if( isset( $_POST['bpbd-activity-delete'] ) && $_POST['bpbd-activity-delete'] == '1' ) {

			check_admin_referer("bpbd-activity-bulk-check");

			$date_end = $this->bpbd_sanitize_date();

			if ( ! $date_end ) {
				echo "There was a problem with your submitted date.";

			} else {
				echo 'Deleting Activity Older Than: <strong>' . $date_end  .  '</strong><br>&nbsp;Please wait...<br>&nbsp;';

				echo 'Finished: <strong>' . $this->bpbd_activity_delete( $date_end ) . ' </strong> activity entries were deleted.';
			}
		}
	?>

		<form action="<?php echo admin_url('tools.php?page=bpbd'); ?>" name="bpbd-activity-delete-form" id="bpbd-activity-delete-form"  method="post" class="standard-form">

			<table border="0" cellspacing="15">

				<tr>
					<td><strong>Date:</strong>&nbsp;&nbsp;<?php echo $this->bpbd_date_dropdown(); ?></td>
				</tr>

				<tr><td>
					<input type="hidden" id="bpbd-activity-delete" name="bpbd-activity-delete" value="1" />
					<?php wp_nonce_field( 'bpbd-activity-bulk-check' ); ?>
					<input id="submit" name="submit" type="submit" class="button button-primary" value=" DELETE ACTIVITY OLDER THAN SELECTED DATE " />
					<br>
					<strong>IMPORTANT:</strong> There is no UnDo for this operation.
				</td></tr>
			</table>

		</form>

	<?php
	}

	private function bpbd_activity_groups_html() {

	    echo '<hr>';
		echo "<h3>Group Activity Bulk Delete</h3>";

		if( isset( $_POST['bpbd-group-activity-delete'] ) && $_POST['bpbd-group-activity-delete'] == '1' ) {

			check_admin_referer("bpbd-group-activity-bulk-check");

			$date_end = $this->bpbd_sanitize_date();
			$group = sanitize_text_field( $_POST['bpbd-groups'] );

			if ( ! $date_end ) {
				echo "There was a problem with your submitted date.";

			} else {
				echo 'Deleting Group Activity Older Than: <strong>' . $date_end  .  '</strong><br>&nbsp;Please wait...<br>&nbsp;';

				echo 'Finished: <strong>' . $this->bpbd_group_activity_delete( $date_end, $group ) . ' </strong> activity entries were deleted.';
			}
		}
	?>

		<form action="<?php echo admin_url('tools.php?page=bpbd'); ?>" name="bpbd-group-activity-delete-form" id="bpbd-group-activity-delete-form"  method="post" class="standard-form">

			<table border="0" cellspacing="15">

				<tr>
					<td><strong>Group:</strong>&nbsp;&nbsp;<?php echo $this->bpbd_groups_dropdown(); ?></td>
				</tr>

				<tr>
					<td><strong>Date:</strong>&nbsp;&nbsp;<?php echo $this->bpbd_date_dropdown(); ?></td>
				</tr>

				<tr><td>
					<input type="hidden" id="bpbd-group-activity-delete" name="bpbd-group-activity-delete" value="1" />
					<?php wp_nonce_field( 'bpbd-group-activity-bulk-check' ); ?>
					<input id="submit" name="submit" type="submit" class="button button-primary" value=" DELETE GROUP ACTIVITY OLDER THAN SELECTED DATE " />
					<br>
					<strong>IMPORTANT:</strong> There is no UnDo for this operation.
				</td></tr>
			</table>

		</form>

	<?php
	}

	private function bpbd_notifications_html() {

		echo '<hr>';
		echo "<h3>Notifications Bulk Delete</h3>";

		if( isset( $_POST['bpbd-notify-delete'] ) && $_POST['bpbd-notify-delete'] == '1' ) {

			check_admin_referer("bpbd-notify-bulk-check");

			$date_end = $this->bpbd_sanitize_date();

			if ( ! $date_end ) {
				echo "There was a problem with your submitted date.";

			} else {

				echo 'Deleting Notifications Older Than: <strong>' . $date_end  .  '</strong><br>&nbsp;Please wait...<br>&nbsp;';

				echo 'Finished: <strong>' . $this->bpbd_notifications_delete( $date_end ) . ' </strong> notifications were deleted.';
			}
		}


	?>
	    <form action="<?php echo admin_url('tools.php?page=bpbd'); ?>" name="bpbd-notify-delete-form" id="bpbd-notify-delete-form"  method="post" class="standard-form">

			<table border="0" cellspacing="15">

				<tr>
					<td><strong>Date:</strong>&nbsp;&nbsp;<?php echo $this->bpbd_date_dropdown(); ?></td>
				</tr>

				<tr><td>
					<input type="hidden" id="bpbd-notify-delete" name="bpbd-notify-delete" value="1" />
					<?php wp_nonce_field( 'bpbd-notify-bulk-check' ); ?>
					<input id="submit" name="submit" type="submit" class="button button-primary" value=" DELETE NOTIFICATIONS OLDER THAN SELECTED DATE " />
					<br>
					<strong>IMPORTANT:</strong> &nbsp;<em>It will delete both Read and Unread notifications</em>. &nbsp;There is no UnDo for this operation.
				</td></tr>
			</table>

		</form>

	<?php
	}


	private function bpbd_messages_html() {

		echo '<hr>';
		echo "<h3>Messages Bulk Delete</h3>";

		if( isset( $_POST['bpbd-message-delete'] ) && $_POST['bpbd-message-delete'] == '1' ) {

			check_admin_referer("bpbd-message-bulk-check");

			$date_end = $this->bpbd_sanitize_date();

			if ( ! $date_end ) {
				echo "There was a problem with your submitted date.";

			} else {

				echo 'Deleting Messages Older Than: <strong>' . $date_end  .  '</strong><br>&nbsp;Please wait...<br>&nbsp;';

				echo 'Finished: <strong>' . $this->bpbd_messages_delete( $date_end ) . ' </strong> messages were deleted.';
			}
		}


	?>
	    <form action="<?php echo admin_url('tools.php?page=bpbd'); ?>" name="bpbd-message-delete-form" id="bpbd-message-delete-form"  method="post" class="standard-form">

			<table border="0" cellspacing="15">

				<tr>
					<td><strong>Date:</strong>&nbsp;&nbsp;<?php echo $this->bpbd_date_dropdown(); ?></td>
				</tr>

				<tr><td>
					<input type="hidden" id="bpbd-message-delete" name="bpbd-message-delete" value="1" />
					<?php wp_nonce_field( 'bpbd-message-bulk-check' ); ?>
					<input id="submit" name="submit" type="submit" class="button button-primary" value=" DELETE MESSAGES OLDER THAN SELECTED DATE " />
					<br>
					<strong>IMPORTANT:</strong> &nbsp;<em>It will delete both Read and Unread messages</em>. &nbsp;There is no UnDo for this operation.
				</td></tr>
			</table>

		</form>

	<?php
	}

}
add_action( 'bp_init', array( 'BP_Bulk_Delete', 'instance' ) );
