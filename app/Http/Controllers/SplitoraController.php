<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\PythonService;

class SplitoraController extends Controller
{
    /**
     * Upload file lalu jalankan Python
     */
    public function process(Request $request, PythonService $python)
    {
        // 1. Validasi dasar
        if (! $request->hasFile('pdf')) {
            return response()->json([
                'success' => false,
                'error' => 'PDF tidak terkirim'
            ], 400);
        }

        // 2. Pastikan folder ada
        Storage::disk('local')->makeDirectory('upload');
        Storage::disk('local')->makeDirectory('tmp');

        // 3. Bersihkan upload lama
        Storage::disk('local')->delete(
            Storage::disk('local')->files('upload')
        );

        // 4. Simpan PDF
        $pdfPath = Storage::disk('local')->putFileAs(
            'upload',
            $request->file('pdf'),
            'input.pdf'
        );

        // 5. Simpan Excel jika mode rename
        $excelPath = null;
        $mode = $request->input('mode', 'split');

        if ($mode === 'rename') {
            if (! $request->hasFile('excel')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Excel wajib untuk mode rename'
                ], 400);
            }

            $excelPath = Storage::disk('local')->putFileAs(
                'upload',
                $request->file('excel'),
                'input.xlsx'
            );
        }

        // 6. Jalankan Python
        $zipPath = $python->run(
            $mode,
            storage_path('app/' . $pdfPath),
            $excelPath ? storage_path('app/' . $excelPath) : null
        );

        // 7. Pastikan ZIP ada
        if (! file_exists($zipPath)) {
            return response()->json([
                'success' => false,
                'error' => 'ZIP tidak ditemukan',
                'path' => $zipPath
            ], 500);
        }

        // 8. Kirim ZIP ke client
        return response()->download(
            $zipPath,
            'splitora-result.zip'
        );
    }

    /**
     * Hapus file sementara (dipanggil saat refresh FE_toggle)
     */
    public function clearTmp()
    {
        Storage::disk('local')->delete(
            Storage::disk('local')->files('tmp')
        );

        return response()->json([
            'success' => true
        ]);
    }
}
