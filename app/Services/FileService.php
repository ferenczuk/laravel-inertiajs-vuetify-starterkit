<?php

namespace App\Services;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

use Storage;
use App\Models\Tenant\File;
use App\Models\Tenant\Company;
use App\Models\Tenant\Departament;


class FileService 
{
    public function createDepartament($departament_id)
    {  
        $departament_dir = tenant('id') . "/Documentos/Departamento_$departament_id";

      
            Storage::disk('tenants')->makeDirectory($departament_dir, 777 , true);

            Storage::disk('tenants')->makeDirectory($departament_dir .'/.Documentos-Gerais');

        
                $companies = Company::all(); 

                    foreach($companies as $company){

                      $company_dir = "Empresa_$company->id";

                        $path_company = tenant('id') . "/$departament_dir/$company_dir"; // dd($path_company);

                        Storage::disk('tenants')->makeDirectory($path_company);

                        Storage::disk('tenants')->makeDirectory($path_company. '/.Cliente');


                    }

              //$this->permissions();
            
    }

   
    public function createCompany($company_id)
    {   

      $company_dir = "Empresa_$company_id";

   
      $departaments = Departament::all();

      foreach($departaments as $departament){

        $departament_dir = "Departamento_$departament->id";

        $dir = tenant('id') . "/Documentos/$departament_dir/$company_dir";

        Storage::disk('tenants')->makeDirectory($dir);

        Storage::disk('tenants')->makeDirectory($dir . '/.Cliente');

           

      }

      //$this->permissions();

    }





    

    public function getPath( File $file )
    {

      $departament_folder = "Departamento_$file->departament_id";


      if($file->company_id == 0){
        
        $company_folder = '.Documentos-Gerais'; 
      
      }else{

          $company_folder = "Empresa_$file->company_id";

      } 


      $file->folder == '/' ? $folder = '' : $folder = $file->folder ;

      $path = tenant('id') . '/Documentos/'. $departament_folder .'/'. $company_folder . $folder .'/'. $file->file_name ;


      $path = str_replace('//', '/', $path); //dd($path);

     return $path;
   
    }



    public function createFolder(Request $request )
    {


      


    $new_folder = $request->path . '/' . $request->folder ;

    Storage::disk('tenants')->makeDirectory( $new_folder );

       

    }




}
