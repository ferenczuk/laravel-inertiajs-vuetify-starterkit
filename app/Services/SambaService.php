<?php

namespace App\Services;
use Storage;
use App\Models\Tenant\User;
use App\Models\Tenant\Departament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;


class SambaService 
{


   
    public function userAdd($user_id)
    {   

        $user = User::find($user_id);

        $group = tenant('id');

        $username = Str::slug($group . '-' . $user->nickname, '-');

        $password = $group . '-' . Str::random(16); 

        $groups = $this->getGroups($user_id);


        $filepath = "/home/web/contabil.work/storage/system/temp/samba" ;

        $filename =  $username .'.sh';

        $content_sh = "#!/bin/bash \n" ;

        $content_sh .= "/usr/sbin/useradd -N -s /bin/false $username \n" ;

        $content_sh .= "/usr/sbin/usermod -aG www-data $username \n" ;
            
            if(count($groups) > 0){

                foreach($groups as $group){

                    $content_sh .= "/usr/sbin/usermod -aG $group $username \n" ;
    
                }

            }
         

        $content_sh .= "(echo $password; echo $password) | smbpasswd -s -a $username \n" ;

        $content_sh .= "/etc/init.d/smbd reload \n";
            
        $content_sh .= "rm $filepath/$filename \n";

        Storage::disk('system')->put("/temp/samba/$filename" , $content_sh );

        User::where('id',$user_id )->update(['user_network' => $username , 'user_network_pass' => $password ]);


    }

    public function addgroups(Array $groups)
    {  

        $filepath = "/home/web/contabil.work/storage/system/temp/samba" ;

        $filename = date('His-') . 'addgroup.sh';

        $content_sh = "#!/bin/bash \n" ;

        foreach($groups as $group){

            $content_sh .= "/usr/sbin/addgroup $group \n" ;

        }

        $content_sh .= "rm $filepath/$filename \n";

        Storage::disk('system')->put("/temp/samba/$filename" , $content_sh );


    }

    public function getGroups($user_id)
    {  
        $user = User::find($user_id);

        $groups = [] ;

        if($user->role == "Administrador"){

            $groups[] = tenant('id');

        }else{
           
            $departaments = Departament::all()->pluck('name', 'id')->all();

                    $admin_roles = json_decode($user->admin_roles, true);

                        if(isset($admin_roles['departaments'])){

                            foreach($admin_roles['departaments'] as $id){

                                $groups[] = tenant('id') . '-' . Str::slug($departaments[$id], '-');
    
                            }
                           
                        }


        }

        return $groups;


    }



    public function addSmbConf($tenant_id, $departament = null)
    {  

        is_null($departament) ? $dep = '' : $dep = '-' . Str::slug($departament, '-');

        is_null($departament) ? $conf = $tenant_id : $conf = "$tenant_id$dep";

        is_null($departament) ? $dir = '' : $dir = "/$departament";

        is_null($departament) ? $groups = "@$tenant_id" : $groups = "@$tenant_id , @$tenant_id$dep";

        
        $content =  "\n#####/ $conf /#####\n\n" ;
      
            $content .=  "[$conf] \n" ;
            $content .=  "path = /home/web/contabil.work/storage/tenants/$tenant_id/Documentos$dir \n" ;
            $content .=  "valid users = $groups \n" ;
            $content .=  "force group = $tenant_id$dep" ;

    
        $content .=  "\n\n#####/ $conf /#####\n" ;
       
       
        Storage::disk('server')->append("/etc/samba/tenants.conf" , $content );

    }



    public function fffadduser($username, $password)
    {   
        $filename = $username .'.sh';

        $content_sh = "#!/bin/bash \n" ;

        //$content_sh .= "chmod +x /home/web/contabil.work/server/etc/samba/$filename \n" ;

       // $content_sh .= "useradd -s /bin/false $username" ;

        //$content_sh .= "/etc/init.d/nginx reload \n" ;
            
        //$content_sh .= "rm $file_path";

        Storage::disk('system')->put("/system/temp/samba/$filename" , $content_sh );


    }




    public function usermod(Request $request)
    {   

        dd($request->all());
        //return true;


    }
   



    public function deluser(Request $request)
    {   

        return true;


    }





}
