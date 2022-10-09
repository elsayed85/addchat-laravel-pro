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
use Classiebit\Addchat\Models\User;
use Classiebit\Addchat\Http\Controllers\AddchatController;

class UserController extends AddchatController
{
    public function __construct()
    {
	    Parent::__construct();
    }

    /*
    * Get users list get_users
    */
    public function get_users($offset = 0,  $users_id = array(), $flag = false)
    {
		// init config in each method
		$this->init_config();

		// check authentication
        $this->check_auth();
		
		$filters					= [];
		$filters                    = array();
		$filters['limit']           = $this->AC_SETTINGS->pagination_limit;
		$filters['offset']          = (int) $offset;
		$filters['search']          = !empty($_POST['search'])  ? $_POST['search'] : null;
		
        //other users blocked by logged in user
        $blocked_by 		 		= [];
        $params                     = [
            'inverse'   => true,
            'buddy'     => null,
        ];
        $blocked_by_t 		 		= $this->block_user->get_blocked_by_users($this->AC_SETTINGS->logged_user_id, $params);
        
		//other users blocked to logged in user
        $blocked_to_me				= array();
        $params                     = [
            'inverse'   => false,
            'buddy'     => null,
        ];
	
		$blocked_to_me_t  			= $this->block_user->get_blocked_by_users($this->AC_SETTINGS->logged_user_id, $params);
	
		if(!empty($blocked_by_t))
		{
			foreach ($blocked_by_t as $val) 
				$blocked_by[] = $val['user_id'];	
		}

		if(!empty($blocked_to_me_t))
		{
			foreach ($blocked_to_me_t as $val) 
				$blocked_to_me[] = $val['user_id'];
		}
		
		$groupchat_id = [];	
		
		// if logged in user have no group's ids then don't call this function
		if(!empty($this->AC_SETTINGS->logged_group_id))
		{	
			// seach only groupchat users
			$groupchat_id  	= $this->group_chat->get_groupchat($this->AC_SETTINGS->logged_group_id);
		}
		
		// if the specific groups has no other group to chat with or
		// if login user is admin then he can chat with all groups
		$gc_id          = array();
		if(!empty($groupchat_id))
		{
			foreach($groupchat_id as $val)
			{
				if(!in_array($val['gc_id'], $gc_id))
					$gc_id[]	= $val['gc_id'];
			}	
		}

		// if no chatgroups added then do not query chatgroups table
		$chat_users_id_temp = array();

		if(!empty($gc_id))
		{
			$params = [
				'group_id'  	=> null,
				'filters'   	=> null,
				'gc_id'     	=> $gc_id,
			];

			$chat_users_id_temp				= $this->user_group->get_groups_users_id(null, $params);
		}		

		$chat_users_id					= array();	

		if(!empty($chat_users_id_temp))
		{
			foreach($chat_users_id_temp as $val)
				$chat_users_id[]	= $val['user_id'];
		}

		// get contacts
		$contacts_id				= array();
	
		if(empty($filters['search']) && empty($flag))
		{
			$contact_users				= $this->contact->get_contact_users($this->AC_SETTINGS->logged_user_id);

			if(!empty($contact_users))
			{
				foreach ($contact_users as $val) 
					$contacts_id[] = $val['contacts_id'];	
			}
		}

        $params = [
            'blocked_by'    	=> $blocked_by,
			'blocked_to_me' 	=> $blocked_to_me,
			'chat_users_id'		=> $chat_users_id,
			'contacts_id'       => $contacts_id,
			'filters'           => $filters,
			'is_admin'          => $this->AC_SETTINGS->is_admin,
			'is_groups'         => $this->AC_SETTINGS->groups_table ? 1 : 0,
			'users_id'          => $users_id,
		];
		
		$users  = $this->user->get_users($this->AC_SETTINGS->logged_user_id, $params);

		// get groupchat users and flag ture  and users_id for groupchat users
		if($flag)			
		{
			// return  users to groupschat_users() function of this controller
			return $users;
		}

		if(empty($users))
	    {
			$data       = array(
                            'users'  	=> array(),
                            'offset'    => 0,
							'more'      => 0,  // to stop load more process
							'status'    => true,
                        );
          return  $this->format_json($data);
        }
        
        $data                       = array();
        $data['users'] 				= $users;
		$data['offset']             = $filters['offset'] == 0 ? $filters['limit'] : $filters['limit']+$filters['offset'];
		$data['more']               = 1;  // to continue load more process
		$data['status'] 			= true;

		return $this->format_json($data);
        
    }
    
    /*
	*	Get user's profile 
	*/
	public function get_profile($is_return = false)
    {
		// init config in each method
		$this->init_config();

		// check authentication
        $this->check_auth();

		$data					= array();
		$data['status'] 		= true;
		$data['profile'] 		= $this->user->get_profile($this->AC_SETTINGS->logged_user_id, $this->AC_SETTINGS->guest_group_id);

		if($is_return)
			return $data;

		// if login user is admin then is_guest_group alway is 1 means true 
		if($this->AC_SETTINGS->logged_user_id == $this->AC_SETTINGS->admin_user_id && (int) $this->AC_SETTINGS->guest_group_id > 0)
		{
			$data['profile']->is_guest_group = 1;
		}
			
		return $this->format_json($data);
    }
    
    /**
	 * Get buddy
	 */
	public function get_buddy(Request $request)
	{
		// init config in each method
		$this->init_config();

		// check authentication
        $this->check_auth();
	
		// input and validate
		$validator = Validator::make($request->all(), [
			'user'    => 'numeric|gte:1',
			'limit'   => 'numeric',
		]);
		
		if($validator->fails())
		{
			$data = array('status' => false, 'response'=> $validator->errors()->all());
			return $this->format_json($data);
		}
		   
		$data				= array();
		$buddy 				= (int) $request->user;

		$params = [
			'user_id' => $buddy,
		];

		$chatbuddy 			= $this->user->get_user($this->AC_SETTINGS->logged_user_id, $params);

		$c_buddy = array(
			'name' 		 	=> ucwords($chatbuddy->fullname),
			'status' 	 	=> $chatbuddy->online,
			'avatar'		=> $chatbuddy->avatar,
			'is_blocked' 	=> $chatbuddy->is_blocked,
			'id' 		 	=> $chatbuddy->id,
			'is_contact'	=> $chatbuddy->is_contact,
			'email'			=> $chatbuddy->email,
			);
		$data['buddy']		=	$c_buddy;
		$data['status']		=	true;
	
		return $this->format_json($data);
	}
	
	/**
	 * 	GET users  from group  
	 */
	public function get_groupschat_users($group_id = null, $offset = 0)
	{
		// init config in each method
		$this->init_config();

		// check authentication
        $this->check_auth();
		
		$group_id			= (int) $group_id;
		// filters
		$filters            = array();
		$filters['limit']   = $this->AC_SETTINGS->pagination_limit;;
		$filters['offset']  = (int) $offset;
		
		$users_id			= array();
		
		// get users id of particular group
		$params = [
			'group_id'  	=> $group_id,
			'filters'   	=> $filters,
			'gc_id'     	=> null,
		];

		$users_id_temp		= $this->user_group->get_groups_users_id($this->AC_SETTINGS->logged_user_id, $params);
		
		if(!empty($users_id_temp))
		{
			foreach($users_id_temp as $val)
			{
				$users_id[]	= $val['user_id'];
			}
			
			// get  group_users of specific group  from get_users() function of this controller

			$group_users    			= $this->get_users(0 , $users_id, true);

			// get guest group users only when group id == 6 
			if($group_id == 6)
				$group_users = $this->user->get_guest_group_users($users_id);


			
			if(!empty($group_users))
			{
				$data					= array();	
				$data['offset']     	= $filters['offset'] == 0 ? $filters['limit'] : $filters['limit']+$filters['offset'];
				$data['more']       	= 1;  // to continue load more process
				$data['status']			= true;
				$data['group_users']	= $group_users;
				return $this->format_json($data);
			}
			else
			{
				$data       = array(
					'group_users'  	=> array(),
					'offset'        => 0,
					'more'          => 0,  // to stop load more process
					'status'        => true,
				);
				return $this->format_json($data);
			}
		}
		else
		{
		    $data       = array(
                            'group_users'  	=> array(),
                            'offset'    	=> 0,
							'more'      	=> 0,  // to stop load more process
							'status'    	=> true,
                        );
            return $this->format_json($data);
        }
	}
    

}    