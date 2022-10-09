<?php

namespace Classiebit\Addchat\Models;
use Classiebit\Addchat\Models\AddchatModel;

use Illuminate\Database\Eloquent\Model;
use DB;

class Contact extends AddchatModel
{
    public $timestamps  = false;
    protected $table    = '';

    /**
     *  construct
     */
    
    public function __construct()
    {   
        parent::__construct();
        $this->table = $this->ac_contacts_tb;
    }

    /**
     * Create contact list for logged in user 
     */
    
    public function create_contacts($params = [])
    {
        $query  = Contact::query();
    
        $result = $query
                    ->select()
                    ->where([
                        
                        'users_id'    => $params['users_id'], 
                        'contacts_id' => $params['contacts_id']
                    ])
                    ->first();
                
        if(empty($result))
        {
            $query->insert(array('users_id' => $params['users_id'], 'contacts_id' => $params['contacts_id'] ,'dt_updated' => date("Y-m-d H:i:s")));
        }

        $query  = DB::table($this->ac_contacts_tb);
        // inverse
        $result =  $query
                    ->select()
                    ->where(array(
                        'users_id'    => $params['contacts_id'], 
                        'contacts_id' => $params['users_id']
                    ))
                    ->first();
            
        if(empty($result))
        {            
            $query->insert(array('users_id' => $params['contacts_id'], 'contacts_id' => $params['users_id'] ,'dt_updated' => date("Y-m-d H:i:s")));
        }

        return true;
        
    }

    /**
     *  Get contact users
     */

    public function get_contact_users($login_user_id = null)
    {
        return Contact::where('users_id',$login_user_id)
                ->get()->toArray();
                
    }
     
    /**
     *  Remove user from contact list
     */
      
    public function remove_contacts($params = [])
    {
        return Contact::where($params)
                ->delete(); 
        
    }
}    