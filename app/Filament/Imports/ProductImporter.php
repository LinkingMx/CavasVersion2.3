<?php

namespace App\Filament\Imports;

use App\Models\Product;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Log;

class ProductImporter extends Importer
{
    protected static ?string $model = Product::class;

    // Esto asegura que la importación funcione correctamente
    protected static bool $shouldRegisterNavigation = false;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('external_sku')
                ->rules(['nullable', 'string', 'max:255']),
        ];
    }

    public function resolveRecord(): ?Product
    {
        try {
            // Log para depuración
            Log::info('ProductImporter resolveRecord called', [
                'data' => $this->data,
            ]);

            // Verificación de datos requeridos
            if (empty($this->data['name'])) {
                Log::error('ProductImporter: Missing required name field');

                return null;
            }

            // Buscar producto existente por SKU si está disponible
            if (! empty($this->data['external_sku'])) {
                $existingProduct = Product::where('external_sku', $this->data['external_sku'])->first();
                if ($existingProduct) {
                    Log::info('ProductImporter: Updating existing product', [
                        'id' => $existingProduct->id,
                        'name' => $this->data['name'],
                    ]);

                    $existingProduct->name = $this->data['name'];
                    $existingProduct->external_sku = $this->data['external_sku'];

                    return $existingProduct;
                }
            }

            // Crear nuevo producto
            Log::info('ProductImporter: Creating new product', [
                'name' => $this->data['name'],
            ]);

            $product = new Product;
            $product->name = $this->data['name'];
            $product->external_sku = $this->data['external_sku'] ?? null;

            return $product;
        } catch (\Exception $e) {
            Log::error('ProductImporter: Error in resolveRecord', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Su importación de productos se ha completado y se '.number_format($import->successful_rows).' '.str('fila')->plural($import->successful_rows).' importadas.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('fila')->plural($failedRowsCount).' no pudieron ser importadas.';
        }

        return $body;
    }
}
