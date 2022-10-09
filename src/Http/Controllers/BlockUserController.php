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

class BlockUserController extends AddchatController
{
    public function __construct()
    {
	    parent::__construct();
    }
    
    /*
	* Block user block_user
	*/
	public function block_user($user_id = null, $is_report = null)
    {
		// init config in each method
		$this->init_config();

		// check authentication
        $this->check_auth();

		$user_id  	=  (int) $user_id;
		$is_report  =  (int) $is_report;

		$params = [
			'blocked_user_id' => $user_id,
			'is_report'       => $is_report,
		];

		if(empty($user_id))
        	return $this->format_json(array('status' => false, 'response'=> __('addchat::ac.user').' '.__('addchat::ac.not_found')));
        
		// block user
		$data   				= array();
		$data['status']			= $this->block_user->block_user($this->AC_SETTINGS->logged_user_id, $params);

		return $this->format_json($data);
	}

	/**
	 *  ADMIN FUNCTION START 
	 */

	/*
	*	Get all blocked users
	*/

	public function get_blocked_users($offset = 0)
	{
		// init config in each method
		$this->init_config();

		//check admin authentication
		$this->check_admin(true);

		// filters
		$filters                    = array();
		$filters['limit']           = $this->AC_SETTINGS->pagination_limit;
		$filters['offset']          = (int) $offset;

		$params  =  [
			'filters'  => $filters
		];

		$blocked_users    = $this->block_user->get_blocked_users($params);

		if(empty($blocked_users))
        {
            $data       = array(
                            'blocked_users'  	=> array(),
                            'offset'    		=> 0,
							'more'      		=> 0,  // to stop load more process
							'status'    		=> true,
                        );
           	return  $this->format_json($data);
        }

		$data                       = array();
        $data['blocked_users'] 		= $blocked_users;
		$data['offset']             = $filters['offset'] == 0 ? $filters['limit'] : $filters['limit']+$filters['offset'];
		$data['more']               = 1;  // to continue load more process
		$data['status'] 			= true;

		return  $this->format_json($data);
	
	}

}    