<?php

namespace App\Services;

use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class TokenService 
{
   
    public function login(Request $request)
    {   

       // dd($request->all()); ///base64url_decode

        $code = explode("|||" , base64url_decode($request->a)); 

       // dd($code);

        $user = User::where('token', $code[0])->first(); 

        if($user){

            $auth =  Auth::loginUsingId($user->id, true);

        }else{

            abort(403, 'Ação Não Autorizada! Este Link Expirou');

        }

           

           

          //dd($code,$user->token ,$auth );

        return redirect($code[1]);


    }
   









}
