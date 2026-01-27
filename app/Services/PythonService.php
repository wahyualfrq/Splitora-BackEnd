<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class PythonService
{
    public function run(string $mode, string $pdfPath, ?string $excelPath = null): string
    {
        // WAJIB: python.exe BUKAN installer
        $pythonBinary = 'C:\\Users\\User\\AppData\\Local\\Programs\\Python\\Python314\\python.exe';

        $scriptPath = base_path('python/process.py');

        $command = [
            $pythonBinary,
            $scriptPath,
            $mode,
            $pdfPath,
        ];

        if ($mode === 'rename' && $excelPath) {
            $command[] = $excelPath;
        }

        $process = new Process($command);
        $process->setWorkingDirectory(base_path());
        $process->setTimeout(300);
        $process->run();

        // Ambil output Python (path ZIP)
        $output = trim($process->getOutput());

        // Validasi hasil
        if ($output === '' || ! file_exists($output)) {
            throw new \RuntimeException(
                "Python gagal menghasilkan ZIP.\nSTDERR:\n" .
                $process->getErrorOutput()
            );
        }

        return $output;
    }
}
