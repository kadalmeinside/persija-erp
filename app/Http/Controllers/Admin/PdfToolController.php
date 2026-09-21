<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use iio\libmergepdf\Merger;
use setasign\Fpdi\Tcpdf\Fpdi;

class PdfToolController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/PdfTools/Index');
    }

    public function merge(Request $request)
    {
        $request->validate([
            'files' => 'required|array|min:2',
            'files.*' => 'required|file|mimes:pdf',
        ]);

        try {
            $merger = new Merger();
            foreach ($request->file('files') as $file) {
                $merger->addFile($file->getPathname());
            }

            $createdPdf = $merger->merge();

            $fileName = 'merged_' . time() . '.pdf';
            
            return response($createdPdf)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menggabungkan PDF: ' . $e->getMessage());
        }
    }

    public function split(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf',
            'pages' => 'required|string'
        ]);

        try {
            $file = $request->file('file');
            $pagesString = $request->input('pages');
            
            // Parse pages string e.g., "1-3, 5, 7-9"
            $pagesToExtract = $this->parsePageRanges($pagesString);
            if (empty($pagesToExtract)) {
                throw new \Exception("Format halaman tidak valid.");
            }

            $pdf = new Fpdi();
            $pageCount = $pdf->setSourceFile($file->getPathname());

            foreach ($pagesToExtract as $pageNo) {
                if ($pageNo > 0 && $pageNo <= $pageCount) {
                    $templateId = $pdf->importPage($pageNo);
                    $size = $pdf->getTemplateSize($templateId);

                    $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $pdf->useTemplate($templateId);
                }
            }

            $fileName = 'split_' . time() . '.pdf';
            
            // 'S' means return as string in TCPDF/FPDI
            $pdfContent = $pdf->Output($fileName, 'S');

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memisahkan PDF: ' . $e->getMessage());
        }
    }

    private function parsePageRanges($string)
    {
        $pages = [];
        $parts = explode(',', $string);
        foreach ($parts as $part) {
            $part = trim($part);
            if (strpos($part, '-') !== false) {
                [$start, $end] = explode('-', $part);
                $start = (int)$start;
                $end = (int)$end;
                if ($start > 0 && $end >= $start) {
                    for ($i = $start; $i <= $end; $i++) {
                        $pages[] = $i;
                    }
                }
            } else {
                $val = (int)$part;
                if ($val > 0) {
                    $pages[] = $val;
                }
            }
        }
        return array_unique($pages);
    }

    public function convertImageToPdf(Request $request)
    {
        $request->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'required|image|mimes:jpeg,png,jpg',
        ]);

        try {
            $pdf = new \TCPDF();
            
            // Remove default header and footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetAutoPageBreak(false, 0);

            foreach ($request->file('files') as $file) {
                // Get image dimensions
                $size = getimagesize($file->getPathname());
                if ($size === false) {
                    continue;
                }

                $width = $size[0];
                $height = $size[1];
                
                // TCPDF uses millimeters by default. Convert pixels to mm.
                // Assuming 72 DPI (1 pixel = 0.352777778 mm)
                $widthMm = $width * 25.4 / 72;
                $heightMm = $height * 25.4 / 72;

                // Determine orientation based on dimensions
                $orientation = ($width > $height) ? 'L' : 'P';
                
                $pdf->AddPage($orientation, [$widthMm, $heightMm]);
                
                // Image(file, x, y, w, h, type, link, align, resize, dpi, align)
                $pdf->Image($file->getPathname(), 0, 0, $widthMm, $heightMm, '', '', '', false, 300, '', false, false, 0);
            }

            $fileName = 'converted_' . time() . '.pdf';
            $pdfContent = $pdf->Output($fileName, 'S');

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengonversi gambar ke PDF: ' . $e->getMessage());
        }
    }

    public function convertWordToPdf(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:doc,docx',
        ]);

        try {
            $file = $request->file('file');
            
            // Set PDF Renderer for PHPWord (menggunakan TCPDF yang sudah ada)
            \PhpOffice\PhpWord\Settings::setPdfRendererPath(base_path('vendor/tecnickcom/tcpdf'));
            \PhpOffice\PhpWord\Settings::setPdfRendererName('TCPDF');

            // Load Word document
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($file->getPathname());

            // Temporary file untuk menyimpan hasil PDF
            $tempPdfPath = tempnam(sys_get_temp_dir(), 'word_to_pdf_') . '.pdf';
            
            // Save as PDF
            $pdfWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'PDF');
            $pdfWriter->save($tempPdfPath);

            $fileName = 'converted_word_' . time() . '.pdf';
            $pdfContent = file_get_contents($tempPdfPath);
            
            // Hapus file sementara
            @unlink($tempPdfPath);

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengonversi Word ke PDF: Sebagian format yang kompleks mungkin tidak didukung penuh. Pesan sistem: ' . $e->getMessage());
        }
    }
}
