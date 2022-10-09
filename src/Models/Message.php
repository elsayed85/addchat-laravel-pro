<?php

namespace Classiebit\Addchat\Models;
use Classiebit\Addchat\Models\AddchatModel;

use Illuminate\Database\Eloquent\Model;
use DB;

use Classiebit\Addchat\Models\Guest;

class Message extends AddchatModel
{
    public $timestamps  = false;
    protected $table    = '';

    /**
     *  construct
     */
    
    public function __construct()
    {
        parent::__construct();
        $this->table = $this->ac_messages_tb;
    }

    /**
     *  Send Message
     */
    public function send_message($params = []) 
    {
       return Message::insertGetId($params);
        
    }

    /*
    * Get Message Between Two Users 
     */
    public function get_messages($login_user_id = null, $params)
    {
        $query = Message::query();
        $query
        ->select(array(
            "$this->ac_messages_tb.id",
            "$this->ac_messages_tb.m_from",
            "$this->ac_messages_tb.m_to",
            "$this->ac_messages_tb.message",
            "$this->ac_messages_tb.attachment",
            "$this->ac_messages_tb.is_read",
            "$this->ac_messages_tb.dt_updated",
            "$this->ac_messages_tb.m_reply_id",
            "$this->ac_messages_tb.reply_user_id",
            DB::raw("(SELECT MU.message FROM $this->ac_messages_tb MU WHERE MU.id = $this->ac_messages_tb.m_reply_id) quote_message"),
            
        ));
        

        $query
        ->whereRaw("( (`$this->ac_messages_tb`.`m_from` = '$login_user_id' AND `$this->ac_messages_tb`.`m_to` = '".$params['chat_user']."')")
        ->orWhereRaw("(`$this->ac_messages_tb`.`m_from` = '".$params['chat_user']."' AND `$this->ac_messages_tb`.`m_to` = '$login_user_id') )")
        
        //removing deleted messages and unsend
        ->whereRaw("( (IF(`$this->ac_messages_tb`.`m_from` = '$login_user_id', `$this->ac_messages_tb`.`m_from_delete`, `$this->ac_messages_tb`.`m_to_delete`) = 0) AND (IF(`$this->ac_messages_tb`.`m_to` = '$login_user_id', `$this->ac_messages_tb`.`m_to_delete`, `$this->ac_messages_tb`.`m_from_delete`) = 0) )");

        if(!$params['count'])
            return $query->count();
        
        $messages   = $query
                    ->orderBy("$this->ac_messages_tb.id")
                    ->limit($params['filters']['limit'])
                    ->offset($params['filters']['offset'])
                    ->get();
                    
        
        Message::where("$this->ac_messages_tb.m_to", $login_user_id)
        ->where("$this->ac_messages_tb.m_from", $params['chat_user'])
        ->update(array("$this->ac_messages_tb.is_read"=>'1'));

        return $messages;
    }

    /**
     *  Message Delete
     */ 
        
    public function message_delete($message_id = null, $login_user_id = null)
	{
        $query = Message::query();

        $message  =    $query
                        ->select('*')
                        ->where("id", $message_id)
                        ->first();
        

        if(empty($message))
            return false;

        $query
            ->where(array("id" => $message_id));
            
        if($message->m_from == $login_user_id)
            return  $query->update(["m_from_delete" => '1']);

        if($message->m_to == $login_user_id)
            return  $query->update(["m_to_delete" => '1']);    

    }
    
     
    /**
     *  unsend message
     */ 

    public function message_unsend($login_user_id = null, $params = [])
    {
        
        return  Message::where(array("id" => $params['message_id'], "m_from" => $login_user_id, "is_read" => '0'))
                    ->update(array("m_to_delete" => '1', "m_from_delete" => '1'));
    }

    /**
     *  delete chat history
     */

    public function delete_chat($login_user_id = null, $params = [])
    {
        Message::where(array("$this->ac_messages_tb.m_from"=>$login_user_id, "$this->ac_messages_tb.m_to"=>$params['user_id']))
        ->update(array("m_from_delete"=>1));
  
        Message::where(array("$this->ac_messages_tb.m_to"=>$login_user_id, "$this->ac_messages_tb.m_from"=>$params['user_id']))
        ->update(array("m_to_delete"=>1));

        return TRUE;
    }

    /**   
     * get latest message
     * 
     */
    public function get_latest_message($login_user_id = null, $params = [])
    {
        $query = Message::query();

        $result =    $query
                    ->select(array(
                        "$this->ac_messages_tb.id",
                        "$this->ac_messages_tb.m_from",
                        "$this->ac_messages_tb.m_to",
                        "$this->ac_messages_tb.message",
                        "$this->ac_messages_tb.attachment",
                        "$this->ac_messages_tb.is_read",
                        "$this->ac_messages_tb.dt_updated",
                        "$this->ac_messages_tb.m_reply_id",
                        "$this->ac_messages_tb.reply_user_id",
                        DB::raw("(SELECT MU.message FROM $this->ac_messages_tb MU WHERE MU.id = $this->ac_messages_tb.m_reply_id) quote_message"),
                        
                    ))
                    ->where(array("$this->ac_messages_tb.m_from" => $params['buddy_id'], "$this->ac_messages_tb.m_to" => $login_user_id, "$this->ac_messages_tb.is_read" => '0'))
                    
                    //group query for removing unsend messages
                    ->where(["$this->ac_messages_tb.m_from_delete" => "0", "$this->ac_messages_tb.m_to_delete" => "0"])
                    ->orderBy("$this->ac_messages_tb.id")
                    ->get()
                    ->toArray();

        // delete notification
        $query = Message::query();

        $query
        ->where("$this->ac_messages_tb.m_to", $login_user_id)
        ->where("$this->ac_messages_tb.m_from", $params['buddy_id'])
        ->update(array("$this->ac_messages_tb.is_read"=>'1'));

        return $result;
    }

    /**
     *====================ADMIN FUNCTION START=============================================== 
    */ 
     


    /**
     *   get chat users who chat with each other means between users
     */
    public function a_chat_between($params = [])
    {
        $mode   = config('database.connections.mysql.strict');

        $query  = Message::query();
        
        if(!$mode)
        {
            // safe mode is off
            $select = array(
                "$this->ac_messages_tb.id",
                "$this->ac_messages_tb.m_to",
                "$this->ac_messages_tb.m_from",
                "$this->ac_messages_tb.dt_updated",
                "$this->ac_messages_tb.message",

                DB::raw("(SELECT PR.fullname  FROM $this->ac_profiles_tb  PR  WHERE PR.user_id  = $this->ac_messages_tb.m_from) m_from_username"),
                DB::raw("(SELECT PR2.fullname FROM $this->ac_profiles_tb  PR2 WHERE PR2.user_id = $this->ac_messages_tb.m_to) m_to_username"),
                DB::raw("(SELECT UR.$this->users_tb_email  FROM $this->users_tb UR WHERE UR.$this->users_tb_id = $this->ac_messages_tb.m_from)
                    m_from_email"),
                DB::raw("(SELECT UR2.$this->users_tb_email FROM $this->users_tb UR2 WHERE UR2.$this->users_tb_id = $this->ac_messages_tb.m_to) 
                    m_to_email"),
            );
        }
        else
        {
            // safe mode is on
            $select = array(
                DB::raw("ANY_VALUE($this->ac_messages_tb.id) as id"),
                DB::raw("ANY_VALUE($this->ac_messages_tb.m_to) as m_to"),
                "$this->ac_messages_tb.m_from",
                DB::raw("ANY_VALUE($this->ac_messages_tb.dt_updated) as dt_updated"),
                DB::raw("ANY_VALUE($this->ac_messages_tb.message) as message"),
                
                DB::raw("ANY_VALUE((SELECT PR.fullname  FROM $this->ac_profiles_tb  PR  
                    WHERE PR.user_id  = $this->ac_messages_tb.m_from)) as m_from_username"
                ),
                DB::raw("ANY_VALUE((SELECT PR2.fullname FROM $this->ac_profiles_tb  PR2
                     WHERE PR2.user_id = $this->ac_messages_tb.m_to)) as m_to_username"
                ),
                DB::raw("ANY_VALUE((SELECT UR.$this->users_tb_email  FROM $this->users_tb UR WHERE UR.$this->users_tb_id = $this->ac_messages_tb.m_from)) as m_from_email"),
                DB::raw("ANY_VALUE((SELECT UR2.$this->users_tb_email FROM $this->users_tb UR2 WHERE UR2.$this->users_tb_id = $this->ac_messages_tb.m_to)) as m_to_email"),
            );
        }

        return  $query
                ->select($select)
                ->where([array('m_to', '!=', '0'), array('m_from', '!=', '0')])
                ->groupBy(array("$this->ac_messages_tb.m_from", "m_to"))
                ->orderBy("id", 'DESC')
                ->limit($params['filters']['limit'])
                ->offset($params['filters']['offset'])  
                ->get()
                ->toArray();
                 
    }

    /**
     *   get conversations between two users
     * 
     */

    public function a_get_conversations($params = [])
    {
        $query  = Message::query();

        $query
        ->select(array(
            "$this->ac_messages_tb.id",
            "$this->ac_messages_tb.m_from",
            "$this->ac_messages_tb.m_to",
            "$this->ac_messages_tb.message",
            "$this->ac_messages_tb.attachment",
            "$this->ac_messages_tb.is_read",
            "$this->ac_messages_tb.dt_updated",
            "$this->ac_messages_tb.m_reply_id",
            "$this->ac_messages_tb.reply_user_id",
            "$this->ac_messages_tb.m_to_delete",
            "$this->ac_messages_tb.m_from_delete",
            DB::raw("(SELECT MU.message FROM $this->ac_messages_tb MU WHERE MU.id = $this->ac_messages_tb.m_reply_id) quote_message"),
            DB::raw("(SELECT PR.avatar  FROM $this->ac_profiles_tb PR WHERE PR.user_id = $this->ac_messages_tb.m_from) m_from_image"),
            DB::raw("(SELECT PR2.avatar FROM $this->ac_profiles_tb PR2 WHERE PR2.user_id  = $this->ac_messages_tb.m_to)   m_to_image"),
        ));
        // //group query for removing deleted messages
        $query
        ->whereRaw("((`$this->ac_messages_tb`.`m_from` = '".$params['m_from']."' AND `$this->ac_messages_tb`.`m_to` = '".$params['m_to']."'))")
        ->orWhereRaw("((`$this->ac_messages_tb`.`m_from` = '".$params['m_to']."'  AND `$this->ac_messages_tb`.`m_to` = '".$params['m_from']."') )");
        
        if($params['count'])
            return $query->count();

        return  $query
                    ->orderBy("$this->ac_messages_tb.id")
                    ->limit($params['filters']['limit'])
                    ->offset($params['filters']['offset'])
                    ->get()
                    ->toArray();

    }

    /**
     * ======================================= GUEST USER FUNCTION START=======================================
     */

    /** 
     * guest can see all messages og guest group users with his messages
     */

    public function get_messages_of_guest($params = [])
    {
        $mode        = config('database.connections.mysql.strict');

        $query       = Message::query();

        $select  = [];
        if(!$mode )
        {
            // safe mode is off
            $select = array(
                "$this->ac_messages_tb.id ",
                "$this->ac_messages_tb.m_from ",
                "$this->ac_messages_tb.m_to",
                "$this->ac_messages_tb.g_to ",
                "$this->ac_messages_tb.g_from ",
                "$this->ac_messages_tb.is_read ",
                "$this->ac_messages_tb.g_random ",
                "$this->ac_messages_tb.message ",
                "$this->ac_messages_tb.dt_updated ",
                DB::raw("(SELECT P.avatar FROM $this->ac_profiles_tb  P WHERE  P.user_id = $this->ac_messages_tb.m_from) m_from_image"),
                DB::raw("(SELECT P2.fullname  FROM $this->ac_profiles_tb  P2 WHERE  P2.user_id = $this->ac_messages_tb.m_from) m_from_name"),
   
            );
        }
        
        else
        {
            // safe mode is on
            $select = array(
                DB::raw("ANY_VALUE($this->ac_messages_tb.id) id"),
                DB::raw("ANY_VALUE($this->ac_messages_tb.m_from) m_from"),
                DB::raw("ANY_VALUE($this->ac_messages_tb.m_to) m_to"),
                DB::raw("ANY_VALUE($this->ac_messages_tb.g_to) g_to"),
                DB::raw("ANY_VALUE($this->ac_messages_tb.g_from) g_from"),
                DB::raw("ANY_VALUE($this->ac_messages_tb.is_read) is_read"),
                "$this->ac_messages_tb.g_random",
                DB::raw("ANY_VALUE($this->ac_messages_tb.message) message"),
                DB::raw("ANY_VALUE($this->ac_messages_tb.dt_updated) dt_updated"),
                
                DB::raw("ANY_VALUE((SELECT P.avatar FROM $this->ac_profiles_tb  P WHERE  P.user_id = $this->ac_messages_tb.m_from)) as                     m_from_image"),
                
                DB::raw("ANY_VALUE((SELECT P2.fullname  FROM $this->ac_profiles_tb  P2 WHERE  P2.user_id = $this->ac_messages_tb.m_from))
                    as m_from_name"),
                
            );
        }
      
        $query
        ->select($select)
        ->orWhere([ "$this->ac_messages_tb.g_to" => $params['guest_id'], "$this->ac_messages_tb.g_from" => $params['guest_id']])
        //group query for removing deleted messages
        ->where(["$this->ac_messages_tb.m_from_delete" => "0", "$this->ac_messages_tb.m_to_delete" => "0"]); 
        
        
        if(!$params['count'])
        {   
            return $query->distinct("$this->ac_messages_tb.g_random")->count("$this->ac_messages_tb.g_random");
            
        }    
        
        $messages   = $query
                    ->groupBy("$this->ac_messages_tb.g_random")
                    ->orderBy("id")
                    ->limit($params['filters']['limit'])
                    ->offset($params['filters']['offset'])
                    ->get();
                    
        
        Message::where("$this->ac_messages_tb.g_to", $params['guest_id'])
        ->update(array("$this->ac_messages_tb.is_read"=>'1'));

        return $messages;
    }
    
    /**
     * guest user can  send messages to login guests users 
     */
    
    public function guest_send_message($data = [])
    {   
        $query     = Message::query();
        return $query->insert($data);
    }

    
    /**
     * get latest message for guest
     */
    public function get_guest_latest_message($params = [])
    {
        $query         = Message::query();

        $query
            ->select(array(
                "$this->ac_messages_tb.id",
                "$this->ac_messages_tb.m_from",
                "$this->ac_messages_tb.m_to",
                "$this->ac_messages_tb.g_from",
                "$this->ac_messages_tb.g_to",
                "$this->ac_messages_tb.message",
                "$this->ac_messages_tb.is_read",
                "$this->ac_messages_tb.dt_updated",
            ));

        $result = [];    

        if(!empty($params['login_user_id']))
        {
            // it is for login user
            $result = $query    
                    ->where(array("$this->ac_messages_tb.g_from" => $params['guest_id'], "$this->ac_messages_tb.m_to" => $params['login_user_id'], "$this->ac_messages_tb.is_read" => '0'))
                    
                    //group query for removing deleted messages
                    ->where(["$this->ac_messages_tb.m_from_delete" => "0", "$this->ac_messages_tb.m_to_delete" => "0"])
                    ->orderBy("$this->ac_messages_tb.id")
                    ->get()
                    ->toArray();
                    
            // delete notification
            Message::where("$this->ac_messages_tb.m_to", $params['login_user_id'])
            ->where("$this->ac_messages_tb.g_from", $params['guest_id'])
            ->update(array("$this->ac_messages_tb.is_read"=>'1'));
        }
        else
        {
            // guest user without login
             $result = $query    
                    ->where(array("$this->ac_messages_tb.g_to" => $params['guest_id'],  "$this->ac_messages_tb.is_read" => '0'))
                    
                    //group query for removing deleted messages
                    ->where(["$this->ac_messages_tb.m_from_delete" => "0", "$this->ac_messages_tb.m_to_delete" => "0"])
                    ->orderBy("$this->ac_messages_tb.id")
                    ->get()
                    ->toArray();
            
            // delete notification
            Message::where("$this->ac_messages_tb.g_to", $params['guest_id'])
            ->update( array("$this->ac_messages_tb.is_read"=>'1'));
        }
       
        return $result;
    }

    /**
     *  get guest messages  for login user
     */
    
    public function get_guest_messages($params = [])
    {
        $query         = Message::query();

        $query
        ->select(array(
            "$this->ac_messages_tb.id",
            "$this->ac_messages_tb.m_from",
            "$this->ac_messages_tb.m_to",
            "$this->ac_messages_tb.g_from",
            "$this->ac_messages_tb.g_to",
            "$this->ac_messages_tb.message",
            "$this->ac_messages_tb.attachment",
            "$this->ac_messages_tb.is_read",
            "$this->ac_messages_tb.dt_updated",
        ));
     
        $query
        ->whereRaw("( (`$this->ac_messages_tb`.`m_from` = '".$params['logged_user_id']."' AND `$this->ac_messages_tb`.`g_to` = '".$params['guest_id']."')")
        ->orWhereRaw("(`$this->ac_messages_tb`.`g_from` = '".$params['guest_id']."' AND `$this->ac_messages_tb`.`m_to` = '".$params['logged_user_id']."') )")
        
        // remove unsend messages
        ->where(["$this->ac_messages_tb.m_from_delete" => '0', "$this->ac_messages_tb.m_to_delete" => '0' ]); 
        

        if(!$params['count'])
            return $query->count();
        
        $messages   = $query
                    ->orderBy("$this->ac_messages_tb.id")
                    ->limit($params['filters']['limit'])
                    ->offset($params['filters']['offset'])
                    ->get();
                    

        Message::where("$this->ac_messages_tb.m_to", $params['logged_user_id'])
        ->where("$this->ac_messages_tb.g_from", $params['guest_id'])
        ->update( array("$this->ac_messages_tb.is_read"=>'1'));

        return $messages;
    }
    
    /**
     * login user  send message to guest
     */
    
    public function send_to_guest($data = [])
    {
        $query         = Message::query();
        return   $query->insertGetId($data);
    }

    /**
     *   check login user have guest account then update guest messages
     */
    
    public  function update_guest_messages($params = [])
    {
        DB::beginTransaction();

        try {
                
            Message::where("g_to", $params['guest_id'])
            ->update(array('m_to' => $params['logged_user_id'] ));

            Message::where("g_from", $params['guest_id'])
            ->update(array('m_from' => $params['logged_user_id']));

            // update status of guest table 
            Guest::where("id", $params['guest_id'])
            ->update(array('status' => '0'));
            
            DB::commit();
            // all good
        } catch (\Exception $e) {
            DB::rollback();
            // something went wrong
            return false;
        }

        return true;
    }

    /**
     *   get chat between guest users
     */
    public function a_chat_between_guest($params = [])
    {
        $mode = config('database.connections.mysql.strict');

        $query       = Message::query();
        
        if(!$mode)
        {
            // safe mode is off
            $select = array(
                "$this->ac_messages_tb.id",
                "$this->ac_messages_tb.m_to",
                "$this->ac_messages_tb.m_from",
                "$this->ac_messages_tb.g_to",
                "$this->ac_messages_tb.g_from",
                "$this->ac_messages_tb.dt_updated",
                "$this->ac_messages_tb.message",

                DB::raw("(SELECT PR.fullname  FROM $this->ac_profiles_tb  PR  WHERE PR.user_id  = $this->ac_messages_tb.m_from) m_from_username"),
                DB::raw("(SELECT PR2.fullname FROM $this->ac_profiles_tb  PR2 WHERE PR2.user_id = $this->ac_messages_tb.m_to) m_to_username"),
                DB::raw("(SELECT UR.$this->users_tb_email  FROM $this->users_tb UR WHERE UR.$this->users_tb_id = $this->ac_messages_tb.m_from)
                    m_from_email"),
                DB::raw("(SELECT UR2.$this->users_tb_email FROM $this->users_tb UR2 WHERE UR2.$this->users_tb_id = $this->ac_messages_tb.m_to) 
                    m_to_email"),
                    
                DB::raw("(SELECT GS.fullname  FROM $this->ac_guests_tb  GS WHERE GS.id = $this->ac_messages_tb.g_from)
                    g_from_fullname"),
                DB::raw("(SELECT GS2.fullname FROM $this->ac_guests_tb GS2 WHERE GS2.id = $this->ac_messages_tb.g_to)
                    g_to_fullname"), 
            );
        }
        else
        {
            // safe mode is on
            $select = array(
                DB::raw("ANY_VALUE($this->ac_messages_tb.id) as id"),
                DB::raw("ANY_VALUE($this->ac_messages_tb.m_to) as m_to"),
                DB::raw("ANY_VALUE($this->ac_messages_tb.g_to) as g_to"),
                DB::raw("ANY_VALUE($this->ac_messages_tb.g_from) as g_from"),
                "$this->ac_messages_tb.m_from",
                DB::raw("ANY_VALUE($this->ac_messages_tb.dt_updated) as dt_updated"),
                DB::raw("ANY_VALUE($this->ac_messages_tb.message) as message"),
                
                DB::raw("ANY_VALUE((SELECT PR.fullname  FROM $this->ac_profiles_tb PR WHERE PR.user_id = $this->ac_messages_tb.m_from)) as m_from_username"),
                DB::raw("ANY_VALUE((SELECT PR2.fullname FROM $this->ac_profiles_tb PR2 WHERE PR2.user_id = $this->ac_messages_tb.m_to)) as m_to_username"),
                DB::raw("ANY_VALUE((SELECT UR.$this->users_tb_email  FROM $this->users_tb UR WHERE UR.$this->users_tb_id = $this->ac_messages_tb.m_from)) as m_from_email"),
                DB::raw("ANY_VALUE((SELECT UR2.$this->users_tb_email FROM $this->users_tb UR2 WHERE UR2.$this->users_tb_id = $this->ac_messages_tb.m_to)) as m_to_email"),
                
                
                DB::raw("ANY_VALUE((SELECT GS.fullname  FROM $this->ac_guests_tb  GS WHERE GS.id = $this->ac_messages_tb.g_from))
                    g_from_fullname"),
                DB::raw("ANY_VALUE((SELECT GS2.fullname FROM $this->ac_guests_tb GS2 WHERE GS2.id = $this->ac_messages_tb.g_to)) 
                    g_to_fullname"),    
            );
        }

        return  $query
                ->select($select)
                ->orWhere([array('g_to', '!=', '0')])
                ->orWhere([array('g_from', '!=', '0')])
                ->groupBy(array("$this->ac_messages_tb.m_from", "m_to", "g_from", "g_to"))
                ->orderBy("id", 'DESC')
                ->limit($params['filters']['limit'])
                ->offset($params['filters']['offset'])  
                ->get()
                ->toArray();
                 
    }

    /**
     *   get guest conversations between guest
     * 
     */

    public function a_get_guest_conversations($params = [])
    {
        $query      = Message::query();

        $query
        ->select(array(
            "$this->ac_messages_tb.id",
            "$this->ac_messages_tb.m_from",
            "$this->ac_messages_tb.m_to",
            "$this->ac_messages_tb.g_from",
            "$this->ac_messages_tb.g_to",
            "$this->ac_messages_tb.message",
            "$this->ac_messages_tb.attachment",
            "$this->ac_messages_tb.is_read",
            "$this->ac_messages_tb.dt_updated",
            "$this->ac_messages_tb.m_reply_id",
            "$this->ac_messages_tb.reply_user_id",
            "$this->ac_messages_tb.m_to_delete",
            "$this->ac_messages_tb.m_from_delete",
            
            DB::raw("(SELECT PR.avatar  FROM $this->ac_profiles_tb PR WHERE PR.user_id = $this->ac_messages_tb.m_from) m_from_image"),
            DB::raw("(SELECT PR2.avatar FROM $this->ac_profiles_tb PR2 WHERE PR2.user_id = $this->ac_messages_tb.m_to)   m_to_image"),
        ));
        // //group query for removing deleted messages
        $query
        ->whereRaw("((`$this->ac_messages_tb`.`m_from` = '".$params['user_id']."' AND `$this->ac_messages_tb`.`g_to` = '".$params['guest_id']."'))")
        ->orWhereRaw("((`$this->ac_messages_tb`.`g_from` = '".$params['guest_id']."'  AND `$this->ac_messages_tb`.`m_to` = '".$params['user_id']."') )");
        
        if($params['count'])
            return $query->count();

        return  $query
                    ->orderBy("$this->ac_messages_tb.id")
                    ->limit($params['filters']['limit'])
                    ->offset($params['filters']['offset'])
                    ->get()
                    ->toArray();

    }

    /**
     *==================== Pusher notification start ===================================================================
     */

    /**
     * update is_read of guest   
     */ 
    public function is_read($login_user_id, $buddy_id)
    {
        $query      = Message::query();

        $query
        ->where("$this->ac_messages_tb.m_to", $login_user_id)
        ->where("$this->ac_messages_tb.m_from", $buddy_id)
        ->update(array("$this->ac_messages_tb.is_read"=>'1'));

    }

    
    /**
     * update is_read of guest user   
     */ 

    public function is_read_guest($login_user_id, $guest_id)
    {
        $query      = Message::query();
        // login guest
        if(!empty($login_user_id))
        {
            // delete notification
            $query
            ->where("$this->ac_messages_tb.g_from", $guest_id)
            ->update(array("$this->ac_messages_tb.is_read"=>'1'));
        }    
        else
        {
            // without login guest
            // delete notification
            $query
            ->where("$this->ac_messages_tb.g_to", $guest_id)
            ->update(array("$this->ac_messages_tb.is_read"=>'1'));
        }

    }


    /**
     * ============================End pusher notification======================================================
     */
    
}    