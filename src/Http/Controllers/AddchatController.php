<?php

namespace Classiebit\Addchat\Http\Controllers;
use Classiebit\Addchat\Models\AddchatModel;
use Facades\Classiebit\Addchat\Addchat;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Crypt;

use App\Http\Controllers\Controller; 

use Illuminate\Http\Request;
use Auth;
use Validator;

use Classiebit\Addchat\Models\BlockUser;
use Classiebit\Addchat\Models\Contact;
use Classiebit\Addchat\Models\Group;
use Classiebit\Addchat\Models\GroupChat;
use Classiebit\Addchat\Models\Guest;
use Classiebit\Addchat\Models\GuestNotification;
use Classiebit\Addchat\Models\Message;
use Classiebit\Addchat\Models\Profile;
use Classiebit\Addchat\Models\User;
use Classiebit\Addchat\Models\UserGroup;
use Classiebit\Addchat\Models\UserNotification;

class AddchatController extends Controller
{
    protected $AC_SETTINGS;
	
    public function __construct()
    {
	    $this->AC_SETTINGS          = (object) config('addchat');

	    // call Addchat model constructor first
		$this->block_user 			= new BlockUser();
		$this->contact 	    		= new Contact();
		$this->group 	    		= new Group();
		$this->group_chat 			= new GroupChat();
		$this->guest 	    		= new Guest();
		$this->guest_notification 	= new GuestNotification();
		$this->message 				= new Message();
		$this->profile 				= new Profile();
		$this->user 			    = new User();
		$this->user_group 			= new UserGroup();
		$this->user_notification 	= new UserNotification();
	}

    /**
     *  initialize configuration setting
     */
	protected function init_config()
	{
        // get the logged-in user
		$this->AC_SETTINGS->logged_user_id  = Auth::id();
		
		// get the admin user
        $this->AC_SETTINGS->is_admin        = 0;
        if((int) $this->AC_SETTINGS->admin_user_id === (int) $this->AC_SETTINGS->logged_user_id)
            $this->AC_SETTINGS->is_admin    = $this->AC_SETTINGS->admin_user_id;
		
		// user belongs to multiple groups
		$logged_group_ids  = [];
		if(!empty($this->AC_SETTINGS->logged_user_id) && $this->AC_SETTINGS->groups_table)
			$logged_group_ids 					= $this->user_group->get_groups_id($this->AC_SETTINGS->logged_user_id);

        // tell addchat about it
		$this->AC_SETTINGS->logged_group_id = [];
		if(!empty($logged_group_ids))
			foreach($logged_group_ids as $key => $value)
				$this->AC_SETTINGS->logged_group_id[$key] = $value['group_id'];

        // get the guest group id
        $this->AC_SETTINGS->guest_mode      = 0;
        if((int) $this->AC_SETTINGS->guest_group_id)
            $this->AC_SETTINGS->guest_mode  = 1;
	}
    
    /**
     *  response in json
     */
    
	protected function format_json($data = array())
	{
		return response($data, Response::HTTP_OK);
	}

    /**
     * Check if user logged in
    */
    protected function check_auth()
    {
        if(!$this->AC_SETTINGS->logged_user_id) 
    		return $this->format_json(array('status' => false, 'response'=> __('addchat::ac.access_denied')));

        return true;
	}
	
	/*
    * Get configurations
    */
    public function get_config()
    { 
		// init config in each method
		$this->init_config();

        // return only selected settings
		$data['config'] 						    = array();
		$data['config']['widget_name'] 		        = $this->AC_SETTINGS->widget_name;
		$data['config']['widget_logo'] 		        = $this->AC_SETTINGS->widget_logo;
		$data['config']['widget_icon'] 		        = $this->AC_SETTINGS->widget_icon;
		$data['config']['widget_user_avatar'] 		= $this->AC_SETTINGS->widget_user_avatar;
		$data['config']['widget_notify_sound'] 		= $this->AC_SETTINGS->widget_notify_sound;
		$data['config']['widget_footer_text'] 	    = $this->AC_SETTINGS->widget_footer_text;
		$data['config']['widget_footer_url']        = $this->AC_SETTINGS->widget_footer_url;
		$data['config']['upload_path']              = $this->AC_SETTINGS->upload_path;
		$data['config']['aui'] 		                = (int) $this->AC_SETTINGS->admin_user_id;
		$data['config']['lui'] 		                = (int) $this->AC_SETTINGS->logged_user_id;
		$data['config']['hide_email'] 		        = $this->AC_SETTINGS->hide_email ? 1 : 0;
		$data['config']['enter_send'] 		        = $this->AC_SETTINGS->enter_send ? 1 : 0;
		$data['config']['open_chat_on_notification']= $this->AC_SETTINGS->open_chat_on_notification ? 1 : 0;
		$data['config']['pusher_key'] 		        = $this->AC_SETTINGS->pusher_key;
		$data['config']['pusher_cluster'] 		    = $this->AC_SETTINGS->pusher_cluster;

		$data['config']['s_host']            = $_SERVER['REMOTE_ADDR'];
		$data['config']['check_session']     = session('ac_verify') ? 1 : 0;
		$data['config']['is_admin']			 = $this->AC_SETTINGS->is_admin;
		$data['config']['is_groups']		 = $this->AC_SETTINGS->groups_table ? 1 : 0;
		$data['config']['include_or_exclude']= (!empty($this->AC_SETTINGS->include_url) || !empty($this->AC_SETTINGS->exclude_url)) ? 1 : 0;
		$data['config']['guest_mode']	     = $this->AC_SETTINGS->guest_mode;
		$data['config']['notification_type'] = $this->AC_SETTINGS->notification_type != 'internal' ? 1 : 0;
		
        return $this->format_json($data);
	}
	
	/**
	 *  check session
	 */
    
    public function check_session()
    {
        session(['ac_verify'=>1]);
        return response()->json(['status' => 1]);
	}    
	
	/**
	 *  multi_array_diff
	 */  
	  
	public function multi_array_diff($arraya, $arrayb)
	{
		foreach ($arraya as $keya => $valuea) 
		{
			if(!in_array($valuea, $arrayb))
			{
				return true;
			}
		}
		return false;
	}
	
	/*
 	 * check admin authentication
     * 
     */
    public function check_admin($is_return = false)
    {
		// init config in each method
		$this->init_config();
		
		if((int) $this->AC_SETTINGS->is_admin !== (int) $this->AC_SETTINGS->logged_user_id)
			return $this->format_json(array('status' => false));

		if(!$is_return)
			return $this->format_json(array('status' => true));

		return true;
	}

    /**
	 *	get guest group users for guest user 
	 */

	protected function get_guest_group_users()
	{
		$this->init_config();
		$guest_group_id		= $this->AC_SETTINGS->guest_group_id;

		$group_users_ids 	= $this->user_group->get_guest_group_users_ids($guest_group_id);
		
		if(empty($group_users_ids))
			return array();

		$users_ids = [];
		
		foreach($group_users_ids as $key => $value)
		{
			$users_ids[$key] = $value['user_id'];
		}	
		
		$group_users 		= $this->user->get_guest_group_users($users_ids);

		return  $group_users;
	}
	
}
