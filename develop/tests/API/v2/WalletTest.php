<?php


use Tests\Support\DatabaseTestCase;

class WalletTest extends DatabaseTestCase{

    public function setUp(): void
	{
		parent::setUp();

		// Extra code to run before each test
	}

    public function tearDown(): void
	{
		parent::tearDown();

        $this->db->table('db_wallet')->emptyTable('db_wallet');

	}
    public function testIndex()
    {
        
    }
}
