<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ruta;

class RutaController extends Controller
{
    public function index()
    {
        $rutas = Ruta::paginate(20);
        return view('admin.rutas.index', compact('rutas'));
    }

    public function create()
    {
        return view('admin.rutas.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'tarifa_base' => 'required|numeric|min:0',
            'linea_numero' => 'nullable|string|max:50',
            'ruta_ida_descripcion' => 'nullable|string',
            'ruta_vuelta_descripcion' => 'nullable|string',
            'tarifa_adulto' => 'required|numeric|min:0',
            'tarifa_descuento' => 'required|numeric|min:0',
            'activa' => 'boolean'
        ]);

        Ruta::create($request->only([
            'nombre',
            'descripcion',
            'tarifa_base',
            'linea_numero',
            'ruta_ida_descripcion',
            'ruta_vuelta_descripcion',
            'tarifa_adulto',
            'tarifa_descuento',
            'activa'
        ]));

        return redirect()->route('admin.rutas.index')->with('success','Ruta creada.');
    }

    public function edit($id)
    {
        $ruta = Ruta::findOrFail($id);
        return view('admin.rutas.edit', compact('ruta'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'tarifa_base' => 'required|numeric|min:0',
            'linea_numero' => 'nullable|string|max:50',
            'ruta_ida_descripcion' => 'nullable|string',
            'ruta_vuelta_descripcion' => 'nullable|string',
            'tarifa_adulto' => 'required|numeric|min:0',
            'tarifa_descuento' => 'required|numeric|min:0',
            'activa' => 'boolean'
        ]);

        $ruta = Ruta::findOrFail($id);
        $ruta->update($request->only([
            'nombre',
            'descripcion',
            'tarifa_base',
            'linea_numero',
            'ruta_ida_descripcion',
            'ruta_vuelta_descripcion',
            'tarifa_adulto',
            'tarifa_descuento',
            'activa'
        ]));

        return redirect()->route('admin.rutas.index')->with('success','Ruta actualizada correctamente.');
    }

    public function destroy($id)
    {
        $ruta = Ruta::findOrFail($id);
        $ruta->delete();

        return redirect()->route('admin.rutas.index')->with('success','Ruta eliminada correctamente.');
    }
}
