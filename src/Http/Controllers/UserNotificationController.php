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

class UserNotificationController extends AddchatController
{
    public function __construct()
    {
	    Parent::__construct();
    }
    
    /*
    * Get realtime updates of messages get_updates
    */
    public function get_updates(Request $request)
	{
		// init config in each method
		$this->init_config();
		// check authentication
        $this->check_auth();

		$notification 	= $this->user_notification->get_updates($this->AC_SETTINGS->logged_user_id);
		
		
		// stop sending notification if in case of same notification
		// get POST data
		$post  = $request->all();
		$is_same = false;
		if(!empty($post['notification']))
		{
			$post['notification'] = json_decode($post['notification'], true);
			$notification         = json_encode($notification);
			$notification         = json_decode($notification, true);
			
			// check notification same or not
			$difference = $this->multi_array_diff($notification, $post['notification']);
			
			// if have no difference then is_same will be true
			if(!$difference)
				$is_same = true;
			
		}
				
		// if no messages then do nothing
		if(empty($notification) || $is_same)
			return	$this->format_json(array('status' => false, 'response'=> 'N/A'));

		return  $this->format_json(array('status' => true, 'notification' => $notification));
	}

	/**
	 *   remove notification if chat box is opened
	 */

	public function remove_notifications()
	{
		// init config in each method
		$this->init_config();	

		// if any new message then remove the specific notification
			// remove notification
		$this->user_notification->remove_notification(array('buddy_id'=>$this->AC_SETTINGS->logged_user_id, 'users_id'=>$_POST['buddy_id']));

		// get notification
		$notification 	= $this->user_notification->get_updates($this->AC_SETTINGS->logged_user_id);

		return $this->format_json(['notifications' =>$notification]);
	}

}