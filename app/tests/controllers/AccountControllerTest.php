<?php

use Carbon\Carbon as Carbon;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.1 on 2014-04-07 at 13:44:09.
 */
class AccountControllerTest extends TestCase
{

    protected $_user;
    protected $_openingbalance = 234.56;
    protected $_date = '2010-02-28';

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();
        $this->_user = User::whereUsername('test')->first();
        $this->be($this->_user);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        DB::table('accounts')->whereOpeningbalance($this->_openingbalance)->delete();
        parent::tearDown();
    }

    /**
     * @covers AccountController::showIndex
     */
    public function testShowIndex()
    {

        $response = $this->action('GET', 'AccountController@showIndex');
        $view = $response->original;

        // is OK?
        $this->assertResponseOk();

        // count the number of accounts:
        $count = DB::table('accounts')->where('user_id', $this->_user->id)->count();
        $this->assertCount($count, $view['accounts']);

        // count the number of balances:
        $this->assertCount($count, $view['balances']);

        // check the title:
        $this->assertEquals('All accounts', $view['title']);

    }

    /**
     * @covers AccountController::add
     */
    public function testAdd()
    {
        $response = $this->action('GET', 'AccountController@add');
        $view = $response->original;

        // is OK?
        $this->assertResponseOk();

        // empty prefilled array should have five entries:
        $this->assertCount(5, $view['prefilled']);

        // check some variables in the prefilled array:
        $this->assertEquals('', $view['prefilled']['name']);
        $this->assertEquals('', $view['prefilled']['openingbalance']);
        $this->assertEquals(date('Y-m-d'), $view['prefilled']['openingbalancedate']);

        // check the title:
        $this->assertEquals('Add a new account', $view['title']);


    }

    /**
     * @covers AccountController::postAdd
     */
    public function testPostAdd()
    {
        // count the number of accounts
        $current = DB::table('accounts')->whereUserId($this->_user->id)->count();

        // the data we will create a new account with:
        $data = [
            'name'               => 'New Test Account',
            'openingbalance'     => $this->_openingbalance,
            'openingbalancedate' => date('Y-m-d'),
            'hidden'             => 0,
            'shared'             => 0
        ];

        // fire!
        $this->action('POST', 'AccountController@postAdd', $data);

        // is OK?
        $this->assertResponseStatus(302);

        // count again
        $new = DB::table('accounts')->whereUserId($this->_user->id)->count();

        $this->assertSessionHas('success');
        $this->assertEquals($current + 1, $new);
        $this->assertRedirectedToAction('HomeController@showIndex');
    }

    /**
     * @covers AccountController::edit
     */
    public function testEdit()
    {
        // find account to edit:
        $account = DB::table('accounts')->whereUserId($this->_user->id)->first();

        // fire!
        $response = $this->action('get', 'AccountController@edit', $account);
        $view = $response->original;

        // is OK?
        $this->assertResponseOk();

        // prefilled array should have five entries:
        $this->assertCount(5, $view['prefilled']);

        // prefilled array should match our account:
        $this->assertEquals(Crypt::decrypt($account->name), $view['prefilled']['name']);
        $this->assertEquals($account->openingbalance, $view['prefilled']['openingbalance']);
        $this->assertEquals($account->openingbalancedate, $view['prefilled']['openingbalancedate']);

        // account object should match our object:
        $this->assertEquals(Crypt::decrypt($account->name), $view['account']->name);
        $this->assertEquals($account->currentbalance, $view['account']->currentbalance);

        // check the title
        $this->assertEquals('Edit account "' . Crypt::decrypt($account->name) . '"', $view['title']);

    }

    /**
     * @covers AccountController::postEdit
     */
    public function testPostEdit()
    {
        // find account to edit:
        $account = DB::table('accounts')->whereUserId($this->_user->id)->first();

        // the data to update the account with:
        $data = [
            'name'               => Str::random(16),
            'openingbalance'     => rand(20, 2000),
            'openingbalancedate' => $this->_date,
            'shared'             => 1,
            'hidden'             => 1
        ];

        // fire the update!
        $this->call('POST', 'home/account/' . $account->id . '/edit', $data);

        // result should be OK
        $this->assertResponseStatus(302);

        // session also OK:
        $this->assertSessionHas('success');

        // account should match $data:
        $updated = DB::table('accounts')->whereId($account->id)->first();

        $this->assertEquals(Crypt::decrypt($updated->name), $data['name']);
        $this->assertEquals($updated->openingbalancedate, $data['openingbalancedate']);
        $this->assertEquals($updated->openingbalance, $data['openingbalance']);
        $this->assertEquals($updated->shared, $data['shared']);
        $this->assertEquals($updated->hidden, $data['hidden']);
        $this->assertRedirectedToAction('HomeController@showIndex');

    }

    /**
     * @covers AccountController::delete
     */
    public function testDelete()
    {
        // find an account:
        $account = DB::table('accounts')->whereUserId($this->_user->id)->first();

        // fire!
        $response = $this->action('GET', 'AccountController@delete', $account);
        $view = $response->original;

        // is OK?
        $this->assertResponseOk();

        // match account object.
        $this->assertEquals(Crypt::decrypt($account->name), $view['account']->name);
        $this->assertEquals($account->openingbalance, $view['account']->openingbalance);
        $this->assertEquals($account->hidden, $view['account']->hidden);

        // check title:
        $this->assertEquals('Delete account "' . Crypt::decrypt($account->name) . '"', $view['title']);


    }

    /**
     * @covers AccountController::postDelete
     */
    public function testPostDelete()
    {
        // count:
        $current = DB::table('accounts')->whereUserId($this->_user->id)->count();

        // create an account (to delete it right after):
        $accountId = DB::table('accounts')->insertGetId(
            [
                'user_id'            => $this->_user->id,
                'name'               => Crypt::encrypt(Str::random(12)),
                'openingbalance'     => 1000,
                'currentbalance'     => 1000,
                'created_at'         => date('Y-m-d H:i:s'),
                'updated_at'         => date('Y-m-d H:i:s'),
                'openingbalancedate' => date('Y') . '-01-01',
                'hidden'             => 0,
                'shared'             => 1
            ]
        );
        // fire!
        $this->call('POST', 'home/account/' . $accountId . '/delete');

        // inspect:
        $this->assertSessionHas('success');
        $this->assertRedirectedToAction('HomeController@showIndex');

        // count again:
        $new = DB::table('accounts')->whereUserId($this->_user->id)->count();

        $this->assertEquals($new, $current);
    }

    /**
     * @covers  AccountController::showOverview
     * @depends testPostEdit
     */
    public function testShowOverview()
    {
        // calculate the number of months manually.
        $now = new Carbon;
        $past = new Carbon($this->_date);
        // the one is for the extra month.
        $diff = $now->diffInMonths($past) + 1;

        // find an account:
        $account = DB::table('accounts')->whereUserId($this->_user->id)->first();

        // fire!
        $response = $this->action('GET', 'AccountController@showOverview', $account);
        $view = $response->original;

        // is OK?
        $this->assertResponseOk();

        $this->assertCount($diff, $view['months']);
        $this->assertEquals('Overview for account "' . Crypt::decrypt($account->name) . '"', $view['title']);

    }

    /**
     * @covers  AccountController::showOverviewChart
     * @depends testPostEdit
     * @depends testShowOverview
     */
    public function testShowOverviewChart()
    {
        // calculate the number of months manually.
        $now = new Carbon;
        $past = new Carbon($this->_date);
        // the one is for the extra month.
        $diff = $now->diffInMonths($past) + 1;

        // find an account:
        $account = DB::table('accounts')->whereUserId($this->_user->id)->first();

        // fire!
        $response = $this->action('GET', 'AccountController@showOverviewChart', $account);
        $view = $response->original;

        // is OK?
        $this->assertResponseOk();

        // it's a PHP array response (JSON):
        $this->assertCount(2, $view); // rows and columns
        $this->assertCount($diff, $view['rows']); // +/- 50 months
        $this->assertCount(2, $view['cols']); // 2 columns

    }

    /**
     * @covers AccountController::showOverviewByMonth
     */
    public function testShowOverviewByMonth()
    {
        // the month we wish to see:
        $date = new Carbon;
        $date->startOfMonth();
        // find an account:
        $account = DB::table('accounts')->whereUserId($this->_user->id)->first();

        // fire!
        $response = $this->call('GET', 'home/account/' . $account->id . '/overview/' . $date->format('Y/m'));
        $view = $response->original;

        // is OK?
        $this->assertResponseOk();

        // check the title:
        $this->assertEquals(
            'Overview for account "' . Crypt::decrypt($account->name) . '" in ' . $date->format('F Y'), $view['title']
        );

        // check mutations:
        $transfers = DB::table('transfers')->where(
            function ($query) use ($account) {
                $query->where('accountto_id', $account->id);
                $query->orWhere('accountto_id', $account->id);
            }
        )->where(DB::Raw('DATE_FORMAT(`date`,"%Y-%m")'), $date->format('Y-m'))->count();
        $transactions = DB::table('transactions')->where('account_id', $account->id)->where(
            DB::Raw('DATE_FORMAT(`date`,"%Y-%m")'), $date->format('Y-m')
        )->count();
        $this->assertCount($transactions + $transfers, $view['mutations']);

        // check date
        $this->assertEquals($date->format('Y-m-d'), $view['date']->format('Y-m-d'));

        // check account object:
        $this->assertEquals(Crypt::decrypt($account->name), $view['account']->name);
        $this->assertEquals($account->currentbalance, $view['account']->currentbalance);


    }

    /**
     * @covers AccountController::showOverviewChartByMonth
     */
    public function testShowOverviewChartByMonth()
    {
        // the month we wish to see:
        $date = new Carbon;
        $date->startOfMonth();
        // find an account:
        $account = DB::table('accounts')->whereUserId($this->_user->id)->first();

        // fire!
        $response = $this->call('GET', 'home/account/' . $account->id . '/overview/chart/' . $date->format('Y/m'));
        $raw = $response->getContent();
        // decode JSON response:
        $json = json_decode($raw);

        //print_r($json);

        // scan JSON response:
        $this->assertCount(intval($date->format('t')), $json->rows); // number of days in month
        $this->assertCount(9, $json->cols); // 9 columns (date, name, 4x interval, 2x anno, 1x certainty
    }

    /**
     * @covers AccountController::add
     */
    public function testAddWithOldInput()
    {
        // array with old input:
        $data = [
            'name'               => Str::random(16),
            'openingbalance'     => 1234,
            'openingbalancedate' => date('Y-m-d'),
            'shared'             => 1
        ];
        $this->session(['_old_input' => $data]);

        $response = $this->action('GET', 'AccountController@add');
        $view = $response->original;

        // is OK?
        $this->assertResponseOk();

        // empty prefilled array should have five entries:
        $this->assertCount(5, $view['prefilled']);

        // check some variables in the prefilled array:
        $this->assertEquals($data['name'], $view['prefilled']['name']);
        $this->assertEquals($data['openingbalance'], $view['prefilled']['openingbalance']);
        $this->assertEquals($data['openingbalancedate'], $view['prefilled']['openingbalancedate']);

        // check the title:
        $this->assertEquals('Add a new account', $view['title']);

    }

    /**
     * @covers AccountController::postAdd
     */
    public function testPostAddWithInvalidData()
    {
        // count the number of accounts
        $current = DB::table('accounts')->whereUserId($this->_user->id)->count();

        // the data we will create a new account with:
        $data = [
            'name'               => null,
            'openingbalance'     => null,
            'openingbalancedate' => null,
            'hidden'             => null,
            'shared'             => null
        ];

        // fire!
        $this->action('POST', 'AccountController@postAdd', $data);

        // is OK?
        $this->assertResponseStatus(302);

        // count again
        $new = DB::table('accounts')->whereUserId($this->_user->id)->count();

        $this->assertSessionHas('error');
        $this->assertEquals($current, $new);
        $this->assertRedirectedToAction('AccountController@add');

    }

    /**
     * @covers AccountController::postAdd
     */
    public function testPostAddFailsTrigger()
    {
        // count the number of accounts
        $current = DB::table('accounts')->whereUserId($this->_user->id)->count();

        // get an account, we'll use its name as input (which should subsequently fail)
        $account = DB::table('accounts')->whereUserId($this->_user->id)->first();

        // the data we will create a new account with:
        $data = [
            'name'               => Crypt::decrypt($account->name),
            'openingbalance'     => 100,
            'openingbalancedate' => date('Y-m-d'),
            'hidden'             => 0,
            'shared'             => 0
        ];

        // fire!
        $this->action('POST', 'AccountController@postAdd', $data);

        // is OK?
        $this->assertResponseStatus(302);

        // count again
        $new = DB::table('accounts')->whereUserId($this->_user->id)->count();

        $this->assertSessionHas('error');
        $this->assertEquals($current, $new);
        $this->assertRedirectedToAction('AccountController@add');

    }

    /**
     * @covers AccountController::edit
     */
    public function testEditWithOldInput()
    {
        // array with old input:
        $data = [
            'name'               => Str::random(16),
            'openingbalance'     => 1234,
            'openingbalancedate' => date('Y-m-d'),
            'shared'             => 1
        ];
        $this->session(['_old_input' => $data]);

        // find account to edit:
        $account = DB::table('accounts')->whereUserId($this->_user->id)->first();

        // fire!
        $response = $this->action('get', 'AccountController@edit', $account);
        $view = $response->original;

        // is OK?
        $this->assertResponseOk();

        // prefilled array should have five entries:
        $this->assertCount(5, $view['prefilled']);

        // prefilled array should match our pre-filled array:
        $this->assertEquals($data['name'], $view['prefilled']['name']);
        $this->assertEquals($data['openingbalance'], $view['prefilled']['openingbalance']);
        $this->assertEquals($data['openingbalancedate'], $view['prefilled']['openingbalancedate']);

        // account object should match our object:
        $this->assertEquals(Crypt::decrypt($account->name), $view['account']->name);
        $this->assertEquals($account->currentbalance, $view['account']->currentbalance);

        // check the title
        $this->assertEquals('Edit account "' . Crypt::decrypt($account->name) . '"', $view['title']);


    }

    /**
     * @covers AccountController::postEdit
     */
    public function testPostEditWithInvalidData()
    {
        // find account to edit:
        $account = DB::table('accounts')->whereUserId($this->_user->id)->first();

        // the data to update the account with:
        $data = [
            'name'               => null,
            'openingbalance'     => null,
            'openingbalancedate' => null,
            'shared'             => null,
            'hidden'             => null
        ];

        // fire the update!
        $this->call('POST', 'home/account/' . $account->id . '/edit', $data);

        // result should be OK
        $this->assertResponseStatus(302);

        // session should be faulty:
        $this->assertSessionHas('error');

        // redirect back.
        $this->assertRedirectedToRoute('editaccount',$account->id);

    }

    /**
     * @covers AccountController::postEdit
     */
    public function testPostEditFailsTrigger()
    {
        // find account to edit:
        $account = DB::table('accounts')->whereUserId($this->_user->id)->first();

        // find another account:
        $secondAccount = DB::table('accounts')->where('id', '!=', $account->id)->whereUserId($this->_user->id)->first();

        // the data to update the account with:
        $data = [
            'name'               => Crypt::decrypt($secondAccount->name),
            'openingbalance'     => rand(20, 2000),
            'openingbalancedate' => $this->_date,
            'shared'             => 1,
            'hidden'             => 1
        ];

        // fire the update!
        $this->call('POST', 'home/account/' . $account->id . '/edit', $data);

        // result should be OK
        $this->assertResponseStatus(302);

        // session should be faulty:
        $this->assertSessionHas('error');

        // redirect back.
        $this->assertRedirectedToRoute('editaccount',$account->id);

    }

    /**
     * @covers AccountController::showOverviewChartByMonth
     */
    public function testShowOverviewChartByMonthFutureMonth()
    {
        // the month we wish to see:
        $date = new Carbon;
        $date->addYear();
        $date->startOfMonth();
        // find an account:
        $account = DB::table('accounts')->whereUserId($this->_user->id)->first();

        // fire!
        $response = $this->call('GET', 'home/account/' . $account->id . '/overview/chart/' . $date->format('Y/m'));
        $raw = $response->getContent();
        // decode JSON response:
        $json = json_decode($raw);

        //print_r($json);

        // scan JSON response:
        $this->assertCount(intval($date->format('t')), $json->rows); // number of days in month
        $this->assertCount(9, $json->cols); // 9 columns (date, name, 4x interval, 2x anno, 1x certainty

    }
}
