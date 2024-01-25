<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Vendor;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
    
        $vendors = Vendor::all();

        if(!is_null($request->search)){

            $users = User::where('name','LIKE','%' . $request->keyword .'%' )
                            ->when($request->vendor_id, function ($query, $vendor_id) {

                                return $query->where('vendor_id', $vendor_id);
                    
                            })->paginate(50);
                         

        }else{

            $users = User::all();

        }

      
        
            return view('system.users.index',compact('users', 'vendors'));

    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $vendors = Vendor::all();
        return view('system.users.create', compact('vendors') , ['user' => new User()]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
       //dd($request->all());

       $data = $this->_validate($request);
    
       if($data['userpwd'] != null and $data['userpwd'] == $data['userpwd_confirmation']){

           $data['password'] = bcrypt($data['userpwd']);

        }

       $user = User::create($data);
     
      

       ///Notification::send($user, (new UserNotification($data['userpwd'])));


       return redirect()->route('system.users.edit', ['user' =>  $user->id ])
                        ->with('sucess','Usuário cadastrado com sucesso');
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
        $user = User::FindOrFail($id);

        $vendors = Vendor::all();

        return view('system.users.edit', compact('user','vendors') );

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
        $user = User::FindOrFail($id);

        $data = $this->_validate($request);
         
        if($data['userpwd'] != null and $data['userpwd'] == $data['userpwd_confirmation']){

            $data['password'] = bcrypt($data['userpwd']);

         }else{

            unset($data['password']);

         }


        $user->fill($data);

        $user->save();

        return redirect()->route('system.users.edit', ['user' => $id ])
                         ->with('sucess','Usuário Atualizado com sucesso');

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
        $user = $request->route('user');
            

        $data =  [
      
            'name' => "required|min:4|max:255|unique:users,name, $user",
            'email' => "required|unique:users,email, $user",
            'role' =>  'required|integer',
            'phone' => 'max:12',
            'userpwd' => 'max:50',
            'userpwd_confirmation' => 'max:50',
            'vendor_id' =>  'required|integer',
           
        ];

        if($request->userpwd){

            $data['userpwd'] = 'required|min:6|max:50|confirmed';
            $data['userpwd_confirmation'] = 'required|min:6|max:50';

         }


        return $this->validate($request , $data);



    }










}
