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
use Carbon\Carbon;

class MessageController extends AddchatController
{
    public function __construct()
    {
	    Parent::__construct();
    }
    
    /**
	 *  message delete
	 */
	public function message_delete($message_id = null)
	{
		// init config in each method
		$this->init_config();

		// check authentication
        $this->check_auth();

		$message_id = (int) $message_id;
		if(empty($message_id))
			return $this->format_json(array('status' => false));
            
		$status  	= $this->message->message_delete($message_id, $this->AC_SETTINGS->logged_user_id);
		if($status)
			return $this->format_json(array('status' => true, 'message' => __('addchat::ac.message').' '.__('addchat::ac.deleted')));
		
		return $this->format_json(array('status' => false, 'message'=> __('addchat::ac.delete').' '.__('addchat::ac.fail')));
	}    

	/*
	* Delete chat history delete_chat
	*/
	public function delete_chat($user_id = null)
	{
		// init config in each method
		$this->init_config();

		// check authentication
        $this->check_auth();

		$user_id = (int) $user_id;
        if(empty($user_id))
        	return $this->format_json(array('status' => false, 'response'=> __('addchat::ac.user').' '.__('addchat::ac.not_found')));
		
		
		$params = [
			'user_id' => $user_id,
		];

		$data					= array();
		$data['status'] 		= $this->message->delete_chat($this->AC_SETTINGS->logged_user_id, $params);

		return $this->format_json($data);
	}

	/*
	* Get messages get_messages
	*/
	public  function get_messages($buddy_id = null, $offset = 0)
	{
		// init config in each method
		$this->init_config();

		// check authentication
        $this->check_auth();

		$buddy_id         			= (int) $buddy_id;
		
		// filters
		$filters                    = array();
		$filters['limit']           = $this->AC_SETTINGS->pagination_limit;
		$filters['offset']          = (int) $offset;

		$params = [
			'chat_user'  => $buddy_id,
			'filters'    => $filters,
			'count'      => false,
		];

		$total_messages 			= $this->message->get_messages($this->AC_SETTINGS->logged_user_id, $params);
 		
		// 1st case
		if($filters['offset'] == 0)
			$filters['offset']		= $total_messages > $filters['limit'] ? $total_messages - $filters['limit'] :	0;

			
		else
			$filters['offset']		= $filters['offset'] - $filters['limit'];

		// last case
		$more = 1;
		if($filters['offset'] < 0 || $filters['offset']==0)
		{
			$filters['limit']  		= $filters['limit'] - $filters['offset'];
			$filters['offset'] 		= 0;
			$more = 0;
		}
		
		$params = [
			'chat_user'  => $buddy_id,
			'filters'    => $filters,
			'count'      => true,
		];

		
		$messages 					= $this->message->get_messages($this->AC_SETTINGS->logged_user_id, $params);
		
		if($messages->isEmpty())
        {
			$data       = array(
				'messages'  => array(),
				'offset'    => 0,
				'more'      => 0,  // to stop load more process
				'status'    => true,
			);
        	return  $this->format_json($data);
		}

		$params = [
			'buddy_id' => $this->AC_SETTINGS->logged_user_id, 
			'users_id' => $buddy_id
		];
		// remove notification
		$this->user_notification->remove_notification($params);

		$data 					= array();
		$data['messages'] 		= array();
		foreach ($messages as $key => $message) 
		{
			$data['messages'][$key]['message_id'] 			= $message->id;
			$data['messages'][$key]['sender'] 				= $message->m_from;
			$data['messages'][$key]['recipient'] 			= $message->m_to;
			$data['messages'][$key]['message'] 				= $message->message;
			$data['messages'][$key]['is_read'] 				= $message->is_read;
			$data['messages'][$key]['attachment'] 			= $message->attachment;
			$data['messages'][$key]['dt_updated'] 			= $message->dt_updated;
			$data['messages'][$key]['m_reply_id'] 			= $message->m_reply_id;
			$data['messages'][$key]['reply_user_id'] 		= $message->reply_user_id;
			$data['messages'][$key]['quote_message'] 		= $message->quote_message;
			
		}
		
		$data['offset']				= $filters['offset'];			
		$data['more']               = $more;  // to continue load more process
		$data['status'] 			= true;
		
		return  $this->format_json($data);
	}

	/*
	* Send message send_message
	*/
	public function send_message(Request $request)
	{
		// init config in each method
		$this->init_config();

		// check authentication
        $this->check_auth();

		// input and validate
		$validator = Validator::make($request->all(), [
			'user'         	=> 'required|numeric|gte:1',
			'm_reply_id'   	=> 'numeric|gte:1|nullable',
			'reply_user_id' => 'numeric|gte:1|nullable',
			'message'       => 'nullable|max:2000',
			'attachment'    => 'image|mimes:jpg,JPG,jpeg,JPEG,png,PNG|max:5000000|nullable',
		]);
		
		if($validator->fails())
		{
			$data = array('status' => false, 'response'=> $validator->errors()->all());
			return $this->format_json($data);
		}
		
		$buddy 				= (int) $validator->valid()['user'];
		$m_reply_id 		= (int) $validator->valid()['m_reply_id'];
		$reply_user_id 		= (int) $validator->valid()['reply_user_id'];
		$message 			= nl2br($validator->valid()['message']);

        // return null if buddy or message is empty
        if(!$buddy)
            return $this->format_json(['status' => false, 'response' => 'N/A']);

        // reject if user is blocked by me
		$params      = [
            'inverse'   => false,
            'buddy'     => $buddy,
        ];			
        $blocked_by 	= $this->block_user->get_blocked_by_users($this->AC_SETTINGS->logged_user_id, $params);
        if(!empty($blocked_by))
            return $this->format_json(['status' => false, 'response' => 'N/A']);

		// upload attachment image
		$filename               = null;
        if(!empty($validator->valid()['attachment'])) // if image 
        {	
			$file  				= $validator->valid()['attachment'];
			$filename           = time().rand(1,988).".".$file->getClientOriginalExtension();

            // upload_path/attachments/user-<id>/files
            // replace storage by public
            $upload_path        = str_replace('storage/', '', $this->AC_SETTINGS->upload_path);
            $attachment_path    = 'public/'.$upload_path.'/attachments/user-'.$this->AC_SETTINGS->logged_user_id.'/';
            $file->storeAs($attachment_path, $filename);

            // add user-id with filename
            $filename           = '/attachments/user-'.$this->AC_SETTINGS->logged_user_id.'/'.$filename;
        }

        // if no message & no attachment, then message is required
        if(empty($filename))
		{
			// input and validate
            $validator = Validator::make($request->all(), [
                'message'       => 'required|max:2000',
            ]);
            
            if($validator->fails())
            {
                $data = array('status' => false, 'response'=> $validator->errors()->all());
                return $this->format_json($data);
            }
		}

        $params    = [
            "m_from"		=> $this->AC_SETTINGS->logged_user_id,
            "m_to" 			=> $buddy,
            "message" 		=> $message,
            "attachment" 	=> $filename,
            "dt_updated" 	=> Carbon::now($request->local_timezone)->toDateTimeString(),
            "m_reply_id"    => $m_reply_id,
            "reply_user_id" => $reply_user_id,
		];
		
        $msg_id = $this->message->send_message($params);

        $data = array(
            array(
                'users_id' 		=> $this->AC_SETTINGS->logged_user_id,
                'contacts_id' 	=> $buddy,
            ),
            array(
                'users_id' 		=> $buddy,
                'contacts_id' 	=> $this->AC_SETTINGS->logged_user_id,
            )
        );

        $chat = array(
            'message_id' 		=> $msg_id,
            'sender' 			=> $params['m_from'], 
            'recipient' 		=> $params['m_to'],
            'attachment' 		=> $params['attachment'],
            'message' 			=> $params['message'],
            'dt_updated' 		=> $params['dt_updated'],
            'is_read' 			=> 0,
            "m_reply_id"    	=> $m_reply_id,
            "reply_user_id" 	=> $reply_user_id,
            "quote_message" 	=> $request->quote_message,
        );

        // create contacts
        $params = [
            'users_id' 		=> $this->AC_SETTINGS->logged_user_id,
            'contacts_id' 	=> $buddy
		];
		
        $this->contact->create_contacts($params);
        

        //  set_notification
        $params  = [
            'users_id' => $this->AC_SETTINGS->logged_user_id, 
            'buddy_id' => $buddy
        ];
        $this->user_notification->set_notification($params);

		$data = array(
            'status' 	=> true,
            'message' 	=> $chat 	  
        );
		return $this->format_json($data);
	}

	/*
	* messages unsend
	*/
	public function message_unsend($message_id = null)
	{
		// init config in each method
		$this->init_config();

		// check authentication
        $this->check_auth();

        $message_id = (int) $message_id;
		if(!$message_id)
			return 	$this->format_json(array('status' => false, 'message' => __('addchat::ac.message').' '.__('addchat::ac.not_found')));

		$params  = [
			'message_id'   => $message_id
		];
		$status  	= $this->message->message_unsend($this->AC_SETTINGS->logged_user_id, $params);
		
		if($status)
			return $this->format_json(array('status' => $status, 'message' => __('addchat::ac.message').' '.__('addchat::ac.deleted')));
		
		return $this->format_json(array('status' => $status, 'message' => __('addchat::ac.unsent_fail')));
	}

	
	/*
    * Get latest message of active buddy
    */
    public function get_latest_message($buddy_id = null)
	{
		// init config in each method
		$this->init_config();

		// check authentication
        $this->check_auth();

		$buddy_id = (int) $buddy_id;	
		
		$messages 	= array();

		if($buddy_id)
		{

			$params  =  [
				'buddy_id'	=> $buddy_id,
			];

			$messages 	= $this->message->get_latest_message($this->AC_SETTINGS->logged_user_id, $params);

			// if any new message then remove the specific notification
			// remove notification


			$params = [
				'buddy_id' => $this->AC_SETTINGS->logged_user_id, 
				'users_id' => $buddy_id
			];
			$this->user_notification->remove_notification($params);

		}

		// if no messages then do nothing
	    if(empty($messages))
	   		return  $this->format_json(array('status' => false, 'response'=> 'N/A'));

		return  $this->format_json(array('status' => true, 'messages' => $messages));
	}

	/**
	 * =============== ADMIN FUNCTION START==========================
	 */

	
	/**
	 *  get chat users who chat with each other means between users
	 * 
	 */
	public function a_chat_between($offset = 0)
	{
		// init config in each method
		$this->init_config();

		//check admin authentication
		$this->check_admin(true);

		// filters
		$filters                    = array();
		$filters['limit']           = $this->AC_SETTINGS->pagination_limit;
		$filters['offset']    		= (int) $offset;

		$params	=	[
			'filters' => $filters,
		];

		$chat_betweens 	= $this->message->a_chat_between($params);

		if(empty($chat_betweens))
		{
			$data       = array(
				'chat_betweens'  	=> array(),
				'offset'    		=> 0,
				'more'      		=> 0,  // to stop load more process
				'status'    		=> true,
			);
			return $this->format_json($data);
		}

		// remove duplicate rows from chat_betweens
		$chat_data  = [];
		
		foreach($chat_betweens as $key => $value)
		{
			if(empty($chat_data))
			{
				$chat_data[] = $value;
			}
			else
			{
				$duplicate = false;

				foreach($chat_data as $key1 => $value1)
				{
					if($value1['m_from'] == $value['m_to'] && $value1['m_to'] == $value['m_from'])
					{
						$duplicate = true;			
					}
				}
				if(!$duplicate)
					$chat_data[] = $value;
			}
		}
		
		$data = array(
			'status' 				=> true,
			'offset'    			=> $filters['offset'] == 0 ? $filters['limit'] : $filters['limit']+$filters['offset'],
			'more'      			=> 1,  // to stop load more process
			'chat_betweens' 		=> $chat_data,
		);

		return $this->format_json($data);
	}

	/**
	 *   get conversation of between to  users
	 */

	public function a_get_conversations($m_from = null, $m_to = null, $offset = 0)
	{
		
		// init config in each method
		$this->init_config();	
		//check admin authentication
		$this->check_admin(true);

		$m_from			= (int) $m_from;
		$m_to			= (int)	$m_to;

		// filters
		$filters                    = array();
		$filters['limit']           = $this->AC_SETTINGS->pagination_limit;
		$filters['offset']          = (int) $offset;

		$params	=	[
			'm_from'  => $m_from, 
			'm_to'    => $m_to, 
			'filters' => $filters, 
			'count'   => true,
		];

		$total_messages 			= $this->message->a_get_conversations($params);
	
		// 1st case
		if($filters['offset'] == 0)
			$filters['offset']		= $total_messages > $filters['limit'] ? $total_messages - $filters['limit'] :	0;

			
		else
			$filters['offset']		= $filters['offset'] - $filters['limit'];

		// last case
		$more = 1;
		if($filters['offset'] < 0 || $filters['offset']==0)
		{
			$filters['limit']  		= $filters['limit'] - $filters['offset'];
			$filters['offset'] 		= 0;
			$more = 0;
		}

		$params	=	[
			'm_from'  => $m_from, 
			'm_to'    => $m_to, 
			'filters' => $filters, 
			'count'   => false,
		];

		$conversations 	= $this->message->a_get_conversations($params);

		if(empty($conversations))
        {
			$data       = array(
				'conversations'  => array(),
				'offset'    	 => 0,
				'more'      	 => 0,  // to stop load more process
				'status'         => true,
			);
          	return  $this->format_json($data);
		}
	
		$data       = array(
			'conversations'  	=> $conversations,
			'status'    		=> true,
			'more'				=> $more,	// to continue load more process
			'offset'			=> $filters['offset'],
		);
		return $this->format_json($data);
	}
	
	/**
	 *  =========================GUEST FUNCTION START================================  
	 */


	/*
	*  guest can see all messages of guest group users with his messages
	*/
	public  function get_messages_of_guest(Request $request)
	{
		$this->init_config();
		// input and validate
		$validator = Validator::make($request->all(), [
			'guest_id'   	=> 'required',
		]);
		
		if($validator->fails())
		{
			$data = array('status' => false, 'response'=> $validator->errors()->all());
			return $this->format_json($data);
		}

		$guest_id         			= $request->guest_id;
	
		$guest_id		  			= Crypt::decryptString($guest_id);
		
		if(empty($guest_id))
			return  $this->format_json(['status' => false]);

		$guest_user                 = $this->guest->get_guest_user($guest_id);		

		if(empty($guest_user))
			return  $this->format_json(['status' => false]);
		
		// filters
		$filters                    = array();
		$filters['limit']           = $this->AC_SETTINGS->pagination_limit;
		$filters['offset']          = (int) $request->offset;

		$params  = [
			'guest_id'		 => $guest_user->id,
			'filters'        => $filters,
			'count'          => false
		];

		$total_messages 			= $this->message->get_messages_of_guest($params);
		
		// if any new message then remove the specific notification
		// remove notification
		$this->guest_notification->remove_guest_notification(array( 'g_to'=>$guest_user->id));

		// 1st case
		if($filters['offset'] == 0)
			$filters['offset']		= $total_messages > $filters['limit'] ? $total_messages - $filters['limit'] :	0;
			
		else
			$filters['offset']		= $filters['offset'] - $filters['limit'];

		// last case
		$more = 1;
		if($filters['offset'] < 0 || $filters['offset']==0)
		{
			$filters['limit']  		= $filters['limit'] - $filters['offset'];
			$filters['offset'] 		= 0;
			$more = 0;
		}
		
		$params  = [
			'guest_id'		 => $guest_user->id,
			'filters'        => $filters,
			'count'          => true
		];

		$messages 			= $this->message->get_messages_of_guest($params);

		if($messages->isEmpty())
        {
			$data       = array(
				'messages'  => array(),
				'offset'    => 0,
				'more'      => 0,  // to stop load more process
				'status'    => true,
			);
            return  $this->format_json($data);
		}

		$data 					= array();
		$data['messages'] 		= array();
		foreach ($messages as $key => $message) 
		{
			$data['messages'][$key]['message_id'] 			= $message->id;
			$data['messages'][$key]['m_from'] 				= $message->m_from;
			$data['messages'][$key]['m_to'] 				= $message->m_to;
			$data['messages'][$key]['g_from'] 			    = $message->g_from;
			$data['messages'][$key]['g_to'] 			    = $message->g_to;
			$data['messages'][$key]['message'] 				= $message->message;
			$data['messages'][$key]['is_read'] 				= $message->is_read;
			$data['messages'][$key]['dt_updated'] 			= $message->dt_updated; 
			$data['messages'][$key]['m_from_image'] 		= $message->m_from_image; 
			$data['messages'][$key]['m_from_name'] 		    = $message->m_from_name; 
		}
		
		$data['offset']				= $filters['offset'];			
		$data['more']               = $more;  // to continue load more process
		$data['status'] 			= true;

		return  $this->format_json($data);
	}

	/**
	 *  guest user can send message
	 */
	public function guest_send_message(Request $request)
	{
		$this->init_config();
		// input and validate
		$validator = Validator::make($request->all(), [
			'guest_id'   	=> 'required',
			'message'       => 'required|max:1000',
		]);
		
		if($validator->fails())
		{
			$data = array('status' => false, 'response'=> $validator->errors()->all());
			return $this->format_json($data);
		}

		$guest_id		  = Crypt::decryptString($request->guest_id);

		$guest_user   	  = $this->guest->get_guest_user($guest_id);		

		if(empty($guest_user))
			return $this->format_json(['status' => false]);

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
		
		$messages	  		= [];
		$notification       = [];

		$g_random  			= time().rand(1,988);
		foreach($group_users as $key => $value)
		{
			$messages[$key]['m_to']  	    	= $value['id'];
			$messages[$key]['message']  		= $request->message;
			$messages[$key]['g_from']   		= $guest_user->id;
			$messages[$key]['m_from'] 			= 0;
			$messages[$key]['is_read'] 			= 0;
			
			$messages[$key]['dt_updated']   	= Carbon::now($request->local_timezone)->toDateTimeString();
			$messages[$key]['g_random']   		= $g_random;
			$notification[$key]['m_to']			= $value['id'];
			$notification[$key]['g_from']		= $guest_user->id;
		}
		
		$status  =  $this->message->guest_send_message($messages);

		// 2. set_notification for guest

		$this->guest_notification->set_guest_notification($notification);

		if(empty($status))
			return $this->format_json(array('status' => false));

		$data  = [
			'message'  		=> $messages[0],
			'status'   		=> true,
			
		];		

		return $this->format_json($data);		
	}

	/*
    * Get latest message for guest user when  guest login
    */
    public function get_guest_latest_message($guest_id = null)
	{
		// check authentication
		$this->init_config();

		// check authentication
        $this->check_auth();
    	
		$guest_id 	= (int) $guest_id;	
		
		$messages 	= array();

		if($guest_id)
		{
			$params  = [
				'login_user_id'  => $this->AC_SETTINGS->logged_user_id,
				'guest_id'       => $guest_id,
			];		

			$messages 	= $this->message->get_guest_latest_message($params);
			
			// if any new message then remove the specific notification
			// remove notification
			$this->guest_notification->remove_guest_notification(array('m_to'=>$this->AC_SETTINGS->logged_user_id, 'g_from'=>$guest_id));

		}

		// if no messages then do nothing
	    if(empty($messages))
	    {
	   		return  $this->format_json(array('status' => false, 'response'=> 'N/A'));
		}

		return  $this->format_json(array('status' => true, 'messages' => $messages));
	}

	/*
	* Get latest message for guest user when guest without login
	 */
    public function get_guest_latest_message1(Request $request)
	{
		$this->init_config();

		// input and validate
		$validator = Validator::make($request->all(), [
			'guest_user_id'   	=> 'required',
		]);
		
		if($validator->fails())
		{
			$data = array('status' => false, 'response'=> $validator->errors());
			return $this->format_json($data);
		}
	
		$guest_id		  		= Crypt::decryptString($request->guest_user_id);
		$guest_user   			= $this->guest->get_guest_user($guest_id);	
		
		if(empty($guest_user))
			return $this->format_json(['status' => false]);

		$params  = [
			'login_user_id'  => $this->AC_SETTINGS->logged_user_id,
			'guest_id'       => $guest_user->id,
		];
		
		$messages 	= $this->message->get_guest_latest_message($params);

		// if any new message then remove the specific notification
		// remove notification
		$this->guest_notification->remove_guest_notification(array( 'g_to'=>$guest_user->id));

	
		// if no messages then do nothing
	    if(empty($messages))
	    {
	   		return $this->format_json(array('status' => false, 'response'=> 'N/A', 'messages' => null ));
		}

		return $this->format_json(array('status' => true, 'messages' => $messages));
	}

	/*
	* Get messages get_messages for login guest user
	*/
	public  function get_guest_messages($guest_id = null, $offset = 0)
	{
		$this->init_config();
		$guest_id         			= (int) $guest_id;
		
		// filters
		$filters                    = array();
		$filters['limit']           = $this->AC_SETTINGS->pagination_limit;
		$filters['offset']          = (int) $offset;

		$params  = [
			'guest_id'		 => $guest_id,
			'filters'        => $filters,
			'logged_user_id' => $this->AC_SETTINGS->logged_user_id,
			'count'          => false
		];

		$total_messages 			= $this->message->get_guest_messages($params);

		// 1st case
		if($filters['offset'] == 0)
			$filters['offset']		= $total_messages > $filters['limit'] ? $total_messages - $filters['limit'] :	0;
			
		else
			$filters['offset']		= $filters['offset'] - $filters['limit'];

		// last case
		$more = 1;
		if($filters['offset'] < 0 || $filters['offset']==0)
		{
			$filters['limit']  		= $filters['limit'] - $filters['offset'];
			$filters['offset'] 		= 0;
			$more = 0;
		}

		$params  = [
			'guest_id'		 => $guest_id,
			'filters'        => $filters,
			'logged_user_id' => $this->AC_SETTINGS->logged_user_id,
			'count'          => true
		];
		
		$messages 			= $this->message->get_guest_messages($params);

	
		// if any new message then remove the specific notification
		// remove notification
		$this->guest_notification->remove_guest_notification(array('m_to'=>$this->AC_SETTINGS->logged_user_id, 'g_from'=>$guest_id));

		if($messages->isEmpty())
        {
			$data       = array(
				'messages'  => array(),
				'offset'    => 0,
				'more'      => 0,  // to stop load more process
				'status'    => true,
			);
           return   $this->format_json($data);
		}

		$data 					= array();
		$data['messages'] 		= array();
		foreach ($messages as $key => $message) 
		{
			$data['messages'][$key]['message_id'] 			= $message->id;
			$data['messages'][$key]['sender'] 				= $message->m_from;
			$data['messages'][$key]['recipient'] 			= $message->m_to;
			$data['messages'][$key]['message'] 				= $message->message;
			$data['messages'][$key]['is_read'] 				= $message->is_read;
			$data['messages'][$key]['attachment'] 			= $message->attachment;
			$data['messages'][$key]['dt_updated'] 			= $message->dt_updated; 
		}
		$data['offset']				= $filters['offset'];			
		$data['more']               = $more;  // to continue load more process
		$data['status'] 			= true;

		return   $this->format_json($data);
	}

	/**
	 *	 login user send message to guest users  
	 */
	
    public function send_to_guest(Request $request)
    {
		$this->init_config();
		// input and validate
		$validator = Validator::make($request->all(), [
			'guest_id'   	=> 'required|numeric',
			'message'		=> 'required|max:1000'
		]);
		
		if($validator->fails())
		{
			$data = array('status' => false, 'response'=> $validator->errors()->all());
			return $this->format_json($data);
		}
		
		$guest_id 		= (int) $request->guest_id;
		$message 		= $request->message;
		$g_random  	    = time().rand(1,988);

        // return null if guest or message is empty
        if(!$message || !$guest_id)
			return $this->format_json(['status' => false,'response' => 'N/A']);
		
        $msg  = array(
            "m_from"		=> $this->AC_SETTINGS->logged_user_id,
            "g_to" 			=> $guest_id,
            "message" 		=> $message,
            "dt_updated" 	=> Carbon::now($request->local_timezone)->toDateTimeString(),
            'g_random'	    => $g_random,
        );
        
        $msg_id = $this->message->send_to_guest($msg);
        if(empty($msg_id))
            return $this->format_json(['status' => false]);

        $notification 			   = [];
        $notification[0]['m_from'] = $this->AC_SETTINGS->logged_user_id;
        $notification[0]['g_to']   = $guest_id;
        
        // 2. set_notification
        $this->guest_notification->set_guest_notification($notification);
        
        $chat = array(
            'message_id' 		=> $msg_id,
            'sender' 			=> $msg['m_from'], 
            'recipient' 		=> $msg['g_to'],
            'message' 			=> $msg['message'],
            'dt_updated' 		=> $msg['dt_updated'],
            'is_read' 			=> 0,
        );

        //it is for pusher notification
        $guest_messages = array(
            'message_id' 		=> $msg_id,
            'm_from' 			=> $msg['m_from'], 
            'g_to' 				=> $msg['g_to'],
            'g_from'            => 0,
            'm_to'            	=> 0,
            'message' 			=> $msg['message'],
            'dt_updated' 		=> $msg['dt_updated'],
            'is_read' 			=> 0,
            
        );

        $data = array(
            'status' 	=> true,
            'message' 	=> $chat, 	  
            'guest_messages' => $guest_messages
        );

		
		//add the header here
		return   $this->format_json($data);
	}

	/**
	 * if user is logged in then
	 *  check user have guest account or not and if have guest account then update messages 
	 */
	public function check_guest_account(Request $request)
	{
		$this->init_config();
		// input and validate
		$validator = Validator::make($request->all(), [
			'guest_id'   	=> 'required',
			
		]);
		
		if($validator->fails())
		{
			$data = array('status' => false, 'response'=> $validator->errors()->all());
			return   $this->format_json($data);
		}
		
		// guest id
		$guest_id	  = Crypt::decryptString($request->guest_id);
		$guest_user   = $this->guest->get_guest_user($guest_id);	
		
		if(empty($guest_user))
			return   $this->format_json(['status' => false]);
		
		if(Auth::user()->email == $guest_user->email)
		{
			$params  = [
				'guest_id' 	 		=> $guest_user->id,
				'logged_user_id'    => $this->AC_SETTINGS->logged_user_id,
			];
		
			// update ac_guest_messages table because now guest user have became login user
			$status = $this->message->update_guest_messages($params);
			
			if(!$status)
				return $this->format_json(['status' => false]);

			
				// get guest users
			$group_users  = $this->get_guest_group_users();
			
			// add one user in contact list
			if(!empty($group_users))
			{
				$params = [
					'users_id' 		=> $this->AC_SETTINGS->logged_user_id,
					'contacts_id' 	=> $group_users[0]->id,
				];

				$status  =  $this->contact->create_contacts($params);
			}

			return 	$this->format_json(['status' => true]);
		}

		return	$this->format_json(['status' => false]);
	}

	
	/**
	 *  chat between guest user
	 */
	public function a_chat_between_guest($offset = 0)
	{
		// init config in each method
		$this->init_config();

		//check admin authentication
		$this->check_admin(true);
		// filters
		$filters                    = array();
		$filters['limit']           = $this->AC_SETTINGS->pagination_limit;
		$filters['offset']    		= (int) $offset;

		$params	=	[
			'filters' => $filters,
		];

		$chat_between_guest 	= $this->message->a_chat_between_guest($params);
		
		if(empty($chat_between_guest))
		{
			$data       = array(
				'chat_between_guest'  	=> array(),
				'offset'    			=> 0,
				'more'      			=> 0,  // to stop load more process
				'status'    			=> true,
			);
			return $this->format_json($data);
		}

		// remove duplicate rows from chat_betweens
		$chat_data  = [];
		
		foreach($chat_between_guest as $key => $value)
		{
			if(empty($chat_data))
			{
				$chat_data[] = $value;
			}
			else
			{
				$duplicate = false;

				foreach($chat_data as $key1 => $value1)
				{
					if($value1['m_from'] == $value['m_to'] && $value1['m_to'] == $value['m_from'] && $value1['g_from'] == $value['g_to'] && 		$value1['g_to'] == $value['g_from']
					)
					{
						$duplicate = true;			
					}
				}
				if(!$duplicate)
					$chat_data[] = $value;
			}
		}
	
		$data = array(
			'status' 				=> true,
			'offset'    			=> $filters['offset'] == 0 ? $filters['limit'] : $filters['limit']+$filters['offset'],
			'more'      			=> 1,  // to stop load more process
			'chat_between_guest' 	=> $chat_data,
		);

		return $this->format_json($data);
	}
	
	/**
	 *   get conversation of between to  users
	 */
	public function a_get_guest_conversations(Request $request)
	{
		// init config in each method
		$this->init_config();	
		//check admin authentication
		$this->check_admin(true);

		// input and validate
		$validator = Validator::make($request->all(), [
			'guest_id'   	=> 'required',
			'user_id'       => 'required'			
		]);
		
		if($validator->fails())
		{
			$data = array('status' => false, 'response'=> $validator->errors()->all());
			return   $this->format_json($data);
		}

		$user_id		= (int) $request->user_id;
		$guest_id		= (int)	$request->guest_id;

		// filters
		$filters                    = array();
		$filters['limit']           = $this->AC_SETTINGS->pagination_limit;
		$filters['offset']          = (int) $request->offset;

		$params	=	[
			'user_id'  	  => $user_id, 
			'guest_id'    => $guest_id, 
			'filters' 	  => $filters, 
			'count'       => true,
		];

		$total_messages 			= $this->message->a_get_guest_conversations($params);
	
		// 1st case
		if($filters['offset'] == 0)
			$filters['offset']		= $total_messages > $filters['limit'] ? $total_messages - $filters['limit'] :	0;

			
		else
			$filters['offset']		= $filters['offset'] - $filters['limit'];

		// last case
		$more = 1;
		if($filters['offset'] < 0 || $filters['offset']==0)
		{
			$filters['limit']  		= $filters['limit'] - $filters['offset'];
			$filters['offset'] 		= 0;
			$more = 0;
		}

		$params	=	[
			'user_id'  	  => $user_id, 
			'guest_id'    => $guest_id, 
			'filters' 	  => $filters,  
			'count'   => false,
		];

		$conversations 	= $this->message->a_get_guest_conversations($params);

		if(empty($conversations))
        {
			$data       = array(
				'conversations'  => array(),
				'offset'    	 => 0,
				'more'      	 => 0,  // to stop load more process
				'status'         => true,
			);
          	return  $this->format_json($data);
		}
	
		$data       = array(
			'conversations'  	=> $conversations,
			'status'    		=> true,
			'more'				=> $more,	// to continue load more process
			'offset'			=> $filters['offset'],
		);
		return $this->format_json($data);
	}
}