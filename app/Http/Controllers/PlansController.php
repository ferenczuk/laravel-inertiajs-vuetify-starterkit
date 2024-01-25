<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Plan;

class PlansController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $plans = Plan::all();
    
        return view('system.plans.index',compact('plans'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('system.plans.create',  ['plan' => new PLan()]);

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
 
        $plan = Plan::create($data);

        return redirect()->route('system.plans.edit', ['plan' =>  $plan->id ])
                         ->with('sucess','Plano cadastrado com sucesso');



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
        $plan = Plan::FindOrFail($id);

        return view('system.plans.edit', compact('plan') );
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
        $plan = Plan::FindOrFail($id);

        $data = $this->_validate($request);

        $plan->fill($data);

        $plan->save();

        return redirect()->route('system.plans.edit', ['plan' => $id ])
                         ->with('sucess','Plano Atualizado com sucesso');
                         
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


        $plan = $request->route('plan');
            

        $data =  [
      
            'name' => "required|min:4|max:255|unique:plans,name, $plan",
            'description' => 'max:10000',
            'type' => 'required|max:100',
            'status' =>  'required|integer',
            'price' =>  'required|integer',
            'cost' =>  'integer',
           
        ];

       


        return $this->validate($request , $data);



    }






}
