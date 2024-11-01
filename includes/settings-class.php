<?php


 if ( ! defined( 'ABSPATH' ) ) exit;

class tdlc_birthdays_mail_settings{

	var $settings;
	var $option = 'tdlc-birthday-mail';

	public function __construct(){
		add_options_page(__('Birthdays ','tdlc-birthdays'),__('Birthdays','tdlc-birthdays'),'manage_options','tdlc-birthdays',array($this,'settings'));
		$this->settings=$this->get(); 
	}

	function get(){
		return get_option($this->option);
	}
	function put($value){
		update_option($this->option,$value);
	}


	function settings(){
		$tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'general';
		$this->settings_tabs($tab);
		$this->$tab();
	}

	function settings_tabs( $current = 'general' ) {
	    $tabs = array( 
	    		'general' => __('General','tdlc-birthdays'), 
	    		);
	    echo '<div id="icon-themes" class="icon32"><br></div>';
	    echo '<h2 class="nav-tab-wrapper">';
	    foreach( $tabs as $tab => $name ){
	        $class = ( $tab == $current ) ? ' nav-tab-active' : '';
	        echo "<a class='nav-tab$class' href='?page=tdlc-birthdays&tab=$tab'>$name</a>";

	    }
	    echo '</h2>';
	    if(isset($_POST['save'])){
	    	$this->save();
	    }
	}

	function general(){
		echo '<h3>'.__('Birthday mail settings','tdlc-birthdays').'</h3>';
	
		$settings=array(
				array(
					'label' => __('Birthday profile field Name','tdlc-birthdays'),
					'name' =>'bp_birthday_profile_field_name',
					'type' => 'text',
					'std' => 'birthday',
					'desc' => __('Provide profile field name for birthday (Case sensitive)','tdlc-birthdays')
				),
				array(
					'label' => __('Birthday Mail Subject','tdlc-birthdays'),
					'name' =>'bp_birthday_mail_subject',
					'type' => 'textarea',
					'std'=>__('Happy birthday {{user}}','tdlc-birthdays'),
					'desc' => __('Set Subject of your birthday email here (Use {{user}} token for user\'s name )','tdlc-birthdays')
				),
				array(
					'label' => __('Birthday Mail content','tdlc-birthdays'),
					'name' =>'bp_birthday_mail_content',
					'type' => 'textarea',
					'std'=>__('Congratulations on your birthday {{user}}','tdlc-birthdays'),
					'desc' => __('Set content of your birthday email here (Use {{user}} token for user\'s name )','tdlc-birthdays')
				),
			);

		$this->generate_form('general',$settings);
	}

	
	
	function generate_form($tab,$settings=array()){
		echo '<form method="post">
				<table class="form-table">';
		wp_nonce_field('save_settings','_wpnonce');   
		echo '<ul class="save-settings">';

		foreach($settings as $setting ){
			echo '<tr valign="top">';
			global $wpdb,$bp;
			switch($setting['type']){
				case 'textarea': 
					echo '<th scope="row" class="titledesc">'.$setting['label'].'</th>';
					echo '<td class="forminp"><textarea name="'.$setting['name'].'">'.(isset($this->settings[$setting['name']])?$this->settings[$setting['name']]:(isset($setting['std'])?$setting['std']:'')).'</textarea>';
					echo '<span>'.$setting['desc'].'</span></td>';
				break;
				case 'select':
					echo '<th scope="row" class="titledesc">'.$setting['label'].'</th>';
					echo '<td class="forminp"><select name="'.$setting['name'].'" class="chzn-select">';
					foreach($setting['options'] as $key=>$option){
						echo '<option value="'.$key.'" '.(isset($this->settings[$setting['name']])?selected($key,$this->settings[$setting['name']]):'').'>'.$option.'</option>';
					}
					echo '</select>';
					echo '<span>'.$setting['desc'].'</span></td>';
				break;
				case 'checkbox':
					echo '<th scope="row" class="titledesc">'.$setting['label'].'</th>';
					echo '<td class="forminp"><input type="checkbox" name="'.$setting['name'].'" '.(isset($this->settings[$setting['name']])?'CHECKED':'').' />';
					echo '<span>'.$setting['desc'].'</span></td>';
				break;
				case 'number':
					echo '<th scope="row" class="titledesc">'.$setting['label'].'</th>';
					echo '<td class="forminp"><input type="number" name="'.$setting['name'].'" value="'.(isset($this->settings[$setting['name']])?$this->settings[$setting['name']]:'').'" />';
					echo '<span>'.$setting['desc'].'</span></td>';
				break;
				case 'hidden':
					echo '<input type="hidden" name="'.$setting['name'].'" value="1"/>';
				break;
				case 'bp_fields':
					echo '<th scope="row" class="titledesc">'.$setting['label'].'</th>';
					echo '<td class="forminp"><a class="add_new_map button">'.__('Add BuddyPress profile field map','tdlc-birthdays').'</a>';

					global $bp,$wpdb;;
					$table =  $bp->profile->table_name_fields;
					$bp_fields = $wpdb->get_results("SELECT DISTINCT name FROM {$table}");

					echo '<ul class="bp_fields">';
					if(is_array($this->settings[$setting['name']]['field']) && count($this->settings[$setting['name']]['field'])){
						foreach($this->settings[$setting['name']]['field'] as $key => $field){
							echo '<li><label><select name="'.$setting['name'].'[field][]">';
							foreach($setting['fields'] as $k=>$v){
								echo '<option value="'.$k.'" '.(($field == $k)?'selected=selected':'').'>'.$k.'</option>';
							}
							echo '</select></label><select name="'.$setting['name'].'[bpfield][]">';
							foreach($bp_fields as $f){
								echo '<option value="'.$f->name.'" '.(($this->settings[$setting['name']]['bpfield'][$key] == $f->name)?'selected=selected':'').'>'.$f->name.'</option>';
							}
							echo '</select><span class="dashicons dashicons-no remove_field_map"></span></li>';
						}
					}
					echo '<li class="hide">';
					echo '<label><select rel-name="'.$setting['name'].'[field][]">';
					foreach($setting['fields'] as $k=>$v){
						echo '<option value="'.$k.'">'.$k.'</option>';
					}
					echo '</select></label>';
					echo '<select rel-name="'.$setting['name'].'[bpfield][]">';
					
					foreach($bp_fields as $f){
						echo '<option value="'.$f->name.'">'.$f->name.'</option>';
					}
					echo '</select>';
					echo '<span class="dashicons dashicons-no remove_field_map"></span></li>';
					echo '</ul></td>';
				break;
				default:
					echo '<th scope="row" class="titledesc">'.$setting['label'].'</th>';
					echo '<td class="forminp"><input type="text" name="'.$setting['name'].'" value="'.(isset($this->settings[$setting['name']])?$this->settings[$setting['name']]:(isset($setting['std'])?$setting['std']:'')).'" />';
					echo '<span>'.$setting['desc'].'</span></td>';
				break;
			}
			
			echo '</tr>';
		}
		echo '</tbody>
		</table>';
		echo '<input type="submit" name="save" value="'.__('Save Settings','tdlc-birthdays').'" class="button button-primary" /></form>';

		//Scheduled emails : 
		echo '<br><br><br><div class="clear"></div><h3>'.__('Scheduled birthday mails','bmbp').'</h3>';
		if(!function_exists('_get_cron_array'))
			return;
		if(!empty($_POST) && isset($_POST['remove_schedule'])){
			if( wp_verify_nonce($_POST['remove_scheduled_email_security'],'remove_scheduled_email_'.$_POST['timestamp']) && !empty($_POST['cron_key'])){
				$key = $_POST['cron_key'];
				$crons = _get_cron_array();
				$timestamp =  $_POST['timestamp'];
				$hook = $_POST['hook'];
			   
			    unset( $crons[$timestamp][$hook][$key] );
			    if ( empty($crons[$timestamp][$hook]) ){
			        unset( $crons[$timestamp][$hook] );
			    }
			    if ( empty($crons[$timestamp]) ){
			        unset( $crons[$timestamp] );
			    }
			    _set_cron_array( $crons );
			    unset($_POST['remove_scheduled_email_security']);
			    echo '<div class="clear"></div><div class="updated notice is-dismissible"><p>'.__('Cron removed.','bmbp').'</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">'.__('Dismiss this notice.','bmbp').'</span></button></div>';
			}else{
				echo '<div class="clear"></div><div class="notice notice-error"><p>'.__('There was an error while removing cron.','bmbp').'</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">'.__('Dismiss this notice.','bmbp').'</span></button></div>';
			}
		}
		echo '<br style="clear:both"><table class="form-table">';
		
		$crons =  _get_cron_array();
		if(!empty($crons)){
/* 			?>
			<script>
			var confirm_message_cron= "<?php echo sanitize_text_field(esc_attr__('Are you sure you want to remove this schedule?','tdlc-birthdays')); ?>";
			jQuery('body').delegate('input[type="submit"].remove_schedule_submit','click',function(e){
			        if(confirm(confirm_message_cron )){

			        }else{
			            e.stopPropagation();
			            e.preventDefault();
			        }
        
    		});
    		</script>
			 */
			$format = get_option('date_format') . ' - '. get_option('time_format');
			$data = '';
			$check_emails = 0;
			foreach($crons as $timestamp => $cron){
				if(isset($cron['bp_send_birthday_email']) || isset($cron['bp_send_birthday_email'])){
					$check_emails++;
					
						$value = $cron['bp_send_birthday_email'];
						$data = '<form method="post" id="drip" class="remove_schedule" action="?page=tdlc-birthdays"><input name="hook" value="bp_send_birthday_email" type="hidden"  data-hook="bp_send_birthday_email" >';
						$data .='<input type="hidden" name="remove_scheduled_email_security" value="'.wp_create_nonce('remove_scheduled_email_'.$timestamp).'" id="remove_scheduled_email_security">';
						foreach($value as $v){
							
							$data .= '<input name="timestamp" type="hidden" value="'.$timestamp.'">';
							$data .= '<input type="hidden" name="cron_key" value="'.md5(serialize($v['args'])).'"> ';
							$data .= '<input type="submit" class="button-primary remove_schedule_submit button" name="remove_schedule" value="'.__('Remove Schedule','bmbp').'"></form>';
							
							$user_id = $v['args'][0];
							break;

					}
					 
					echo '<tr><th><label>'.__('Birthday email','bmbp').'</label></th><td>'.get_date_from_gmt ( date( 'Y-m-d H:i:s', $timestamp ), $format ).'</td><td>'.(!empty($user_id)?bp_core_get_user_displayname($user_id):__('N.A','bmbp')).'</td><td>'.$data.'</td></tr>';

				}
			}
			
			if($check_emails == 0){
				echo '<div class="message"><h3>'.__('No Scheduled emails','bmbp').'</h3></div>';
			}
		}else{
			echo '<div class="message"><h3>'.__('No Scheduled emails','bmbp').'</h3></div>';
		}
		echo '</table>';

	}


	function save(){
		//$none = $_POST['save_settings'];
		if ( !isset($_POST['save']) || !isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'],'save_settings') ){
		    _e('Security check Failed. Contact Administrator.','tdlc-birthdays');
		    die();
		}
		unset($_POST['_wpnonce']);
		unset($_POST['_wp_http_referer']);
		unset($_POST['save']);

		foreach($_POST as $key => $value){
			$this->settings[$key]=$value;
		}

		$this->put($this->settings);
	}
}

add_action('admin_menu','init_tdlc_birthdays_mail_settings',100);
function init_tdlc_birthdays_mail_settings(){
	if(function_exists('bp_core_get_user_displayname'))
	new tdlc_birthdays_mail_settings;	
}
