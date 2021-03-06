<?php
use Carbon\Carbon as Carbon;

/**
 * Class ReportHelper
 */
class ReportHelper
{

    public static function summaryCompared(Carbon $dateOne, Carbon $dateTwo)
    {
        $data = [
            $dateOne->format('Y') => self::summary($dateOne, 'year'),
            $dateTwo->format('Y') => self::summary($dateTwo, 'year')
        ];
        return $data;
    }

    public static function monthsCompared(Carbon $dateOne, Carbon $dateTwo)
    {
        $data = [
            $dateOne->format('Y') => self::months($dateOne, 'year'),
            $dateTwo->format('Y') => self::months($dateTwo, 'year')
        ];
        return $data;
    }

    public static function summary(Carbon $date, $period)
    {
        $key = $date->format('Ymd') . 'reportsummary' . $period;
        if (Cache::has($key)) {
            return Cache::get($key);
        }
        $start = clone $date;
        $start->subDay();
        $end = clone $date;
        switch ($period) {
            default:
            case 'month':
                $endOf = 'endOfMonth';
                $startOf = 'startOfMonth';
                $inPeriod = 'inMonth';
                break;
            case 'year':
                $endOf = 'endOfYear';
                $startOf = 'startOfYear';
                $inPeriod = 'inYear';
                break;
        }
        $date->$startOf();
        $end->$endOf();

        // get the incomes:
        $income = floatval(
            Auth::user()->transactions()->$inPeriod($date)->incomes()
                ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
                ->where('accounts.shared', 0)
                ->sum('amount')
        );

        // get the expenses:
        $expenses = floatval(
            Auth::user()->transactions()->$inPeriod($date)->expenses()
                ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
                ->where('accounts.shared', 0)
                ->sum('amount')
        );


        // received in total from shared accounts (this might be income):
        $receivedFromShared = floatval(
            Auth::user()->transfers()->leftJoin('accounts', 'accounts.id', '=', 'transfers.accountfrom_id')->where(
                'accounts.shared', 1
            )->$inPeriod(
                    $date
                )->sum('amount')
        );

        $sentToShared = floatval(
            Auth::user()->transfers()->leftJoin('accounts', 'accounts.id', '=', 'transfers.accountto_id')->where(
                'accounts.shared', 1
            )->$inPeriod(
                    $date
                )->sum('amount')
        );
        $shared = ($receivedFromShared - $sentToShared);

        // received more: income!
        if ($shared > 0) {
            $income += $shared;
        } else {
            // spent more, expense!
            $expenses += $shared;
        }

        // get the net worth:
        $nwEnd = 0;
        $nwStart = 0;
        foreach (Auth::user()->accounts()->notInactive()->notShared()->get() as $account) {
            $nwEnd += $account->balanceOnDate($end);
            $nwStart += $account->balanceOnDate($start);
        }


        $data = [
            'income'   => [
                'income'  => $income,
                'expense' => $expenses,
            ],
            'networth' => [
                'start'     => $nwStart,
                'startdate' => $date,
                'end'       => $nwEnd,
                'enddate'   => $end
            ]
        ];
        Cache::forever($key, $data);
        return $data;
    }

    public static function biggestExpenses(Carbon $date, $period)
    {
        $key = 'biggestExpenses' . $date->format('Ymd') . $period;
        if (Cache::has($key)) {
            return Cache::get($key);
        }

        switch ($period) {
            default:
            case 'month':
                $inPeriod = 'inMonth';
                break;
            case 'year':
                $inPeriod = 'inYear';
                break;
        }
        $transactions = Auth::user()->transactions()->expenses()->orderBy('amount', 'ASC')->whereNull('predictable_id')
            ->take(10)->$inPeriod(
                $date
            )->get();
        $transfers = Auth::user()->transfers()->leftJoin('accounts', 'accounts.id', '=', 'transfers.accountto_id')
            ->where('accounts.shared', 1)->$inPeriod(
                $date
            )->get(['transfers.*', DB::Raw('amount *-1 AS amount')]);
        $mutations = [];

        // we have both:
        if (count($transfers) > 0 && count($transactions) > 0) {
            $mutations = $transactions->merge($transfers);
            $mutations = $mutations->sortBy(
                function ($a) {
                    return $a->amount;
                }
            );
        }
        // we have transactions:
        if (count($transfers) == 0 && count($transactions) > 0) {
            $mutations = $transactions;
        }
        // we have transfers:
        if (count($transactions) == 0 && count($transfers) > 0) {
            $mutations = $transfers;
        }
        Cache::forever($key, $mutations);
        return $mutations;
    }

    public static function predicted($date)
    {
        $key = 'predicted' . $date->format('Ymd');
        if (Cache::has($key)) {
            return Cache::get($key);
        }
        $transactions = Auth::user()->transactions()->expenses()->orderBy('amount', 'ASC')->whereNotNull(
            'predictable_id'
        )->take(10)->inMonth($date)->get();
        $transactions->each(
            function (Transaction $t) {
                $t->predicted = $t->predictable()->first()->amount;
            }
        );

        Cache::forever($key, $transactions);
        return $transactions;
    }

    public static function months(Carbon $date)
    {
        $date->startOfYear();
        $end = clone $date;
        $end->endOfYear();
        $current = clone $date;
        $list = [];

        while ($current <= $end) {

            $out = Auth::user()->transactions()->inMonth($current)->expenses()->sum('amount');
            $in = Auth::user()->transactions()->inMonth($current)->incomes()->sum('amount');

            $list[] = [
                'date' => $current->format('F Y'),
                'in'   => $in,
                'out'  => $out,
                'url'  => URL::Route('monthreport', [$current->format('Y'), $current->format('m')])
            ];

            $current->addMonth();
        }
        return $list;

    }

    public static function incomes(Carbon $date, $period)
    {
        $cacheKey = 'reportincomes' . $date->format('Ymd') . $period;
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        switch ($period) {
            case 'month':
                $inPeriod = 'inMonth';
                break;
            case 'year':
                $inPeriod = 'inYear';
                break;
        }
        $beneficiaryType = Type::whereType('beneficiary')->first();

        $data = [];
        $transactions = Auth::user()->transactions()->incomes()->with(
            ['components' => function ($query) use ($beneficiaryType) {
                    $query->where('type_id', $beneficiaryType->id);
                }]
        )->$inPeriod(
                $date
            )
            ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
            ->where('accounts.shared', 0)
            ->get();

        /** @var $t Transaction */
        foreach ($transactions as $t) {
            $key = $t->hasComponentOfType($beneficiaryType) ? $t->getComponentOfType($beneficiaryType)->id : 0;
            if (isset($data[$key])) {
                $data[$key]['transactions'][] = $t;
            } else {
                $data[$key] = [
                    'beneficiary'  => [
                        'id'   => $key,
                        'name' => $t->hasComponentOfType($beneficiaryType) ? $t->getComponentOfType(
                                $beneficiaryType
                            )->name : '(no beneficiary)'
                    ],
                    'transactions' => [$t]
                ];
            }
        }
        Cache::forever($cacheKey, $data);
        return $data;
    }

    public static function categories(Carbon $date)
    {
        $data = [];
        $transactions = Auth::user()->transactions()->whereNull('predictable_id')->expenses()->with(
            ['components' => function ($query) {
                    $query->where('type', 'category');
                }]
        )->inMonth($date)->get();

        foreach ($transactions as $t) {
            $key = $t->category->id;
            if (isset($data[$key])) {
                $data[$key]['transactions'][] = $t;
                $data[$key]['category']['sum'] += $t->amount;
            } else {
                $data[$key] = [
                    'category'     => [
                        'id'   => $key,
                        'name' => $t->category->name,
                        'sum'  => $t->amount
                    ],
                    'transactions' => [$t]
                ];
            }
        }
        return $data;
    }

    public static function expensesGrouped($date, $period, Type $type)
    {
        $cacheKey = 'reportexpensesGrouped' . $date->format('Ymd') . $period . $type;
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        $data = [];
        // get the transfers with this $type and $date
        $transactions = Auth::user()->transactions()->expenses()->with(
            ['components' => function ($query) use ($type) {
                    $query->where('type_id', $type->id);
                }]
        )->inMonth($date)->get();

        // get the transfers TO a shared account
        // with this $type and $date.
        $transfers = Auth::user()->transfers()->with(
            ['components' => function ($query) use ($type) {
                    $query->where('type_id', $type->id);
                }]
        )->leftJoin('accounts', 'accounts.id', '=', 'transfers.accountto_id')
            ->where('accounts.shared', 1)->inMonth(
                $date
            )->get(['transfers.*', DB::Raw('`amount` * -1 AS `amount`')]);
        // merge the two lists:
        $mutations = $transactions->merge($transfers);
        $mutations = $mutations->sortBy(
            function ($a) {
                return $a->amount;
            }
        );


        /** @var $t ComponentEnabledModel */
        foreach ($mutations as $t) {
            $key = $t->hasComponentOfType($type) ? $t->getComponentOfType($type)->id : 0;
            if (isset($data[$key])) {
                $data[$key]['transactions'][] = $t;
                $data[$key]['component']['sum'] += $t->amount;
            } else {
                $data[$key] = [
                    'component'    => [
                        'id'   => $key,
                        'name' => $t->hasComponentOfType($type) ? $t->getComponentOfType($type)->name : '(no '.$type->type.')',
                        'sum'  => $t->amount,
                        'hasIcon' => $t->hasComponentOfType($type) ? $t->getComponentOfType($type)->hasIcon() : false,
                        'iconTag' => $t->hasComponentOfType($type) ? $t->getComponentOfType($type)->iconTag() : '',
                    ],
                    'transactions' => [$t]
                ];
            }
        }
        // collect sums:
        uasort($data, 'ReportHelper::sortExpenses');

        Cache::forever($cacheKey, $data);
        return $data;
    }

    public static function sortExpenses($a, $b)
    {
        $sumA = $a['component']['sum'];
        $sumB = $b['component']['sum'];
        if ($sumA == $sumB) {
            return 0;
        }
        return ($sumA < $sumB) ? -1 : 1;
    }
}