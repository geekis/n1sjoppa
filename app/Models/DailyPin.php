<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class DailyPin extends Model
{
    protected $fillable = [
        'date',
        'pin',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /**
     * Today's kiosk PIN row, if one has been set.
     */
    public static function forToday(): ?self
    {
        return static::whereDate('date', Carbon::today())->first();
    }

    /**
     * Set (or replace) today's PIN.
     */
    public static function setToday(string $pin): self
    {
        return static::updateOrCreate(
            ['date' => Carbon::today()->toDateString()],
            ['pin' => $pin],
        );
    }

    /**
     * Does the given value match today's PIN?
     */
    public static function matchesToday(string $pin): bool
    {
        $today = static::forToday();

        return $today !== null && hash_equals($today->pin, $pin);
    }
}
