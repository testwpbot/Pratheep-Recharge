<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value'];

    /**
     * Get a setting value by group + key.
     */
    public static function get(string $group, string $key, $default = null)
    {
        $row = static::where('group', $group)->where('key', $key)->first();
        return $row ? $row->value : $default;
    }

    /**
     * Set a setting value.
     */
    public static function set(string $group, string $key, $value): void
    {
        static::updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => is_array($value) ? json_encode($value) : (string) $value]
        );
    }

    /**
     * Get all settings for a group, keyed.
     */
    public static function forGroup(string $group): array
    {
        return static::where('group', $group)->pluck('value', 'key')->toArray();
    }

    /**
     * SEO values with safe defaults so views never hit a missing key.
     *
     * @return array<string,string>
     */
    public static function seo(): array
    {
        $site = static::get('general', 'site_name', config('app.name', 'Happy Pratheep Recharge'));
        $defaults = [
            'meta_title'               => $site . ' — Mobile reloads, bills and DTH in Sri Lanka',
            'meta_description'         => 'Mobile reloads, data packages, ISP bills and utility payments for Sri Lanka. Fast, secure bank transfers.',
            'meta_keywords'            => 'reload, Dialog, Mobitel, Airtel, Hutch, electricity bill, DTH, Sri Lanka',
            'og_title'                 => '',
            'og_description'           => '',
            'og_image_url'             => '',
            'og_image_path'            => '',
            'favicon_path'             => '',
            'robots'                   => 'index',
            'google_site_verification' => '',
        ];
        $saved = static::forGroup('seo');
        $out = array_merge($defaults, is_array($saved) ? $saved : []);
        foreach ($out as $k => $v) {
            $out[$k] = is_string($v) ? $v : (string) ($v ?? '');
        }
        if (trim($out['og_title']) === '') {
            $out['og_title'] = $out['meta_title'];
        }
        if (trim($out['og_description']) === '') {
            $out['og_description'] = $out['meta_description'];
        }

        return $out;
    }

    /**
     * WhatsApp chat button. Hidden until admin turns it on and sets a number.
     *
     * @return array{enabled:bool,phone:string,message:string,href:?string}
     */
    public static function whatsapp(): array
    {
        $saved = static::forGroup('whatsapp');
        $phone = trim((string) ($saved['phone'] ?? ''));
        $message = trim((string) ($saved['message'] ?? ''));
        if ($message === '') {
            $message = 'Hi Happy Pratheep, I need help with a recharge.';
        }
        $enabled = (($saved['enabled'] ?? '0') === '1');
        $digits = static::whatsappDigits($phone);

        return [
            'enabled' => $enabled,
            'phone'   => $phone,
            'message' => $message,
            'href'    => ($enabled && $digits !== '')
                ? 'https://wa.me/' . $digits . '?text=' . rawurlencode($message)
                : null,
        ];
    }

    /** Turn 0771234567 / +94 77 123 4567 into digits WhatsApp accepts. */
    public static function whatsappDigits(?string $phone): string
    {
        $raw = preg_replace('/[^\d+]/', '', (string) $phone);
        $raw = ltrim((string) $raw, '+');
        $raw = preg_replace('/\D/', '', (string) $raw) ?: '';
        if ($raw === '') {
            return '';
        }
        if (preg_match('/^0[1-9]\d{8}$/', $raw)) {
            return '94' . substr($raw, 1);
        }

        return $raw;
    }

    /**
     * Boot SMTP config into the mailer at runtime (called from a service provider).
     */
    public static function bootMailConfig(): void
    {
        $smtp = static::forGroup('smtp');
        $host = $smtp['host'] ?? null;
        if (!$host) return; // no SMTP configured, leave defaults

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host'       => $host,
            'mail.mailers.smtp.port'       => (int) ($smtp['port'] ?? 587),
            'mail.mailers.smtp.username'   => $smtp['username'] ?? null,
            'mail.mailers.smtp.password'   => $smtp['password'] ?? null,
            'mail.mailers.smtp.encryption' => ($smtp['encryption'] ?? 'tls') ?: null,
            'mail.from.address'            => $smtp['from_address'] ?? config('mail.from.address'),
            'mail.from.name'               => $smtp['from_name'] ?? config('mail.from.name'),
        ]);
    }
}
