<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bus;
use App\Models\User;
use App\Models\Ruta;

class BusController extends Controller
{
    public function index()
    {
        $buses = Bus::with('ruta', 'driver')->paginate(20);
        return view('admin.bus.index', compact('buses'));
    }

    public function create()
    {
        $rutas = Ruta::all();
        $drivers = User::where('role', 'driver')->where('active', 1)->get();
        return view('admin.bus.create', compact('rutas', 'drivers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'plate' => 'required|unique:buses,plate',
            'code' => 'required|unique:buses,code',
            'ruta_id' => 'required|exists:rutas,id',
            'driver_id' => 'nullable|exists:users,id'
        ]);

        Bus::create($request->only(['plate','code','brand','model','ruta_id','driver_id']));

        return redirect()->route('admin.buses.index')->with('success','Bus creado correctamente.');
    }

    public function edit(Bus $bus)
    {
        $rutas = Ruta::all();
        $drivers = User::where('role', 'driver')->where('active', 1)->get();
        $bus->load('driver');
        return view('admin.bus.edit', compact('bus', 'rutas', 'drivers'));
    }

    public function update(Request $request, Bus $bus)
    {
        $request->validate([
            'plate' => "required|unique:buses,plate,{$bus->id}",
            'code' => "required|unique:buses,code,{$bus->id}",
            'brand' => 'nullable|string',
            'model' => 'nullable|string',
            'ruta_id' => 'required|exists:rutas,id',
            'driver_id' => 'nullable|exists:users,id'
        ]);

        $bus->update($request->only(['plate','code','brand','model','ruta_id','driver_id']));

        return redirect()->route('admin.buses.index')->with('success','Bus actualizado correctamente.');
    }

    public function destroy(Bus $bus)
    {
        $bus->delete();
        return redirect()->route('admin.buses.index')->with('success', 'Bus eliminado correctamente.');
    }
}