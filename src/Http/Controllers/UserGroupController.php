<?php

namespace Classiebit\Addchat\Http\Controllers;


use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Crypt;

use App\Http\Controllers\Controller; 

use Illuminate\Http\Request;
use Facades\Classiebit\Addchat\Addchat;
use Auth;

use Validator;
use Classiebit\Addchat\Http\Controllers\AddchatController;

class UserGroupController extends AddchatController
{
    public function __construct()
    {
	    Parent::__construct();
    }
    
    

}