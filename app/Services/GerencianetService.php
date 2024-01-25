<?php

namespace App\Services;

use Illuminate\Http\Request;

use Gerencianet\Exception\GerencianetException;
use Gerencianet\Gerencianet;

use App\Models\Tenant\User;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\Company;


use Illuminate\Support\Facades\Notification;
use App\Notifications\CreateInvoiceNotification;

class GerencianetService 
{
   

    public $errors = [

        '/payment/banking_billet/customer/phone_number' => 'Verifique o Número de Telefone no cadastro da Empresa',
        '/payment/banking_billet/customer/cpf' => 'Verifique o CNPJ ou CPF no Cadastro da Empresa',
        '/payment/banking_billet/customer/juridical_person/cnpj' => 'Verifique o CNPJ ou CPF no Cadastro da Empresa',
        '/payment/banking_billet/customer/juridical_person/corporate_name' => 'Verifique o Razão Social no cadastro da Empresa',
        '/payment/banking_billet/customer/name' => 'Verifique o Nome Completo no cadastro da Empresa',
        'expire_at' => 'Verifique a Data de Vencimento da Fatutra',
        '43500034' => 'Verifique o Valor da Fatura',

    ];

    





    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function init($id)
    {
        $invoice = Invoice::FindOrFail($id);


        if( is_null($invoice->invoice_url) and $invoice->status == 10 ) {

            return $this->payCharge($id);

        }  else{

            return $invoice->invoice_url;

        }

      return $this->payCharge($id);

    }


public function credentials($args)
{  
        $invoice_pay_method = json_decode(setting('invoice_pay_method'), true);
        $credentials =  $invoice_pay_method['gerencianet'] ;


        if($args == 'options'){

            if(!$credentials['client_id']){

                $default = [
                    'client_id' => 'Client_Id_40bc620c0582afacb76a25dbf045d385b6cdd015',
                    'client_secret' => 'Client_Secret_cbebc9a795bec91e360e2e41351318cdda4d2b56',
                    'sandbox' => true,
                        ];

            return  $default ;

            }else{

                $options = [
                    'client_id' => $credentials['client_id'],
                    'client_secret' => $credentials['client_secret'],
                    'sandbox' => isset($credentials['sandbox']) ? 1 : 0,
                    'partner_token' => '859866c7c03292835fa0def317907f4bbe65d0fc'
                ];

            return  $options ;

            }


                    
        }

                if($args == 'configurations'){

                        $configurations = [
                            'fine' => intval(clear($credentials['multa'])),
                            'interest' => intval(clear($credentials['juros'])) * 10,
                        ];

                    return  $configurations ;
                    
                }


}



    public function dateCheck($id)
    {

        $invoice = Invoice::FindOrFail($id);

       

        if(strtotime(date('Y-m-d')) > strtotime($invoice->due_date)){

            
            $data_method = json_decode(setting('invoice_pay_method'));
            
            $fine = ( (int)  clear($data_method->gerencianet->multa) / 100 ) *  $invoice->amount / 100;

            $interest = ( (int) clear($data_method->gerencianet->juros) / 100 ) * $invoice->amount / 100;

            $interval = strtotime('today') - strtotime($invoice->due_date);
        
            $days = floor($interval / (60 * 60 * 24));

            $days > 1 ? $s = 's' : $s = '';

            $interest_total = $interest * $days ;

            $invoice->due_date = date('Y-m-d');

            $items = json_decode( $invoice->items , true);

                $items[] = [

                            "name" => "Multa por Atraso: " . $data_method->gerencianet->multa .'%',
                            "amount" => "1",
                            "value" => (int) $fine
                            
                            ];

                $items[] = [

                            "name" => "Juros Diários: " . $data_method->gerencianet->juros .'% - ' . $days . " Dia$s de Atraso" ,
                            "amount" => "1",
                            "value" => (int) $interest_total
                    
                    ];

            $invoice->items = json_encode( $items );

            $invoice->amount =  $invoice->amount + (int) $fine + (int) $interest_total  ;

        }

        

       //dd($items );

        return $invoice;

    }



    

    public function payCharge($id)
    {

        $invoice = $this->dateCheck($id);
        
        $company = Company::FindOrFail($invoice->company_id);

        
       
        $options = $this->credentials('options');

          $itens = json_decode($invoice->items, true);

          $items = [];

            foreach($itens as $item){

                $items[] = [

                    'name'=>$item['name'],
                    'amount'=>1,
                    'value'=>intval(clear($item['value']))
        
                ];
                
            }


        $metadata = [
            'custom_id' => strval($id),
            'notification_url'=> tenant('principal_domain') . '/return/gerencianet',
        ];


        $body  =  [
            'items' =>  $items,
            'metadata' => $metadata
        ];




            try {
                $api = new \Gerencianet\Gerencianet($options);
                $charge = $api->createCharge([], $body);

              
                // echo '<pre>';
                        
                // print_r($charge);

                //  echo '</pre>';

                //  echo '<hr>';

                $params = [
                    'id' => $charge['data']['charge_id'],
                  ];


                  $corporate_number = clear($company->corporate_number);

                  if (strlen($corporate_number) >= 14) {
      
                      $juridical_data = [
                          'corporate_name' => $company->corporate_name,
                          'cnpj' => $corporate_number,
                      ];
      
                            $customer = [
                                'phone_number' =>clear($company->tel_principal),
                                'juridical_person' => $juridical_data,
                            ];

                  } else {

                            $customer = [
                                'name' => $company->corporate_name,
                                'cpf' => $corporate_number,
                                'phone_number' => clear( $company->tel_principal),
                            ];
                  }



      
                  $configurations = $this->credentials('configurations');

                  
      
                  $bankingBillet = [

                      'expire_at' => $invoice->due_date,
                      'customer' => $customer,
                      'configurations' => $configurations,

                    

                        //'message'=> 'teste mensagem',
                    
                  
                  ];

                  if($invoice->conditional_discount_value > 0){

                        $bankingBillet['conditional_discount'] = [

                            'type'=> $invoice->conditional_discount,
                            'value'=> $invoice->conditional_discount_value , 
                            'until_date' => $invoice->due_date

                        ];

                  }

                  
               
      
                 
      
                  $payment = ['banking_billet' => $bankingBillet];
      
                  $body = ['payment' => $payment];



                try {
                  
                    $pay_charge = $api->payCharge($params, $body);
                    
                        // echo '<pre>';
                        
                        //     print_r($pay_charge);

                        // echo '</pre>'  ;


                        $invoice->fill([
                            'charge' => $pay_charge['data']['charge_id'] ,
                            'invoice_url' => $pay_charge['data']['billet_link'] 
                            ]);

                        $invoice->save();

                           
                        Notification::send($company->users, (new CreateInvoiceNotification($invoice)));

                          
                        return $pay_charge['data']['billet_link'];

                ////////////   catch  Pay charge 
                } catch (GerencianetException $e) {
                   
                    
                    echo "<b>Erro na Geração do Boleto - ( $e->code ) </b><hr>"; 

                                       

                    if( isset($e->errorDescription['property']) and isset( $this->errors[$e->errorDescription['property']] ) ){

                        echo  '<h2 style="color:red;">' . $this->errors[$e->errorDescription['property']] .'</h2>';
                        echo '<p>' . $e->errorDescription['message'] . '</p>';

                    }else{

                        echo  '<h2 style="color:red;">Erro de Cadastro</h2>';
                        echo '</pre>';    print_r($e->errorDescription) ;   echo '</pre>';

                   
                    }

                    echo '<hr>';


                } catch (Exception $e) {

                   echo '<pre>'; print_r($e->getMessage());  echo '</pre>';

                }



            ////////////   catch charge       ///////////////////////////////////
            } catch (GerencianetException $e) {

               
                echo '<h4>Erro na Transação</h4><hr>';
                
                
                if(isset($this->errors[$e->code])){

                    echo  '<h4 style="color:red;">' . $this->errors[$e->code] .'</h4>';

                }else{

                    echo  '<h2 style="color:red;">Erro de Cadastro</h2>';
                    echo '</pre>';    print_r($e->errorDescription) ;   echo '</pre>';

                }



            } catch (Exception $e) {
                
                echo '<pre>'; print_r($e->getMessage());  echo '</pre>';

            }




    }





    public function deleteCharge($charge)
    {

        $options = $this->credentials('options');

        $params = [
            'id' => $charge 
          ];
           
          try {
              $api = new Gerencianet($options);
           
              $api->cancelCharge($params, []);

     
              return true ;
           
          ////////////   catch charge       ///////////////////////////////////
        } catch (GerencianetException $e) {

            echo '<h4>Erro na Transação</h4><hr>'; 
            echo '<pre>';  print_r($e->code) ;  echo '</pre>';

            echo '<pre>';  print_r($e->error)  ; echo '</pre>';

            echo '</pre>';    print_r($e->errorDescription) ;   echo '</pre>';

        } catch (Exception $e) {
            
            echo '<pre>'; print_r($e->getMessage());  echo '</pre>';

        }




    }



    public function settleCharge($charge)
    {

        $options = $this->credentials('options');

        $params = [
            'id' => $charge 
          ];
           
          try {
              $api = new Gerencianet($options);
              $charge = $api->settleCharge($params, []);

              return true ;
           
          ////////////   catch charge       ///////////////////////////////////
        } catch (GerencianetException $e) {

            echo '<h4>Erro na Transação</h4><hr>'; 
            echo '<pre>';  print_r($e->code) ;  echo '</pre>';

            echo '<pre>';  print_r($e->error)  ; echo '</pre>';

            echo '</pre>';    print_r($e->errorDescription) ;   echo '</pre>';

        } catch (Exception $e) {
            
            echo '<pre>'; print_r($e->getMessage());  echo '</pre>';

        }




    }



    public function return(Invoice $invoice, Request $request)
    {

        $options = $this->credentials('options');


        $params = [
          'token' =>$_POST["notification"]
        ];
        
        try {
            $api = new Gerencianet($options);
            $notification = $api->getNotification($params, []);
           
            
                $invoice = Invoice::find($notification['data'][0]['custom_id']);
           

                $data = [];

                $data['returns'] = json_encode($notification);

           
             echo 'Sucesso';
            // print_r($notification) . '<br><br><br><br>';
            // echo '</pre>';

           // echo $notification['data'][0]['custom_id'] ;
        

        
                foreach($notification['data'] as $status) {

                    if($status['status']['current'] =='paid') {
            
                    $data['status'] = 40;

                    $data['pay_date'] = date('Y-m-d');
            
            
                    }
        
                 }
                 
                 $invoice->fill($data);
                
                 $invoice->save(); 
        

            } catch (GerencianetException $e) {

                echo '<h4>Erro na Transação</h4><hr>'; 
                echo '<pre>';  print_r($e->code) ;  echo '</pre>';
    
                echo '<pre>';  print_r($e->error)  ; echo '</pre>';
    
                echo '</pre>';    print_r($e->errorDescription) ;   echo '</pre>';
    
            } catch (Exception $e) {
                
                echo '<pre>'; print_r($e->getMessage());  echo '</pre>';
    
            }




    }

    

    public function updateBillet($charge_id , $new_date)
    {

        $options = $this->credentials('options');

        // $charge_id refere-se ao ID da transação gerada anteriormente
            $params = [
                'id' => $charge_id
            ];
            
            $body = [
                'expire_at' => $new_date
            ];

          
            
            try {
                $api = new Gerencianet($options);
                $charge = $api->updateBillet($params, $body);

               //dd($charge);

            } catch (GerencianetException $e) {
               
                
                echo '<h4>Erro na Transação</h4><hr>'; 
                echo '<pre>';  print_r($e->code) ;  echo '</pre>';
                echo '<pre>';  print_r($e->error)  ; echo '</pre>';
                echo '</pre>';    print_r($e->errorDescription) ;   echo '</pre>';

                die();

            } catch (Exception $e) {
                
                echo '<pre>'; print_r($e->getMessage());  echo '</pre>';
                die();
            }

        


    }









    public function checkout()
    {

        $options = $this->credentials('options');

        $invoices = Invoice::where('status', '!=', 40)
                           ->where('method' , 1)
                           ->where('charge' ,'>', 1)
                           ->orderBy('id', 'desc')
                           ->get();

                       
           
        echo '<ol>';

        foreach($invoices as $invoice) {

            echo '<li>';

            echo '<b>' . $invoice->id . ' | ' . $invoice->title . ' | ' . money($invoice->amount) . ' | Venc.: ' . date("d/m/Y" , strtotime($invoice->due_date) ) . ' | Charge ID: ' . $invoice->charge .  '</b><br>';


            $params = [
              'id' => $invoice->charge // $charge_id refere-se ao ID da transação ("charge_id")
            ];
            
            try {
                $api = new Gerencianet($options);
                $charge = $api->detailCharge($params, []);
                
                if($charge['data']['status'] == 'paid') {
                
                    
                $data = array_reverse($charge['data']['history']) ;
                
                $data_ok = date("Y-m-d", strtotime($data[0]['created_at']));
            
            
             Invoice::where('id', $invoice->id)->update(['status'=>40 , 'pay_date'=>$data_ok]);
                
                echo '<h4>Fatura Paga em ' . $data_ok . '</h4><hr>';
                
             } else {
                 
                echo 'Status: ' . $charge['data']['status'] . '<hr>';
                 
             }
                
            } catch (GerencianetException $e) {
                print_r($e->code);
                print_r($e->error);
                print_r($e->errorDescription);
            } catch (Exception $e) {
                print_r($e->getMessage());
            }

            echo '</li>';


        }

        echo '</ol>';


    }











}
