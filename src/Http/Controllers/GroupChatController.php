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

class GroupChatController extends AddchatController
{
    public function __construct()
    {
	    Parent::__construct();
    }
    
    /**
     *  ADMIN FUNCTION START
     */
    
	/**
	 *  save groupchat settings means one group can chat other groups
	 */

	public function save_groupchat(Request $request)
	{
		// init config in each method
		$this->init_config();

		//check admin authentication
		$this->check_admin(true);

		// input and validate
		$validator = Validator::make($request->all(), [
			'group_id' 	 => 'required|numeric|gte:1',
			'gc_id'      => 'required',  
			
		]);
		
		if($validator->fails())
		{
			$data = array('status' => false, 'response'=> $validator->errors()->all());
			return $this->format_json($data);
		}
	
		$group_id 			= (int) $validator->valid()['group_id'];
		$gc_id 				= $validator->valid()['gc_id'];


		$params					= array();
		foreach($gc_id as $key => $value)
		{
			$params[$key]['group_id']	= $group_id;
			$params[$key]['gc_id']	    = $value;
		}

		$status    = $this->group_chat->save_groupchat($group_id, $params);	
		
		return    $this->format_json(array('status' => $status));
		
	} 

}