<?php

namespace Classiebit\Addchat\Models;
use Classiebit\Addchat\Models\AddchatModel;

use Illuminate\Database\Eloquent\Model;
use DB;

class BlockUser extends AddchatModel
{
    public $timestamps  = false;
    protected $table    = '';

    /**
     *  construct
     */
    
    public function __construct()
    {
        parent::__construct();
        $this->table = $this->ac_blocked_tb;
    }

    
    /**
     *  get blocked users
     */ 
    
    public function get_blocked_by_users($login_user_id = null, $params = [])
    {
        // get other users who blocked by logged in user
        $query = BlockUser::query();
        
        if($params['inverse'])
        {
            $query
            ->select(array(
                "$this->ac_blocked_tb.blocked_users_id as  user_id",
            ))
            ->where(array("$this->ac_blocked_tb.users_id"=>$login_user_id));
        } 
        else
        {
            
            // get others users who blocked to logged in user 
            $query
            ->select(array(
                "$this->ac_blocked_tb.users_id as user_id",
            ))
            ->where(array("$this->ac_blocked_tb.blocked_users_id"=>$login_user_id));
        }    

        // can't send messages if user is blocked
        if(!empty($params['buddy']))
        {
            $query
            ->select(array(
                "$this->ac_blocked_tb.users_id as user_id",
            ))
            ->where(array("$this->ac_blocked_tb.blocked_users_id"=>$params['buddy']));
        }

        return $query->get()->toArray();
    }
    
    /**
    *   Block user
    */
    
    public function block_user($login_user_id = null, $params = [])
    {
        $query = BlockUser::query();
        
        $blocked_check = $query
                            ->where(array("users_id" => $login_user_id, "blocked_users_id"=>$params['blocked_user_id']))
                            ->delete();

        if($blocked_check) // if already blocked then unblock it
        {
            return 0;
        }
        else // block the user 
        {
            $query->insert(array("users_id" => $login_user_id, "blocked_users_id"=>$params['blocked_user_id'], "is_reported" => $params['is_report'] ? $params['is_report'] : 0,  'dt_updated' => date("Y-m-d H:i:s")));
            return 1;            
        }

        return FALSE;
    }

    /**
     *====================ADMIN FUNCTION START=============================================== 
    */ 

    /*
     *     get all blocked users
     */ 

    public function get_blocked_users($params = [])
    {   
        $query = BlockUser::query();

        return    $query
                    ->select(array(
                                "$this->ac_blocked_tb.is_reported",
                                "$this->ac_blocked_tb.dt_updated",
                                DB::raw("(SELECT PR.fullname  FROM $this->ac_profiles_tb PR  WHERE PR.user_id  = $this->ac_blocked_tb.blocked_users_id) blocked_users"),
                                DB::raw("(SELECT PR2.fullname FROM $this->ac_profiles_tb PR2 WHERE PR2.user_id = $this->ac_blocked_tb.users_id) blocked_by_users"),
                                DB::raw("(SELECT UR.$this->users_tb_email  FROM $this->users_tb UR WHERE UR.$this->users_tb_id = $this->ac_blocked_tb.blocked_users_id) blocked_users_email"),
                                DB::raw("(SELECT UR2.$this->users_tb_email FROM $this->users_tb UR2 WHERE UR2.$this->users_tb_id = $this->ac_blocked_tb.users_id) 
                                    blocked_by_users_email"),
                            ))
                    ->limit($params['filters']['limit'])
                    ->offset($params['filters']['offset'])        
                    ->get()
                    ->toArray();    
    }
}    