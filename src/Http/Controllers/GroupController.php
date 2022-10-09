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

class GroupController extends AddchatController
{
    public function __construct()
    {
	    Parent::__construct();
    }
    
    /**
	 * Get groups name of logged in user 
	 */
	public function get_groups()
	{			
		// init config in each method
		$this->init_config();

		// check authentication
        $this->check_auth();
		

		// if logged in user have no group's ids then don't call this function
		$groupchat_id = [];
		if(!empty($this->AC_SETTINGS->logged_group_id))
		{
			$groupchat_id  	= $this->group_chat->get_groupchat($this->AC_SETTINGS->logged_group_id);
		}	
		
		$gc_id          = array();
		if(empty($groupchat_id) && empty($this->AC_SETTINGS->is_admin))
		{
			$data = [
				'status' 	=> true , 
				'responce' 	=> 'no groups'
			];
			return $this->format_json($data);
		}

		foreach($groupchat_id as $val)
		{
			if(!in_array($val['gc_id'], $gc_id))
				$gc_id[]	= $val['gc_id'];
		}
		
		
		$params  = [
			'group_ids' => $gc_id,
			'is_admin'  => $this->AC_SETTINGS->is_admin,
		];		

		$groups 		= $this->group->get_chatgroups($params);
		
		if(!empty($groups))
		{
			$data = [
				'status' => true, 
				'groups' => $groups
			];

			return 	$this->format_json($data);
		}
		else
		{
			$data = [
				'status'  => false,
			];

			return $this->format_json($data);
		}
	}

	/**
	 *  ADMIN FUNCTION START
	 */
	/**
	 * 	get group names
	 */

	public function a_get_groups() 
	{
		// init config in each method
		$this->init_config();

		//check admin authentication
		$this->check_admin(true);
		$groups 		= $this->group->a_get_groups();
		
		if(empty($groups))
		{
			return $this->format_json(array('status' => false));
		}
		
		// chatgroups
		$chatgroups_tmp 	= $this->group_chat->a_get_chatgroups();

		$chatgroups 		= array();
		foreach($chatgroups_tmp as $key => $val)
			$chatgroups[$val['group_id']][] = $val['gc_id'];

		$data = array(
			'status' 		=> true,
			'groups' 		=> $groups,
			'chatgroups'	=> $chatgroups,
		);
		
		return  $this->format_json($data);
	}

    

}