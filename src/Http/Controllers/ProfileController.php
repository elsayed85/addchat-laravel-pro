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

class ProfileController extends AddchatController
{
    public function __construct()
    {
	    Parent::__construct();
    }
    
    /**
	 *  size change
	 */
	public function size_change(Request $request)
	{
	    // init config in each method
		$this->init_config();

		// check authentication
        $this->check_auth();
    	
		// input and validate
		$validator = Validator::make($request->all(), [
			'size'    	 => 'required|numeric',
			'fullname'   => 'required'
		]);
		
		if($validator->fails())
		{
			$data = array('status' => false, 'response'=> $validator->errors()->all());
			return  $this->format_json($data);
		}

		$data = [
			'size_small' => $request->input('size'),
			'fullname'   => $request->input('fullname'),
			'user_id'    => $this->AC_SETTINGS->logged_user_id,
			'dt_updated' => date('Y-m-d H:i:s'),
		];
		
		$status  	= $this->profile->update_user($this->AC_SETTINGS->logged_user_id, $data);

		return $this->format_json(array('status' => $status));
		
	}

	/**
	 *  dark mode change
	 */
	public function dark_mode_change(Request $request)
	{
		// init config in each method
		$this->init_config();

		// check authentication
        $this->check_auth();

		// input and validate
		$validator = Validator::make($request->all(), [
			'dark_mode'    => 'required|numeric',
			'fullname'   => 'required'
		]);
		
		if($validator->fails())
		{
			$data = array('status' => false, 'response'=> $validator->errors()->all());
			return  $this->format_json($data);
		}

		$data = [
			'dark_mode'  => $request->input('dark_mode'),
			'fullname'   => $request->input('fullname'),
			'user_id'    => $this->AC_SETTINGS->logged_user_id,
			'dt_updated' => date('Y-m-d H:i:s'),
		];
		
		$status  	= $this->profile->update_user($this->AC_SETTINGS->logged_user_id, $data);

		return  $this->format_json(array('status' => $status));
		
	}

	/*
	* Update profile profile_update
	*/
    public function profile_update(Request $request)
    {
		// init config in each method
		$this->init_config();

		// check authentication
        $this->check_auth();
		
		// input and validate
		$validator = Validator::make($request->all(), [
			'status'        => 'required',
			'fullname'   	=> 'required',
			'user_id'       => 'required|numeric|gte:1',
			'image'         => 'image|mimes:jpg,JPG,jpeg,JPEG,png,PNG|max:5000000|nullable',
		]);
		
		if($validator->fails())
		{
			$data = array('status' => false, 'response'=> $validator->errors());
			return $this->format_json($data);
		}
		
		// upload attachment image
		$filename               = null;
		if(!empty($validator->valid()['image'])) // if image 
        {
			$file  				= $validator->valid()['image'];
			$filename           = time().rand(1,988).".".$file->getClientOriginalExtension();

            // upload_path/profiles/user-<id>/files
            // replace storage by public
            $upload_path        = str_replace('storage/', '', $this->AC_SETTINGS->upload_path);
            $profile_pic_path    = 'public/'.$upload_path.'/profiles/user-'.$this->AC_SETTINGS->logged_user_id.'/';
            $file->storeAs($profile_pic_path, $filename);

            // add user-id with filename
            $filename           = '/profiles/user-'.$this->AC_SETTINGS->logged_user_id.'/'.$filename;
        }

		$params					= array();
		$params['status']		= $validator->valid()['status'];
		$params['fullname']		= $validator->valid()['fullname'];
		$params['user_id']		= $validator->valid()['user_id'];
		$params['dt_updated'] =  date("Y-m-d H:i:s");


		if(!empty($filename))
			$params['avatar'] = $filename;
		
		// update user status
		$status           =  $this->profile->update_user($this->AC_SETTINGS->logged_user_id, $params);	

		if($status)
		{
			$data					= array();
			$data['status'] 		= true;
			$data['profile'] 		= $this->user->get_profile($this->AC_SETTINGS->logged_user_id, $this->AC_SETTINGS->guest_group_id);
			
			// if login user is admin then is_guest_group alway is 1 means true 
			if($this->AC_SETTINGS->logged_user_id == $this->AC_SETTINGS->admin_user_id && (int) $this->AC_SETTINGS->guest_group_id > 0)
			{
				$data['profile']->is_guest_group = 1;
			}
			
			return $this->format_json($data);
		}
			
	}

}