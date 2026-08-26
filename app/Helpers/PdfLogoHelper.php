<?php

namespace App\Helpers;

class PdfLogoHelper
{
    public static function buildLogoData(): array
    {
        $logoPath = public_path('logoSemPalavra.png');
        $logoDataUri = null;
        $hasGd = extension_loaded('gd');

        if (file_exists($logoPath) && $hasGd) {
            try {
                $logoDataUri = self::toJpegDataUri($logoPath);
            } catch (\Throwable $e) {
                $logoDataUri = null;
            }
        }

        $emitidoEm = now()->format('d/m/Y H:i');

        return [
            'logoDataUri' => $logoDataUri,
            'emitidoEm' => $emitidoEm,
        ];
    }

    private static function toJpegDataUri(string $pngPath): string
    {
        $src = @imagecreatefrompng($pngPath);
        if (!$src) {
            throw new \RuntimeException('Não foi possível ler PNG do logo.');
        }

        $sx = imagesx($src);
        $sy = imagesy($src);

        $dst = imagecreatetruecolor($sx, $sy);
        $branco = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $branco);
        imagealphablending($dst, true);
        imagecopy($dst, $src, 0, 0, 0, 0, $sx, $sy);

        ob_start();
        imagejpeg($dst, null, 92);
        $jpegBytes = ob_get_clean();

        if (PHP_MAJOR_VERSION < 8) {
            imagedestroy($src);
            imagedestroy($dst);
        }

        if ($jpegBytes === false || $jpegBytes === '') {
            throw new \RuntimeException('Falha ao converter logo para JPEG.');
        }

        return 'data:image/jpeg;base64,' . base64_encode($jpegBytes);
    }
}
