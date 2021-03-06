<?php
/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.1 on 2014-04-07 at 16:29:33.
 */
class PageControllerTest extends TestCase
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
     * @covers PageController::recalculate
     * @todo   Implement testRecalculate().
     */
    public function testRecalculate()
    {
        $this->action('GET', 'PageController@recalculate');

        // is ok!
        $this->assertResponseStatus(302);

        // is redirect to index!
        $this->assertRedirectedToAction('HomeController@showIndex');


    }

    /**
     * @covers PageController::flush
     * @todo   Implement testRecalculate().
     */
    public function testFlush()
    {
        Cache::put('testkey','testvalue',10);

        $this->assertTrue(Cache::has('testkey'));
        $this->action('GET', 'PageController@flush');
        $this->assertFalse(Cache::has('testkey'));

        // TODO validate empty cache

        // is ok!
        $this->assertResponseStatus(302);

        // is redirect to index!
        $this->assertRedirectedToAction('HomeController@showIndex');
    }
}
