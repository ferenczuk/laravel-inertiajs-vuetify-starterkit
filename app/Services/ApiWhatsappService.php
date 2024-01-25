<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Http;


class ApiWhatsappService
{

    public $api_whatsapp_url = 'https://whatsapp.contabil.work';

    public $api_whatsapp_token = 'tefd5efetdf3ge%TG9iuh62nud72jduuejjduFRvafRvafqrfFARfvr82';

  

    public function initInstance($key){


        $curl = curl_init();


        curl_setopt_array($curl, array(
          CURLOPT_URL => "$this->api_whatsapp_url/instance/init?key=$key",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_HTTPHEADER => array(
            "Content-Type: application/x-www-form-urlencoded",
            "Authorization: Bearer $this->api_whatsapp_token"
          )
        ));
        
        $response = json_decode(curl_exec($curl)); 
        
        curl_close($curl);


        return $response;




    }



    public function getQrcode($key)
    {
        
      sleep(2);
      
        $curl = curl_init();

          curl_setopt_array($curl, array(
            CURLOPT_URL => "$this->api_whatsapp_url/instance/qrbase64?key=$key",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
              
               "Content-Type: application/x-www-form-urlencoded", 
               "Authorization: Bearer $this->api_whatsapp_token"

            )
          ));
  

          $response = json_decode(curl_exec($curl));
          
          curl_close($curl);


          return $response;
          

    }


    public function status($key){


        $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => "$this->api_whatsapp_url/instance/info?key=$key",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
              "Content-Type: application/x-www-form-urlencoded", 
              "Authorization: Bearer $this->api_whatsapp_token"
            )
            ));

            $response = json_decode(curl_exec($curl)); //dd($response );

            curl_close($curl);

            return $response;

    }

    public function listInstances()
    {

      $response = Http::withToken( $this->api_whatsapp_token )
                          ->get( "$this->api_whatsapp_url/instance/list");

      return $response;


    }

    public function restoreInstances()
    {

      $response = Http::withToken( $this->api_whatsapp_token )
      ->get( "$this->api_whatsapp_url/instance/restore");

      return $response;


    }

    public function deleteInstance($key)
    {
        
        $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => "$this->api_whatsapp_url/instance/delete?key=$key",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_HTTPHEADER => array(
              "Content-Type: application/x-www-form-urlencoded", 
               "Authorization: Bearer $this->api_whatsapp_token"
            )
            ));

        $response = json_decode(curl_exec($curl));

        curl_close($curl);

        return $response;


    }

    public function send($key,$ddi , $number, $message)
    {  

      switch (setting('api_whatsapp')) {
        case 'whatsapp':
           $this->sendWhatsapp($key,$ddi , $number, $message);
            break;
        case 'digisac':
          $this->sendDigisac($key,$ddi , $number, $message);
            break;
        case 'zapcontabil':
          $this->sendZapContabil($key,$ddi , $number, $message);
            break;
    }



    }

    public function sendWhatsapp($key,$ddi , $number, $message)
    {  
        
      $number = clear($number);

      $ddd = (int) substr($number, 0, 2); 

      if($ddd > 27 and strlen($number) == 11 ){

          $number =  $ddd . substr($number,-8); 

      }

        $curl = curl_init();

          curl_setopt_array($curl, array(
                CURLOPT_URL => "$this->api_whatsapp_url/message/text?key=$key",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => "id=$ddi$number&message=$message",
                  CURLOPT_HTTPHEADER => array(
                    "Content-Type: application/x-www-form-urlencoded", 
                    "Authorization: Bearer $this->api_whatsapp_token"
                  )
          ));

              $response = curl_exec($curl);

             $resp =  json_decode($response);

              // if( $resp->error != false){

              //       //save_settings(['api_whatsapp_status'=> 0]);
                
              // }

                curl_close($curl);

                return $response;


 

  }

    


    public function sendDigisac($key, $ddi,  $number, $message)
    {  


        $whatsapp = clear($number);

        if(strlen($whatsapp) == 10 || strlen($whatsapp) == 11 ) {


          $token =  setting('mandeumzap_token_default') ;

          $user = Auth::user(); 

          if(!is_null($user) and !is_null($user->token_mandeumzap) ){

            $token = $user->token_mandeumzap ;

          }


                $curl = curl_init();

                $serviceId = setting('mandeumzap_service_id');

                $authorization = "Authorization: Bearer $token";
                
                $number = $ddi . $whatsapp; // '5541987944067'; 
                
            

                curl_setopt_array($curl, array(
                  CURLOPT_URL =>  setting('mandeumzap_url'),
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_ENCODING => '',
                  CURLOPT_MAXREDIRS => 10,
                  CURLOPT_TIMEOUT => 0,
                  CURLOPT_FOLLOWLOCATION => true,
                  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  CURLOPT_CUSTOMREQUEST => 'POST',
                  CURLOPT_POSTFIELDS => "number=$number&serviceId=$serviceId&text=$message&dontOpenTicket=true&origin=bot",
                  CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/x-www-form-urlencoded' , $authorization
                  ),
                ));
                
                $response = curl_exec($curl); 
                
                //dd( $response);
                
                curl_close($curl);

                return $response;
           

        }

      }


    public function sendZapContabil($key, $ddi , $number, $message)
    {  


        $whatsapp = clear($number);

        if(strlen($whatsapp) == 10 || strlen($whatsapp) == 11 ) {


                $number = $ddi . $whatsapp; // '5541987944067'; 

                $data = [
                  "body" => $message,
                  "connectionFrom" =>  (int)setting('zapcontabil_connection_id')
                ];
        

                $response = Http::withToken( setting('zapcontabil_token') )
                ->post(setting('zapcontabil_url') .  $number ,$data);


                return $response;
           

        }




    }





} //fecha class
