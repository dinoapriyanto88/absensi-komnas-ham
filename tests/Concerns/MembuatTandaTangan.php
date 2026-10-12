<?php

namespace Tests\Concerns;

trait MembuatTandaTangan
{
    protected function tandaTanganValid(): string
    {
        $im = $this->kanvasTransparan();
        imagealphablending($im, true);
        $hitam = imagecolorallocate($im, 20, 20, 20);
        imagesetthickness($im, 4);
        imageline($im, 20, 120, 380, 30, $hitam);
        imageline($im, 20, 30, 380, 120, $hitam);

        return $this->keDataUrl($im);
    }

    protected function tandaTanganKosong(): string
    {
        return $this->keDataUrl($this->kanvasTransparan());
    }

    private function kanvasTransparan(): \GdImage
    {
        $im = imagecreatetruecolor(400, 150);
        imagesavealpha($im, true);
        imagealphablending($im, false);
        imagefill($im, 0, 0, imagecolorallocatealpha($im, 255, 255, 255, 127));

        return $im;
    }

    private function keDataUrl(\GdImage $im): string
    {
        ob_start();
        imagepng($im);
        $png = (string) ob_get_clean();
        imagedestroy($im);

        return 'data:image/png;base64,'.base64_encode($png);
    }
}
