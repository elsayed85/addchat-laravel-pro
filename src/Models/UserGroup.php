<?php

namespace Classiebit\Addchat\Models;
use Classiebit\Addchat\Models\AddchatModel;

use Illuminate\Database\Eloquent\Model;
use DB;

class UserGroup extends AddchatModel
{
    public $timestamps  = false;
    protected $table    = '';
    
    /**
     *  construct
     */
    
    public function __construct()
    {
        parent::__construct();
        $this->table = $this->ug_tb;
    }

    /**
     *   get group id of logged in user
     */
    
    public function get_groups_id($login_user_id = null)
    {
        return  UserGroup::select("$this->ug_tb_group_id as group_id")
                ->where($this->ug_tb_user_id, $login_user_id)
                ->get()->toArray();
    }

    
    /*
    *  get group users id
    */ 
    public function get_groups_users_id($login_user_id = null, $params = [])
    {
        $query = UserGroup::query();
  
        $query
        ->select(array(
            "$this->ug_tb_user_id as user_id",
        ))
        // exclude logged in user
        ->where("$this->ug_tb_user_id","<>" ,$login_user_id);
    
        if(!empty($params['gc_id']))
        {
            $query        
            ->whereIn($this->ug_tb_group_id, $params['gc_id']);
        }
        else
        {
            $query       
            ->where($this->ug_tb_group_id, $params['group_id'])
            ->limit($params['filters']['limit'])
            ->offset($params['filters']['offset']);
        }
        return  $query->get()->toArray();
                    
    }

    
    /**
     * ======================================= GUEST USER FUNCTION START=======================================
     */

    /**
     *  get guest group users ids form ac_group_chat table
     */
     
    public function get_guest_group_users_ids($gc_id = null)
    {
        $query = UserGroup::query();
        return  $query
                    ->select(array(
                        "$this->ug_tb_user_id as user_id",
                    ))
                    ->where($this->ug_tb_group_id, $gc_id)
                    ->get()
                    ->toArray();
    }
}    