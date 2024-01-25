<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{

    protected $connection = 'mysql';

    protected $fillable = [

        'name',
        'status',
        'email', 
        'domain',
        'domain_tenant_trial',
        'url_helpers',
        'default_pass',

        'logo_login',
        'bg_login',
        'icon_admin',
        'logo_admin',
        'bg_logo_admin',
        'primary_color',
        'secondary_color',

        'phone',
        'corporate_name',
        'corporate_number',
        'zip_code',
        'address',
        'number', 
        'complement',
        'bairro',
        'city',
        'state',
        'country' ,
        'info' ,
        'next_invoice' ,
        'not_suspend' ,
        'payment_method' ,

    ];



    
}
