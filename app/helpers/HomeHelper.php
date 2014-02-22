<?php
include_once(app_path() . '/helpers/Toolkit.php');


use Carbon\Carbon as Carbon;

/**
 * Class HomeHelper
 */
class HomeHelper
{
    /**
     * Returns a list of active accounts for a given month to be used on
     * the home page.
     *
     * @param Carbon $date
     *
     * @return array
     */
    public static function homeAccountList(Carbon $date)
    {
        $start = clone $date;
        $start->startOfMonth();
        $query = Auth::user()->accounts()->remember('homeAccountList', 1440)
            ->notHidden()->get();
        $accounts = [];

        foreach ($query as $account) {
            $url = URL::Route(
                'accountoverview',
                [$account->id, $date->format('Y'), $date->format('m')]
            );

            $entry = [];
            $entry['name'] = $account->name;
            $entry['url'] = $url;
            $entry['balance'] = $account->balanceOnDate($start);
            $entry['current'] = $account->balanceOnDate($date);
            $entry['diff'] = $entry['current'] - $entry['current'];
            $accounts[] = $entry;
        }
        unset($query, $entry);

        return $accounts;
    }

//    /**
//     * Returns a list of transactions for the home page for a given month.
//     *
//     * @param Carbon $date
//     *
//     * @return array
//     */
//    public static function homeTransactionList(Carbon $date)
//    {
//        return Auth::user()->transactions()->orderBy(
//            'date', 'DESC'
//        )->inMonth($date)->orderBy('id', 'DESC')->take(5)->get();
//
//    }
//
//    /**
//     * Returns a list of transfers for the home page.
//     *
//     * @param Carbon $date
//     *
//     * @return array
//     */
//    public static function homeTransferList(Carbon $date)
//    {
//        return Auth::user()->transfers()->orderBy('date', 'DESC')->inMonth(
//            $date
//        )->orderBy('id', 'ASC')->take(10)->get();
//
//    }

//    /**
//     * Shows a chart for a beneficiary, category or budget.
//     *
//     * @param string $type
//     * @param int    $year
//     * @param int    $month
//     */
//    public static function homeComponentChart($type, $year, $month)
//    {
//
//        // prep some vars:
//        $date = Toolkit::parseDate($year, $month, new Carbon);
//
//        // get two lists of components.
//        $currentList = self::homeComponentList($type, $date, true);
//
//        // make a chart:
//        $chart = App::make('gchart');
//        $chart->addColumn(ucfirst($type), 'string');
////        $chart->addColumn('Budgeted', 'number', 'old-data');
//        $chart->addColumn('Amount', 'number');
//
//        // get allowance info which might be relevant for the
//        // chart:
//        $allowanceInfo = HomeHelper::getAllowanceInformation($date);
//
//        $left = $allowanceInfo['amount'] - $allowanceInfo['spent'];
//
//
//        // loop the current list:
//        $index = 0;
//        foreach ($currentList as $obj) {
//            if ($index < 10) {
//                $chart->addRow(
//                    ['f' => $obj['name'], 'v' => $obj['id']], $obj['amount']
//                );
//            }
//            $index++;
//        }
//        // apart from the "empty object" entry, we also add
//        // a special "allowance left" entry if relevant.
//        if ($left > 0) {
//            $chart->addRow(
//                'Allowance left', $left
//            );
//        }
//
//
//        $chart->generate();
//        $return = $chart->getData();
//
//        return $return;
//
//    }

//    /**
//     * Returns a list of [type]s for the home page for a given date (month).
//     *
//     * @param string $type
//     * @param Carbon $date
//     *
//     * @return array
//     */
//    public static function homeComponentList(
//        $type, Carbon $date, $noNegatives = false
//    ) {
//        $objects = [];
//        // a special empty component:
//        $empty = ['id'           => 0, 'name' => '(No ' . $type . ')',
//                  'amount'       => 0, 'url' => URL::Route(
//                    'empty' . Str::singular($type), [$date->format('Y'), $date->format('m')]
//                ), 'limit'       => null];
//
//
//        $limits = [];
//        // get all transactions for this month.
//        // later on, we filter on the component.
//        $query = Auth::user()->transactions()->with(
//            ['components'              => function ($query) use ($type) {
//                    return $query->where('type', $type);
//                }, 'components.limits' => function ($query) use ($date) {
//                    return $query->inMonth($date);
//                }]
//        )->inMonth($date);
//
//        if ($type == 'budget') {
//            $query->expenses();
//        }
//
//        if ($noNegatives) {
//            $query->expenses();
//        }
//        $transactions = $query->get();
//
//        foreach ($transactions as $t) {
//            if ($noNegatives) {
//                $t->amount = $t->amount < 0 ? $t->amount * -1 : $t->amount;
//            }
//
//
//            $component = $t->getComponentByType($type);
//            if (is_null($component)) {
//
//                $empty['amount'] += $t->amount;
//                continue;
//            }
//            $id = intval($component->id);
//            if (isset($objects[$id])) {
//                // append data:
//                $objects[$id]['amount'] += floatval($t->amount);
//            } else {
//                // new object:
//                $url = URL::Route(
//                    $type . 'overview',
//                    [$component->id, $date->format('Y'), $date->format('m')]
//                );
//                $current = [];
//                $current['id'] = $component->id;
//                $current['name'] = $component->name;
//                $current['amount'] = floatval($t->amount);
//                $current['url'] = $url;
//                $current['limit'] = null;
//                $current['left'] = 100;
//                $objects[$id] = $current;
//                // find a limit for this month
//                // and save it to $limits
//                $limit = $component->limits->first();
//                if ($limit) {
//                    $limits[$id] = $limit;
//                }
//            }
//            unset($current);
//        }
//        $objects[0] = $empty;
//
//        // loop the $limits array and check the $objects:
//        foreach ($limits as $id => $limit) {
//            $object = $objects[$id];
//            $spent = $object['amount'] * -1;
//            $max = floatval($limit->amount);
//            $object['limit'] = $max;
//
//            if ($spent > $max) {
//                $object['overpct'] = round(
//                    ($max / $spent) * 100
//                );
//                $object['spent'] = 100 - $object['overpct'];
//                $object['overspent'] = 100 - $object['spent'];
//            } else {
//                $object['spent'] = round(
//                    ($spent / $max) * 100
//                );
//                $object['left'] = 100 - $object['spent'];
//            }
//            $objects[$id] = $object;
//        }
//
//        return $objects;
//    }

//    public static function getAllowanceInformation(Carbon $date)
//    {
//        // default values and array
//        $defaultAllowance = Setting::getSetting('defaultAllowance');
//        $specificAllowance = Auth::user()->settings()->where(
//            'name', 'specificAllowance'
//        )->where('date', $date->format('Y-m') . '-01')->first();
//        $allowance = !is_null($specificAllowance) ? $specificAllowance
//            : $defaultAllowance;
//
//        $amount = floatval($allowance->value);
//        $allowance = ['amount' => $amount, 'over' => false, 'spent' => 0];
//        $days = round(
//            (intval($date->format('d')) / intval(
//                    $date->format('t')
//                )) * 100
//        );
//        $allowance['days'] = $days;
//        // start!
//        if ($amount > 0) {
//            $spent = floatval(
//                    Auth::user()->transactions()->inMonth($date)->expenses()
//                        ->where('ignoreallowance', 0)->sum('amount')
//                ) * -1;
//            $allowance['spent'] = $spent;
//            // overspent this allowance:
//            if ($spent > $amount) {
//                $allowance['over'] = true;
//                $allowance['pct'] = round(($amount / $spent) * 100);
//            }
//            // did not overspend this allowance.
//            if ($spent <= $amount) {
//                $allowance['pct'] = round(($spent / $amount) * 100);
//            }
//        }
//
//        return $allowance;
//    }
//
//    public static function getPredictionInfo(Carbon $date)
//    {
//        // predict today and tomorrow for the preferred account(s)
//        // in the prefs
//        // get the user's front page accounts:
//        $accounts = Toolkit::getFrontpageAccounts();
//
//        $eom = clone $date;
//        $eom = $eom->endOfMonth();
//        $current = clone $date;
//        // initial array:
//        //$p = ['ptdy' => null, 'ptmr' => null, 'stdy' => null,
//        //'stmr' => null];
//        $p = [];
//
//        // values!
//        while ($current <= $eom) {
//            // key = date:
//            $diff = $current->diffInDays($date);
//            switch ($diff) {
//                case 0:
//                    $key = 'Today';
//                    break;
//                case 1:
//                    $key = 'Tomorrow';
//                    break;
//                default:
//                    $key = $current->format('d F');
//                    break;
//            }
//            $spent = 0;
//            $predicted = 0;
//            foreach ($accounts as $account) {
//                // spent that day (usually 0)
//                $spent += floatval(
//                        Auth::user()->transactions()->expenses()->where(
//                            'ignoreallowance', 0
//                        )->onDay($current)->sum('amount')
//                    ) * -1;
//                $prediction = $account->predictOnDate($current);
//                $predicted += $prediction['prediction'];
//                unset($prediction);
//            }
//            $p[$key] = ['spent' => $spent, 'predicted' => $predicted];
//            $current->addDay();
//        }
//
//        return $p;
//    }

//    /**
//     * For each entry in the data array, find the component
//     * and possibly a limit that goes with it. The sum of the
//     * returned list should be 2000, which currently is a hard coded value.
//     *
//     * @param array  $data
//     * @param Carbon $date
//     */
//    public static function homeComponentChartLimitData($data, Carbon $date)
//    {
//
//        // first force this to be just 2000 euro's.
//        $chart = App::make('gchart');
//        $chart->addColumn('Some object', 'string');
//        $chart->addColumn('Amount', 'number');
//        $left = 2000;
//        foreach ($data as $name => $row) {
//            $amount = $row['amount'] < 0 ? $row['amount'] * -1 : $row['amount'];
//            //echo 'now at '.$row['id'].'<br>';
//
//            $limit = Limit::where(
//                'component_id', $row['id']
//            )->inMonth($date)->first();
//            $left -= floatval($amount);
//            if (!is_null($limit)) {
//                $amount = floatval($limit->amount);
//            }
//
//            $chart->addRow(['v' => $row['id'], 'f' => $name], $amount);
//        }
//        $left = $left < 0 ? 0 : $left;
//        $chart->addRow('Left', $left);
//
//
//        $chart->generate();
//
//        return $chart->getData();
//    }

    /**
     * Display a month's overview of balance history.
     *
     * @param int $year
     * @param int $month
     *
     * @return string
     */
    public static function homeAccountChart($year, $month)
    {
        $realDay = new Carbon; // for the prediction.
        $start = Toolkit::parseDate($year, $month);
        $start->startOfMonth();
        $end = clone $start;
        $end->endOfMonth();
        $start->subDay(); // also last day of previous month


        // get the user's front page accounts:
        $accounts = Toolkit::getFrontpageAccounts();

        // create chart:
        $chart = App::make('gchart');
        $chart->addColumn('Day of the month', 'date');

        // create chart & columns for each chart:
        foreach ($accounts as $account) {
            // column for account X:
            $c = $chart->addColumn($account->name . ' balance', 'number');
            $account->balance = $account->balanceOnDate($start);
            $account->balanceMost = $account->balance;
            $account->balanceLeast = $account->balance;
            $chart->addCertainty($c); // whether or not we're certain
            $chart->addInterval($c); // interval cheapest day $cheap
            $chart->addInterval($c); // interval most expensive day. $max

            // column for original prediction of account X:
            $chart->addColumn(
                $account->name . ' original prediction', 'number'
            );
        }

        // loop for each day of the month:
        $current = clone $start;
        while ($current <= $end) {
            // add the current date:
            $row = [];
            $row[] = clone $current;

            // now start generating numbers for each account:
            $todayOrPast = $current < $realDay;
            $future = !$todayOrPast;
            $fom = $current->format('d') == '1'; // first of month

            foreach ($accounts as $account) {
                if ($todayOrPast) {
                    // simply get the current balance, put it in chart.
                    $account->balance = $account->balanceOnDate($current);

                    // update the least/most balances:
                    $account->balanceMost = $account->balance;
                    $account->balanceLeast = $account->balance;

                    $row[] = $account->balance;
                    $row[] = true; // certainty
                    // we ignore the rest
                    $row[] = null; // cheapest
                    $row[] = null; // expensive
                }
                if ($future) {
                    // get a prediction:
                    Log::debug('Balance just now: ' . $account->balance);
                    $prediction = $account->predictOnDate($current);

                    // set the balances:
                    // update values:
                    $account->balance -= $prediction['prediction'];
                    $account->balanceLeast -= $prediction['least'];
                    $account->balanceMost -= $prediction['most'];

                    // add balance - predictions and certainty
                    $row[] = $account->balance;
                    $row[] = false; // certainty
                    $row[] = $account->balanceLeast;
                    $row[] = $account->balanceMost;


                }
                $row[] = null; // original prediction

            }


            $chart->addRowArray($row);
            $current->addDay();
        }


        $chart->generate();

        return $chart->getData();

    }


//    public static function homeAccountChartOld($year, $month)
//    {
//        // start with some dates:
//        // some dates:
//        $realDay = new Carbon; // for the prediction.
//
//        $start = Toolkit::parseDate($year, $month);
//        $start->startOfMonth();
//        $end = clone $start;
//        $end->endOfMonth();
//        $start->subDay(); // also last day of previous month
//
//        // get the user's front page accounts:
//        $accounts = Toolkit::getFrontpageAccounts();
//
//        // create chart:
//        $chart = App::make('gchart');
//        $chart->addColumn('Day of the month', 'date');
//
//
//        // add columns for all accounts:
//        foreach ($accounts as $account) {
//            $c = $chart->addColumn($account->name, 'number');
//            $account->balance = $account->balanceOnDate($start);
//            $chart->addCertainty($c);
//            $chart->addInterval($c); // interval cheapest day $cheap
//            $chart->addInterval($c); // interval most expensive day. $max
//        }
//
//        // loop each day, then loop all accounts:
//        $current = clone $start;
//        $row = 0;
//        while ($current <= $end) {
//            $chart->addCell($row, 0, clone $current);
//
//            // loop the accounts:
//            $cell = 1;
//            foreach ($accounts as $account) {
//
//                // don't predict, just show
//                if ($current <= $realDay) {
//                    // by doing this the balance gets "reset"
//                    // and are the predicted balances.
//                    $account->balance = $account->balanceOnDate($current);
//
//                    $certainty = true;
//                    $cheap = null;
//                    $max = null;
//                } else {
//                    // predict:
//                    $certainty = false;
//                    $prediction = $account->predictOnDate($current);
//                    $cheap = ($account->balance - $prediction['least']);
//                    $max = ($account->balance - $prediction['most']);
//                    $account->balance -= $prediction['prediction'];
//                }
//
//                $chart->addCell($row, $cell, $account->balance);
//                $cell++;
//                $chart->addCell($row, $cell, $certainty);
//                $cell++;
//                $chart->addCell($row, $cell, $cheap);
//                $cell++;
//                $chart->addCell($row, $cell, $max);
//                $cell++;
//            }
//
//
//            $current->addDay();
//            $row++;
//        }
//
//        $chart->generate();
//
//        return $chart->getData();
//    }


    /**
     * This method returns the JSON required to render a gauge. The gauge
     * will display the (predicted) balance for the date entered. Subsequently,
     * rendered on a HTML page this gives the user a visual of the (predicted)
     * state of the day in question.
     *
     *
     * @param Carbon $date
     */
    public static function homeGauge(Carbon $date)
    {
        /*
         * var data = google.visualization.arrayToDataTable([
        ['Label', 'Value'],
        ['Current', -40],
        ['Expected', {'v': gaugeFinalValue,'f': gaugeFinalValue*-1}]
//    ]);
         */

        $chart = App::make('gchart');
        $chart->addColumn('Name', 'string');
        $chart->addColumn('Value', 'number');

        $balance = 0;
        $accounts = Toolkit::getFrontpageAccounts();
        $title = $date->format('d M');

        $today = new Carbon;
        $today->startOfDay();
        if ($date <= $today) {
            foreach ($accounts as $account) {
                $balance += $account->balanceOnDate($date);
            }
        } else {
            // grab latest balance
            foreach ($accounts as $account) {
                $balance += $account->balanceOnDate($today);
            }
            $current = clone $today;
            $current->addDay();
            Log::debug('Start balance before prediction: ' . $balance);
            Log::debug('Current: ' . $current->format('d-M-Y'));
            Log::debug('Date: ' . $date->format('d-M-Y'));
            Log::debug('Today: ' . $today->format('d-M-Y'));
            while ($current <= $date) {
                Log::debug('Try: ' . $current->format('d-M-Y'));
                foreach ($accounts as $account) {
                    $prediction = $account->predictOnDate($current);
                    $balance -= $prediction['prediction'];
                }
                $current->addDay();
            }
        }
        $chart->addRow($title, ['v' => $balance, 'f' => '€ ' . $balance]);
        $chart->generate();

        return $chart->getData();
    }

    public static function componentTable($type, Carbon $date)
    {
        $type = Str::singular($type);
        $year = $date->format('Y');
        $month = $date->format('m');
        $rows = [];
        $empty = ['title' => '(empty ' . $type . ')', 'amount' => 0,
                  'url'   => URL::Route('empty' . $type, [$year, $month])];

        // got to find them all!
        $query = Auth::user()->transactions()->hasComponentType($type)->inMonth(
            $date
        );

        if ($type == 'budget') {
            $query->expenses();
        }
        $transactions = $query->get();

        $ids = []; // we need dis

        foreach ($transactions as $t) {
            $component = $t->getComponentByType($type);

            // count for empty ones:
            if (is_null($component)) {
                $empty['amount'] += $t->amount;
                continue;
            }
            $ids[] = $component->id;
            // object already exists for table:
            if (isset($rows[$component->id])) {
                $rows[$component->id]['amount'] += floatval($t->amount);
                continue;
            }
            // create object:
            $url = URL::Route(
                $type . 'overview', [$component->id, $year, $month]
            );

            $c = ['url'    => $url, 'title' => $component->name,
                  'amount' => floatval($t->amount)];
            $rows[$component->id] = $c;
        }
        // get the limits we might have for this month:
        $limits = Limit::whereIn('component_id', $ids)->inMonth($date)->get();
        foreach ($limits as $limit) {
            $id = $limit->component_id;
            $info = ['over'  => false, 'amount' => $limit->amount,
                     'spent' => $rows[$id]['amount'] * -1];
            if ($limit->amount < ($info['spent'])) {
                $info['over'] = true;
            }
            $rows[$id]['limit'] = $info;
        }

        // add empty one
        if ($empty['amount'] != 0) {
            $rows[] = $empty;
        }


        // sort:
        $amount = [];
        foreach ($rows as $key => $row) {
            $amount[$key] = $row['amount'];
        }
        array_multisort($amount, SORT_ASC, $rows);
        if ($type == 'budget') {
            $view = View::make('tables.budget')->with('rows', $rows);
        } else {
            $view = View::make('tables.component')->with('rows', $rows);
        }

        return $view->render();
    }

    public static function transactionTable(Carbon $date)
    {
        $rows = Auth::user()->transactions()->orderBy('date', 'DESC')->inMonth(
            $date
        )->orderBy('id', 'DESC')->take(25)->get();
        $view = View::make('tables.transactions')->with('rows', $rows);

        return $view->render();
    }

    public static function transferTable(Carbon $date)
    {
        $rows = Auth::user()->transfers()->orderBy('date', 'DESC')->inMonth(
            $date
        )->orderBy('id', 'DESC')->take(25)->get();
        $view = View::make('tables.transfers')->with('rows', $rows);

        return $view->render();
    }

    public static function predictionTable(Carbon $date)
    {
        // start at the first of the month.
        // predict the outcome of that day
        // and the actual amount.
        $realDay = new Carbon;
        $accounts = Toolkit::getFrontpageAccounts();

        $eom = clone $date;
        $eom = $eom->endOfMonth();
        $current = clone $date;
        $rows = [];

        // start balance: first day is "base" for predictions.
        $start = 0;
        foreach ($accounts as $a) {
            $start += $a->balanceOnDate($date);
        }
        $latest = $start;
        while ($current <= $eom) {
            $entry = [];
            $entry['date'] = clone $current;
            $actual = 0;

            if ($current <= $realDay) {
                foreach ($accounts as $a) {
                    $actual += $a->balanceOnDate($current);
                }
                $entry['actual'] = $actual;
                $latest = $actual;
                // predict from start:
                foreach ($accounts as $a) {
                    $pred = $a->predictOnDate($current);
                    $start -= $pred['prediction'];
                }
                $entry['prediction'] = $start;
            } else {

                // start with latest balance ($latest).
                foreach ($accounts as $a) {
                    $actual += $a->balanceOnDate($current);
                    // substract prediction:
                    $pred = $a->predictOnDate($current);
                    $latest -= $pred['prediction'];
                }
                $entry['actual'] = null;
                $entry['prediction'] = $latest;

            }


            $rows[] = $entry;
            $current->addDay();
        }


        $view
            = View::make('tables.predictions')->with('rows', $rows)->with(
            'today', $realDay
        );

        return $view->render();
    }
}