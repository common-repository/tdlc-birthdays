<?php
if(!class_exists('tdlc_mail_send_Class'))
{   
	#[AllowDynamicProperties]
    class tdlc_mail_send_Class  // We'll use this just to avoid function name conflicts 
    {
        var $option = 'tdlc-birthday-mail';
        
        var $settings;
        public function __construct(){ 
            $this->settings = get_option($this->option);

            add_action('xprofile_data_after_save',array($this,'remove_previous_crons_on_update'),10,1);
            add_action('bp_send_birthday_email',array($this,'bp_send_birthday_email'),10,1);
            add_filter( 'cron_schedules',array($this, 'cron_add_year' ));
            //add_action('xprofile_updated_profile',array($this,'remove_previous_crons'),10,5);
            //add_action('bp_core_activated_user',array($this,'schedule_birthday_mail'));
            //add_action('user_register',array($this,'schedule_birthday_mail'));
              
            


     
        } // END public function __construct
        
        public static function activate(){
            // ADD Custom Code which you want to run when the plugin is activated
            
            $emails=apply_filters('extend_bmbp',array('bmbp_mail'=>array(
            'description'=> __('Happy birthday','vibe'),
            'subject' =>  sprintf(__('Happy birthday %s','vibe'),'{{user}}'),
            'message' =>  sprintf(__('Congratulations on your birthday %s','vibe'),'{{user}}')
            )));


            $post_type = (function_exists('bp_get_email_post_type')?bp_get_email_post_type():'email');
            $tax_type = bp_get_email_tax_type();
            foreach($emails as $id=>$email){
                
                if(!term_exists($id,$tax_type)){
                  $id = wp_insert_term($id,$tax_type, array('description'=> $email['description']));
                  if(!is_wp_error($id)){
                      $textbased = str_replace('titlelink','name',$email['message']);
                      $textbased = str_replace('userlink','name',$email['message']);
                      $post_id = wp_insert_post(array(
                                  'post_title'=> '[{{{site.name}}}] '.$email['subject'],
                                  'post_content'=> $email['message'],
                                  'post_excerpt'=> $textbased,
                                  'post_type'=> $post_type,
                                  'post_status'=> 'publish',
                              ),true);

                      wp_set_object_terms( $post_id, $id, $tax_type );
                  }
                }
            }
        }

        public static function deactivate(){
            if(wp_next_scheduled('bp_send_birthday_email'))
                wp_clear_scheduled_hook('bp_send_birthday_email');     
        }
        
        function remove_previous_crons_on_update($fields){
            if(empty($this->settings) || empty($this->settings['bp_birthday_profile_field_name']))
                return;
            $field = new BP_XProfile_Field( $fields->field_id );

            if($field->name == $this->settings['bp_birthday_profile_field_name']){

                $year = date('Y');
                $birthday = substr($fields->value,5);
                $birthday  = strtotime( ($year.'-'.$birthday) );
                if(($year % 4) == 0){
                   $birthday = $birthday + 86400; 
                }
                $timestamp = $birthday +( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
                $args = array($fields->user_id);
                wp_clear_scheduled_hook('bp_send_birthday_email',array($fields->user_id));
                if($timestamp <= time()){
                    $timestamp = $timestamp + 31536000;
                }

                    if(!wp_next_scheduled('bp_send_birthday_email',$args)){
                        wp_schedule_event( $timestamp ,'everyyear','bp_send_birthday_email',$args);
                    }
            }
        }
        
        /*function remove_previous_crons($user_id, $posted_field_ids, $errors, $old_values, $new_values){
            $field_birthday = ''; 
            foreach($posted_field_ids as $field_id){
                $field = new BP_XProfile_Field( $field_id );
                if($field->name == 'birthday'){
                    $field_birthday = $field->name;
                    break;
                }else{
                    return;
                }
            }
            $args_user_field = array(
                    'field'   =>  $field_birthday , // Field name or ID.
                    'user_id' => $user_id
                    );
            $year = date('Y');
            $birthday = bp_get_profile_field_data( $args_user_field );
            $birthday = substr($birthday,5);
            $birthday  = strtotime( ($year.'-'.$birthday) );
            if(($year % 4) == 0){
               $birthday = $birthday + 86400; 
            }
            $timestamp = $birthday +( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
            $args = array($user_id);
            wp_clear_scheduled_hook('bp_send_birthday_email',array($user_id));
            $timestamp = $birthday +( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
            if($timestamp <= time()){
                $timestamp = $timestamp + 31536000;
            }
            if(!wp_next_scheduled('bp_send_birthday_email',$args)){
                 wp_schedule_event( $timestamp ,'everyyear','bp_send_birthday_email',$args);
            }
        }*/

        function schedule_birthday_mail($user_id){
            if(empty($this->settings) || empty($this->settings['bp_birthday_profile_field_name']))
                return;
            $args_user_field = array(
                    'field'   => $this->settings['bp_birthday_profile_field_name'], // Field name or ID.
                    'user_id' => $user_id
                    );
           
            $year = date('Y');
            $birthday = bp_get_profile_field_data( $args_user_field );
            print_r(    $birthday);
            $birthday = substr($birthday,5);
            $birthday  = strtotime( ($year.'-'.$birthday) );
            if(($year % 4) == 0){
               $birthday = $birthday + 86400; 
            }
            $timestamp = $birthday +( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
            $args = array($user_id);
            wp_clear_scheduled_hook('bp_send_birthday_email',array($user_id));
                if(!wp_next_scheduled('bp_send_birthday_email',$args)){
           
          
                     wp_schedule_event( $timestamp ,'everyyear','bp_send_birthday_email',$args);
                }
        }

        function bp_send_birthday_email($user_id){
            //Check is user ID exists.
            if(!empty($user_id))
            $user = get_user_by('id',$user_id);

            if( email_exists($user->user_email)){
            //-----

                /*$headers = array('Content-Type: text/html; charset=UTF-8');
                $username = bp_core_get_user_displayname($user_id);
                $subject = str_replace('{{user}}',$username ,$subject);
                $message = str_replace('{{user}}',$username ,$message);

                   /*

                wp_mail($user->user_email,$subject,$message, $headers);*/

                $args = array('action'=>'bmbp_mail','tokens'=>array('user'=>bp_core_get_user_displayname($user_id)));
                $email_type = $args['action'];
                $bpargs = array(
                    'tokens' => $args['tokens'],
                );
                $status =  bp_send_email( $email_type,$user->user_email, $bpargs );


               
            }
        }

        
   
        // add custom interval
        function cron_add_year( $schedules ) {
            // Adds once every minute to the existing schedules.
            $schedules['everyyear'] = array(
                'interval' => 31536000,
                'display' => sanitize_text_field(esc_attr__( 'Once Every Year' ,'tdlc-birthdays'))
            );
            return $schedules;
        }
       
        
        // ADD custom Code in clas
        
    } // END class WPLMS_Customizer_Class
} // END if(!class_exists('WPLMS_Customizer_Class'))
