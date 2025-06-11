<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ProductImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $filePath;

    protected User $user;

    // Configuraciones para archivos grandes
    public $timeout = 3600; // 1 hora de timeout

    public $memory = 512; // 512MB de memoria

    public $tries = 3; // 3 intentos en caso de falla

    /**
     * Crea una nueva instancia del Job.
     */
    public function __construct(string $filePath, User $user)
    {
        $this->filePath = $filePath;
        $this->user = $user;
    }

    /**
     * Ejecuta el Job.
     */
    public function handle(): void
    {
        // Incrementar límites de memoria y tiempo para archivos grandes
        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        $createdCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $errors = [];
        $batchSize = 500; // Procesar en lotes de 500 registros

        try {
            // Lee el archivo Excel usando lazy collection para mejor manejo de memoria
            $rows = Excel::toCollection(new \stdClass, $this->filePath)->first()->slice(1);
            $totalRows = $rows->count();

            // Procesar en chunks para evitar problemas de memoria
            $rows->chunk($batchSize)->each(function ($chunk, $chunkIndex) use (&$createdCount, &$updatedCount, &$skippedCount, &$errors, $batchSize) {
                $this->processChunk($chunk, $chunkIndex * $batchSize, $createdCount, $updatedCount, $skippedCount, $errors);

                // Limpiar memoria después de cada chunk
                gc_collect_cycles();
            });

            // Eliminar archivo temporal después del procesamiento
            if (file_exists($this->filePath)) {
                unlink($this->filePath);
            }

            // --- NOTIFICACIÓN DE ÉXITO ---
            $body = "Importación finalizada con éxito. <br>
                     - **{$createdCount}** productos creados <br>
                     - **{$updatedCount}** productos actualizados <br>
                     - **{$skippedCount}** filas omitidas (duplicados sin cambios o errores)";

            if (! empty($errors)) {
                $body .= '<br><br><strong>Primeros errores encontrados:</strong><br><ul>';
                foreach (array_slice($errors, 0, 5) as $error) { // Muestra hasta 5 errores
                    $body .= "<li>{$error}</li>";
                }
                $body .= '</ul>';
            }

            Notification::make()
                ->title('Importación Completada')
                ->body($body)
                ->success()
                ->sendToDatabase($this->user); // Envía la notificación al usuario que inició el proceso

        } catch (Throwable $e) {
            // --- NOTIFICACIÓN DE FALLO INESPERADO ---
            Log::error('Error en importación de productos: '.$e->getMessage(), [
                'file' => $this->filePath,
                'user_id' => $this->user->id,
                'trace' => $e->getTraceAsString(),
            ]);

            Notification::make()
                ->title('Error en la Importación')
                ->body('Ocurrió un error inesperado durante el proceso. Contacta a soporte.')
                ->danger()
                ->sendToDatabase($this->user);

            // Limpiar archivo en caso de error
            if (file_exists($this->filePath)) {
                unlink($this->filePath);
            }
        }
    }

    /**
     * Procesa un chunk de datos para optimizar el uso de memoria
     */
    private function processChunk($chunk, $offset, &$createdCount, &$updatedCount, &$skippedCount, &$errors): void
    {
        $productsToInsert = [];
        $seenSkus = []; // Para detectar duplicados de SKU dentro del chunk
        $seenNames = []; // Para detectar duplicados de nombre dentro del chunk

        foreach ($chunk as $index => $row) {
            $rowNumber = $offset + $index + 2; // El número de fila real en el Excel

            $name = trim($row[0] ?? '');
            $sku = trim($row[1] ?? '');
            $price = trim($row[2] ?? '');

            // --- REGLAS DE VALIDACIÓN ---
            if (empty($sku)) {
                $errors[] = "Fila {$rowNumber}: 'external_sku' no puede estar vacío. Fila omitida.";
                $skippedCount++;

                continue;
            }

            if (empty($name)) {
                $errors[] = "Fila {$rowNumber}: 'name' no puede estar vacío (SKU: {$sku}). Fila omitida.";
                $skippedCount++;

                continue;
            }

            if (! empty($price) && ! is_numeric($price)) {
                $errors[] = "Fila {$rowNumber}: 'price' debe ser un número (SKU: {$sku}). Fila omitida.";
                $skippedCount++;

                continue;
            }

            // --- VERIFICAR DUPLICADOS DENTRO DEL ARCHIVO ---
            if (isset($seenSkus[$sku])) {
                $errors[] = "Fila {$rowNumber}: SKU '{$sku}' ya existe en la fila {$seenSkus[$sku]}. Fila omitida.";
                $skippedCount++;

                continue;
            }

            if (isset($seenNames[$name])) {
                $errors[] = "Fila {$rowNumber}: Nombre '{$name}' ya existe en la fila {$seenNames[$name]}. Fila omitida.";
                $skippedCount++;

                continue;
            }

            // Registrar SKU y nombre como vistos
            $seenSkus[$sku] = $rowNumber;
            $seenNames[$name] = $rowNumber;

            // Preparar datos para inserción masiva
            $productData = [
                'external_sku' => $sku,
                'name' => $name,
                'price' => ! empty($price) ? (float) $price : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $productsToInsert[] = $productData;
        }

        // Usar upsert para inserción/actualización masiva más eficiente
        if (! empty($productsToInsert)) {
            try {
                DB::transaction(function () use ($productsToInsert, &$createdCount, &$updatedCount, &$skippedCount, &$errors) {
                    // Obtener SKUs y nombres existentes para verificar duplicados en la base de datos
                    $skusToCheck = collect($productsToInsert)->pluck('external_sku')->toArray();
                    $namesToCheck = collect($productsToInsert)->pluck('name')->toArray();

                    $existingProducts = Product::whereIn('external_sku', $skusToCheck)
                        ->get(['external_sku', 'name', 'price'])
                        ->keyBy('external_sku');

                    $existingNames = Product::whereIn('name', $namesToCheck)
                        ->pluck('external_sku', 'name')
                        ->toArray();

                    // Separar productos nuevos de actualizaciones
                    $newProducts = [];
                    $updateData = [];

                    foreach ($productsToInsert as $productData) {
                        $sku = $productData['external_sku'];
                        $name = $productData['name'];

                        if ($existingProducts->has($sku)) {
                            // Producto existe por SKU, verificar si necesita actualización
                            $existing = $existingProducts->get($sku);
                            if ($existing->name !== $productData['name'] ||
                                $existing->price != $productData['price']) {

                                // Verificar que el nuevo nombre no esté siendo usado por otro producto
                                if (isset($existingNames[$name]) && $existingNames[$name] !== $sku) {
                                    $errors[] = "SKU {$sku}: No se puede actualizar. El nombre '{$name}' ya está siendo usado por el SKU '{$existingNames[$name]}'.";
                                    $skippedCount++;
                                } else {
                                    $updateData[] = [
                                        'sku' => $sku,
                                        'data' => $productData,
                                    ];
                                }
                            } else {
                                $skippedCount++; // Sin cambios
                            }
                        } else {
                            // Producto nuevo, verificar que ni el SKU ni el nombre existan
                            if (isset($existingNames[$name])) {
                                $errors[] = "SKU {$sku}: No se puede crear. El nombre '{$name}' ya está siendo usado por el SKU '{$existingNames[$name]}'.";
                                $skippedCount++;
                            } else {
                                $newProducts[] = $productData;
                            }
                        }
                    }

                    // Inserción masiva de productos nuevos
                    if (! empty($newProducts)) {
                        // Insertar en chunks más pequeños para evitar problemas de memoria
                        $insertChunks = array_chunk($newProducts, 100);
                        foreach ($insertChunks as $chunk) {
                            try {
                                Product::insert($chunk);
                                $createdCount += count($chunk);
                            } catch (Throwable $e) {
                                // Si falla la inserción masiva, insertar uno por uno
                                foreach ($chunk as $product) {
                                    try {
                                        Product::create($product);
                                        $createdCount++;
                                    } catch (Throwable $e2) {
                                        $errors[] = "Error insertando SKU {$product['external_sku']}: ".$e2->getMessage();
                                        $skippedCount++;
                                    }
                                }
                            }
                        }
                    }

                    // Actualización de productos existentes
                    foreach ($updateData as $update) {
                        try {
                            $updated = Product::where('external_sku', $update['sku'])
                                ->update([
                                    'name' => $update['data']['name'],
                                    'price' => $update['data']['price'],
                                    'updated_at' => $update['data']['updated_at'],
                                ]);

                            if ($updated) {
                                $updatedCount++;
                            } else {
                                $skippedCount++;
                            }
                        } catch (Throwable $e) {
                            $errors[] = "Error actualizando SKU {$update['sku']}: ".$e->getMessage();
                            $skippedCount++;
                        }
                    }
                });
            } catch (Throwable $e) {
                Log::error('Error procesando chunk: '.$e->getMessage());
                // No incrementar skippedCount aquí, ya que se maneja individualmente arriba
                $errors[] = 'Error procesando lote: '.$e->getMessage();
            }
        }
    }
}
