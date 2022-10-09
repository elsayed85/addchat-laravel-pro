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

class ContactController extends AddchatController
{
    public function __construct()
    {
	    Parent::__construct();
    }
    
    /**
	 * 	Remove  user from contact list
	 */
	public function remove_contacts($user_id = null)
	{
		// init config in each method
		$this->init_config();

		// check authentication
        $this->check_auth();

		$user_id = (int) $user_id;

		if(empty($user_id))
        {
        	$data  =  array('status' => false, 'response'=>__('addchat::ac.remove').' '.__('addchat::ac.fail'));
			return $this->format_json($data);
		}
		
		// remove user from contact list
		$params	= [
			'users_id' => $this->AC_SETTINGS->logged_user_id, 
			'contacts_id' => $user_id
		];

		$status			= $this->contact->remove_contacts($params);

		if($status)
			return $this->format_json(['status' => true]);

		return $this->format_json(['status' => false]);	
	}

	
	/**
	 *  Add user into contact list
	 */
	public function add_contacts($user_id = null)
	{
		// init config in each method
		$this->init_config();

		// check authentication
        $this->check_auth();

		$user_id = (int) $user_id;
		if(empty($user_id))
        {
        	$data  =  array('status' => false, 'response'=> __('addchat::ac.user').' '.__('addchat::ac.not_found'));
			return $this->format_json($data);
		}
		
		$params = [
			'users_id' 		=> $this->AC_SETTINGS->logged_user_id,
			'contacts_id' 	=> $user_id
		];

		$status  =  $this->contact->create_contacts($params);

		return $this->format_json(array('status'=>$status));
	}
    

}