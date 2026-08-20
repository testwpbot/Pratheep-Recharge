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
