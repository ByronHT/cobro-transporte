<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::paginate(15);
        return view('admin.users.index', compact('users'));
    }
    public function dashboardCliente()
    {
    $user = Auth::user();

    if ($user->role !== 'passenger') {
        abort(403, 'Acceso no autorizado');
    }

    return view('cliente.dashboard', compact('user'));
    }


    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'=>'required|string|max:255',
            'email'=>'required|email|unique:users',
            'nit'=>'nullable|string|max:20',
            'password'=>'required|string|min:6|confirmed',
            'role'=>'required|in:admin,driver,passenger',
            'login_code'=>'required|string|size:4|unique:users,login_code',
            'ci'=>'nullable|string|max:20',
            'birth_date'=>'nullable|date',
            // user_type solo requerido si el rol es pasajero
            'user_type'=>'required_if:role,passenger|nullable|in:adult,senior,minor,student_school,student_university',
            'school_name'=>'required_if:user_type,student_school|nullable|string|max:255',
            'university_name'=>'required_if:user_type,student_university|nullable|string|max:255',
            'university_year'=>'required_if:user_type,student_university|nullable|integer|min:1|max:7',
            'university_end_year'=>'required_if:user_type,student_university|nullable|integer|min:2025'
        ]);

        User::create([
            'name'=>$request->name,
            'email'=>$request->email,
            'nit'=>$request->nit,
            'password'=>Hash::make($request->password),
            'role'=>$request->role,
            'active'=>$request->has('active') ? true : false,
            'login_code'=>$request->login_code,
            'ci'=>$request->ci,
            'birth_date'=>$request->birth_date,
            // Solo guardar user_type si el rol es pasajero, sino guardar 'adult' por defecto
            'user_type'=>$request->role === 'passenger' ? $request->user_type : 'adult',
            'school_name'=>$request->school_name,
            'university_name'=>$request->university_name,
            'university_year'=>$request->university_year,
            'university_end_year'=>$request->university_end_year,
            'total_earnings'=>0
        ]);

        return redirect()->route('admin.users.index')->with('success','Usuario creado correctamente');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'=>'required|string|max:255',
            'email'=>"required|email|unique:users,email,{$user->id}",
            'nit'=>'nullable|string|max:20',
            'password'=>'nullable|string|min:6|confirmed',
            'role'=>'required|in:admin,driver,passenger',
            'login_code'=>"required|string|size:4|unique:users,login_code,{$user->id}",
            'ci'=>'nullable|string|max:20',
            'birth_date'=>'nullable|date',
            'user_type'=>'required_if:role,passenger|nullable|in:adult,senior,minor,student_school,student_university',
            'school_name'=>'required_if:user_type,student_school|nullable|string|max:255',
            'university_name'=>'required_if:user_type,student_university|nullable|string|max:255',
            'university_year'=>'required_if:user_type,student_university|nullable|integer|min:1|max:7',
            'university_end_year'=>'required_if:user_type,student_university|nullable|integer|min:2025'
        ]);

        $data = $request->only([
            'name', 'email', 'nit', 'role', 'ci', 'birth_date', 'login_code',
            'user_type', 'school_name', 'university_name', 'university_year', 'university_end_year'
        ]);
        $data['active'] = $request->has('active');
        
        // Si el rol no es pasajero, limpiar los campos específicos de pasajero
        if ($request->role !== 'passenger') {
            $data['user_type'] = 'adult'; // Default
            $data['school_name'] = null;
            $data['university_name'] = null;
            $data['university_year'] = null;
            $data['university_end_year'] = null;
        }

        $user->update($data);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        return redirect()->route('admin.users.index')->with('success','Usuario actualizado correctamente');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('admin.users.index')->with('success','Usuario eliminado');
    }
        
}
