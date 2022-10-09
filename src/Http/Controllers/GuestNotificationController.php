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

class GuestNotificationController extends AddchatController
{
    public function __construct()
    {
	    Parent::__construct();
    }
    
    /**
	 *  get guest notification 
	 */

	public function get_guest_updates(Request $request)
	{
		$this->init_config();
		
		
		$post  = $request->all();

		$guest_notifications = [];
		// notification for loing user
		if(!empty($this->AC_SETTINGS->logged_user_id))
		{
			$params  = [
				'login_user_id' => $this->AC_SETTINGS->logged_user_id,
			];

			$guest_notifications     = $this->guest_notification->get_guest_updates($params);
		}	
		else
		{	
			// input and validate
			
			if(empty($post['guest_id']))
			{	
				return $this->format_json(['status' => false, 'response' => 'N/A']);
			}
		
			// notification for guests user
			$guest_id		  		= Crypt::decryptString($post['guest_id']);

			$guest_user   			= $this->guest->get_guest_user($guest_id);	

			if(empty($guest_user))
				return $this->format_json(['status' => false]);

			$params  = [
				'login_user_id'  => $this->AC_SETTINGS->logged_user_id,
				'guest_id'       => $guest_user->id,
			];		

			$guest_notifications    = $this->guest_notification->get_guest_updates($params);
		}
		// stop sending notification if in case of same notification
		$is_same = false;
		if(!empty($post['notification']))
		{
			$post['notification'] = json_decode($post['notification'], true);
			$guest_notifications  = json_encode($guest_notifications);
			$guest_notifications  = json_decode($guest_notifications, true);
			
			// check notification same or not
			$difference = $this->multi_array_diff($guest_notifications, $post['notification']);
			

			// if have no difference then is_same will be true
			if(!$difference)
				$is_same = true;

		}
		// if no messages then do nothing
	    if(empty($guest_notifications) || $is_same)
	    {
	   		return $this->format_json(array('status' => false, 'response'=> 'N/A'));
		}
		
		return $this->format_json(array('status' => true, 'guest_notifications' => $guest_notifications));
    }
    
    /**
	 *  remove guest notification for guest
	 */

	public function remove_guest_notifications()
	{
		// init config in each method
		$this->init_config();

		$guest_id 		= $_POST['guest_id'];

		$guest_user     = $this->guest->get_guest_user($guest_id);

		if(empty($guest_user))
			return $this->format_json(array('status' => false, 'response'=> __('addchat::ac.guest').' '.__('addchat::ac.not_found')));

		
		$guest_notifications = [];
		
		// notification for loing user
		if(!empty($this->AC_SETTINGS->logged_user_id))
		{
			// remove notification
			$this->guest_notification->remove_guest_notification(array('m_to'=>$this->AC_SETTINGS->logged_user_id, 'g_from'=>$guest_id));
			
			$params = [
				'login_user_id' => 	$this->AC_SETTINGS->logged_user_id,
				'guest_id'      =>  null,
			];
			
			$guest_notifications     = $this->guest_notification->get_guest_updates($params);

		}	
		else
		{
			// remove notification
			$this->guest_notification->remove_guest_notification(array( 'g_to'=>$guest_id));
			
			
			$params = [
				'login_user_id' => 	null,
				'guest_id'      =>  $guest_id,
			];

			$guest_notifications    = $this->guest_notification->get_guest_updates($this->AC_SETTINGS->logged_user_id, $guest_user->id);
		}

		return $this->format_json(['guest_notifications' =>$guest_notifications]);
		
	}

}