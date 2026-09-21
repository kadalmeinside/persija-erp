<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ImageToolController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/ImageTools/Index');
    }

    public function compress(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg',
            'level' => 'required|string|in:low,medium,high'
        ]);

        try {
            $file = $request->file('file');
            $level = $request->input('level');

            $quality = 75; // Default (low compression, high quality)
            if ($level === 'medium') {
                $quality = 50;
            } elseif ($level === 'high') {
                $quality = 20; // High compression, low quality
            }

            // Create temporary file
            $tempPath = tempnam(sys_get_temp_dir(), 'img_compress_');
            $extension = $file->getClientOriginalExtension();
            $mime = $file->getMimeType();

            if ($mime == 'image/jpeg' || $mime == 'image/jpg') {
                $image = imagecreatefromjpeg($file->getPathname());
                imagejpeg($image, $tempPath, $quality);
            } elseif ($mime == 'image/png') {
                $image = imagecreatefrompng($file->getPathname());
                
                // PNG quality is 0-9. Map JPEG quality (0-100) to PNG (0-9) inverted
                $pngQuality = 9 - round(($quality / 100) * 9);
                imagepng($image, $tempPath, $pngQuality);
            }

            if (isset($image)) {
                imagedestroy($image);
            }

            $content = file_get_contents($tempPath);
            @unlink($tempPath);

            $fileName = 'compressed_' . time() . '.' . $extension;

            return response($content)
                ->header('Content-Type', $mime)
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengompres gambar: ' . $e->getMessage());
        }
    }

    public function upscale(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg',
            'mode' => 'required|string|in:hd,2x'
        ]);

        try {
            $file = $request->file('file');
            $mode = $request->input('mode');

            $sourcePath = $file->getPathname();
            list($origWidth, $origHeight) = getimagesize($sourcePath);
            $mime = $file->getMimeType();
            $extension = $file->getClientOriginalExtension();

            $newWidth = $origWidth;
            $newHeight = $origHeight;

            if ($mode === 'hd') {
                // Upscale to HD width (1920px) if smaller
                if ($origWidth < 1920) {
                    $newWidth = 1920;
                    $newHeight = (int)(($origHeight / $origWidth) * $newWidth);
                }
            } elseif ($mode === '2x') {
                $newWidth = $origWidth * 2;
                $newHeight = $origHeight * 2;
            }

            // Load original image
            $sourceImage = null;
            if ($mime == 'image/jpeg' || $mime == 'image/jpg') {
                $sourceImage = imagecreatefromjpeg($sourcePath);
            } elseif ($mime == 'image/png') {
                $sourceImage = imagecreatefrompng($sourcePath);
            }

            if (!$sourceImage) {
                throw new \Exception("Format gambar tidak didukung oleh prosesor ini.");
            }

            // Create new empty image
            $destinationImage = imagecreatetruecolor($newWidth, $newHeight);

            // Preserve transparency for PNG
            if ($mime == 'image/png') {
                imagealphablending($destinationImage, false);
                imagesavealpha($destinationImage, true);
                $transparent = imagecolorallocatealpha($destinationImage, 255, 255, 255, 127);
                imagefilledrectangle($destinationImage, 0, 0, $newWidth, $newHeight, $transparent);
            }

            // Resize (using bicubic interpolation via imagecopyresampled)
            imagecopyresampled($destinationImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

            $tempPath = tempnam(sys_get_temp_dir(), 'img_upscale_');

            if ($mime == 'image/jpeg' || $mime == 'image/jpg') {
                imagejpeg($destinationImage, $tempPath, 100);
            } elseif ($mime == 'image/png') {
                imagepng($destinationImage, $tempPath, 0); // max quality
            }

            imagedestroy($sourceImage);
            imagedestroy($destinationImage);

            $content = file_get_contents($tempPath);
            @unlink($tempPath);

            $fileName = 'upscaled_' . time() . '.' . $extension;

            return response($content)
                ->header('Content-Type', $mime)
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membesarkan gambar: ' . $e->getMessage());
        }
    }
}
