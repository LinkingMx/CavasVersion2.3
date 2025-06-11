<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductImportController extends Controller
{
    /**
     * Permite al usuario descargar la plantilla de Excel para la importación de productos.
     */
    public function downloadTemplate(): BinaryFileResponse
    {
        $path = storage_path('app/templates/product_import_template.xlsx');

        // Verifica que el archivo exista antes de intentar descargarlo
        if (! file_exists($path)) {
            abort(404, 'El archivo de plantilla no fue encontrado.');
        }

        return response()->download($path);
    }
}
