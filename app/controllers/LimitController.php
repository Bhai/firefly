<?php
// @codeCoverageIgnoreStart
/** @noinspection PhpIncludeInspection */
require_once(app_path() . '/helpers/Toolkit.php');
// @codeCoverageIgnoreEnd

/**
 * Class LimitController
 */
class LimitController extends BaseController
{

    /**
     * Add a limit to a certain component for a given year and month.
     *
     * @param \Component $component The component
     * @param int        $year      The year
     * @param int        $month     The month.
     *
     * @return \Illuminate\View\View
     */
    public function add(Component $component, $year, $month)
    {
        if (!Input::old()) {
            Session::put('previous', URL::previous());
        }
        $date = Toolkit::parseDate($year, $month);

        return View::make('meta-limit.add')->with('component', $component)->with('date', $date);
    }

    /**
     * Process the addition of a new limit.
     *
     * @param Component $component The component
     * @param int       $year      The year
     * @param int       $month     The month
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function postAdd(Component $component, $year, $month)
    {
        $date = Toolkit::parseDate($year, $month);
        $limit = new Limit(
            [
                'component_id' => $component->id,
                'date'         => $date,
                'amount'       => floatval(Input::get('amount'))
            ]
        );

        $validator = Validator::make($limit->toArray(), Limit::$rules);

        // it fails!
        if ($validator->fails()) {
            Session::flash('error', 'Could not add ' . OBJ . ' limit.');
            return Redirect::route(OBJ . 'overview', [$component->id]);
        }
        // save
        $result = $limit->save();

        // failed again!
        if (!$result) {
            Session::flash('error', 'Could not add ' . OBJ . ' limit.');
            return Redirect::route(OBJ . 'overview', [$component->id]);
        }

        Session::flash('success', 'Limit saved!');
        return Redirect::to(Session::get('previous'));

    }

    /**
     * Edit a limit (show the view).
     *
     * @param Limit $limit The limit
     *
     * @return \Illuminate\View\View
     */
    public function edit(Limit $limit)
    {
        if (!Input::old()) {
            Session::put('previous', URL::previous());
        }
        $component = Auth::user()->components()->find($limit->component_id);

        return View::make('meta-limit.edit')->with('component', $component)->with('limit', $limit);
    }

    /**
     * Process the editing of a limit.
     *
     * @param Limit $limit The limit
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function postEdit(Limit $limit)
    {
        $component = Auth::user()->components()->find($limit->component_id);
        $limit->amount = floatval(Input::get('amount'));

        $validator = Validator::make($limit->toArray(), Limit::$rules);
        if ($validator->fails()) {
            Session::flash('error', 'Could not edit ' . OBJ . 'limit.');
            return Redirect::route(OBJ . 'overview', [$component->id]);
        }
        // save
        Session::flash('success', 'Limit edited!');
        $limit->save();

        return Redirect::to(Session::get('previous'));
    }

    /**
     * Delete a limit (shows the view)
     *
     * @param Limit $limit The limit
     *
     * @return \Illuminate\View\View
     */
    public function delete(Limit $limit)
    {
        if (!Input::old()) {
            Session::put('previous', URL::previous());
        }
        $component = Auth::user()->components()->find($limit->component_id);

        return View::make('meta-limit.delete')->with('component', $component)->with('date', $limit->date);
    }

    /**
     * Process the deletion.
     *
     * @param Limit $limit The limit
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function postDelete(Limit $limit)
    {
        $limit->delete();
        Session::flash('success', 'Limit removed.');

        return Redirect::to(Session::get('previous'));
    }
}