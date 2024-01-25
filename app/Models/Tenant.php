<?php

namespace App\Models;


use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{

    //protected $connection = 'mysql';


    use HasDatabase, HasDomains;
  

    protected $fillable = [

       'status', 
       'base_domain',
       'principal_domain',
       'vendor_id',
       'modules_plan',
       'access_plan',
       'ssl', 
       'free_date',
       'settings'

      
    ];


    public function vendor()
    {
        return $this->belongsTo('App\Models\Vendor');
    }

}
