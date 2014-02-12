<?php

/**
 * Class TransferController
 */
class TransferController extends BaseController
{

    /**
     * Add a new transfer
     *
     * @return View
     */
    public function showIndex()
    {
        $transfers = Auth::user()->transfers()->orderBy('date', 'DESC')
            ->orderBy('id', 'DESC')->paginate(50);

        return View::make('transfers.index')->with('title', 'All transfers')
            ->with('transfers', $transfers);
    }

    /**
     * Add a transfer (to an account)
     *
     * @param Account $account The account
     *
     * @return View
     */
    public function add(Account $account = null)
    {
        Session::put('previous', URL::previous());
        $accounts = [];
        foreach (Auth::user()->accounts()->where('hidden', 0)->get() as $a) {
            $accounts[$a->id] = $a->name;
        }

        return View::make('transfers.add')->with('title', 'Add a transfers')
            ->with('account', $account)->with('accounts', $accounts)->with(
                'id', $account ? $account->id : null
            );
    }

    /**
     * Post process a new transfer
     *
     * @return Redirect
     */
    public function postAdd()
    {
        $data = ['description' => Input::get('description'),
                 'amount' => floatval(Input::get('amount')),
                 'accountfrom_id' => intval(Input::get('accountfrom_id')),
                 'accountto_id' => intval(Input::get('accountto_id')),
                 'date' => Input::get('date'), 'user_id' => Auth::user()->id];
        $transfer = new Transfer($data);

        // validate and save:
        $validator = Validator::make($transfer->toArray(), Transfer::$rules);
        if ($validator->fails()) {
            return Redirect::route('addtransfer')->withInput()->withErrors(
                $validator
            );
        } else {
            $transfer->save();
            Session::flash('success', 'The transfer has been created.');

            return Redirect::to(Session::get('previous'));
        }
    }

    /**
     * Show the view to edit a transfer
     *
     * @param Transfer $transfer The transfer
     *
     * @return \Illuminate\View\View
     */
    public function edit(Transfer $transfer)
    {
        Session::put('previous', URL::previous());
        $accounts = [];
        foreach (Auth::user()->accounts()->where('hidden', 0)->get() as $a) {
            $accounts[$a->id] = $a->name;
        }

        return View::make('transfers.edit')->with('transfer', $transfer)->with(
            'accounts', $accounts
        )->with('title', 'Edit transfer ' . $transfer->description);
    }

    /**
     * Process the changes to the transfer.
     *
     * @param Transfer $transfer the transfer.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function postEdit(Transfer $transfer)
    {

        $transfer->description = Input::get('description');
        $transfer->amount = floatval(Input::get('amount'));
        $transfer->date = Input::get('date');
        $transfer->accountto_id = intval(Input::get('accountto_id'));
        $transfer->accountfrom_id = intval(Input::get('accountfrom_id'));


        $validator = Validator::make(
            $transfer->toArray(), Transfer::$rules
        );
        if ($validator->fails()) {
            return Redirect::route(
                'edittransfer', $transfer->id
            )->withInput()->withErrors(
                    $validator
                );
        } else {
            $transfer->save();
            Session::flash('success', 'The transfer has been edited.');

            return Redirect::to(Session::get('previous'));
        }
    }

    /**
     * Delete a transfer
     *
     * @param Transfer $transfer The transfer
     *
     * @return \Illuminate\View\View
     */
    public function delete(Transfer $transfer)
    {
        Session::put('previous', URL::previous());

        return View::make('transfers.delete')->with('transfer', $transfer)
            ->with('title', 'Delete transfer ' . $transfer->description);
    }

    /**
     * Actually delete the transfer (POST).
     *
     * @param Transfer $transfer The transfer
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postDelete(Transfer $transfer)
    {
        $transfer->delete();

        return Redirect::to(Session::get('previous'));
    }
}
