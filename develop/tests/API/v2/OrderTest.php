<?php


use Tests\Support\DatabaseTestCase;

class OrderTest extends DatabaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Extra code to run before each test
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->db->table('db_order')->emptyTable('db_order');
    }
    public function testIndex()
    {
    }
}
