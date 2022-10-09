<?php

namespace Classiebit\Addchat\Models;
use Classiebit\Addchat\Models\AddchatModel;

use Illuminate\Database\Eloquent\Model;
use DB;

class Profile extends AddchatModel
{
    public $timestamps  = false;
    protected $table    = '';
    
    /**
     *  construct
     */
    
    public function __construct()
    {
        parent::__construct();
        $this->table = $this->ac_profiles_tb;
    }

    /**
     *  UPDATE USER START
     * 
     */
    
    public function update_user($login_user_id = 0, $params = [])
    {
        $result =  Profile::where('user_id', $login_user_id)
                    ->first();
                    
        // insert data in profile table if user have not exist 
        if(empty($result))
        {
            return  Profile::insert($params);
        }
        else
        {
          // if user have exist then update user data  
            return Profile::where("user_id", $login_user_id)
                            ->update($params);
        }        
        
    }

    

}