<?php

namespace Classiebit\Addchat\Models;
use Classiebit\Addchat\Models\AddchatModel;

use Illuminate\Database\Eloquent\Model;
use DB;

class GroupChat extends AddchatModel
{
    public $timestamps  = false;
    protected $table    = '';
    
    /**
     *  construct
     */
    
    public function __construct()
    {
        parent::__construct();
        $this->table = $this->ac_groupchat_tb;
    }

    /**
     *  get groupchat ids
     */   
    public function get_groupchat($group_ids = [])
    {
        return   GroupChat::select(array(
                    "gc_id",
                ))
                ->whereIn("group_id", $group_ids)
                ->get()->toArray();
    }

    /**
     *====================ADMIN FUNCTION START=============================================== 
    */ 
     

    /**
    *   get chatgroups
    */
    public function a_get_chatgroups()
    {
        $query  =  GroupChat::query();
        
        return  $query
                    ->get()
                    ->toArray();
    }

    /*
     *   save group chat settings
     */
    public function save_groupchat($group_id = null, $params = [])
    {
        $query  =  GroupChat::query();
        // delete then insert
        $query
            ->where('group_id', $group_id)
            ->delete();
      
        $query  =  GroupChat::query();
        return  $query->insert($params); 
    }
}    