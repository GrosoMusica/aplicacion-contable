<?php

namespace App\Http\Controllers;

use App\Models\Comprador;
use App\Models\Acreedor;
use App\Models\Lote;
use App\Models\Financiacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompradorController extends Controller
{
    // Método para mostrar la lista de compradores
    public function index()
    {
        // Cargar compradores con sus financiaciones y cuotas
        $compradores = Comprador::with('financiacion.cuotas')->get();
        return view('compradores_index', compact('compradores'));
    }

    public function edit($id)
    {
        $comprador = Comprador::findOrFail($id);
        return view('edit_comprador', compact('comprador'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
            'telefono' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'dni' => 'required|string|max:20',
        ]);

        $comprador = Comprador::findOrFail($id);
        $comprador->update($request->only(['nombre', 'direccion', 'telefono', 'email', 'dni', 'judicializado']));

        return redirect()->route('compradores.index')->with('success', 'Comprador actualizado exitosamente.');
    }

    public function toggleJudicializado($id)
    {
        $comprador = Comprador::findOrFail($id);
        $comprador->judicializado = !$comprador->judicializado;
        $comprador->save();

        return redirect()->route('compradores.index')->with('success', 'Estado de judicialización actualizado.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            // Validar los datos del comprador
            $request->validate([
                'nombre' => 'required|string|max:255',
                'direccion' => 'required|string|max:255',
                'telefono' => 'required|string|max:20',
                'email' => 'required|email|max:255',
                'dni' => 'required|string|max:20',
                // Otros campos necesarios...
            ]);

            // Crear el nuevo comprador
            $comprador = Comprador::create($request->all());

            // Crear el lote asociado al comprador
            $lote = Lote::create([
                'comprador_id' => $comprador->id,
                // ... otros datos del lote ...
            ]);

            // Crear la financiación asociada al comprador
            $financiacion = Financiacion::create([
                'comprador_id' => $comprador->id,
                'monto_a_financiar' => $request->input('monto_a_financiar'),
                // Otros campos de financiación...
            ]);

            // Asignar el acreedor "admin" al nuevo comprador
            $adminAcreedorId = 1; // ID del acreedor "admin"
            $comprador->acreedores()->attach($adminAcreedorId, ['porcentaje' => 100]);

            // Establecer las relaciones en el comprador
            $comprador->lote_comprado_id = $lote->id;
            $comprador->financiacion_id = $financiacion->id;
            $comprador->save();

            DB::commit();

            return redirect()->route('compradores.index')->with('success', 'Comprador creado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear comprador: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al crear comprador: ' . $e->getMessage())->withInput();
        }
    }

    public function buscar()
    {
        $compradores = Comprador::with('lote')->get();
        return response()->json($compradores);
    }

    // Otros métodos CRUD pueden ir aquí
} 