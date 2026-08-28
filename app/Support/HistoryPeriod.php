<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Display-only date window for history lists.
 * Default is calendar today (Asia/Colombo). Records are never deleted.
 */
class HistoryPeriod
{
    public const KEY = 'period';

    public string $period = 'today';

    public ?Carbon $from = null;

    public ?Carbon $to = null;

    public string $label = 'Today';

    public static function fromRequest(?Request $request = null): self
    {
        $request ??= request();
        $tz = (string) config('app.timezone', 'Asia/Colombo');
        $now = now($tz);

        $period = strtolower(trim((string) $request->query(self::KEY, '')));
        $fromRaw = trim((string) $request->query('from', ''));
        $toRaw = trim((string) $request->query('to', ''));

        if ($period === '' && $fromRaw === '' && $toRaw === '') {
            $period = 'today';
        } elseif ($period === '' || ($period === 'today' && ($fromRaw !== '' || $toRaw !== ''))) {
            $period = 'custom';
        }

        if (! in_array($period, ['today', 'yesterday', '7d', '30d', 'all', 'custom'], true)) {
            $period = 'today';
        }

        $self = new self;
        $self->period = $period;

        if ($period === 'today') {
            $self->from = $now->copy()->startOfDay();
            $self->to = $now->copy()->endOfDay();
            $self->label = 'Today';
        } elseif ($period === 'yesterday') {
            $self->from = $now->copy()->subDay()->startOfDay();
            $self->to = $now->copy()->subDay()->endOfDay();
            $self->label = 'Yesterday';
        } elseif ($period === '7d') {
            $self->from = $now->copy()->subDays(6)->startOfDay();
            $self->to = $now->copy()->endOfDay();
            $self->label = 'Last 7 days';
        } elseif ($period === '30d') {
            $self->from = $now->copy()->subDays(29)->startOfDay();
            $self->to = $now->copy()->endOfDay();
            $self->label = 'Last 30 days';
        } elseif ($period === 'all') {
            $self->label = 'All days';
        } else {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromRaw)) {
                $self->from = Carbon::parse($fromRaw, $tz)->startOfDay();
            }
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $toRaw)) {
                $self->to = Carbon::parse($toRaw, $tz)->endOfDay();
            }
            if (! $self->from && ! $self->to) {
                $self->period = 'today';
                $self->from = $now->copy()->startOfDay();
                $self->to = $now->copy()->endOfDay();
                $self->label = 'Today';
            } else {
                $self->label = 'Chosen dates';
            }
        }

        return $self;
    }

    public function apply($query, string $column = 'created_at', ?callable $alsoKeep = null)
    {
        if ($this->period === 'all' || ($this->from === null && $this->to === null)) {
            return $query;
        }

        $from = $this->from;
        $to = $this->to;

        return $query->where(function ($inner) use ($column, $from, $to, $alsoKeep) {
            $inner->where(function ($day) use ($column, $from, $to) {
                if ($from) {
                    $day->where($column, '>=', $from);
                }
                if ($to) {
                    $day->where($column, '<=', $to);
                }
            });
            if ($alsoKeep) {
                $inner->orWhere($alsoKeep);
            }
        });
    }

    public function chipUrl(string $period, array $keep = []): string
    {
        $query = array_merge($keep, [self::KEY => $period]);
        unset($query['from'], $query['to'], $query['page'], $query['tx_page'], $query['dep_page'], $query['snap_page'], $query['wallet_page'], $query['order_page']);

        return request()->url().'?'.http_build_query(array_filter($query, fn ($v) => $v !== null && $v !== ''));
    }

    public function emptyMessage(string $noun = 'records'): string
    {
        if ($this->period === 'all') {
            return 'No '.$noun.' yet.';
        }
        if ($this->period === 'today') {
            return 'No '.$noun.' today. Pick another day to see older history. Nothing was deleted.';
        }

        return 'No '.$noun.' in '.$this->label.'. Pick another day to see older history.';
    }
}
