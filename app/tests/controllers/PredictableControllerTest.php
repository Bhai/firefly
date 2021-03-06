<?php

use Carbon\Carbon as Carbon;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.1 on 2014-04-07 at 16:30:44.
 */
class PredictableControllerTest extends TestCase
{
    /**
     * @var User
     */
    protected $_user;

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
    }

    /**
     * @covers PredictableController::index
     */
    public function testIndex()
    {
        // grab the index
        $response = $this->action('GET', 'PredictableController@index');
        $view = $response->original;

        // should be okay
        $this->assertResponseOk();

        // count should equal
        $count = DB::table('predictables')->whereUserId($this->_user->id)->count();
        $this->assertCount($count, $view['predictables']);

        // title should match
        $this->assertEquals('Predictables', $view['title']);

    }

    /**
     * @covers PredictableController::overview
     */
    public function testOverview()
    {
        // grab a predictable to overview:
        $predictable = DB::table('predictables')->whereAmount(-500)->whereUserId($this->_user->id)->first();

        // grab the page
        $response = $this->action('GET', 'PredictableController@overview', $predictable);
        $view = $response->original;

        // should be OK
        $this->assertResponseOk();

        // date (domDisplay) matches?
        $domDisplay = new Carbon('2012-01-' . $predictable->dom);
        $this->assertEquals($domDisplay->format('jS'), $view['predictable']->domDisplay);

        // title matches?
        $this->assertEquals('Overview for ' . Crypt::decrypt($predictable->description), $view['title']);

        // ID matches?
        $this->assertEquals($predictable->id, $view['predictable']->id);

    }

    /**
     * @covers PredictableController::add
     */
    public function testAdd()
    {
        // grab the page
        $response = $this->action('GET', 'PredictableController@add');
        $view = $response->original;

        // should be OK
        $this->assertResponseOk();

        // prefilled should be 9 long
        $this->assertCount(9, $view['prefilled']);

        // and empty
        $this->assertEquals('', $view['prefilled']['description']);
        $this->assertEquals(10, $view['prefilled']['pct']);

        // title must match
        $this->assertEquals('Add a predictable', $view['title']);

        // list should be 3
        $this->assertCount(3, $view['components']);
    }

    /**
     * @covers PredictableController::add
     */
    public function testAddWithOldInput()
    {

        // array with old input:
        $data = [
            'description' => Str::random(16),
            'amount'      => -100,
            'pct'         => 15,
            'dom'         => 12,
            'beneficiary' => 0,
            'category'    => 0,
            'budget'      => 0,
            'inactive'    => false
        ];
        $this->session(['_old_input' => $data]);

        // grab the page
        $response = $this->action('GET', 'PredictableController@add');
        $view = $response->original;

        // should be OK
        $this->assertResponseOk();

        // prefilled should be 9 long
        $this->assertCount(9, $view['prefilled']);

        // and empty
        $this->assertEquals($data['description'], $view['prefilled']['description']);
        $this->assertEquals($data['pct'], $view['prefilled']['pct']);

        // title must match
        $this->assertEquals('Add a predictable', $view['title']);

        // list should be 3
        $this->assertCount(3, $view['components']);
    }

    /**
     * @covers PredictableController::postAdd
     * @todo   Implement testPostAdd().
     */
    public function testPostAdd()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers PredictableController::edit
     * @todo   Implement testEdit().
     */
    public function testEdit()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers PredictableController::postEdit
     * @todo   Implement testPostEdit().
     */
    public function testPostEdit()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers PredictableController::delete
     * @todo   Implement testDelete().
     */
    public function testDelete()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers PredictableController::postDelete
     * @todo   Implement testPostDelete().
     */
    public function testPostDelete()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers PredictableController::rescan
     * @todo   Implement testRescan().
     */
    public function testRescan()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers PredictableController::rescanAll
     * @todo   Implement testRescanAll().
     */
    public function testRescanAll()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }
}
