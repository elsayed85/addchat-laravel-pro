<?php

namespace Classiebit\Addchat\Models;

use Illuminate\Database\Eloquent\Model;


class AddchatModel extends Model
{
    public function __construct()
    {
        // Addchat tables globals
        $this->ac_profiles_tb               = 'ac_profiles';
        $this->ac_messages_tb               = 'ac_messages';
        $this->ac_users_messages_tb         = 'ac_users_messages';
        $this->ac_blocked_tb                = 'ac_blocked';
        $this->ac_contacts_tb               = 'ac_contacts';
        $this->ac_groupchat_tb              = 'ac_groupchat';
        $this->ac_guests_tb                 = 'ac_guests';
        $this->ac_guests_messages_tb        = 'ac_guests_messages';

        // External tables according to the names in config file
        $external_tables                    = (object) config('addchat');

        // users table
        $this->users_tb                     = $external_tables->users_table;
        $this->users_tb_id                  = $external_tables->users_col_id;
        $this->users_tb_email               = $external_tables->users_col_email;
            
        // groups table
        $this->groups_tb                    = $external_tables->groups_table;
        $this->groups_tb_id                 = $external_tables->groups_col_id;
        $this->groups_tb_name               = $external_tables->groups_col_name;
            
        // users_groups pivot (bridge) table
        $this->ug_tb                        = $external_tables->ug_table;
        $this->ug_tb_user_id                = $external_tables->ug_col_user_id;
        $this->ug_tb_group_id               = $external_tables->ug_col_group_id;
    }

}
