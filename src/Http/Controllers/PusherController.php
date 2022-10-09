<?php

namespace Classiebit\Addchat\Http\Controllers;


use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Crypt;

use App\Http\Controllers\Controller; 

use Illuminate\Http\Request;
use Facades\Classiebit\Addchat\Addchat;
use Auth;

use Validator;
use Classiebit\Addchat\Http\Controllers\AddchatController;

class PusherController extends AddchatController
{
    
    /**
     *  construct
     */
    
    public function __construct()
    {
        Parent::__construct();
        
    }

    /**
	 *  pusher intialization
	 */

	protected function pusher_init()
	{
		// init config in each method
		$this->init_config();

		$pusher_key      = str_replace('"', '',$this->AC_SETTINGS->pusher_key);
		$pusher_secret	 = str_replace('"', '',$this->AC_SETTINGS->pusher_secret);
		$pusher_app_id   = str_replace('"', '',$this->AC_SETTINGS->pusher_app_id);
		$pusher_cluster  = str_replace('"', '',$this->AC_SETTINGS->pusher_cluster);
		
		$pusher = new \Pusher\Pusher($pusher_key, $pusher_secret, $pusher_app_id, array('cluster' => $pusher_cluster));

		return $pusher;
	}
	
	/**
	 *  check user authorization
	 */

	public function auth(Request $request)
	{
		// init config in each method
		$this->init_config();	

		$pusher_key      = str_replace('"', '',$this->AC_SETTINGS->pusher_key);
		$pusher_secret	 = str_replace('"', '',$this->AC_SETTINGS->pusher_secret);
		$pusher_app_id   = str_replace('"', '',$this->AC_SETTINGS->pusher_app_id);
		$pusher_cluster  = str_replace('"', '',$this->AC_SETTINGS->pusher_cluster);
		
		
		if(empty($this->AC_SETTINGS->logged_user_id) && empty($_POST['guest_id']))
		{
			echo(__('addchat::ac.login_first'));
			die();
		}
	
		$socket_id  	= $_POST['socket_id'];
		$channel_name    = $_POST['channel_name'];
		
		$pusher = new \Pusher\Pusher($pusher_key, $pusher_secret, $pusher_app_id , array('cluster' => $pusher_cluster));
		
		
		$auth  = $pusher->socket_auth($channel_name, $socket_id);
		
		echo($auth);
	}

	/**
	 *   real time message send notification
	 */

	public function message_notification(Request $request)
	{
		// init config in each method
		$this->init_config();	
		
		// pusher intialization
		$pusher = $this->pusher_init();
		
		// get notification
		$notification 	= $this->user_notification->get_updates($request->buddy_id);


		$data  = [
			'message' 	  =>	json_decode($request->latest_message), 
			'notification' =>	$notification,
			'status'       =>   true,
		];

		$pusher->trigger('private-message.'.$request->buddy_id, 'message-send', $data);

		return $this->format_json(['status' =>true]);
	}

	/**
	 * 	is typing
	 */
	public function is_typing()
    {
		// init config in each method
		$this->init_config();	
		
		// pusher intialization
		$pusher = $this->pusher_init();
		
		
		$pusher->trigger('private-typing.'.$_POST['buddy_id'], 'is-typing', array('typing_user' => json_decode($_POST['typing_user'])));

		return $this->format_json(['status' =>true]);
	}


	/**
	 *   is_read _update
	 */

	public function is_read()
	{	
		// init config in each method
		$this->init_config();

		// pusher intialization
		$pusher = $this->pusher_init();

		$this->message->is_read($this->AC_SETTINGS->logged_user_id, $_POST['buddy_id']);

		$data['status'] 			= true;

		$pusher->trigger('private-read.'.$_POST['buddy_id'], 'is-read', array('data' => $data, 'buddy_id' => $this->AC_SETTINGS->logged_user_id));
		
		return $this->format_json(['status' =>true]);
	} 

    //=============pusher notification for guest=========================

	/**
	 *   real time message send notification for guest
	 */

	public function guest_message_notification(Request $request)
	{
		// init config in each method
		$this->init_config();

		// pusher intialization
		$pusher = $this->pusher_init();

		$guest_messages      = [];
		$guest_notifications = [];

		$guest_messages  = (array)json_decode($request->latest_message);
		
		
		// notification for loing user
		if(!empty($this->AC_SETTINGS->logged_user_id))
		{
			$params = [
				'guest_id' => $request->guest_id,
			];

			$guest_notifications    = $this->guest_notification->get_guest_updates($params);

			$user               	= $this->user->get_profile($this->AC_SETTINGS->logged_user_id, $this->AC_SETTINGS->guest_group_id);
			
			$guest_messages['m_from_image']   = $user->avatar;
			$guest_messages['m_from_name']    = $user->fullname;
			
			
			$data  = [
				'guest_messages' 	  =>	$guest_messages, 
				'guest_notifications' =>	$guest_notifications,
				'status'              =>    true,
			];
			
			$pusher->trigger('private-guest-message.'.'g_'.$request->guest_id, 'guest-message-send', $data);
		}	
		else
		{	
			$group_users  = [];
			// get guest group users	
			$group_users  		= (array)$this->get_guest_group_users();

			// get admin user
			if(!empty($this->AC_SETTINGS->admin_user_id))
			{
				$admin_guest        = $this->user->get_profile($this->AC_SETTINGS->admin_user_id, $this->AC_SETTINGS->guest_group_id);

				if(!empty($group_users && !empty($admin_guest)))
					array_push($group_users, $admin_guest);
				else if(empty($group_users) && !empty($admin_guest))
					$group_users = 	$admin_guest;
			}	
			
			
			if(empty($group_users))
				return $this->format_json(array('status' => false));

				
			foreach($group_users as $key => $value)
			{
				$params   = [
					'login_user_id' => $value['id'],
				];
				
				$guest_notifications     = $this->guest_notification->get_guest_updates($params);

				$data  = [
					'guest_messages' 	  =>	$guest_messages, 
					'guest_notifications' =>	$guest_notifications,
					'status'              =>    true,
				
				];
				$pusher->trigger('private-guest-message.'.'lg_'.$value['id'], 'guest-message-send', $data);
			}	
			
		}

		return $this->format_json(['status' =>true]);

	}

	
	/**
	 *   is_read _update  for guest
	 */

	public function is_read_guest()
	{	
		// init config in each method
		$this->init_config();

		// pusher intialization
		$pusher = $this->pusher_init();

		$this->message->is_read_guest($this->AC_SETTINGS->logged_user_id, $_POST['guest_id']);

		if(empty($this->AC_SETTINGS->logged_user_id))
		{
			$group_users  = [];
			// get guest group users	
			$group_users  		= (array)$this->get_guest_group_users();

			// get admin user
			if(!empty($this->AC_SETTINGS->admin_user_id))
			{
				$admin_guest        = $this->user->get_profile($this->AC_SETTINGS->admin_user_id, $this->AC_SETTINGS->guest_group_id);

				if(!empty($group_users && !empty($admin_guest)))
					array_push($group_users, $admin_guest);
				else if(empty($group_users) && !empty($admin_guest))
					$group_users = 	$admin_guest;
			}
			
			
			if(empty($group_users))
				return $this->format_json(array('status' => false));

			$data['status'] 			= true;

			foreach($group_users as $key => $value)
			{
				$pusher->trigger('private-guest-read.'.'lg_'.$value['id'], 'guest-is-read', array('data' => $data, 'guest_id' => $_POST['guest_id']));	
			}
		}
		else
		{	
			$data['status'] 			= true;
			$pusher->trigger('private-guest-read.'.'g_'.$_POST['guest_id'], 'guest-is-read', array('data' => $data));	
		}	
		
		return $this->format_json(['status' =>true]);
		
	} 


	/**
	 * 	is typing guest  for guest
	 */
	public function is_typing_guest()
    {
		// init config in each method
		$this->init_config();
		
		// pusher intialization
		$pusher = $this->pusher_init();

		$this->message->is_read_guest($this->AC_SETTINGS->logged_user_id, $_POST['guest_id']);

		if(empty($this->AC_SETTINGS->logged_user_id))
		{
			$group_users  = [];
			// get guest group users	
			$group_users  		= (array)$this->get_guest_group_users();

			
			// get admin user
			if(!empty($this->AC_SETTINGS->admin_user_id))
			{
				$admin_guest        = $this->user->get_profile($this->AC_SETTINGS->admin_user_id, $this->AC_SETTINGS->guest_group_id);

				if(!empty($group_users && !empty($admin_guest)))
					array_push($group_users, $admin_guest);
				else if(empty($group_users) && !empty($admin_guest))
					$group_users = 	$admin_guest;
			}
			
			if(empty($group_users))
				return $this->format_json(array('status' => false));

			$data['status'] 			= true;

			foreach($group_users as $key => $value)
			{
				$pusher->trigger('private-guest-typing.'.'lg_'.$value['id'], 'guest-is-typing', array('guest_typing_user' => json_decode($_POST['guest_typing_user'])));
			}
		}
		else
		{	
			$data['status'] 			= true;
				
			$pusher->trigger('private-guest-typing.'.'g_'.$_POST['guest_id'], 'guest-is-typing', array('guest_typing_user' => json_decode($_POST['guest_typing_user']), 'guest_id' => $_POST['guest_id']));
				
		}	
		
		return $this->format_json(['status' =>true]);
	}
}    