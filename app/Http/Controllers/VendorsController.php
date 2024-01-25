<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Vendor;
use Illuminate\Support\Facades\Storage;

class VendorsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $vendors = Vendor::all();

            return view('system.vendors.index',compact('vendors'));

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('system.vendors.create', ['vendor' => new Vendor()]);
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
 
        $vendor = Vendor::create($data);

        return redirect()->route('system.vendors.edit', ['vendor' =>  $vendor->id ])
                         ->with('sucess','Revendedor cadastrado com sucesso');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
     
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $vendor = Vendor::FindOrFail($id);

        return view('system.vendors.edit', compact('vendor') );
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
        $vendor = Vendor::FindOrFail($id);

        $data = $this->_validate($request);


            $path_uploads = 'vendors/' . $vendor->id . '/images/' ;

            if(!is_null($request->file('logo_login'))){
                        
                $data['logo_login'] = $path_uploads . $this->upload($id ,$request->file('logo_login') , 'logo_login' );

                Storage::disk('uploads')->delete( $vendor->logo_login );
            
            }

            if(!is_null($request->file('bg_login'))){
                   
                $data['bg_login'] =  $path_uploads . $this->upload($id ,$request->file('bg_login') , 'bg_login' );

                Storage::disk('uploads')->delete( $path_uploads . $vendor->bg_login);
            

            }

            if(!is_null($request->file('icon_admin'))){
                   
                $data['icon_admin'] =  $path_uploads . $this->upload($id ,$request->file('icon_admin') , 'icon_admin' );

                Storage::disk('uploads')->delete( $path_uploads .$vendor->icon_admin);
            }

            if(!is_null($request->file('logo_admin'))){
                        
                $data['logo_admin'] =  $path_uploads . $this->upload($id ,$request->file('logo_admin') , 'logo_admin' );

                Storage::disk('uploads')->delete( $path_uploads . $vendor->logo_admin);
            }

      


        $vendor->fill($data);

        $vendor->save();

        return redirect()->route('system.vendors.edit', ['vendor' => $id ])
                         ->with('sucess','Revendedor Atualizado com sucesso');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }



    protected function _validate(Request $request)
    {


        //dd($request->all());

        $vendorID = $request->route('vendor');
            

        $data =  [
      
            'name' => "required|min:4|max:255|unique:vendors,name, $vendorID",
            'domain' => "required|unique:vendors,domain, $vendorID",
            'email' => "required|unique:vendors,email, $vendorID",
            'url_helpers' => 'max:120',
            'bg_logo_admin' => 'max:10',
            'logo_login' => 'image',
            'default_pass' => 'required|min:8|max:100',
            
            'bg_login' => 'image',
            'icon_admin' => 'image',
            'logo_admin' => 'image',
            'primary_color' => 'max:10',
            'secondary_color' => 'max:10',

            'phone' => 'required|max:15',
            'corporate_name' => "required|min:4|max:255|unique:vendors,corporate_name, $vendorID",
            'corporate_number' => "required|unique:vendors,corporate_number,$vendorID",
            'status' => 'required|max:120',
            'payment_method' => 'required|max:120',
            'next_invoice' => 'max:10',
            'info' => 'max:9000',
        ];

   
        return $this->validate($request , $data);



    }



    public function upload($vendor_id , $file, $name)
    {
       //// Uso >>>  upload($request->file('nome-do-campo', 'name'))


        $validExtensions = ['webp','jpeg', 'jpg', 'png', 'gif'];

        if (!is_null($file) and $file->isValid() and in_array($file->extension(), $validExtensions)) {
            
            $name = $name . '-' . date('sidH') .'.'. $file->extension();
        
            $file->storeAs('vendors/' . $vendor_id .'/images' , $name , 'uploads');
        
                return $name;

        }

        
     
    }





}
