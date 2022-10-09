<?php

namespace Classiebit\Addchat\Models;
use Classiebit\Addchat\Models\AddchatModel;

use Classiebit\Addchat\Models\Group;

use Illuminate\Database\Eloquent\Model;
use DB;

class User extends AddchatModel
{
    public $timestamps  = false;
    protected $table    = '';
    
    /**
     *  construct
     */
    
    public function __construct()
    {
        parent::__construct();
        $this->table = $this->users_tb;
    }

    /**
     * get user's groups.  (many to many relationship)
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'users_groups', 'user_id', 'group_id');
    }

    /**
     *   get user's chat_groups. (many to many relationship)
     */

    public function chat_groups()
    {
        return $this->belongsToMany(Group::class, 'ac_groupchat','group_id', 'gc_id');
    }


    /**
    *    get logged in user profile 
    */
    
    public function get_profile($login_user_id = null, $guest_group_id = null)
    {
        $select = array(
            "$this->users_tb.$this->users_tb_id as id",
            "$this->users_tb.$this->users_tb_email as email",
            "$this->ac_profiles_tb.fullname",
            "$this->ac_profiles_tb.avatar",
            "$this->ac_profiles_tb.dark_mode",
            "$this->ac_profiles_tb.size_small",
            "$this->ac_profiles_tb.status as online"
        );

        // if guest mode is on then check that logged in user part of guest group or not
        if($guest_group_id > 0)
        {
            $is_guest_group = DB::raw("(SELECT UG.$this->ug_tb_user_id  FROM $this->ug_tb  UG WHERE  UG.$this->ug_tb_user_id = $login_user_id AND UG.$this->ug_tb_group_id = $guest_group_id)  is_guest_group");
            
            array_push($select, $is_guest_group); 
        }

        
        return  User::select($select)
                ->leftjoin($this->ac_profiles_tb, "$this->ac_profiles_tb.user_id", "=" ,"$this->users_tb.$this->users_tb_id")
                ->where("$this->users_tb.$this->users_tb_id", $login_user_id)
                ->first();
    }
        
    /**
     * get specific user by id 
     */
    
    
    public function get_user($login_user_id  = 0, $params = [])
    {
        
        $select = array(
            "$this->users_tb.$this->users_tb_id as id",
            "$this->users_tb.$this->users_tb_email as email",

            "$this->ac_profiles_tb.fullname",
            "$this->ac_profiles_tb.avatar",
            "$this->ac_profiles_tb.dark_mode",
            "$this->ac_profiles_tb.status as online",
        );
        
        if($login_user_id)
        {
            // check buddy user block or not
            array_push($select, DB::raw("(SELECT BU.users_id FROM $this->ac_blocked_tb BU WHERE  BU.blocked_users_id = '".$params['user_id']."' AND BU.users_id =  $login_user_id) is_blocked"));
            
            // check buddy user in contact list or not of logged in user
            array_push($select, DB::raw("(SELECT AC.users_id FROM $this->ac_contacts_tb AC WHERE  AC.users_id = $login_user_id AND AC.contacts_id =  '".$params['user_id']."') is_contact"));
        }
            
        return  User::select($select)
                    ->leftjoin($this->ac_profiles_tb, "$this->ac_profiles_tb.user_id", "=" ,"$this->users_tb.$this->users_tb_id")
                    ->where("$this->users_tb.$this->users_tb_id", $params['user_id'])
                    ->get()
                    ->first();
    }

    /**
     *  get users 
     */

    public function get_users($login_user_id = null, $params = [])
    {
        $query = User::query();
        
        $query
        ->select(array(
            "$this->users_tb.$this->users_tb_id as id",
            "$this->users_tb.$this->users_tb_email as email",
            "$this->ac_profiles_tb.avatar",
            "$this->ac_profiles_tb.fullname as username",
            "$this->ac_profiles_tb.status as online",

            DB::raw("(SELECT IF(COUNT(ACM.id) > 0, COUNT(ACM.id), null) FROM $this->ac_messages_tb ACM WHERE ACM.m_to = '$login_user_id' AND ACM.m_from = '$this->users_tb.$this->users_tb_id' AND ACM.is_read = '0') as unread"),
        ))
        ->leftJoin($this->ac_profiles_tb, "$this->ac_profiles_tb.user_id",  '=' ,"$this->users_tb.$this->users_tb_id")
        ->where("$this->users_tb.$this->users_tb_id", "!=" , $login_user_id);

           
        if(!empty($params['blocked_by']))
        {
            $query
            ->whereNotIn($this->users_tb.".".$this->users_tb_id, $params['blocked_by']);  
        }    

        if(!empty($params['blocked_to_me']))
            $query
            ->whereNotIn($this->users_tb.".".$this->users_tb_id, $params['blocked_to_me']);      

        // in case of search, search amongst all users
        if(!empty($params['filters']['search']) )
        {
            $query
            ->whereRaw("($this->ac_profiles_tb.fullname LIKE '%".$params['filters']['search']."%' 
                    OR $this->users_tb.$this->users_tb_email LIKE '%".$params['filters']['search']."%')");
        }
        else
        {
            if(!empty($params['users_id']))
            {
                // only specific group users  
                $query->whereIn("$this->users_tb.$this->users_tb_id",$params['users_id']); 
            }
            else
            {   
                // have no contact user then show all users
                if(!empty($params['contacts_id']))
                {
                    // only contact users 
                    $query->whereIn("$this->users_tb.$this->users_tb_id",$params['contacts_id']); 
                } 
            }
        }

        // admin can see all
        // other groups users can see according to groupchat permissions
        // only if is_groups is enabled
        if(!empty($params['chat_users_id'] && empty($params['is_admin']) && $params['is_groups']))
        {
            // all group users     
            $query
            ->whereIn("$this->users_tb.$this->users_tb_id", $params['chat_users_id']); 
        }

        return  $query
                ->limit($params['filters']['limit'])
                ->offset($params['filters']['offset'])
                ->get()
                ->toArray();
       
    }

    /**
     * ======================================= GUEST USER FUNCTION START=======================================
     */
    
    /**
     * get guest group users from users table
     */ 
    public function get_guest_group_users($users_ids = [])
    {
        $query = User::query();

        $select = array(
            "$this->users_tb.$this->users_tb_id as id",
            "$this->users_tb.$this->users_tb_email as email",
            "$this->ac_profiles_tb.avatar",
            "$this->ac_profiles_tb.fullname as username",
        );
            
        return  $query
                ->select($select)
                ->leftJoin($this->ac_profiles_tb, "$this->ac_profiles_tb.user_id",  '=' ,"$this->users_tb.$this->users_tb_id")
                ->whereIn("$this->users_tb.$this->users_tb_id", $users_ids)
                ->get()
                ->toArray();
    }

    

    

    
    
   

}    