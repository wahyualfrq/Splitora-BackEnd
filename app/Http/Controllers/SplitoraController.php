<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PythonService;

class SplitoraController extends Controller
{
    public function process(Request $request, PythonService $python)
    {
        // 1. Validasi PDF
        if (! $request->hasFile('pdf')) {
            return response()->json([
                'success' => false,
                'error' => 'PDF tidak terkirim'
            ], 400);
        }

        // 2. Normalisasi & validasi mode
        $mode = strtolower(trim($request->input('mode', 'split')));

        if (! in_array($mode, ['split', 'rename'], true)) {
            return response()->json([
                'success' => false,
                'error' => 'Mode tidak valid'
            ], 400);
        }

        // 3. Siapkan folder
        $uploadDir = storage_path('app/upload');
        $tmpDir = storage_path('app/tmp');

        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        // 4. Bersihkan file lama
        foreach (glob($uploadDir . '/*') as $file) {
            if (is_file($file)) unlink($file);
        }

        foreach (glob($tmpDir . '/*') as $file) {
            if (is_file($file)) unlink($file);
        }

        // 5. Simpan PDF
        $pdfPath = $uploadDir . '/input.pdf';
        $request->file('pdf')->move($uploadDir, 'input.pdf');

        // 6. Simpan Excel jika rename
        $excelPath = null;

        if ($mode === 'rename') {
            if (! $request->hasFile('excel')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Excel wajib untuk mode rename'
                ], 400);
            }

            $excelPath = $uploadDir . '/input.xlsx';
            $request->file('excel')->move($uploadDir, 'input.xlsx');
        }

        // 7. Jalankan Python
        $zipPath = $python->run(
            $mode,
            $pdfPath,
            $mode === 'rename' ? $excelPath : null
        );

        // 8. Validasi ZIP
        if (! $zipPath || ! file_exists($zipPath)) {
            return response()->json([
                'success' => false,
                'error' => 'ZIP tidak ditemukan',
                'debug' => [
                    'mode' => $mode,
                    'zipPath' => $zipPath
                ]
            ], 500);
        }

        return response()->download($zipPath, 'splitora-result.zip');
    }

    public function clearTmp()
    {
        $tmpDir = storage_path('app/tmp');

        if (is_dir($tmpDir)) {
            foreach (glob($tmpDir . '/*') as $file) {
                if (is_file($file)) unlink($file);
            }
        }

        return response()->json(['success' => true]);
    }
}
