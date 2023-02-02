<?php


use Tests\Support\DatabaseTestCase;

class PaymentTest extends DatabaseTestCase{

    public function setUp(): void
	{
		parent::setUp();

		// Extra code to run before each test
	}

    public function tearDown(): void
	{
		parent::tearDown();

        $this->db->table('db_payment')->emptyTable('db_payment');

	}
    public function testIndex()
    {
        
    }
}
