<?php

namespace App\Support;

class SriLankanBanks
{
    /**
     * Licensed / commonly used Sri Lankan banks for deposit transfers.
     *
     * @return list<array{slug:string,name:string,logo:?string}>
     */
    public static function all(): array
    {
        return [
            ['slug' => 'bank-of-ceylon', 'name' => 'Bank of Ceylon', 'logo' => 'assets/banks/bank-of-ceylon.png'],
            ['slug' => 'peoples-bank', 'name' => "People's Bank", 'logo' => 'assets/banks/peoples-bank.png'],
            ['slug' => 'commercial-bank', 'name' => 'Commercial Bank', 'logo' => 'assets/banks/commercial-bank.png'],
            ['slug' => 'hatton-national-bank', 'name' => 'Hatton National Bank (HNB)', 'logo' => 'assets/banks/hatton-national-bank.png'],
            ['slug' => 'sampath-bank', 'name' => 'Sampath Bank', 'logo' => 'assets/banks/sampath-bank.png'],
            ['slug' => 'nations-trust-bank', 'name' => 'Nations Trust Bank', 'logo' => 'assets/banks/nations-trust-bank.png'],
            ['slug' => 'seylan-bank', 'name' => 'Seylan Bank', 'logo' => 'assets/banks/seylan-bank.png'],
            ['slug' => 'national-development-bank', 'name' => 'NDB Bank', 'logo' => 'assets/banks/national-development-bank.png'],
            ['slug' => 'dfcc-bank', 'name' => 'DFCC Bank', 'logo' => 'assets/banks/dfcc-bank.png'],
            ['slug' => 'pan-asia-bank', 'name' => 'Pan Asia Bank', 'logo' => 'assets/banks/pan-asia-bank.png'],
            ['slug' => 'union-bank', 'name' => 'Union Bank', 'logo' => 'assets/banks/union-bank.png'],
            ['slug' => 'amana-bank', 'name' => 'Amana Bank', 'logo' => 'assets/banks/amana-bank.png'],
            ['slug' => 'cargills-bank', 'name' => 'Cargills Bank', 'logo' => 'assets/banks/cargills-bank.png'],
            ['slug' => 'national-savings-bank', 'name' => 'National Savings Bank', 'logo' => 'assets/banks/national-savings-bank.png'],
            ['slug' => 'hsbc', 'name' => 'HSBC', 'logo' => 'assets/banks/hsbc.png'],
            ['slug' => 'standard-chartered', 'name' => 'Standard Chartered', 'logo' => 'assets/banks/standard-chartered.png'],
            ['slug' => 'custom', 'name' => 'Other bank (own logo)', 'logo' => null],
        ];
    }

    public static function find(string $slug): ?array
    {
        foreach (static::all() as $row) {
            if ($row['slug'] === $slug) {
                return $row;
            }
        }

        return null;
    }

    public static function logoAsset(?string $slug): ?string
    {
        $row = $slug ? static::find($slug) : null;
        if (! $row || empty($row['logo'])) {
            return null;
        }

        return asset($row['logo']);
    }
}
