<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

use App\Models\Tenant\Invoice;
use App\Models\Tenant\Company;

use Illuminate\Support\Facades\Notification;
use App\Notifications\CreateInvoiceNotification;


use ctodobom\APInterPHP\BancoInter;
use ctodobom\APInterPHP\TokenRequest;
use ctodobom\APInterPHP\BancoInterException;
use ctodobom\APInterPHP\Cobranca\Boleto;
use ctodobom\APInterPHP\Cobranca\Mensagem;
use ctodobom\APInterPHP\Cobranca\Pagador;
use ctodobom\APInterPHP\Cobranca\Multa;
use ctodobom\APInterPHP\Cobranca\Mora;
use ctodobom\APInterPHP\Cobranca\Desconto;


class BancoInterService
{
    public $cnpj ;
 
    public function init($id)
    {
        $invoice = Invoice::FindOrFail($id);

       

        if( is_null($invoice->invoice_url) and $invoice->status == 10){

            $charge = $this->payCharge($id);
            $invoice->invoice_url = $this->getBoleto($charge, $id);

        }

        
        return $invoice->invoice_url;


    }


    public function credentials()
    {  

        $invoice_pay_method = json_decode(setting('invoice_pay_method'), true);

        $credentials =  $invoice_pay_method['banco_inter'] ;
  

        $path = 'banco-inter/' . tenant('id') . '/' ;

        $this->cnpj = clear($credentials['cnpj']);

        $conta = $credentials['conta'];
       
        $certificado = storage_path( $path . 'API_Certificado.crt' );
        $chavePrivada = storage_path( $path . 'API_Chave.key' );

        try{

            $token = new TokenRequest( $credentials['client_id'], $credentials['client_secret'] ,'boleto-cobranca.read boleto-cobranca.write');
        
        }catch(BancoInterException $e){


            die('Erro no Token');


        }
 
      try{

        $banco = new BancoInter($conta, $certificado, $chavePrivada, $token,[]);

 

             // define callback para salvar token emitido
                $banco->setTokenNewCallback(function(string $tokenJson) {

                    $path = 'banco-inter/' . tenant('id') . '/' ;

                    if ($tokenFile = fopen( storage_path( $path . 'inter-oauth-token.txt'),'w+') ) {
                        fwrite($tokenFile, $tokenJson);
                        fclose($tokenFile);
                    }

                  
                });

                // define callback para obter token do cache
                $banco->setTokenLoadCallback(function() {
                    $path = 'banco-inter/' . tenant('id') . '/' ;
                    $oAuthTokenData = null;
                    // uso do @ para evitar o warning se o arquivo não existe
                    if (($tokenFile = @fopen(storage_path( $path . 'inter-oauth-token.txt'),'r')) !== false) {
                        // se tiver arquivo com token, carrega ele e retorna
                        $tokenJson = fread($tokenFile, 8192);
                        $oAuthTokenData = json_decode($tokenJson, true);
                        fclose($tokenFile);
                        return $oAuthTokenData;
                    } else {
                        // retorno "falso" força a emissão de novo token
                        return false;
                    }
                });



        return $banco;

    }catch(BancoInterException $e){


        die('Erro nas Credenciais');


    }

    }



    
    public function dateCheck($id)
    {
    
        $invoice = Invoice::FindOrFail($id);
    
       
    
        if(strtotime(date('Y-m-d')) > strtotime($invoice->due_date)){
    
            
            $data_method = json_decode(setting('invoice_pay_method'));
            
            $fine = ( (int)  clear($data_method->banco_inter->multa) / 100 ) *  $invoice->amount / 100;
    
            $interest = ( (int) clear($data_method->banco_inter->juros) /100 ) * $invoice->amount / 100;
    
            $interval = strtotime('today') - strtotime($invoice->due_date);
        
            $days = floor($interval / (60 * 60 * 24));
    
            $days > 1 ? $s = 's' : $s = '';
    
            $interest_total = $interest * $days ;
    
            $invoice->due_date = date('Y-m-d');
    
            $items = json_decode( $invoice->items , true);
    
                $items[] = [
    
                            "name" => "Multa por Atraso: " . $data_method->banco_inter->multa .'%',
                            "amount" => "1",
                            "value" => number_format($fine  / 100, 2, ',', '.')
                            
                            ];
    
                $items[] = [
    
                            "name" => "Juros Diários: " . $data_method->banco_inter->juros .'% - ' . $days . " Dia$s de Atraso" ,
                            "amount" => "1",
                            "value" =>  number_format($interest_total  / 100, 2, ',', '.')
                    
                    ];


                    
    
            $invoice->items = json_encode( $items );
    
            $invoice->amount =  $invoice->amount + (int) $fine + (int) $interest_total  ;
    
        }
    
        
        //dd($items );
     
    
        return $invoice;
    
    }


    public function payCharge($id)
    {  

        $banco = $this->credentials();


        $invoice = $this->dateCheck($id);

        

        $invoice->save();

        $company = Company::FindOrFail($invoice->company_id);


        try {

        $pagador = new Pagador();

        $corporate_number = clear($company->corporate_number); 

        if (strlen($corporate_number) >= 14) {

            $pagador->setTipoPessoa(Pagador::PESSOA_JURIDICA);

        }else{

            $pagador->setTipoPessoa(Pagador::PESSOA_FISICA);

        }

        is_null( $company->numero) ? $company->numero = 's/n' : $company->numero = $company->numero ;

        if(!is_null($company->cep) and !is_null($company->endereco) and !is_null($company->bairro) and !is_null($company->cidade) and !is_null($company->uf)) {

       

        $pagador->setNome($company->corporate_name);
        $pagador->setEndereco($company->endereco);
        $pagador->setNumero($company->numero);
        $pagador->setBairro($company->bairro);
        $pagador->setCidade($company->cidade);
        $pagador->setCep(clear($company->cep));
        $pagador->setCnpjCpf($corporate_number);
        $pagador->setUf($company->uf);


    

        /// Mesagens
        $mensagem = new Mensagem();

            $item = json_decode($invoice->items, true); //dd($item);

               // $mensagem->setLinha1( Str::limit($invoice->title, 75, '...' ) );



                isset($item[0]['name']) ? $setLinha2 = Str::limit($item[0]['name'] , 70, '...')  : $setLinha2 = NULL; 
                    $mensagem->setLinha1($setLinha2);

               isset($item[1]['name']) ? $setLinha3 = Str::limit($item[1]['name'] , 70, '...') : $setLinha3 = NULL; 
                    $mensagem->setLinha2($setLinha3);

                isset($item[2]['name']) ? $setLinha4 = Str::limit($item[2]['name'] , 70, '...') : $setLinha4 = NULL; 
                    $mensagem->setLinha3($setLinha4);

                isset($item[3]['name']) ? $setLinha5 = Str::limit($item[3]['name'] , 70,'...') : $setLinha5 = NULL; 
                    $mensagem->setLinha4($setLinha5);
              
                    

        /// Multa
        $invoice_pay_method = json_decode(setting('invoice_pay_method'), true);
        $credentials =  $invoice_pay_method['banco_inter'] ;

        //dd($credentials);

        $multa = new Multa();

        $boleto = new Boleto();

        $dataMulta = date('Y-m-d', strtotime("+1 day", strtotime($invoice->due_date)));

        $taxaMulta = (int) $credentials['multa']  ;
        $taxaMora = (int) $credentials['juros'];

        //dd( $taxaMulta, $taxaMora );

        $multa->setCodigoMulta('PERCENTUAL');
        $multa->setData($dataMulta);
        $multa->setTaxa($taxaMulta);
      
        $mora = new Mora();

        $taxaMora = (int) clear($credentials['juros']);

        $mora->setCodigoMora('TAXAMENSAL');
        $mora->setData($dataMulta);
        $mora->setTaxa($taxaMora);


        if($invoice->conditional_discount_value > 0){

            $desconto = new Desconto();

            if($invoice->conditional_discount == 'percentage' ){

                $desconto->setCodigoDesconto('PERCENTUALDATAINFORMADA');
                $desconto->setTaxa(clear($invoice->conditional_discount_value) / 100);

            }else{

                $desconto->setCodigoDesconto('VALORFIXODATAINFORMADA');
                $desconto->setValor(clear($invoice->conditional_discount_value) / 100);

            }
           

            $desconto->setData($invoice->due_date);
        
            $boleto->setDesconto1($desconto);

      }
        

        /// Gerar Boleto

           
            $boleto->setCnpjCPFBeneficiario($this->cnpj);
            $boleto->setPagador($pagador);
            $boleto->setMensagem($mensagem);
            $boleto->setMulta($multa);
            $boleto->setMora($mora);
           
            $boleto->setSeuNumero($invoice->id);
            $boleto->setDataEmissao(date('Y-m-d'));
            $boleto->setValorNominal(number_format($invoice->amount/100, 2, '.', ''));
            $boleto->setDataVencimento($invoice->due_date);

            
        
           
         
           // dd($boleto);
      
                $banco->createBoleto($boleto);


               $invoice->fill(['charge'=> $boleto->getNossoNumero()]);

               $invoice->save();

             $this->getBoleto( $invoice->charge, $invoice->id);

         
             Notification::send($company->users, (new CreateInvoiceNotification($invoice)));

            
               return $boleto->getNossoNumero();


            }else{

                return NULL;
            }

            } catch ( BancoInterException $e ) {
                echo "\n\n".$e->getMessage();
                echo '<hr>';
                echo "\n\nCabeçalhos: \n";
                echo $e->reply->header;
                echo '<hr>';
                echo "\n\nConteúdo: \n";
                echo $e->reply->body;
            }

    }


    public function getBoleto($charge, $id)
    {  

        $invoice = Invoice::FindOrFail($id);

        $company = Company::FindOrFail($invoice->company_id);

        $banco = $this->credentials();

        try {

          
            $path = storage_path('banco-inter/' . tenant('id') . '/boletos/'); //dd($path );
            
            $boleto = $banco->getPdfBoleto($charge, $path);

  
           
            $invoice->fill(['token'=>$boleto ,'invoice_url'=> config('app.url') . "/invoices/$id/banco-inter"]);
            $invoice->save();

            ///////////   notifica    ///////////////////////////////////////////////

           // Notification::send($company->users, (new CreatedInvoiceNotification($invoice)));


            return $invoice->invoice_url;




        } catch ( BancoInterException $e ) {

            echo "\n\n".$e->getMessage();
            echo "\n\nCabeçalhos: \n";
            echo $e->reply->header;
            echo "\n\nConteúdo: \n";
            echo $e->reply->body;

        }


    }


    public function settleCharge($charge)
    {

        if($charge > 0){


                $banco = $this->credentials();

                try {
                    echo "\nBaixando boleto\n";
                    $tt = $banco->baixaBoleto($charge, 'PAGODIRETOAOCLIENTE'); 
                    echo "Boleto Baixado";
                } catch ( BancoInterException $e ) {
                    echo "\n\n".$e->getMessage();
                    echo "\n\nCabeçalhos: \n";
                    echo $e->reply->header;
                    echo "\n\nConteúdo: \n";
                    echo $e->reply->body;
                    echo "\n\n".$e->getTraceAsString();
                }


        }


    }


    public function deleteCharge($charge)
    {

        if($charge > 0){


                $banco = $this->credentials();

                try {
                    echo "\nBaixando boleto\n";
                    $tt = $banco->baixaBoleto($charge, INTER_BAIXA_ACERTOS); 
                    echo "Boleto Baixado";
                } catch ( BancoInterException $e ) {
                    echo "\n\n".$e->getMessage();
                    echo "\n\nCabeçalhos: \n";
                    echo $e->reply->header;
                    echo "\n\nConteúdo: \n";
                    echo $e->reply->body;
                }

        }


    }

    
    public function list()
    {
        $banco = $this->credentials();

        try {
           
            $listaBoletos = $banco->listaBoletos('2021-08-01', '2021-10-01', 0 , 10);

            //echo "<pre>";
               // var_dump($listaBoletos->content);
            //echo "</pre>";

            foreach($listaBoletos->content as $boleto){

               
                echo $dataHoraSituacao;

                echo "<hr>";
            }


        } catch ( BancoInterException $e ) {
            echo "\n\n".$e->getMessage();
            echo "\n\nCabeçalhos: \n";
            echo $e->reply->header;
            echo "\n\nConteúdo: \n";
            echo $e->reply->body;
        }

    }

 
    public function checkout()
    {
        $banco = $this->credentials();


        $date_start = date('Y-m-d' , strtotime('-5 days')); //dd($date_start);

        $date_final = date('Y-m-d' );
      
        $boletos = $banco->listaBoletos($date_start , $date_final  . "&filtrarDataPor=SITUACAO", 0, 999,"PAGO","DATASITUACAO", true);

 
       // dd($boletos);

        $invoices = Invoice::whereIn('status', [10, 20, 99, 100])
                            ->where('method' , 2)
                            ->where('charge' ,'>', 1)
                            ->get();

     echo '<h3>Nº de Faturas: ' . count($invoices) . ' em Aberto (Pendentes ou Atrasadas )</h3>';              
        echo '<ol>';
        

        foreach($boletos->content as $boleto) {

            echo '<li>';
            

            if($boleto->situacao == 'PAGO') {
                
                echo ' Boleto: ' . $boleto->seuNumero . ' | ' . $boleto->situacao . ' em ' . $boleto->dataHoraSituacao ;

                  $invoice = $invoices->find( $boleto->seuNumero );

                  if(!is_null($invoice)){


                    echo ' | ' . $invoice->title ;

                    $valorTotalRecebimento = (int)clear(number_format($boleto->valorTotalRecebimento, 2, '.', ''));
                    $invoice->update(['status'=>40 , 'amount'=> $valorTotalRecebimento, 'pay_date'=>$boleto->dataHoraSituacao]);
                   
                    echo '<b style="color:green;"> >>>>> Fatura Paga em ' . $boleto->dataHoraSituacao . ' | Valor Pago: ' . money($valorTotalRecebimento) . ' <<<<<</b>';
              

                    echo '<hr>';
                  }

                 
               
                }


        

            
            echo '</li>';


        }

        echo '</ol>';



    }









} //fecha class
