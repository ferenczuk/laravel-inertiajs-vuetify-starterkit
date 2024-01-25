<?php

namespace App\Services;

use Storage;
use App\Models\System\Tenant;


class NginxService 
{
   
    public static function create($principal_domain)
    { 
        
    $d = explode("://" , $principal_domain ); // retira http://

    $hostname = $d[1];
       
    $uri = '$uri';
    $query_string = '$query_string';

    $public_path = public_path();

    $content = "server {

    listen 80; 
    listen [::]:80; 

        root $public_path/; 
        
        add_header X-Frame-Options 'SAMEORIGIN';
        add_header X-XSS-Protection '1; mode=block';
        add_header X-Content-Type-Options 'nosniff';
        charset utf-8;
    
  
        index index.php index.html index.htm; 

        server_name $hostname ; 

        location / {
        
            try_files $uri $uri/ /index.php?$query_string; 

        } 

        location ~ \.php$ { 
            include snippets/fastcgi-php.conf;
            fastcgi_pass unix:/run/php/php7.2-fpm.sock;
        } 

        location ~ /\.ht { 
            deny all;
        } 


    }";

    

        $nginx = Storage::disk('server')->put('/etc/nginx/'.$hostname , $content );

        ///////   criar .sh   ////
           
            if($nginx){
           
                $file_path = "/home/web/contabil.work/storage/system/temp/nginx/$hostname.sh" ;

                    $content_sh = "#!/bin/bash \n" ;

                    $content_sh .= "chmod +x /home/web/contabil.work/server/etc/nginx/$hostname \n" ;

                    $content_sh .= "ln -s /home/web/contabil.work/server/etc/nginx/$hostname /etc/nginx/sites-enabled/$hostname \n" ;

                    $content_sh .= "/etc/init.d/nginx reload \n" ;
                        
                    $content_sh .= "rm $file_path";

                        Storage::disk('system')->put("/temp/nginx/$hostname.sh" , $content_sh );

            }
   


    }


}