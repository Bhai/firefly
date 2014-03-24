<?php

class AccountControllerTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();
        $user = User::where('username', 'test')->first();
        $this->be($user);
    }

    private $balance = 543.21; // used to find the right account.

    public function testShowIndex()
    {
        $accounts = Auth::user()->accounts()->count();
        $response = $this->call('GET', 'home/account');
        $view = $response->original;
        $this->assertResponseStatus(200);
        $this->assertEquals('All accounts', $view['title']);
        $this->assertCount($accounts, $view['accounts']);
    }

    public function testAdd()
    {
        $crawler = $this->client->request('GET', 'home/account/add');
        $this->assertCount(1, $crawler->filter('h2:contains("Add a new account")'));
        $this->assertCount(1, $crawler->filter('title:contains("Add account")'));
        $this->assertCount(1,$crawler->filter('input[name="shared"]'));
        $this->assertCount(1,$crawler->filter('label[for="inputShared"]'));


        $this->assertResponseStatus(200);
        $this->assertSessionHas('previous');

    }

    public function testEmptyPostAdd()
    {
        $count = Auth::user()->accounts()->count();
        $this->call('POST', 'home/account/add');
        $newCount = Auth::user()->accounts()->count();
        $this->assertEquals($count,$newCount);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('error');
        $this->assertHasOldInput();
    }

    public function testFilledPostAdd()
    {
        $count = Auth::user()->accounts()->count();
        // account data:
        $data = ['name'               => 'New-test-account',
                 'openingbalance'     => $this->balance,
                 'openingbalancedate' => date('Y-m-d'),
        'shared' => 1];

        $this->call('POST', 'home/account/add', $data);

        // find account:
        $account = Account::where('openingbalance', $this->balance)->first();
        $newCount = Auth::user()->accounts()->count();

        $this->assertNotNull($account);
        $this->assertEquals($newCount, ($count + 1));
        $this->assertResponseStatus(302);
        $this->assertRedirectedToRoute('index');
        $this->assertSessionHas('success');
        $this->assertEquals($account->openingbalance, $data['openingbalance']);
        $this->assertEquals($account->name, $data['name']);
        $this->assertEquals($account->shared, $data['shared']);
    }

    /**
     * @depends testFilledPostAdd
     */
    public function testDoubleFilledPostAdd()
    {
        $count = Auth::user()->accounts()->count();

        // account data:
        $data = ['name' => 'New-test-account', 'openingbalance' => 300,
                 'openingbalancedate' => date('Y-m-d')];
        $this->call('POST', 'home/account/add', $data);
        $newCount = Auth::user()->accounts()->count();

        $this->assertEquals($count, $newCount);
        $this->assertResponseStatus(302);
        $this->assertRedirectedToRoute('addaccount');
        $this->assertSessionHas('error');
    }


    /**
     * @depends testFilledPostAdd
     */
    public function testEdit()
    {
        $account = Auth::user()->accounts()->where(
            'openingbalance', $this->balance
        )->first();
        $crawler = $this->client->request('GET', 'home/account/' . $account->id . '/edit');
        $this->assertCount(1, $crawler->filter('h2:contains("Edit '.$account->name.'")'));
        $this->assertCount(1, $crawler->filter('title:contains("Edit account '.$account->name.'")'));
        $this->assertCount(1,$crawler->filter('input[name="shared"]'));
        $this->assertCount(1,$crawler->filter('label[for="inputShared"]'));

        $this->assertResponseStatus(200);
        $this->assertSessionHas('previous');
    }

    public function testPostEdit()
    {
        $account = Auth::user()->accounts()->where(
            'openingbalance', $this->balance
        )->first();
        $count = Auth::user()->accounts()->count();
        $data = ['name'               => 'New-test-account-edited',
                 'openingbalance'     => $this->balance,
                 'openingbalancedate' => date('Y-m-d')];
        $this->call('POST', 'home/account/' . $account->id . '/edit', $data);
        $newCount = Auth::user()->accounts()->count();
        $this->assertEquals($newCount, $count);
        $this->assertResponseStatus(302);
        $this->assertRedirectedToRoute('index');
        $this->assertSessionHas('success');

    }

    public function testPostFailedEdit()
    {
        $account = Auth::user()->accounts()->where(
            'openingbalance', $this->balance
        )->first();
        $count = Auth::user()->accounts()->count();
        $data = ['name' => null, 'openingbalance' => $this->balance,
                 'openingbalancedate' => date('Y-m-d')];
        $this->call('POST', 'home/account/' . $account->id . '/edit', $data);
        $newCount = Auth::user()->accounts()->count();
        $this->assertEquals($count, $newCount);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('error');
        $this->assertRedirectedToRoute('editaccount', $account->id);

    }

    public function testPostDoubleEdit()
    {
        $account = Auth::user()->accounts()->where(
            'openingbalance', $this->balance
        )->first();
        $data = ['name' => 'TestAccount #1', 'openingbalance' => $this->balance,
                 'openingbalancedate' => date('Y-m-d')];
        $this->call('POST', 'home/account/' . $account->id . '/edit', $data);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('error');
        $this->assertRedirectedToRoute('editaccount', $account->id);

    }

    /**
     * @depends testFilledPostAdd
     */
    public function testDelete()
    {
        $account = Auth::user()->accounts()->where(
            'openingbalance', $this->balance
        )->first();
        $response = $this->call(
            'GET', 'home/account/' . $account->id . '/delete'
        );

        $view = $response->original;
        $this->assertResponseStatus(200);
        $this->assertSessionHas('previous');

        $this->assertEquals($view['title'], 'Delete account ' . $account->name);

        $this->assertEquals($view['account']->name, $account->name);
    }

    public function testPostDelete()
    {
        $count = Auth::user()->accounts()->count();
        $account = Account::where('openingbalance', $this->balance)->first();
        $this->call('POST', 'home/account/' . $account->id . '/delete');
        $newCount = Auth::user()->accounts()->count();
        $this->assertResponseStatus(302);
        $this->assertEquals($count, ($newCount + 1));
        $this->assertSessionHas('success');
        $this->assertRedirectedToRoute('index');
    }

    public function testShowOverview()
    {
        $account = Auth::user()->accounts()->first();
        $response = $this->call(
            'GET', 'home/account/' . $account->id . '/overview'
        );
        $view = $response->original;
        $this->assertResponseStatus(200);
        $this->assertEquals($view['title'], 'Overview for ' . $account->name);
        $this->assertEquals($view['account']->name, $account->name);

        // the app falls back to
        $start = Config::get('firefly.predictionStart');
        $date = new Carbon\Carbon($start['value']);
        $diff = $date->diffInMonths(new Carbon\Carbon);

        $this->assertCount(($diff + 1), $view['transactions']);
    }

    public function testShowOverviewByMonth()
    {
        $account = Auth::user()->accounts()->first();
        $response = $this->call(
            'GET', 'home/account/' . $account->id . '/overview/' . date('Y/m')
        );
        $view = $response->original;
        $this->assertResponseStatus(200);
        $this->assertEquals(
            $view['title'],
            'Overview for ' . $account->name . ' in ' . date('F Y')
        );
        $this->assertEquals($view['account']->name, $account->name);
        $count = $account->transactions()->inMonth(new Carbon\Carbon)->count();
        $this->assertCount($count, $view['transactions']);
    }

    public function testShowChartOverview()
    {
        $account = Auth::user()->accounts()->first();
        $response = $this->call(
            'GET', 'home/account/' . $account->id . '/overview/chart'
        );
        $this->assertResponseStatus(200);
        $this->assertNotNull($response);

        $jsonResponse = $this->client->getResponse()->getContent();
        $responseData = json_decode($jsonResponse, true);
        $this->assertArrayHasKey('cols', $responseData);
        $this->assertArrayHasKey('rows', $responseData);
        $this->assertCount(7, $responseData['cols']);
    }

    public function testShowChartOverviewDebug()
    {
        $account = Auth::user()->accounts()->first();
        $response = $this->call(
            'GET', 'home/account/' . $account->id . '/overview/chart?debug=true'
        );
        $this->assertResponseStatus(200);
        $this->assertNotNull($response);
    }

    public function testShowChartOverviewByMonth()
    {
        $account = Auth::user()->accounts()->first();
        $response = $this->call(
            'GET',
            'home/account/' . $account->id . '/overview/chart/' . date('Y/m')
        );
        $this->assertResponseStatus(200);
        $this->assertNotNull($response);

        $jsonResponse = $this->client->getResponse()->getContent();
        $responseData = json_decode($jsonResponse, true);
        $this->assertArrayHasKey('cols', $responseData);
        $this->assertArrayHasKey('rows', $responseData);
        $this->assertCount(7, $responseData['cols']);
    }

    public function testShowChartOverviewByMonthDebug()
    {
        $account = Auth::user()->accounts()->first();
        $response = $this->call(
            'GET',
            'home/account/' . $account->id . '/overview/chart/' . date('Y/m')
            . '?debug=true'
        );
        $this->assertResponseStatus(200);
        $this->assertNotNull($response);
    }

    public function testShowChartAllOverview()
    {
        $response = $this->call(
            'GET', 'home/account/overview/chart/' . date('Y/m')
        );
        $this->assertResponseStatus(200);
        $this->assertNotNull($response);

        $accounts = Auth::user()->accounts()->count();
        $columns = $accounts * 3 + 1;

        $jsonResponse = $this->client->getResponse()->getContent();
        $responseData = json_decode($jsonResponse, true);
        $this->assertArrayHasKey('cols', $responseData);
        $this->assertArrayHasKey('rows', $responseData);
        $this->assertCount($columns, $responseData['cols']);

    }


}