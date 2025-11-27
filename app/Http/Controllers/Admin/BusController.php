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
        $buses = Bus::with('ruta')->paginate(20);
        return view('admin.bus.index', compact('buses'));
    }

    public function create()
    {
        $rutas = Ruta::all();
        return view('admin.bus.create', compact('rutas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'plate' => 'required|unique:buses,plate',
            'code' => 'required|unique:buses,code',
            'ruta_id' => 'required|exists:rutas,id',
            'brand' => 'nullable|string',
            'model' => 'nullable|string'
        ]);

        Bus::create($request->only(['plate','code','brand','model','ruta_id']));

        return redirect()->route('admin.buses.index')->with('success','Bus creado correctamente.');
    }

    public function edit(Bus $bus)
    {
        $rutas = Ruta::all();
        return view('admin.bus.edit', compact('bus', 'rutas'));
    }

    public function update(Request $request, Bus $bus)
    {
        $request->validate([
            'plate' => "required|unique:buses,plate,{$bus->id}",
            'code' => "required|unique:buses,code,{$bus->id}",
            'brand' => 'nullable|string',
            'model' => 'nullable|string',
            'ruta_id' => 'required|exists:rutas,id'
        ]);

        $bus->update($request->only(['plate','code','brand','model','ruta_id']));

        return redirect()->route('admin.buses.index')->with('success','Bus actualizado correctamente.');
    }

    public function destroy(Bus $bus)
    {
        $bus->delete();
        return redirect()->route('admin.buses.index')->with('success', 'Bus eliminado correctamente.');
    }
}