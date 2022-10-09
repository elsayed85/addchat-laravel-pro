<?php

namespace Classiebit\Addchat\Models;
use Classiebit\Addchat\Models\AddchatModel;

use Illuminate\Database\Eloquent\Model;
use DB;

class GuestNotification extends AddchatModel
{
    public $timestamps  = false;
    protected $table    = '';
    
    /**
     *  construct
     */
    
    public function __construct()
    {
        parent::__construct();
        $this->table = $this->ac_guests_messages_tb;
    }

    
    /**
     * set notification for guest 
     */
    
    public function set_guest_notification($notification = array())
    {
        $data_insert   = [];
        $data_update   = [];

        foreach($notification as $key => $value)
        {
            $result =  GuestNotification::select('*')
                        ->where($value)
                        ->first();

            if(empty($result))
            {
                $data_insert[] = $value;
            }
            else
            {
                $data_update[] = $value;
            }
        }        
        
        // insert
        if(!empty($data_insert))
        {
            GuestNotification::insert($data_insert);
        }
       
        //update
        if(!empty($data_update)) 
        {
            foreach($data_update as $key => $value)
            {
                GuestNotification::where($value)
                ->increment('messages_count', 1);
               
            }
        }

        return true;
    }

    /**
     *  get guest notification
     */
    
    public function get_guest_updates($params = [])
    {
        $query    = GuestNotification::query();

        $query
        ->select(array(
            "$this->ac_guests_messages_tb.m_to",
            "$this->ac_guests_messages_tb.m_from",
            "$this->ac_guests_messages_tb.g_to",
            "$this->ac_guests_messages_tb.g_from",
            "$this->ac_guests_messages_tb.messages_count",
        ));

        if(!empty($params['login_user_id']))
        {
            
            $query->where(["m_to" => $params['login_user_id']]);
        }    
        else    
        {   
            
            $query->where([ "g_to" => $params['guest_id']]);
        }    

        return $query
                ->get()
                ->toArray();
    }

    /**
     * Remove notification 
     */
     
    public function remove_guest_notification($notification = array())
    {
        $query    = GuestNotification::query();
        return $query
                ->where($notification)
                ->delete(); 
        
    }
}    