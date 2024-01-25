<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Storage;

use App\Models\Tenant;

use App\Models\Vendor;
use App\Models\Plan;
use App\Services\NginxService;
use Illuminate\Support\Str;

use Inertia\Inertia;


class TenantsController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        
        $vendors = Vendor::all();

        $tenants = Tenant::all();

   

        if(!is_null($request->search)){

            $tenants = \App\Models\Tenant::where('principal_domain','LIKE','%' . $request->keyword .'%' )
                            ->when($request->vendor_id, function ($query, $vendor_id) {

                                return $query->where('vendor_id', $vendor_id);
                    
                            })->when($request->status, function ($query, $status) {

                                return $query->where('status', $status);
                    
                            });

            
            $tenants_count = $tenants->count();

            $tenants =  $tenants->paginate(50);  

              
        }
       
      
        return Inertia::render('Central/Tenants/Index', ['tenants' => $tenants] );
               
         
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $plans = Plan::all();
        
        $vendors = Vendor::where('status', 1)->get();

        //return view('system.tenants.create', compact('vendors', 'plans') );
        return Inertia::render('Central/Tenants/Create', ['plans' => $plans , 'vendors' => $vendors ] );

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $this->_validate($request);
 
        $id = Str::slug($data['id'], '-');

        $domains = [$id . '.' . env('DOMAIN_SYSTEM'), $data['principal_domain']];

        $companies_path = storage_path('tenants') . '/'. $id . '/Documentos';

    
        $tenant = Tenant::create($domains, [
            'id'=> $id,
            '_tenancy_db_name' => 'tenant_' . $id,
            'vendor_id' => $data['vendor_id'],
            'base_domain' => 'http://' . $id . '.' . env('DOMAIN_SYSTEM'),
            'principal_domain' => 'http://' . $data['principal_domain'],
            'status' => $data['status'],
            'modules_plan' => $data['vendor_id'],
            'access_plan' => $data['vendor_id'],
            'settings' => '{}',
            'documents_path' => $companies_path,
     
        ]);

            if($tenant->principal_domain){

                NginxService::create($tenant->principal_domain);

            }
            

        return redirect()->route('system.tenants.edit', ['tenant' =>  $tenant->id ])
                         ->with('sucess','Contabilidade cadastrada com sucesso');
   
   
   
                        }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {

        $tenant = Tenant::find($id);

        $plans = Plan::all();

        $vendors = Vendor::where('status', 1)->get();


        return view('system.tenants.edit', compact('tenant', 'plans','vendors') );

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $tenant = Tenant::find($id);

        $data =  [
      
            'vendor_id' =>  'required|integer',
            'status' =>  'required|integer',
            'modules_plan' =>  'required|integer',
            'access_plan' =>  'required|integer',
             
        ];
       


        $data = $this->validate($request , $data);


        if($tenant->ssl == 2) {

           // $data['principal_domain'] = substr_replace($tenant->principal_domain, 's', 4, 0);

        }
        
        $plans = Plan::all();

        $vendors = Vendor::all();


        $tenant->put($data);

        return redirect()->route('system.tenants.edit', ['tenant' => $id ])
                         ->with('sucess','Contabilidade Atualizada com sucesso');
    
    
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $tenant = Tenant::find($id);

        //Storage::disk('trash')->makeDirectory($tenant->id);

        $trash =  storage_path("trash/$tenant->id") ;

          

       

           $DB_HOST = env('DB_HOST');
           $DB_DATABASE = "tenant_$tenant->id";
           $DB_USERNAME = env('DB_USERNAME');
           $DB_PASSWORD = env('DB_PASSWORD');
     
          system( "mysqldump -u$DB_USERNAME -p$DB_PASSWORD $DB_DATABASE  > $trash/$DB_DATABASE.sql" );




        //$tenant->delete();

        return redirect()->route('system.tenants.index', ['tenant' => $id ])
                         ->with('sucess','Contabilidade  ( ' . $id . ' )  Excluída com Sucesso.');
    }



    protected function _validate(Request $request)
    {


        $tenant = $request->route('tenant');
            

        $data =  [
      
            'id' => "required|min:3|max:55|unique:tenants,id, $tenant",
            'principal_domain' => "required|min:4|max:55|unique:tenants,principal_domain, $tenant",
            'vendor_id' =>  'required|integer',
            'status' =>  'required|integer',
            'modules_plan' =>  'required|integer',
            'access_plan' =>  'required|integer',
             
        ];

       


        return $this->validate($request , $data);



    }








}
