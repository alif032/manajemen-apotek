<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\Category;
use App\Models\StockMutation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class MedicineController extends Controller
{
    // Menampilkan daftar obat dengan filter
    public function index(Request $request)
    {
        $filter = $request->query('filter');

        $query = Medicine::with('category')
            ->withSum('batches as total_stock', 'stock')
            ->with('batches');

        // Filter obat yang sudah kedaluwarsa
        if ($filter === 'expired') {
            $query->whereHas('batches', function ($q) {
                $q->whereDate('expired_date', '<', now()->toDateString())
                    ->where('stock', '>', 0);
            });
        }

        // Filter obat yang hampir kedaluwarsa (90 hari)
        elseif ($filter === 'near_expiry') {
            $query->whereHas('batches', function ($q) {
                $q->whereBetween('expired_date', [
                    now()->toDateString(),
                    now()->addDays(90)->toDateString()
                ])->where('stock', '>', 0);
            });
        }

        $medicines = $query->get();

        // Filter obat dengan stok menipis
        if ($filter === 'low_stock') {
            $medicines = $medicines->filter(function ($medicine) {
                return ($medicine->total_stock ?? 0)
                    <= $medicine->minimum_stock;
            })->values();
        }

        return Inertia::render('Medicines/Index', [
            'medicines' => $medicines,
            'filter' => $filter,
        ]);
    }

    // Form tambah obat
    public function create()
    {
        return Inertia::render('Medicines/Create', [
            'categories' => Category::all()
        ]);
    }

    // Mencari obat berdasarkan barcode
    public function findByCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        $medicine = Medicine::where('code', $request->code)->first();

        if ($medicine) {
            return response()->json([
                'found' => true,
                'message' => 'Obat terdaftar! Silakan masukkan nomor batch dan jumlah stok masuk.',
                'data' => [
                    'id' => $medicine->id,
                    'code' => $medicine->code,
                    'name' => $medicine->name,
                    'category_id' => $medicine->category_id,
                    'price' => $medicine->price,
                    'minimum_stock' => $medicine->minimum_stock,
                    'rack_location' => $medicine->rack_location,
                ]
            ]);
        }

        return response()->json([
            'found' => false,
            'message' => 'Kode obat baru. Silakan isi form secara manual untuk mendaftarkan obat.'
        ]);
    }

    // Menambahkan obat dan batch stok
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'minimum_stock' => 'required|integer|min:0',
            'rack_location' => 'required|string|max:100',
            'batch_number' => 'required|string|max:100',
            'quantity' => 'required|integer|min:1',
            'expired_date' => 'required|date',
        ]);

        DB::transaction(function () use ($request) {
            $medicine = Medicine::firstOrCreate(
                ['code' => $request->code],
                [
                    'name' => $request->name,
                    'category_id' => $request->category_id,
                    'price' => $request->price,
                    'minimum_stock' => $request->minimum_stock,
                    'rack_location' => $request->rack_location,
                ]
            );

            $medicine->update([
                'name' => $request->name,
                'category_id' => $request->category_id,
                'price' => $request->price,
                'minimum_stock' => $request->minimum_stock,
                'rack_location' => $request->rack_location,
            ]);

            $batch = MedicineBatch::create([
                'medicine_id' => $medicine->id,
                'batch_number' => $request->batch_number,
                'stock' => $request->quantity,
                'expired_date' => $request->expired_date,
            ]);

            // Catat riwayat stok masuk agar terbaca di Laporan Bulanan
            StockMutation::create([
                'medicine_id' => $medicine->id,
                'medicine_batch_id' => $batch->id,
                'type' => 'in',
                'quantity' => $request->quantity,
                'description' => 'Stok Masuk - Batch ' . $request->batch_number,
            ]);
        });

        return redirect()
            ->route('medicines.index')
            ->with('success', 'Stok obat berhasil ditambahkan!');
    }

    // Detail obat
    public function show(Medicine $medicine)
    {
        $medicine->load([
            'category',
            'batches' => function ($query) {
                $query->orderBy('expired_date', 'asc');
            }
        ]);

        return Inertia::render('Medicines/Show', [
            'medicine' => $medicine
        ]);
    }

    // Form edit obat
    public function edit(Medicine $medicine)
    {
        return Inertia::render('Medicines/Edit', [
            'medicine' => $medicine,
            'categories' => Category::all()
        ]);
    }

    // Update data obat
    public function update(Request $request, Medicine $medicine)
    {
        $request->validate([
            'code' => 'required|string|unique:medicines,code,' . $medicine->id,
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'minimum_stock' => 'required|integer|min:0',
            'rack_location' => 'required|string|max:100',
        ]);

        $medicine->update($request->only([
            'code',
            'name',
            'category_id',
            'price',
            'minimum_stock',
            'rack_location'
        ]));

        return redirect()
            ->route('medicines.index')
            ->with('success', 'Data obat berhasil diperbarui.');
    }

    // Hapus obat
    public function destroy(Medicine $medicine)
    {
        $medicine->delete();

        return redirect()
            ->route('medicines.index')
            ->with('success', 'Data obat berhasil dihapus.');
    }
}