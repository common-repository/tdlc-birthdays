<?php
/* TDLC Birthdays plugin - main file
   v0.4
*/

/* Classes */
#[AllowDynamicProperties]
class TDLC_BuddyWithABirthdate {
	// property declaration
	public $birthdate;
	public $id;

	// constructor (PHP4 style for backwards compatibility)
	function __construct($id, $b) {
		$this->birthdate = $b;
		$this->id = $id;
	}

	public function isBirthdayToday() {
		if ((date_i18n("n")==date("n",$this->birthdate))&&(date_i18n("j")==date("j",$this->birthdate)))
			return true;
		else
			return false;
	}

	public function isBirthdayWithinNDays($offset) {
		// Lets calculate the max date according to the offset setting
		$upcoming = mktime(0, 0, 0, date_i18n("m"), date_i18n("d")+$offset, date_i18n("Y"));
		// Now perform the check and return the answer.
		$bday = $this->getNextBirthday();
		if (($bday > time()) && ($bday <= $upcoming))
			return true;
		else 
			return false;
	}

	public function getNextBirthday() {
		if (
			(date("n",$this->birthdate) < date_i18n("n"))
			|| 
			(
				(date("n",$this->birthdate) == date_i18n("n"))
				&& 
				(date("j",$this->birthdate) < date_i18n("j"))
			)
		)
			return mktime(0, 0, 0, date("m", $this->birthdate), date("d", $this->birthdate), date_i18n("Y")+1);
		else
			return mktime(0, 0, 0, date("m", $this->birthdate), date("d", $this->birthdate), date_i18n("Y"));
	}

	// Returns the age the buddy is going to celebrate next (ie NOT current age, except if the birthday is today)
	public function getAge() {
		return date("Y",$this->getNextBirthday())-date("Y",$this->birthdate);
	}
}
#[AllowDynamicProperties]
class TDLCBirthday {
	// Property declaration
	private $bdfid;
	private $offset;
	private $hideage;
	private $friendsarray;
	private $showlink;

	// constructor (PHP4 style for backwards compatibility)
	function __construct($options) {
		if (is_array($options)) {
			if(is_numeric($options['bdfid']))
				$this->bdfid = $options['bdfid'];
			else
				$this->bdfid = xprofile_get_field_id_from_name($options['bdfid']);
			$this->offset = $options['offset'];
			$this->hideage = $options['hide_age'];
			$this->showlink = $options['show_link'];
		}
	}

	public function printFriendsBirthdays($userid) {
		// Fill out the friends array with the connected user's friends
		if (!empty($userid)) {
			$this->friendsarray = BP_Friends_Friendship::get_friend_user_ids($userid);
			// user's got some friends, let's print their birthdays
			if (!empty($this->friendsarray)){ 
				$this->printBirthdays();
			}
			// user is logged in but has no friends
			else echo sanitize_text_field(esc_attr__('You should make some friends to see their birthday here!', 'tdlc-birthdays' ) );
		}
		// user is not logged in
		else echo sanitize_text_field(esc_attr__('You must be logged in to see the birthday of your friends.', 'tdlc-birthdays' ) );
	}
	
	public function printFollowedBirthdays($userid, $friendstoo) {
		// Fill out the friends array with followed people
		if (!empty($userid)) {
			if (function_exists('bp_follow_get_following')) {
				$this->friendsarray = array();
				// Fill out the friends array with every followed user and friends if desired
				if($friendstoo) {
					$this->friendsarray = array_unique(array_merge((array)bp_follow_get_following(array( 'user_id' => $userid )), (array)BP_Friends_Friendship::get_friend_user_ids($userid)));
				}
				else $this->friendsarray = bp_follow_get_following(array( 'user_id' => $userid ));
				if (!empty($this->friendsarray)){ 
					$this->printBirthdays();
				}
				// user is logged in but does not follow people
				else echo sanitize_text_field(esc_attr__('You should follow people to see their birthday here!', 'tdlc-birthdays' ) );
			}
			// Followers plugin not activated
			else echo sanitize_text_field(esc_attr__('Followers plugin should be enabled to see the birthday of followed people', 'tdlc-birthdays' ) ); 
		}
		// user is not logged in
		else echo sanitize_text_field(esc_attr__('You must be logged in to see the birthday of the people you follow.', 'tdlc-birthdays' ) );
	}

	public function printAllBirthdays() {
		$this->printBirthdays(true);
	}	

	// Check if there is data to print, otherwise hide the widget
	public function checkBirthdays($showeveryone = false) {
		global $wpdb, $bp;
		
		// Build birthdates query depending on widget settings (friends or everyone) and availability of the spam flag in the database
		if($showeveryone) {
			$spam_column_test = $wpdb->get_row("SELECT ID FROM $wpdb->users WHERE ID=1 AND user_status != 1");
			if(empty($spam_column_test))
				$sql = $wpdb->prepare( "SELECT user_id, value FROM {$bp->profile->table_name_data} WHERE field_id = %d", $this->bdfid);
			else
				$sql = $wpdb->prepare( "SELECT profile.user_id, profile.value FROM {$bp->profile->table_name_data} profile INNER JOIN $wpdb->users users ON profile.user_id = users.id AND user_status != 1 WHERE profile.field_id = %d", $this->bdfid);
		}
		else {
			$sql = $wpdb->prepare( "SELECT user_id, value FROM {$bp->profile->table_name_data} WHERE field_id = %d AND user_id IN (".implode(',', $this->friendsarray).")", $this->bdfid); 
		}
		
		$profiledata = $wpdb->get_results($sql);
		
		foreach ($profiledata as $userobj) {
			$this->serveroffset = get_option('gmt_offset');
			// The plugin uses a timestamp integer as birthdate
			// in BP 1.5+ the stored object is a string so we need to convert it
			if(!is_numeric($userobj->value)) {
				$bdate = strtotime($userobj->value) + $this->serveroffset;
			}
			// or we've got old metadata (timestamp)
			else {
				$bdate = $userobj->value + $this->serveroffset;
			}
			$buddy = new TDLC_BuddyWithABirthdate($userobj->user_id, $bdate);
			// If it's this buddy's birthday today, push it in the array.
			if($buddy->isBirthdayToday()) {
				$happybday[] = $buddy;
			}
			// Or if this buddy's birthday is soon, push it in this other array.
			else if($buddy->isBirthdayWithinNDays($this->offset)) {
				$upcoming[] = $buddy;
			}
		}
		// If there are no birthdays to announce...
		if (empty($happybday)&&empty($upcoming)) {
			switch ($this->offset) {
				case 0:
					return false;
					break;
				case 1:
					return false;
					break;
				default:
					return false;
					break;
			}
		}
		// There is something to show !
		else {
			return true;
		}

	}
	
	private function printBirthdays($showeveryone = false) {
		global $wpdb, $bp;
		
		// Build birthdates query depending on widget settings (friends or everyone) and availability of the spam flag in the database
		if($showeveryone) {
			$spam_column_test = $wpdb->get_row("SELECT ID FROM $wpdb->users WHERE ID=1 AND user_status != 1");
			if(empty($spam_column_test))
				$sql = $wpdb->prepare( "SELECT user_id, value FROM {$bp->profile->table_name_data} WHERE field_id = %d", $this->bdfid);
			else
				$sql = $wpdb->prepare( "SELECT profile.user_id, profile.value FROM {$bp->profile->table_name_data} profile INNER JOIN $wpdb->users users ON profile.user_id = users.id AND user_status != 1 WHERE profile.field_id = %d", $this->bdfid);
		}
		else {
			$sql = $wpdb->prepare( "SELECT user_id, value FROM {$bp->profile->table_name_data} WHERE field_id = %d AND user_id IN (".implode(',', $this->friendsarray).")", $this->bdfid); 
		}
		
		$profiledata = $wpdb->get_results($sql);
		
		foreach ($profiledata as $userobj) {
			$this->serveroffset = get_option('gmt_offset');
			// The plugin uses a timestamp integer as birthdate
			// in BP 1.5+ the stored object is a string so we need to convert it
			if(!is_numeric($userobj->value)) {
				$bdate = strtotime($userobj->value) + $this->serveroffset;
			}
			// or we've got old metadata (timestamp)
			else {
				$bdate = $userobj->value + $this->serveroffset;
			}
			$buddy = new TDLC_BuddyWithABirthdate($userobj->user_id, $bdate);
			// If it's this buddy's birthday today, push it in the array.
			if($buddy->isBirthdayToday()) {
				$happybday[] = $buddy;
			}
			// Or if this buddy's birthday is soon, push it in this other array.
			else if($buddy->isBirthdayWithinNDays($this->offset)) {
				$upcoming[] = $buddy;
			}
		}
		// If there are no birthdays to announce...
		if (empty($happybday)&&empty($upcoming)) {
			switch ($this->offset) {
				case 0:
					echo sanitize_text_field(esc_attr__('No birthday today...', 'tdlc-birthdays') );
					break;
				case 1:
					echo sanitize_text_field(esc_attr__('No birthdays today or tomorrow...', 'tdlc-birthdays' ) );
					break;
				default:
					printf(sanitize_text_field(esc_attr__('No birthdays in the next %d days...', 'tdlc-birthdays' ) ), $this->offset);
					break;
			}
		}
		// Else just print the lists out !
		else {
			if (!empty($happybday)) $this->happy_bday($happybday); 
			if (!empty($upcoming)) $this->upcoming_bdays($upcoming);
		}
		
		// Display link to user's profile if their birthdate is missing (optional)
		if(!empty($bp->loggedin_user->id)&&$this->showlink==1) {
			if(!xprofile_get_field_data($this->bdfid, $bp->loggedin_user->id)) {
				echo '<br/><a class="tdlc-profilelink" href="'.esc_url($bp->loggedin_user->domain).esc_attr($bp->profile->slug).'/edit/">';
				echo sanitize_text_field(esc_attr__('Add your birthdate to your profile', 'tdlc-birthdays' ) );
				echo '</a>';
			}
		}
	}

	// This short function handles the printing of the Today's Birthdays list
	private function happy_bday($list) {
		echo '<ul>';
		foreach($list as $buddy) {
			$bpbuddy = new BP_Core_User($buddy->id);
			?>
			<style>
			.tdlc-flexcontainer, .tdlc-flexitem-left, .tdlc-flexitem-right {
			  box-sizing: border-box;
			}

			.tdlc-flexcontainer {
			  display: flex;
			  flexwrap: wrap;
			  text-align: left;
			  flexdirection: row;
			}

			.tdlc-flexitem-left {
			  padding: 1px;
			  flex: 25%;
			}

			.tdlc-flexitem-right {
			  padding: 1px;
			  flex: 75%;
			}
			</style>
				<li style="min-height:60px;height:60px">
					<div class="tdlc-flexcontainer" >
						<div class="tdlc-flexitem-left">
						<a href="<?php echo $bpbuddy->user_url ?>"><?php echo $bpbuddy->avatar_thumb ?></a></div>
							<div class="tdlc-flexitem-right"><?php
							echo tdlc_sanitize_text(esc_attr__('Happy birthday to ', 'tdlc-birthdays' ) ).$bpbuddy->user_link;
							if ($this->hideage==1 || $buddy->getAge()==0)
								echo tdlc_sanitize_text(esc_attr__('!', 'tdlc-birthdays') );
							else
								echo tdlc_sanitize_text(esc_attr__(' who turned ', 'tdlc-birthdays' ) ).esc_attr($buddy->getAge()).tdlc_sanitize_text(esc_attr__(' today!', 'tdlc-birthdays'));
							?></div>
						
					</div>
				</li>
			<?php
		}
		echo "</ul>";
	}

	// This other short function handles the printing of the Upcoming Birthdays list
	private function upcoming_bdays($list) {
		// Sort buddies by closest birthday
		usort($list, "tdlc_compareBuddiesBirthdays");
		echo tdlc_sanitize_text(esc_attr__('Upcoming birthdays:', 'tdlc-birthdays'));
		echo '<ul>';
		foreach($list as $buddy) {
			$bpbuddy = new BP_Core_User($buddy->id);
			?>
			<style>
			.tdlc-flexcontainer, .tdlc-flexitem-left, .tdlc-flexitem-right {
			  box-sizing: border-box;
			}

			.tdlc-flexcontainer {
			  display: flex;
			  flexwrap: wrap;
			  text-align: left;
			  flexdirection: row;
			}

			.tdlc-flexitem-left {
			  padding: 1px;
			  flex: 25%;
			}

			.tdlc-flexitem-right {
			  padding: 1px;
			  flex: 75%;
			}
			</style>			
				<li>
				<li style="min-height:60px;height:60px">
					<div class="tdlc-flexcontainer" >
						<div class="tdlc-flexitem-left">
							<a href="<?php echo $bpbuddy->user_url ?>"><?php echo $bpbuddy->avatar_thumb ?></a>
						</div>
						<div class="tdlc-flexitem-right"><?php
						if ($this->hideage==1 || $buddy->getAge()==0) 
							echo $bpbuddy->user_link.sprintf(tdlc_sanitize_text(esc_attr__(' (%1$d/%2$d)', 'tdlc-birthdays' ) ), esc_attr(date("m",$buddy->birthdate)), esc_attr(date("j",$buddy->birthdate)));
						else
							echo $bpbuddy->user_link.sprintf(tdlc_sanitize_text(esc_attr__(' (%1$d/%2$d, %3$d years old)', 'tdlc-birthdays' )), esc_attr(date("m",$buddy->birthdate)), esc_attr(date("j",$buddy->birthdate)), esc_attr($buddy->getAge()));
						?>
						</div>
					</div>
				</li>
			<?php
		}
		echo "</ul>";
	}
}

class TDLCBirthdaysWidget extends WP_Widget {
 
	/**
	 * constructor
	 */	 
	function __construct() {
		parent::__construct('widget_tdlcBirthdays', tdlc_sanitize_text(esc_attr__('Birthdays', 'tdlc-birthdays') ), array('description' => 'TDLC Birthdays for BuddyPress'));	
	}
 
	/**
	 * display widget
	 */	 
	function widget($args, $instance) {
		extract($args, EXTR_SKIP);
		$tdlcBirthdays = new TDLCBirthday($instance);
		if ($tdlcBirthdays->checkBirthdays(true) == false )
			return;
				
		echo $before_widget;
		$title = empty($instance['title']) ? '&nbsp;' : apply_filters('widget_title', $instance['title']);
		// Check for the required settings
		if (!is_array( $instance )||empty($instance['bdfid']))
			echo tdlc_sanitize_text(esc_attr__('Please configure mandatory settings in the widget settings.', 'tdlc-birthdays' ) );
		// Run the main func
		else {
			echo $before_title.$title.$after_title;
			$tdlcBirthdays = new TDLCBirthday($instance);
			global $bp;
			switch ($instance['show_all_bdays']) {
			case 1:
				// everyone
				$tdlcBirthdays->printAllBirthdays();
				break;
			case 2:
				// followed 
				$tdlcBirthdays->printFollowedBirthdays($bp->loggedin_user->id, false);
				break;
			case 3:
				// followed & friends
				$tdlcBirthdays->printFollowedBirthdays($bp->loggedin_user->id, true);
				break;
			default:
				$tdlcBirthdays->printFriendsBirthdays($bp->loggedin_user->id);
			}
		}
		echo $after_widget;
	}
 
	/**
	 *	update/save function
	 */	 	
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		if (isset( $new_instance['title'] ) ) {
			$instance['title'] = htmlspecialchars($new_instance['title']);
		}
		if (isset( $new_instance['bdfid'] ) ) {
			$instance['bdfid'] = esc_attr(tdlc_sanitize_text(htmlspecialchars($new_instance['bdfid'])));
		}
		if (isset( $new_instance['offset'] ) ) {
			$submittedOffset = esc_attr((int)abs($new_instance['offset']));
		}
		if (is_numeric( $submittedOffset ) ) {
			if ($submittedOffset > 364) $submittedOffset = 364;
			$instance['offset'] = esc_attr($submittedOffset);
		}
		if ( isset( $new_instance['hide_age'] ) ) {
			$instance['hide_age'] = esc_attr(htmlspecialchars($new_instance['hide_age']));
		} else {
			$instance['hide_age'] = '';
		}
		if ( isset( $new_instance['show_all_bdays'] )) {
			$instance['show_all_bdays'] = esc_attr(htmlspecialchars($new_instance['show_all_bdays']));
		}
		if ( isset( $new_instance['show_link'] ) ) {
			$instance['show_link'] = esc_attr(htmlspecialchars($new_instance['show_link']));
		} else {
			$instance['show_link'] = '';
		}
		return $instance;
	}
 
	/**
	 *	admin control form
	 */	 	
	function form($instance) {
		$default = 	array(
				'title' => tdlc_sanitize_text(esc_attr__('Birthdays', 'tdlc-birthdays')),
				'bdfid' => '',
				'offset' => '7',
				'hide_age' => '',
				'show_all_bdays' => '',
				'show_link' => ''
		);
		$instance = wp_parse_args( (array) $instance, $default );
		$bpfollowenabled = false;
		if (function_exists('bp_follow_get_following')) {
			$bpfollowenabled = true;
		}
 
		//$field_id = $this->get_field_id('title');
		//$field_name = $this->get_field_name('title');
		//echo "\r\n".'<p><label for="'.$field_id.'">'.__('Title').': <input type="text" class="widefat" id="'.$field_id.'" name="'.$field_name.'" value="'.attribute_escape( $instance['title'] ).'" /><label></p>';
		?>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php echo tdlc_sanitize_text(esc_attr__('Widget Title: ', 'tdlc-birthdays' ) ); ?>
				<input type="text" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" value="<?php echo esc_attr(tdlc_sanitize_text($instance['title']));?>" />
			</label><br />
		</p>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('bdfid')); ?>"><?php echo tdlc_sanitize_text(esc_attr__('Birthday field ID or exact Name: ', 'tdlc-birthdays' ) ); ?>
			<input type="text" id="<?php echo esc_attr($this->get_field_id('bdfid')); ?>" name="<?php echo esc_attr($this->get_field_name('bdfid')); ?>" value="<?php echo esc_attr($instance['bdfid']);?>" />
			</label><br />
			<?php echo tdlc_sanitize_text(esc_attr__('Create a datebox in your BuddyPress Profile Fields to store the birthdays and add its ID or Name here.', 'tdlc-birthdays' ) ); ?>
		</p>
		<p>
			<?php echo tdlc_sanitize_text(esc_attr__('Display birthdays of :', 'tdlc-birthdays' ) ); ?>
					<ul>
						<li>
							<label for="<?php echo esc_attr($this->get_field_id('show_all_bdays')); ?>">
							<input type="radio" id="<?php echo esc_attr($this->get_field_id('show_all_bdays')); ?>" name="<?php echo esc_attr($this->get_field_name('show_all_bdays')); ?>" value="1" <?php if($instance['show_all_bdays']==1) echo 'checked="checked"'; ?> />
							<?php echo tdlc_sanitize_text(esc_attr__(' everyone ', 'tdlc-birthdays')); ?>
							</label>
						</li>
						<li>
							<label for="tdlcBirthdays-ShowFriendsBdays">
							<input type="radio" id="tdlcBirthdays-ShowFriendsBdays" name="<?php echo esc_attr($this->get_field_name('show_all_bdays')); ?>" value="0" <?php if($instance['show_all_bdays']==0 || $instance['show_all_bdays']=='') echo 'checked="checked"'; ?> />
							<?php echo tdlc_sanitize_text(esc_attr__(' friends only ', 'tdlc-birthdays' ) ); ?>
							</label>
						</li>
						<li>
							<label for="tdlcBirthdays-ShowFollowedBdays">
							<input type="radio" id="tdlcBirthdays-ShowFollowedBdays" name="<?php echo esc_attr($this->get_field_name('show_all_bdays')); ?>" value="2" <?php if($instance['show_all_bdays']==2) echo 'checked="checked"'; if(!$bpfollowenabled) echo ' disabled="disabled"'; ?> />
							<?php echo tdlc_sanitize_text(esc_attr__(' followed people only ', 'tdlc-birthdays') ); ?>
							</label>
						</li>
						<li>
							<label for="tdlcBirthdays-ShowFollowedAndFriendsBdays">
							<input type="radio" id="tdlcBirthdays-ShowFollowedAndFriendsBdays" name="<?php echo esc_attr($this->get_field_name('show_all_bdays')); ?>" value="3" <?php if($instance['show_all_bdays']==3) echo 'checked="checked"'; if(!$bpfollowenabled) echo ' disabled="disabled"'; ?> />
							<?php echo tdlc_sanitize_text(esc_attr__(' friends and followed people ', 'tdlc-birthdays' ) ); ?>
							</label>
						</li>
					</ul>
			<?php echo tdlc_sanitize_text(esc_attr__('Only display birthdays of the users friends', 'tdlc-birthdays' ) ); ?>
				<br />
			<?php if(!$bpfollowenabled){
				echo tdlc_sanitize_text(esc_attr__('For Followed people - you must install the Followers plugin', 'tdlc-birthdays' ) ); 
				}
				else echo tdlc_sanitize_text(esc_attr__('For \'followed people\', the user must be registered, connected and follow people!','tdlc-birthdays' ) );
	
			?>
		</p>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('offset')); ?>"><?php echo tdlc_sanitize_text(esc_attr__('Display birthdays happening in the next: ', 'tdlc-birthdays') ); ?>
			<input type="text" id="<?php echo esc_attr($this->get_field_id('offset')); ?>" name="<?php echo esc_attr($this->get_field_name('offset')); ?>" size="2" value="<?php echo esc_attr($instance['offset']);?>" /><?php echo tdlc_sanitize_text(esc_attr__(' days.', 'tdlc-birthdays' ) ); ?></label><br />
			<?php echo tdlc_sanitize_text(esc_attr__('Set to 0 if you don\'t want to display upcoming birthdays.', 'tdlc-birthdays' ) ); ?>
		</p>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('hide_age')); ?>"><?php echo tdlc_sanitize_text(esc_attr__('Hide ages', 'tdlc-birthdays')); ?>
			<input type="checkbox" id="<?php echo esc_attr($this->get_field_id('hide_age')); ?>" name="<?php echo esc_attr($this->get_field_name('hide_age')); ?>" value="1" <?php if($instance['hide_age']==1) echo 'checked="checked"'; ?> /></label><br />
			<?php echo tdlc_sanitize_text(esc_attr__('Check this if you don\'t want the widget to tell how old people are!', 'tdlc-birthdays' ) ); ?>
		</p>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('show_link')); ?>"><?php echo tdlc_sanitize_text(esc_attr__('Show profile link', 'tdlc-birthdays' ) ); ?>
			<input type="checkbox" id="<?php echo esc_attr($this->get_field_id('show_link')); ?>" name="<?php echo esc_attr($this->get_field_name('show_link')); ?>" value="1" <?php if($instance['show_link']==1) echo 'checked="checked"'; ?> /></label><br />
			<?php echo tdlc_sanitize_text(esc_attr__('Check this if you want the widget to display an "add your birdthdate" link if current user\'s birthdate is missing from their profile.', 'tdlc-birthdays' ) ); ?>
		</p>
		<?php
	}
}

/* Tool functions */
// Comparaison function for sorting birthdays
function tdlc_compareBuddiesBirthdays($buddya, $buddyb) {
	if ( $buddya->getNextBirthday() == $buddyb->getNextBirthday() )
		return 0;
	return ( $buddya->getNextBirthday() < $buddyb->getNextBirthday() ) ? -1 : 1;
}

// Get all WP users ids - deprecated
function tdlc_getAllWPUsersIds() {
	global $wpdb;
	$has_spam_column = $wpdb->query("SELECT ID, display_name FROM $wpdb->users WHERE spam != 1 ORDER BY ID");	  
	if ($has_spam_column === False) {
		$wp_user_search = $wpdb->get_results("SELECT ID, display_name FROM $wpdb->users ORDER BY ID");	
	}
	else {
		$wp_user_search = $wpdb->get_results("SELECT ID, display_name FROM $wpdb->users WHERE spam != 1 ORDER BY ID");	
	}
	foreach ( $wp_user_search as $userid ) {
		$userids[] = (int) $userid->ID;
	}
	return $userids;
}

function tdlc_sanitize_text($text) {
    $text = preg_replace('/[\cK\f\r\x85]+/', '', $text);
    $text = preg_replace('/\h+/', ' ', $text);
    return  $text;
}


// Localization and start
function tdlc_Birthdays_init() {

	load_plugin_textdomain('tdlc-birthdays', false, dirname(plugin_basename( __FILE__ ) ).'/languages/' );
	register_widget('TDLCBirthdaysWidget');
}
 
add_action('widgets_init', 'tdlc_Birthdays_init');
?>