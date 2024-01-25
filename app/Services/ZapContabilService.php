<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;


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
        
      sleep(1);
      
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


    public function send($key, $number, $message)
    {  
        
      $number = clear($number);

      $ddd = (int) substr($number, 0, 2); 

      if($ddd > 27 and strlen($number) == 11 ){

        $number = $ddd . substr($number,-8); 

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
                CURLOPT_POSTFIELDS => "id=55$number&message=$message",
                  CURLOPT_HTTPHEADER => array(
                    "Content-Type: application/x-www-form-urlencoded", 
                    "Authorization: Bearer $this->api_whatsapp_token"
                  )
          ));

              $response = curl_exec($curl);

             $resp =  json_decode($response);

              if( $resp->error != false){

                    //save_settings(['api_whatsapp_status'=> 0]);
                
              }

                curl_close($curl);


                return $response;


    }



    

    public function sendDigisac($key, $number, $message)
    {  


        $whatsapp = clear($number);

        if(strlen($whatsapp) == 10 || strlen($whatsapp) == 11 ) {


          $token =  tenant('mandeumzap_token_default') ;

          $user = Auth::user(); 

          if(!is_null($user) and !is_null($user->token_mandeumzap) ){

            $token = $user->token_mandeumzap ;

          }

                //setting('mandeumzap_token_default')

              // 'mandeumzap_status' ,
              // 'mandeumzap_url' , 'https://palearicontabilidade.digisac.co/api/v1/messages',
              // 'mandeumzap_service_id' , '61e4bf15-aae8-4080-91c9-773f15176970';  
              //  'mandeumzap_token_default' 'fbae2adea798d71177a613341442b68ad56f3889' 
        


                $curl = curl_init();

                $serviceId = tenant('mandeumzap_service_id');

                $authorization = "Authorization: Bearer $token";
                
                $number = '55' . $whatsapp; // '5541987944067'; 
                
            

                curl_setopt_array($curl, array(
                  CURLOPT_URL =>  tenant('mandeumzap_url'),
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
                
                $response = curl_exec($curl); //dd( $response);
                
                curl_close($curl);

                return $response;
           

        }




    }




} //fecha class
