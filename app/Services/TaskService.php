<?php

namespace App\Services;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

use Storage;
use App\Models\Tenant\Task;


class TaskService 
{

    public function getInterval($init ,$final)
    {  
        
      $interval = strtotime($final) - strtotime($init);

      return $interval ;
            
    }


    public function setInterval($interval ,$date)
    {  

      $task = new Task();

      $get_interval = $task->options['frequency'][$interval]['value'] ;

      $new = date("Y-m-d H:i:s", strtotime( $get_interval, strtotime($date)));

        return $this->work_schedule($new) ;
            
    }


    public function work_schedule($date)
    {  

      $new_date = $date ;

       $time =  date('H:i', strtotime($date));

        $work_schedule = json_decode( setting('task_work_schedule') , true );

          $i = 0;
          
            while ($i <= 12 ) { 

              $weekday = date('w',strtotime( "+$i days", strtotime($date)) ) ;

              $data = $work_schedule[$weekday] ;
              
              if($work_schedule[$weekday]['status'] == 'true' ){ 

                $day =  date('Y-m-d',strtotime( "+$i days", strtotime($date)) ) ;

                  
                if(strtotime($time) > strtotime($data['start']) and strtotime($time) < strtotime($data['break'])  ){

                  $new_date = $date;

                  break;

                }


                if(strtotime($time) < strtotime($data['start'])  ){

                  $new_date = $day . ' ' . $data['start'] . ':00';

                  break;

                }


                if(strtotime($time) > strtotime($data['break']) and strtotime($time) < strtotime($data['restart'])  ){

                  $new_date = $day . ' ' . $data['restart'] . ':00';

                  break;
        
                }


                if(strtotime($time) > strtotime($data['restart']) and strtotime($time) < strtotime($data['end'])  ){

                  $new_date = $date;

                  break;
        
                }


                if(strtotime($time) > strtotime($data['end'])  ){

                  $time =  $data['start'] ;

                }
                  

              }else{

                $time =  $data['start'] ;

              }

              $i++;
        
            } //// fecha while ($i <= 12 ) /////////


        return $new_date ;

    }






    /**
     * 
     * Duplica para Empresas.
     *
     */
    public function duplicateForCompanies( $task_id , $companyIDs , $interval)
    {

      $data = Task::find( $task_id )->toArray();

    
        foreach($companyIDs as $company_id){

          if($task_id !=  $company_id ){

            $data['company_id'] = $company_id;

            $data['last_repetition'] = NULL;


              if($interval > 0){

                  $data['start_date'] =  $this->setInterval($interval, $data['start_date']); 

                  $data['final_date'] = $this->setInterval($interval,  $data['final_date']); 
                  
              }

 
              $task = Task::create($data);

              if(isset($data['parent_id'])){

                  //$model_id , $duplicate_in
                  $this->subTasksDuplicate($task_id, $task->id,$company_id );

              }

            $this->recurrence($task->id);
         
          } /// fecha if($task_id !=  $company_id )

        }

     

    }








    public function recurrence($id)
    {  

        $task = Task::find($id);


        if($task->frequency > 0){


               
              
              is_null($task->last_repetition) ? $last_repetition = $task->start_date : $last_repetition = $task->last_repetition;

              

              $interval = $this->getInterval($task->start_date ,$task->final_date);

              $last_repetition_str = strtotime($last_repetition);


                $data = $task->toArray();

                $data['recurrent_id'] = $id;
                $data['frequency'] = 0;
                $data['repeat_until'] = NULL;
                $data['last_repetition'] = NULL;
                $data['date_closing'] = NULL;
             

                // criar a 1ª na mesma data
                if(is_null($task->last_repetition)){

                  $copy_task = Task::create($data);

                  if($task->category == 2){

                    //$model_id , $duplicate_in
                    $this->subTasksDuplicate($task->id, $copy_task->id);
    
                  }

                }



                $task_limit_create = setting('task_limit_create');
                
                $limit_date =  strtotime("+$task_limit_create days", strtotime('now') );


                $recurrence = $task->options['frequency'][$task->frequency]['value']; //dd($recurrence);
                

               // dd($last_repetition_str , $limit_date );

                while ( $last_repetition_str < $limit_date ) { 


                  
                  $start_date = date("Y-m-d H:i:s" , strtotime($recurrence , $last_repetition_str )  ) ;
                  
                
                  $data['start_date'] =  $this->work_schedule($start_date);
                  
                    $final_date = date("Y-m-d H:i:s" , strtotime($start_date) + $interval ) ;

                  $data['final_date'] = $this->work_schedule($final_date);


                  if( !is_null( $data['competence_date']) ){

                    $data['competence_date'] = date("Y-m-d", strtotime( $task->options['frequency'][$task->frequency]['value'], strtotime($data['competence_date']))  );

                  }
                  
                  $data['status'] =  60 ;

///dd($data);
          
                  $new_task = Task::create($data);

                    if($task->category == 2){

                     $this->subTasksDuplicate($task->id, $new_task->id);
      
                  }

                  $last_repetition_str =  strtotime($recurrence, $last_repetition_str) ;

                 

                }

                
                $task->update( ['last_repetition' => date("Y-m-d H:i:s" , strtotime($recurrence , $last_repetition_str )  ) ] );
                

                  

        }
        
    }




       /**
     * 
     * Duplica as Sub Tarefas 
     * 
     */
    
    public function subTasksDuplicate($model_project_id , $new_project_id, $company_id=NULL)
    {


      $model_project = Task::find($model_project_id);

      $new_project = Task::find($new_project_id);


      $tasks = Task::where('parent_id' , $model_project_id)
                      ->orderBy('start_date', 'asc')
                      ->orderBy('final_date', 'asc')
                      ->get();

        foreach($tasks as $task){

           $data =  $task->toArray();

           unset($data['id']);
           
           $data['parent_id'] = $new_project_id ;

           $data['type'] = 0 ;

           $data['date_closing'] = NULL ;


              $interval = $this->getInterval($model_project->start_date ,$new_project->start_date);

              $start_date = date("Y-m-d H:i:s" , strtotime($data['start_date']) + $interval) ;

              $data['start_date'] = $this->work_schedule($start_date); 

              //echo 'model: ' . $model_project->start_date . ' | ' . $data['start_date'] . '<hr>';

          
            
              $final_date = date("Y-m-d H:i:s" , strtotime($data['final_date']) + $interval) ;

              $data['final_date'] = $this->work_schedule($final_date); 

              //echo 'model: ' . $model_project->final_date . ' | ' . $data['final_date'] . '<hr>';

            $syncDate = $this->syncDate($data['start_date'] , $data['final_date']);

            $data['start_date'] = $syncDate['start_date'];

            $data['final_date'] = $syncDate['final_date'];

           $data['last_repetition'] = NULL;



           $data['status'] = $task->autoStatus($data);

           if( isset($company_id)){

              $data['company_id'] = $company_id ;

           }

            $data['company_id'] = $new_project->company_id;


           Task::create($data);

         // die();

        }

    }


    
    public function syncDate($start_date , $final_date)
    {

        $difference = strtotime( $final_date ) - strtotime( $start_date );

        $now = strtotime('now') ;
       
            if($now > strtotime( $start_date ) ){

                $data = [

                    'start_date' => date("Y-m-d H:i:s" ) ,
                    'final_date' => date("Y-m-d H:i:s" , strtotime('now') + $difference  ) ,
                    'difference' => $difference

                ];

            }else{

                $data = [

                    'start_date' => $start_date ,
                    'final_date' => $final_date ,
                    'difference' => $difference
                
                ];

            }
       
        return $data ;

    }
   


























        /**
     * 
     * Reconrencia.  OLD
     *
     */
    public function repeat($data)
    {

        //dd($data);

        $recurrence = new Task();

        $interval = $recurrence->getRecurrence('values')[$data['recurrence']];

        $dates = $this->syncDate($data['start_date'] , $data['final_date']);

        $start_date = $dates['start_date']; //echo $start_date .'<hr>';

        $final_date = $dates['final_date']; //echo $final_date .'<hr>';

        $repeat_until = date('Y-m-d H:i:s', strtotime($data['repeat_until']));

        $model_id = $data['model_id'] ;

        unset($data['model_id']);
        unset($data['recurrence']);
        unset($data['repeat_until']);
        unset($data['feedback']);


        while (strtotime($start_date) <= strtotime($repeat_until)) {

            $final_date = date("Y-m-d H:i:s", strtotime($interval, strtotime($final_date)));

            $start_date = date("Y-m-d H:i:s", strtotime($interval, strtotime($start_date)));

            //echo "inicia: $start_date | Entrega em: $final_date<br>";

            $data['start_date'] = $start_date;

            $data['final_date'] = $final_date;

 
            $task = Task::create($data);

            if(isset($data['parent_id'])){

                //$model_id , $duplicate_in
                $this->subTasksDuplicate($model_id, $task->id);

            }
         

        }

    }

}
