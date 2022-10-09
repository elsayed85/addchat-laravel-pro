<?php


use Classiebit\Addchat\Facades\Addchat;

/*
|--------------------------------------------------------------------------
| Package Routes
|--------------------------------------------------------------------------
|
*/

$namespace = '\Classiebit\Addchat\Http\Controllers';
Route::group([
    'namespace' => $namespace,
    'as'    => 'addchat.'
], function() use($namespace) {

    // API ROUTES
    Route::prefix('addchat_api')->group(function () use($namespace) {
        
        // auth end point for events
        Route::post('get_lang', function() use($namespace) {

            // default lang
            $lang = config('app.locale');
            // user lang
            if(!empty($_POST['lang']))
            {
                $lang = $_POST['lang'];    
                \App::setLocale($lang);
            }
            $data = [
                'lang' => Lang::get('addchat::ac'),
            ];
            return response()->json($data);
        });


        //=============AddchatController Routes============

        $controller = $namespace."\AddchatController";
        
        //get configurations
        Route::post('get_config', "$controller@get_config");
        
        // check session
        Route::post('check_session', "$controller@check_session");

        /**
         *  Admin function
         */

        // check admin 
        Route::post('check_admin/{is_return?}', "$controller@check_admin");

        //=======End===================


        /**
         *===================  PusherController Routes=============================================== 
         */
        
        
        ///==================user pusher notification===============
        
        $controller = $namespace."\PusherController";
        
        // auth end point for events
        Route::post('auth', "$controller@auth");

        // message send notification 
        Route::post('message_notification', "$controller@message_notification");

        // is_typing 
        Route::post('is_typing', "$controller@is_typing");

        // remove notification
        Route::post('is_read', "$controller@is_read");

        
        ///==================guest pusher notification===============
        
        
        // message send notification for guest
        Route::post('guest_message_notification', "$controller@guest_message_notification");

        //is_typing for guest
        Route::post('is_typing_guest', "$controller@is_typing_guest");


        // remove notification
        Route::post('is_read_guest', "$controller@is_read_guest");

        //=======End===================



        /**
         * ================UserNotificationController Routes==================================
         */
        
        $controller = $namespace."\UserNotificationController";

        //get updates
        Route::post('get_updates', "$controller@get_updates");
        
        // remove notification
        Route::post('remove_notifications', "$controller@remove_notifications");

        //=======End===================


        /**
         * ================GuestNotificationController Routes==================================
         */
        
        $controller = $namespace."\GuestNotificationController";

        // get guest updates
        Route::post('get_guest_updates', "$controller@get_guest_updates"); 

        // remove notification
        Route::post('remove_guest_notifications', "$controller@remove_guest_notifications");

        //=======End===================

        /**
         * ================= ProfileController Routes=========================
         */
        
        $controller = $namespace."\ProfileController";


        // size change
        Route::post('size_change', "$controller@size_change");

        // dark mode
        Route::post('dark_mode_change', "$controller@dark_mode_change");

        // profile update
        Route::post('profile_update', "$controller@profile_update");
        
        //=============End==================================


        /**
         * =======MessageController Routes===============
         */
        
        $controller = $namespace."\MessageController";

        // message delete for logged in user
        Route::post('message_delete/{message_id}', "$controller@message_delete");

        // delete chat history
        Route::post('delete_chat/{user_id?}', "$controller@delete_chat");

        // get messages
        Route::post('get_messages/{buddy_id?}/{offset}', "$controller@get_messages");

        // send message
        Route::post('send_message', "$controller@send_message");

        // message unsend
        Route::post('message_unsend/{message_id?}', "$controller@message_unsend");

        // get_latest_message
        Route::post('get_latest_message/{buddy_id?}', "$controller@get_latest_message");

        /**
         *  Admin functions routes
         */
        
        // a_chat_between
        Route::post('a_chat_between/{offset?}', "$controller@a_chat_between");

        // a_get_conversations
        Route::post('a_get_conversations/{m_from?}/{m_to?}/{offset?}', "$controller@a_get_conversations");


        /**
         *  Guest functions routes
         */

        // get messages for guest when guest not login
        Route::post('get_messages_of_guest', "$controller@get_messages_of_guest"); 

        // guest send message
        Route::post('guest_send_message', "$controller@guest_send_message"); 

        // get latest messages for login guest user    
        Route::post('get_guest_latest_message/{guest_id}', "$controller@get_guest_latest_message"); 

        // get latest messages for guest user without login
        Route::post('get_guest_latest_message1', "$controller@get_guest_latest_message1"); 

        // get guests messages for login guest user
        Route::post('get_guest_messages/{guest_id}/{offset}', "$controller@get_guest_messages"); 

        //login guest user can send messages without login guest
        Route::post('send_to_guest', "$controller@send_to_guest"); 

        //check logged user have guest account or not when user logged in
        Route::post('check_guest_account', "$controller@check_guest_account");

        // chat between guest users 
        Route::post('a_chat_between_guest/{offset}', "$controller@a_chat_between_guest");

        // a_get_guest_conversations
        Route::post('a_get_guest_conversations', "$controller@a_get_guest_conversations");
        
        //==========End=================

        /**
         *  ==============ContactController Routes==========================
         */
        
        $controller = $namespace."\ContactController";
        
        // remove contacts
        Route::post('remove_contacts/{user_id?}', "$controller@remove_contacts");

        // add contacts
        Route::post('add_contacts/{user_id?}', "$controller@add_contacts");

        //===========================End=============

        /**
         *  ==============BlockUserController Routes==========================
         */
        
        $controller = $namespace."\BlockUserController";

        // block to user
        Route::post('block_user/{user_id?}/{is_report?}', "$controller@block_user");

        /**
         *  ADMIN FUNCTION
         */

        // get all block user for admin
        Route::post('get_blocked_users/{offset?}', "$controller@get_blocked_users");

        //===========================End=============

        
        /**
         *  ==============UserController Routes==========================
         */
        
        $controller = $namespace."\UserController";

        // get users
        Route::post('get_users/{offset?}/{users_id?}/{flag?}', "$controller@get_users");

        // get profile
        Route::post('get_profile', "$controller@get_profile");

        // get buddy
        Route::post('get_buddy', "$controller@get_buddy");

        // get groupschat_users
        Route::post('get_groupschat_users/{group_id?}/{offset?}', "$controller@get_groupschat_users");

        //===========End=======================


        /**
         * ==========GroupController Routes================================
         */
        
        $controller = $namespace."\GroupController";
         
        // get groups
        Route::post('get_groups', "$controller@get_groups");

        /**
         *  ADMIN FUNCTION
         */

        // a_get_groups
        Route::post('a_get_groups', "$controller@a_get_groups");

        //===========End===================================

        
        /**
         * ==========GroupChatController Routes================================
         */

        $controller = $namespace."\GroupChatController";
        
        // save_groupchat
        /**
         *  ADMIN FUNCTION
         */

        // save groupchat 
        Route::post('save_groupchat', "$controller@save_groupchat");

        //=========End===========================

        
        /**
         * ================================= GuestController Routes===========================================
         */
        
        $controller = $namespace."\GuestController";

        // guest login with cookie 
        Route::post('guest_login', "$controller@guest_login");

        // check guest exist or not into guest table
        Route::post('get_guest_user', "$controller@get_guest_user"); 


        // get guests user for login user if have guest group
        Route::post('get_guests/{offset}', "$controller@get_guests"); 

        // get guests user for login user if have guest group
        Route::post('get_guest_buddy', "$controller@get_guest_buddy"); 
        
        //=========End===========================

    });
    


});