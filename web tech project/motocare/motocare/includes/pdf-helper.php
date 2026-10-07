<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: includes/pdf-helper.php
 * Stage 2: Lightweight Standalone Pure-PHP PDF Generation Engine
 * Generates 100% standard PDF 1.4 documents without third-party frameworks.
 * ============================================================================
 */

class MotoCarePDF {
    protected $pageWidth = 595.28;  // A4 width in pt (72 dpi)
    protected $pageHeight = 841.89; // A4 height in pt
    protected $margin = 40.0;
    protected $x;
    protected $y;
    protected $stream = '';
    protected $objects = [];
    protected $currentFont = 'F1';
    protected $currentFontSize = 10;
    protected $title = 'MotoCare Invoice';

    public function __construct($title = 'MotoCare Invoice') {
        $this->title = $title;
        $this->x = $this->margin;
        $this->y = $this->pageHeight - $this->margin; // PDF origin is bottom-left, we use top-down coordinates
    }

    public function getStream() {
        return $this->stream;
    }

    // Set font: 'regular' or 'bold'
    public function setFont($style = 'regular', $size = 10) {
        $this->currentFont = (strtolower($style) === 'bold') ? 'F2' : 'F1';
        $this->currentFontSize = $size;
        $this->stream .= sprintf("/%s %F Tf\n", $this->currentFont, $size);
    }

    // Set text color (RGB 0.0 - 1.0)
    public function setTextColor($r, $g, $b) {
        $this->stream .= sprintf("%F %F %F rg\n", $r, $g, $b);
    }

    // Set line/stroke color (RGB 0.0 - 1.0)
    public function setStrokeColor($r, $g, $b) {
        $this->stream .= sprintf("%F %F %F RG\n", $r, $g, $b);
    }

    // Set fill color
    public function setFillColor($r, $g, $b) {
        $this->stream .= sprintf("%F %F %F rg\n", $r, $g, $b);
    }

    // Draw horizontal line
    public function drawLine($x1, $y, $x2, $lineWidth = 0.75) {
        $pdfY = $this->pageHeight - $y;
        $this->stream .= sprintf("%F w\n%F %F m\n%F %F l\nS\n", $lineWidth, $x1, $pdfY, $x2, $pdfY);
    }

    // Draw filled rectangle
    public function drawRect($x, $y, $w, $h, $fill = true, $stroke = false) {
        $pdfY = $this->pageHeight - ($y + $h);
        $op = ($fill && $stroke) ? 'B' : ($fill ? 'f' : 'S');
        $this->stream .= sprintf("%F %F %F %F re\n%s\n", $x, $pdfY, $w, $h, $op);
    }

    // Write text at position
    public function text($x, $y, $text) {
        $pdfY = $this->pageHeight - $y;
        // Escape special PDF characters: (, ), \
        $safeText = strtr($text, [
            '\\' => '\\\\',
            '(' => '\\(',
            ')' => '\\)',
            '₹' => 'Rs. ' // Standard ASCII fallback for Type1 font compatibility
        ]);
        $this->stream .= sprintf("BT\n/%s %F Tf\n%F %F Td\n(%s) Tj\nET\n", 
            $this->currentFont, $this->currentFontSize, $x, $pdfY, $safeText);
    }

    // Write text right-aligned
    public function textRight($rightX, $y, $text) {
        // Approximate width for Helvetica characters (~0.52 * font size)
        $approxWidth = strlen($text) * ($this->currentFontSize * 0.52);
        $this->text($rightX - $approxWidth, $y, $text);
    }

    // Output complete binary PDF content
    public function render() {
        $content = $this->stream;
        $contentLen = strlen($content);

        $out = "%PDF-1.4\n";
        $out .= "%\xE2\xE3\xCF\xD3\n";

        $offsets = [];

        // 1 0 obj: Catalog
        $offsets[1] = strlen($out);
        $out .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        // 2 0 obj: Pages
        $offsets[2] = strlen($out);
        $out .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        // 3 0 obj: Page
        $offsets[3] = strlen($out);
        $out .= sprintf("3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %F %F] /Contents 4 0 R /Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> >>\nendobj\n",
            $this->pageWidth, $this->pageHeight);

        // 4 0 obj: Stream content
        $offsets[4] = strlen($out);
        $out .= sprintf("4 0 obj\n<< /Length %d >>\nstream\n%sendstream\nendobj\n", $contentLen, $content);

        // 5 0 obj: Font F1 (Helvetica regular)
        $offsets[5] = strlen($out);
        $out .= "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>\nendobj\n";

        // 6 0 obj: Font F2 (Helvetica bold)
        $offsets[6] = strlen($out);
        $out .= "6 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>\nendobj\n";

        // Xref table
        $xrefOffset = strlen($out);
        $out .= "xref\n0 7\n";
        $out .= "0000000000 65535 f \n";
        for ($i = 1; $i <= 6; $i++) {
            $out .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        // Trailer
        $out .= "trailer\n";
        $out .= sprintf("<< /Size 7 /Root 1 0 R /Info << /Title (%s) /Producer (MotoCare Invoicing Engine) >> >>\n", 
            strtr($this->title, ['(' => '\\(', ')' => '\\)']));
        $out .= "startxref\n";
        $out .= sprintf("%d\n", $xrefOffset);
        $out .= "%%EOF\n";

        return $out;
    }
}
