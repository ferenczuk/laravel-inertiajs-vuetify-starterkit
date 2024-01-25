<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

use Storage;
use App\Models\Tenant\Company;
use App\Models\Tenant\Departament;
use App\Models\Tenant\File;
use App\Models\Tenant\Task;

use App\Models\Tenant;

use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;

class ToolsService
{


    public function index()
    {
 
        return view('tenant.admin.settings.devtools.index');

    }


    public function smtp()
    {

        $user = Auth::user();

        $subject = 'Teste de Envio SMTP - ' . date('d/m/Y H:i:s'); 
        $message = 'Olá <b>' .  $user->name . ', </b><br>Se você está lendo esta mensagem é porque o sistema foi configurado corretamente para enviar emails!';

        
            
        $transport = new Swift_SmtpTransport(setting('smtp_host'), setting('smtp_port'), setting('smtp_encryption'));
        $transport->setUsername(setting('smtp_username'));
        $transport->setPassword(setting('smtp_password'));

        $mailer = new Swift_Mailer($transport);

      
        $send = (new Swift_Message($subject))
        ->setFrom(setting('smtp_from_address'), setting('smtp_from_name'))
        ->setTo($user->email)
        ->setBody($message, 'text/html');


        try {

            $result = $mailer->send($send);

        echo '<div class="alert alert-success" onload="set_card(2)">';
        
        echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close"> <span aria-hidden="true">×</span> </button><i class="fas fa-check"></i> ';
        echo ' Sucesso!!! o Email de teste foi enviado para ' . $user->email;
       
        echo '</div>';
        
        echo"
        <script>
            document.getElementById('disabled').className = 'd-none';
            document.getElementById('enabled').className = 'card-header bg-success text-white';
        </script>
       ";

       save_settings(['smtp_status' => 1]);

        }
        catch (\Swift_TransportException $e) {

            save_settings(['smtp_status' => 0]);

            $result = $e->getMessage() ;

         

            echo '<div class="alert alert-danger">';
        
            echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close"> <span aria-hidden="true">×</span> </button><i class="fas fa-exclamation-triangle"></i> <b>ERRO!!!</b>';
        
             echo '<br>';

            echo $result ;
          

            echo '</div>';

            echo"
            <script>
                document.getElementById('enabled').className = 'd-none';
                document.getElementById('disabled').className = 'card-header bg-danger text-white';
            </script>
           ";

        }
        


    }

    public function backupDocs()
    {
   

        $tenant_id = tenant('id');

        $docs = "/home/web/contabil.work/storage/tenants/$tenant_id/Documentos";

        $date = date('Y-m-d_H-i-s') ;

        $backup_dir = "/home/web/contabil.work/backup-updates/$tenant_id-$date/";

        system("cp -r $docs $backup_dir" );



    }


    public function backupDatabase()
    {

        $tenant_id = tenant('id');

        $date = date('Y-m-d_H-i-s') ;

        $tenant_dir = "/home/web/contabil.work/backup-updates";

        $DB_DATABASE = "tenant_$tenant_id" ;
        $DB_USERNAME = env('DB_USERNAME');
        $DB_PASSWORD = env('DB_PASSWORD');

        $sql = "$tenant_id-$date.sql";

        system( "mysqldump -u$DB_USERNAME -p$DB_PASSWORD $DB_DATABASE  > $tenant_dir/$sql" );


    }


    public function backupRun()
    {
        $tenant_id = tenant('id');

        $this->backupDocs();

        $this->backupDatabase();

        

        return redirect()->route('devtools')
                         ->with('sucess','Backup gerado com sucesso > ' . "/home/web/contabil.work/backups-updates/$tenant_id"  );

    }



    public function tasks()
    {

        $tasks = Task::where('frequency', '>', 1)->get();

        $recurrents = Task::all();




        foreach($tasks as $task){

           
               echo "<span>$task->id <b>$task->title</b> |  $task->last_repetition</span><br>";

           
            
                  $recurrent =$recurrents->where('recurrent_id',$task->id )->last();

                        echo "<small>$recurrent->id |$recurrent->title | $recurrent->recurrent_id | $recurrent->start_date </small><br>";

                 

                        $task->update(['last_repetition'=> $recurrent->start_date]);


            echo '<hr>';

        }

    }























}
