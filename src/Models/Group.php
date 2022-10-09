<?php

namespace Classiebit\Addchat\Models;
use Classiebit\Addchat\Models\AddchatModel;

use Illuminate\Database\Eloquent\Model;
use DB;

class Group extends AddchatModel
{
    public $timestamps  = false;
    protected $table    = '';
    
    /**
     *  construct
     */
    
    public function __construct()
    {
        parent::__construct();
        $this->table = $this->groups_tb;
    }
    
    /**
     *  get group name 
     */
    public function get_chatgroups($params = [])
    {
        $query = Group::query();
            
        $query
        ->select(array(
            "$this->groups_tb_id as id",
            "$this->groups_tb_name as name",
            
        DB::raw("(SELECT count(*)  FROM $this->ug_tb  UG WHERE UG.$this->ug_tb_group_id = $this->groups_tb.$this->groups_tb_id) group_users_count"),
        ));
            
        if(empty($params['is_admin']))
        {
            $query->whereIn("$this->groups_tb_id", $params['group_ids']);
        }
        return  $query
                    ->get()
                    ->toArray();
    }

    /**
     *====================ADMIN FUNCTION START=============================================== 
    */ 
     

    /**
    *   get groups
    */
    public function a_get_groups()
    {
        $query = Group::query();
            
        $query
        ->select(array(
            "$this->groups_tb_id as id",
            "$this->groups_tb_name as name",
        ));

        return  $query
                    ->get()
                    ->toArray();
    }
}  