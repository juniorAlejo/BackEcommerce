<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;

class ImageProcessor
{
    /**
     * Procesa una imagen subida: la redimensiona (máx 1600px de ancho),
     * la convierte a WebP con calidad 82 y la guarda en el disco "public".
     * Retorna la ruta relativa guardada.
     */
    public static function processAndStore($file, string $folder): string
    {
        try {
            // Crear manager con driver GD
            $manager = new ImageManager(new Driver());
            
            // Leer la imagen desde el archivo subido usando decodePath
            $image = $manager->decodePath($file->getRealPath());

            // Redimensionar si es necesario
            if ($image->width() > 1600) {
                $image->scaleDown(width: 1600);
            }

            // Generar nombre único
            $filename = $folder . '/' . uniqid('img_', true) . '.webp';

            // Asegurar que el directorio existe
            $publicPath = storage_path('app/public');
            $folderPath = $publicPath . DIRECTORY_SEPARATOR . $folder;
            if (!is_dir($folderPath)) {
                mkdir($folderPath, 0755, true);
            }

            // Guardar como WebP con calidad 82
            $filePath = $publicPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filename);
            $image->encodeUsingFormat(Format::WEBP, 82)->save($filePath);

            return $filename;
        } catch (\Exception $e) {
            \Log::error('Error en ImageProcessor::processAndStore', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }
}