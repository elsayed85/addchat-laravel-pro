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

class GuestController extends AddchatController
{
    public function __construct()
    {
	    Parent::__construct();
    }
    
    /**
	   *  check guest exist or not in guest table
	   */
	public function guest_login(Request $request)
	{
		$this->init_config();
		// input and validate
		$validator = Validator::make($request->all(), [
			'fullname'   	=> 'required|max:256|min:5',
			'email'      	=> 'required|email',
		]);
		
		if($validator->fails())
		{
			$data = array('status' => false, 'response'=> $validator->errors()->all());
			return $this->format_json($data);
		}

		$params =  [
			
			'fullname' 		=> $request->fullname,
			'email'    		=> $request->email,
			'dt_created'	=> date('Y-m-d H:i:s'),
			'dt_updated'    => date('Y-m-d H:i:s'),
		];

		$guest_user     	= $this->guest->guest_login($params);
		
		$guest_user_id    = Crypt::encryptString($guest_user->id);
		
		$data = [
			'guest_user_id' 		=> $guest_user_id,
			'guest_user_email'		=> $guest_user->email,
			'guest_user_fullname'	=> $guest_user->fullname,
			'status'                => true
		];
		
		return  $this->format_json($data);
	}

	/**
	 *  check guest exist or not in guest table
	 */
	public function get_guest_user(Request $request)
	{
		$this->init_config();
		
		$guest_user = null;
		if($request->guest_user_id && $request->guest_user_id != "null")
		{
			$guest_id		  = Crypt::decryptString($request->guest_user_id);
			$guest_user       = $this->guest->get_guest_user($guest_id);
		}

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

		$guest_group_name   = null;

		if(!empty($this->AC_SETTINGS->guest_group_id))
		{
			$params  = [
				'group_ids' => [$this->AC_SETTINGS->guest_group_id],
				'is_admin'  => $this->AC_SETTINGS->is_admin,
			];		

			$guest_group 		= $this->group->get_chatgroups($params);

			if(!empty($guest_group))
				$guest_group_name  = $guest_group[0]['name'];
		}	

		if(empty($guest_user))
			return $this->format_json(['status' => false, 'guest_group_users' => $group_users, 'guest_group_name' => $guest_group_name]);
		
		$data = [
			'status' 			=> true,
			'guest_group_users' => $group_users,
			'guest'             => $guest_user,
			'guest_group_name'  => $guest_group_name
		];

		return $this->format_json($data);
		
	}

	/*
    * get guest users for login user if login user have guest group  
    */
    public function get_guests($offset = 0)
    {
		$this->init_config();

		
		// filters
		$filters                    = array();
		$filters['limit']           = $this->AC_SETTINGS->pagination_limit;
		$filters['offset']          = (int) $offset;
		$filters['search']          = (string) (!empty($_POST['search']) ? $_POST['search'] : null);

		$params  = [
			'filters'  => 	$filters,
		];

		$guest_users      	 = $this->guest->get_guests($params);

		if(empty($guest_users))
        {
            $data       = array(
                            'guest_users'  	=> array(),
                            'offset'    => 0,
							'more'      => 0,  // to stop load more process
							'status'    => true,
                        );
            return   $this->format_json($data);
		}
    
        $data                       = array();
        $data['guest_users'] 		= $guest_users;
		$data['offset']             = $filters['offset'] == 0 ? $filters['limit'] : $filters['limit']+$filters['offset'];
		$data['more']               = 1;  // to continue load more process
		$data['status'] 			= true;

		return   $this->format_json($data);
	}

	/**
	 * Get guest buddy for login user
	 */

	public function get_guest_buddy(Request $request)
	{
		$this->init_config();
	
		if(empty($request->guest_id))
			return 	$this->format_json(['status' => false]);
			
		$data				= array();
		$guest_user_id 		= (int) $request->guest_id;
		
		$guest_buddy 	    = $this->guest->get_guest_user($guest_user_id);
		
		if(empty($guest_buddy))
			return $this->format_json(['status' => false]);

		$data['buddy']		=	$guest_buddy;
		$data['status']		=	true;
		return    $this->format_json($data);
	}
	

}