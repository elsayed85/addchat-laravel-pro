<?php

namespace Classiebit\Addchat\Models;
use Classiebit\Addchat\Models\AddchatModel;

use Illuminate\Database\Eloquent\Model;
use DB;

class UserNotification extends AddchatModel
{
    public $timestamps  = false;
    protected $table    = '';
    
    /**
     *  construct
     */
    
    public function __construct()
    {   
        parent::__construct();
        $this->table = $this->ac_users_messages_tb;
    }

    /**
     *  add notification
     */  
    public function set_notification($params = [])
    {
        $query  = UserNotification::query();
        $result =  $query
                    ->select()
                    ->where($params)
                    ->first();
        
        // insert
        if(empty($result))
        {            
            $query->insert($params);
        }
        else // update messages_count
        {
            
            $query
            ->where($params)
            ->increment('messages_count', 1);
        }

        return true;
    }
     
    /**
     *  Remove notification
     */ 
    public function remove_notification($params = [])
    {
        return UserNotification::where($params)
                ->delete(); 
        
    }
    
    /**
     *  get notification
     */  
    public function get_updates($login_user_id = null)
    {
        $query  = UserNotification::query();
        
        $query
        ->select(array(
            "$this->ac_users_messages_tb.users_id",
            "$this->ac_users_messages_tb.buddy_id",
            "$this->ac_users_messages_tb.messages_count",
        ))
        ->where("buddy_id", $login_user_id);
        
        return $query
                ->get()
                ->toArray();
    }
}    