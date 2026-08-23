<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Generates a professional PNG invoice using PHP GD.
 * Fixed canvas size: 1200px wide. Height grows with content.
 */
class InvoiceService
{
    const INVOICE_DIR = 'invoices'; // storage/app/public/invoices/
    const W = 1200;
    const MARGIN = 60;

    // Brand palette
    const NAVY   = [0x0B, 0x2A, 0x5B];
    const GOLD   = [0xE8, 0xA3, 0x17];
    const GOLD_T = [0xFF, 0xDA, 0x85];
    const CREAM  = [0xFF, 0xF9, 0xEC];
    const INK    = [0x14, 0x2C, 0x52];
    const MUTED  = [0x7A, 0x85, 0x99];
    const LINE   = [0xE6, 0xEB, 0xF3];
    const GREEN  = [0x22, 0xC5, 0x5E];
    const WHITE  = [0xFF, 0xFF, 0xFF];

    public function generate(Order $order): string
    {
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagepng')) {
            throw new \RuntimeException('PHP GD is not enabled, so a receipt image cannot be created.');
        }

        $order->loadMissing(['user', 'service', 'provider']);

        $rows = $this->buildRows($order);

        // Measure rows
        $rowH = 44;
        $sectionH = 48;
        $sectionGap = 18;
        $headerH = 360;           // white header area (logo + invoice label + meta)
        $footerH = 120;
        $contentStart = $headerH + 10;
        $contentH = 0;
        foreach ($rows as $r) {
            $contentH += !empty($r['section']) ? $sectionH + $sectionGap : $rowH;
        }
        $h = $contentStart + $contentH + $footerH;

        $img = imagecreatetruecolor(self::W, $h);
        // Turn off alpha for a fully-opaque canvas (the invoice has no
        // transparency — disabling savealpha prevents GD's resampler from
        // leaving faint dark/halo pixels along resized-image edges and
        // the top edge of the canvas).
        imagealphablending($img, true);
        imagesavealpha($img, false);

        $alloc = function (array $rgb) use ($img) {
            return imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
        };
        $navy  = $alloc(self::NAVY);
        $gold  = $alloc(self::GOLD);
        $goldT = $alloc(self::GOLD_T);
        $ink   = $alloc(self::INK);
        $muted = $alloc(self::MUTED);
        $line  = $alloc(self::LINE);
        $green = $alloc(self::GREEN);
        $white = $alloc(self::WHITE);

        // Solid white base — fill the entire canvas FIRST (opaque, no alpha)
        imagefilledrectangle($img, 0, 0, self::W - 1, $h - 1, $white);

        // Left gold accent bar (full height, 10px wide: x 0..9)
        imagefilledrectangle($img, 0, 0, 9, $h - 1, $gold);

        // ---- CLEAN WHITE HEADER ----
        // Logo on white (left), no navy band behind it — logo's navy parts
        // and grungy edges were designed for a white background, so they
        // only look crisp against white.
        $logoPath = public_path('assets/logo.png');
        $logoTargetH = 190;
        $logoX = self::MARGIN;
        $logoY = 50; // extra top padding so the resample kernel can't paint above the canvas
        $logoW = 0;
        if ($this->safeIsFile($logoPath)) {
            $logo = $this->loadImage($logoPath);
            if ($logo) {
                imagealphablending($logo, true);
                imagesavealpha($logo, true);
                $lw = imagesx($logo);
                $lh = imagesy($logo);
                $scale = $logoTargetH / $lh;
                $logoW = (int)($lw * $scale);

                // Render the scaled logo onto a TEMPORARY solid-white image
                // first. This guarantees that any resample-kernel overshoot
                // above/beside the source logo lands on white rather than
                // leaving a faint dark halo against the main canvas.
                $tmp = imagecreatetruecolor($logoW, $logoTargetH);
                imagealphablending($tmp, false);
                imagesavealpha($tmp, false);
                imagefilledrectangle($tmp, 0, 0, $logoW - 1, $logoTargetH - 1, $white);
                imagealphablending($tmp, true);
                imagecopyresampled($tmp, $logo, 0, 0, 0, 0, $logoW, $logoTargetH, $lw, $lh);
                imagedestroy($logo);

                // Wipe target pocket to white, then paste the flattened logo
                imagefilledrectangle(
                    $img, $logoX, $logoY,
                    $logoX + $logoW - 1, $logoY + $logoTargetH - 1,
                    $white
                );
                imagecopy($img, $tmp, $logoX, $logoY, 0, 0, $logoW, $logoTargetH);
                imagedestroy($tmp);
            }
        }

        // Right side: INVOICE label
        $invoiceLabelY = 75;
        $this->text($img, 'RECEIPT', self::W - self::MARGIN, $invoiceLabelY, 48, $navy, true, 'right');
        // Gold underline under INVOICE
        $ulW = 120;
        $ulH = 5;
        $ulX = self::W - self::MARGIN - $ulW;
        $ulY = $invoiceLabelY + 62;
        imagefilledrectangle($img, $ulX, $ulY, $ulX + $ulW, $ulY + $ulH, $gold);

        // Small tagline under the gold underline (right-aligned)
        $this->text($img, 'Fast · Secure · Sri Lanka', self::W - self::MARGIN, $ulY + 18, 12, $muted, false, 'right');

        // Gold divider across full width below header (starts AFTER the 10px gold bar)
        $divY = 270;
        imagefilledrectangle($img, 10, $divY, self::W - 1, $divY + 4, $gold);

        // Reference / date (left side, under divider)
        $this->text($img, $order->reference, self::MARGIN, $divY + 25, 22, $navy, true);
        $this->text($img, 'Issued ' . $order->created_at->timezone('Asia/Colombo')->format('d M Y · h:i A'), self::MARGIN, $divY + 60, 13, $muted);

        // PAID badge (right side, aligned with reference row)
        $badgeW = 160;
        $badgeH = 56;
        $badgeX = self::W - self::MARGIN - $badgeW;
        $badgeY = $divY + 22;
        $badgeColor = $order->status === 'success' ? $green : $gold;
        $badgeText  = $order->status === 'success' ? 'PAID' : strtoupper($order->status);
        $this->roundRect($img, $badgeX, $badgeY, $badgeX + $badgeW, $badgeY + $badgeH, 14, $badgeColor);
        // Pass geometric center of the badge and use vCenter=true for perfect centering
        $badgeCenterX = $badgeX + (int)($badgeW / 2);
        $badgeCenterY = $badgeY + (int)($badgeH / 2);
        $this->text($img, $badgeText, $badgeCenterX, $badgeCenterY, 22, $white, true, 'center', true);

        // Soft divider between header meta and content
        imagefilledrectangle($img, self::MARGIN, $contentStart - 10, self::W - self::MARGIN, $contentStart - 9, $line);

        // Rows
        $y = $contentStart + 25;
        $valueX = self::MARGIN + 420;

        foreach ($rows as $r) {
            if (!empty($r['section'])) {
                $this->text($img, $r['section'], self::MARGIN, $y, 18, $navy, true);
                $y += $sectionH;
                imagefilledrectangle($img, self::MARGIN, $y - $sectionGap - 4, self::W - self::MARGIN, $y - $sectionGap - 2, $line);
                continue;
            }
            $bold = !empty($r['bold']);
            $this->text($img, $r['label'], self::MARGIN, $y, 14, $muted);
            $this->text($img, $r['value'], $valueX, $y, 14, $ink, $bold);
            $y += $rowH;
        }

        // Footer gold bar
        imagefilledrectangle($img, 0, $h - 8, self::W, $h, $gold);
        $fy = $h - $footerH + 20;
        $this->text($img, 'Thank you for your recharge!', self::MARGIN, $fy + 20, 18, $navy, true);
        $this->text($img, 'This is a computer-generated receipt. For support, contact support@happypratheep.lk', self::MARGIN, $fy + 60, 12, $muted);

        // Save — Laravel storage first, then public/storage (DirectAdmin).
        $name = $order->reference . '.png';
        $rel = self::INVOICE_DIR . '/' . $name;
        $written = null;
        foreach ($this->writeTargets($rel) as $abs) {
            try {
                File::ensureDirectoryExists(dirname($abs));
                if (! $this->safeIsDir(dirname($abs)) || ! $this->safeIsWritable(dirname($abs))) {
                    continue;
                }
                if (imagepng($img, $abs) && $this->safeIsFile($abs) && @filesize($abs) > 100) {
                    $written = $abs;
                    break;
                }
            } catch (\Throwable $e) {
                Log::warning('Receipt image write failed at '.$abs.': '.$e->getMessage());
            }
        }
        imagedestroy($img);

        if (! $written) {
            throw new \RuntimeException('Could not write the receipt image file.');
        }

        $order->invoice_path = $rel;
        $order->save();
        $this->publishPublicCopy($order);

        return $rel;
    }

    public function relativePath(Order $order): string
    {
        if ($order->invoice_path) {
            return ltrim((string) $order->invoice_path, '/');
        }

        return self::INVOICE_DIR . '/' . $order->reference . '.png';
    }

    /** Every place the PNG might already live (Laravel storage or public_html/storage). */
    public function candidatePaths(Order $order): array
    {
        $rels = array_unique(array_filter([
            $order->invoice_path ? ltrim((string) $order->invoice_path, '/') : null,
            self::INVOICE_DIR . '/' . $order->reference . '.png',
        ]));

        $paths = [];
        foreach ($rels as $rel) {
            $paths[] = storage_path('app/public/' . $rel);
            $paths[] = public_path('storage/' . $rel);
        }

        return array_values(array_unique($paths));
    }

    public function absolutePath(Order $order): ?string
    {
        foreach ($this->candidatePaths($order) as $path) {
            if ($this->safeIsFile($path) && @filesize($path) > 100) {
                return $path;
            }
        }

        return null;
    }

    public function fileIsReady(Order $order): bool
    {
        return $this->absolutePath($order) !== null;
    }

    /** Make the PNG even if the public/storage symlink is missing (DirectAdmin). */
    public function ensureGenerated(Order $order): ?string
    {
        if ($order->status !== Order::STATUS_SUCCESS && $order->status !== 'success') {
            return null;
        }

        if ($this->fileIsReady($order)) {
            if (! $order->invoice_path) {
                $order->invoice_path = $this->relativePath($order);
                $order->save();
            }
            $this->publishPublicCopy($order);

            return $order->invoice_path;
        }

        return $this->generate($order);
    }

    public function publishPublicCopy(Order $order): void
    {
        $abs = $this->absolutePath($order);
        if (! $abs) {
            return;
        }

        $dest = public_path('storage/' . $this->relativePath($order));
        if ($this->sameFile($abs, $dest)) {
            return;
        }

        try {
            File::ensureDirectoryExists(dirname($dest));
            if (! $this->safeIsDir(dirname($dest)) || ! $this->safeIsWritable(dirname($dest))) {
                return;
            }
            if (! $this->safeIsFile($dest) || @filesize($dest) !== @filesize($abs)) {
                File::copy($abs, $dest);
            }
        } catch (\Throwable $e) {
            // Broken public/storage symlink on DirectAdmin must not kill the receipt.
            Log::warning('Could not copy receipt into public/storage: '.$e->getMessage());
        }
    }

    protected function writeTargets(string $rel): array
    {
        $rel = ltrim($rel, '/');

        return array_values(array_unique([
            storage_path('app/public/' . $rel),
            public_path('storage/' . $rel),
        ]));
    }

    protected function sameFile(string $a, string $b): bool
    {
        try {
            return @realpath($a) && @realpath($a) === @realpath($b);
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function safeIsFile(string $path): bool
    {
        if ($path === '') {
            return false;
        }
        try {
            return @is_file($path);
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function safeIsDir(string $path): bool
    {
        if ($path === '') {
            return false;
        }
        try {
            return @is_dir($path);
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function safeIsWritable(string $path): bool
    {
        if ($path === '') {
            return false;
        }
        try {
            return @is_writable($path);
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function buildRows(Order $order): array
    {
        $u = $order->user;
        $rows = [];
        $rows[] = ['section' => 'Customer'];
        $rows[] = ['label' => 'Name',             'value' => $u->name ?? '—'];
        $rows[] = ['label' => 'Email',            'value' => $u->email ?? '—'];
        $rows[] = ['label' => 'Mobile / Account', 'value' => $order->account_number];

        $rows[] = ['section' => 'Order Details'];
        $rows[] = ['label' => 'Service',          'value' => $order->service->name ?? '—'];
        // Do NOT expose third-party provider names to customers — show the brand instead.
        $rows[] = ['label' => 'Processed via',    'value' => 'Happy Pratheep Recharge'];
        if ($order->notify_number && $order->notify_number !== $order->account_number) {
            $rows[] = ['label' => 'Notify Number','value' => $order->notify_number];
        }
        $rows[] = ['label' => 'Status',           'value' => ucfirst($order->status)];
        $rows[] = ['label' => 'Provider Ref',     'value' => $order->provider_txn_id ?? '—'];
        $rows[] = ['label' => 'Order Ref',        'value' => $order->reference];
        $rows[] = ['label' => 'Date & Time',      'value' => ($order->completed_at ?: $order->created_at)->timezone('Asia/Colombo')->format('d M Y, h:i A')];

        $rows[] = ['section' => 'Payment Summary'];
        $rows[] = ['label' => 'Recharge Amount',  'value' => 'LKR ' . number_format((float) $order->amount, 2)];
        if ((float) $order->profit > 0) {
            $rows[] = ['label' => 'Cashback Earned', 'value' => 'LKR ' . number_format((float) $order->profit, 2)];
        }
        $rows[] = ['label' => 'Total',            'value' => 'LKR ' . number_format((float) $order->amount, 2), 'bold' => true];
        return $rows;
    }

    // -------- GD text helper (TTF with built-in fallback) --------

    protected static ?string $_fontReg = null;
    protected static ?string $_fontBold = null;

    protected function findFont(bool $bold): ?string
    {
        if ($bold && static::$_fontBold !== null) return static::$_fontBold;
        if (!$bold && static::$_fontReg !== null) return static::$_fontReg;

        // Prefer fonts shipped in the repo. DirectAdmin open_basedir blocks
        // /usr/share/fonts — a bare file_exists() there becomes an ErrorException
        // in Laravel and used to abort the whole receipt image.
        $candidates = $bold
            ? [
                resource_path('fonts/DejaVuSans-Bold.ttf'),
                base_path('resources/fonts/DejaVuSans-Bold.ttf'),
                public_path('fonts/DejaVuSans-Bold.ttf'),
                'C:/Windows/Fonts/arialbd.ttf',
                'C:/Windows/Fonts/calibrib.ttf',
              ]
            : [
                resource_path('fonts/DejaVuSans.ttf'),
                base_path('resources/fonts/DejaVuSans.ttf'),
                public_path('fonts/DejaVuSans.ttf'),
                'C:/Windows/Fonts/arial.ttf',
                'C:/Windows/Fonts/calibri.ttf',
              ];

        $found = null;
        foreach ($candidates as $p) {
            if ($this->safeIsFile($p)) {
                $found = $p;
                break;
            }
        }

        if ($bold) static::$_fontBold = $found;
        else       static::$_fontReg  = $found;
        return $found;
    }

    protected function text($img, string $text, int $x, int $y, int $px, $color, bool $bold = false, string $align = 'left', bool $vCenter = false): void
    {
        $pt = $px * 0.75;
        $f = $bold ? $this->findFont(true) : $this->findFont(false);
        if ($f && function_exists('imagettftext')) {
            // imagettfbbox returns 8 values [xll,yll,xlr,ylr,xur,yur,xul,yul]
            // with y=0 being the BASELINE (negative y = above baseline, positive = below).
            $bbox = imagettfbbox($pt, 0, $f, $text);
            if ($bbox) {
                $textW = $bbox[2] - $bbox[0];

                // Horizontal alignment
                if ($align === 'right') {
                    $drawX = $x - $textW - $bbox[0];
                } elseif ($align === 'center') {
                    $drawX = $x - (int)($textW / 2) - $bbox[0];
                } else {
                    $drawX = $x - $bbox[0];
                }

                if ($vCenter) {
                    // $y is the visual CENTER of where the text should sit.
                    $topY = min($bbox[1], $bbox[3], $bbox[5], $bbox[7]);
                    $botY = max($bbox[1], $bbox[3], $bbox[5], $bbox[7]);
                    $baseline = (int)($y - ($topY + $botY) / 2);
                } else {
                    // Preserve original semantics (matches existing layout):
                    // $y is roughly the top of the text.
                    $th = $bbox[1] - $bbox[7];
                    $bl = $bbox[1];
                    $baseline = $y + ($th - $bl);
                }
                imagettftext($img, $pt, 0, $drawX, $baseline, $color, $f, $text);
                return;
            }
        }
        // Fallback: built-in GD font
        $fontId = $bold ? 5 : 3;
        $tw = imagefontwidth($fontId) * strlen($text);
        $th = imagefontheight($fontId);
        if ($align === 'right') $x = $x - $tw;
        elseif ($align === 'center') $x = $x - (int)($tw / 2);
        $drawY = $vCenter ? (int)($y - $th / 2) : $y;
        imagestring($img, $fontId, $x, $drawY, $text, $color);
    }

    protected function roundRect($img, int $x1, int $y1, int $x2, int $y2, int $r, $color): void
    {
        imagefilledrectangle($img, $x1 + $r, $y1, $x2 - $r, $y2, $color);
        imagefilledrectangle($img, $x1, $y1 + $r, $x2, $y2 - $r, $color);
        imagefilledellipse($img, $x1 + $r, $y1 + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($img, $x2 - $r, $y1 + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($img, $x1 + $r, $y2 - $r, $r * 2, $r * 2, $color);
        imagefilledellipse($img, $x2 - $r, $y2 - $r, $r * 2, $r * 2, $color);
    }

    protected function loadImage(string $path)
    {
        if (! $this->safeIsFile($path)) return null;
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'png' && function_exists('imagecreatefrompng'))  return @imagecreatefrompng($path);
        if (($ext === 'jpg' || $ext === 'jpeg') && function_exists('imagecreatefromjpeg')) return @imagecreatefromjpeg($path);
        if ($ext === 'gif' && function_exists('imagecreatefromgif'))  return @imagecreatefromgif($path);
        if ($ext === 'webp' && function_exists('imagecreatefromwebp')) return @imagecreatefromwebp($path);
        return null;
    }
}
