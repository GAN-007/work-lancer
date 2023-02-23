<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class PagesController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }
    public function employer_account(){
        return view('auth.register-employer');
    }
    public function createEmployer(Request $request){
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $new = new User;
        $new->name = $request->name;
        $new->email = $request->email;
        $new->password = bcrypt($request->password);
        $new->save();
        $new->attachRole('user');
        return redirect()->route('login')->with('success','You are successfully Registered as an Employer');
    
    }
    public function createFreelancer(Request $request){
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $new = new User;
        $new->name = $request->name;
        $new->email = $request->email;
        $new->password = bcrypt($request->password);
        $new->save();
        $new->attachRole('freelancer');
        return redirect()->route('login')->with('success','You are successfully Registered as an Freelancer');
    
    }
}