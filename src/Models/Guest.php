<?php

namespace Classiebit\Addchat\Models;
use Classiebit\Addchat\Models\AddchatModel;

use Illuminate\Database\Eloquent\Model;
use DB;

class Guest extends AddchatModel
{
    public $timestamps  = false;
    protected $table    = '';

    /**
     *  construct
     */
    
    public function __construct()
    {
        parent::__construct();
        $this->table = $this->ac_guests_tb;
    }

     /**
      *   guest user login 
      */

    public function guest_login($params = [])
    {
        $query = Guest::query();
    
        $guest_user = [];
        // check guest user that he is already exits into table or not if user already exist into table then don't insert data again
        $guest_user      = $query->select(['id', 'fullname', 'email', 'status'])->where(array('email' => $params['email']))->first();
    
        if(empty($guest_user))
        {   
            $id = $query->insertGetId($params);
            $guest_user     = $query->select(['id', 'fullname', 'email'])->where( array('id' => $id))->first();
        }
        else
        {
            if(empty($guest_user->status))
            {
                // update status of guest table 
                Guest::where("id", $guest_user->id)
                ->update(array('status' => '1'));
            }
        }
        
        return $guest_user;
    }

    /**
     *   get guest user only one base on id
     */
    
    public function get_guest_user($guest_user_id = null)
    {
        $query = Guest::query();
        return $query->select(['id', 'fullname', 'email'])->where( array('id' => $guest_user_id, 'status' => 1))->first();   
    }

    /**
     *  get all guests list
     */
    
    public function get_guests($params = [])
    {
        $query         = Guest::query();
        
        $query
            ->select([
                'id',
                'fullname',
                'email',
                'dt_updated'
            ]);

            // in case of search, search amongst all users
        if(!empty($params['filters']['search']) )
        {
            $query
            ->orWhere([ 
                ['fullname', 'LIKE', '%' . $params['filters']['search']. '%'],
                ['email', 'LIKE', '%' . $params['filters']['search']. '%']]);
        }
        
        return  $query
                ->where(['status' => 1])
                ->limit($params['filters']['limit'])
                ->offset($params['filters']['offset'])
                ->orderBy("dt_updated", 'DESC')
                ->get()
                ->toArray();
    }
    
}    