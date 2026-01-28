<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class PythonService
{
    public function run(string $mode, string $pdfPath, ?string $excelPath = null): string
    {
        // Python binary di Linux container
        $pythonBinary = 'python3';

        // Path script Python (HARUS sesuai struktur repo)
        $scriptPath = base_path('app/python/split.py');

        if (! file_exists($scriptPath)) {
            throw new \RuntimeException("Script Python tidak ditemukan: {$scriptPath}");
        }

        if (! file_exists($pdfPath)) {
            throw new \RuntimeException("PDF tidak ditemukan: {$pdfPath}");
        }

        $command = [
            $pythonBinary,
            $scriptPath,
            $mode,
            $pdfPath,
        ];

        if ($mode === 'rename' && $excelPath) {
            if (! file_exists($excelPath)) {
                throw new \RuntimeException("Excel tidak ditemukan: {$excelPath}");
            }
            $command[] = $excelPath;
        }

        $process = new Process($command);
        $process->setWorkingDirectory(base_path());
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException(
                "Python error:\n" . $process->getErrorOutput()
            );
        }

        $zipPath = trim($process->getOutput());

        if ($zipPath === '' || ! file_exists($zipPath)) {
            throw new \RuntimeException(
                "Python tidak mengembalikan path ZIP.\nOutput:\n" . $zipPath
            );
        }

        return $zipPath;
    }
}
