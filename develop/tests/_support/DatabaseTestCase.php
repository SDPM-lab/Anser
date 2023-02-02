<?php namespace Tests\Support;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Database\Migrations\initDataBase;

/**
 * 測試用資料庫環境初始化
 */
class DatabaseTestCase extends CIUnitTestCase
{
	use FeatureTestTrait, DatabaseTestTrait;

	/**
	 * Should the database be refreshed before each test?
	 *
	 * @var boolean
	 */
	protected $refresh = false;

	protected $seed = '';

	protected $basePath = SUPPORTPATH . 'Database/';

	protected $namespace = null;

	protected $migrate = true;

	/**
	 * 全域可用的使用者主鍵
	 * 用來產生基本關聯
	 *
	 * @var int
	 */
	protected $userKey;

	public function setUp(): void
	{
		parent::setUp();
		InitDatabase::InitDatabase('tests');
		// Extra code to run before each test
	}

	public function tearDown(): void
	{
		parent::tearDown();
		// \Config\Services::migrations()->regress();
		// InitDatabase::deleteTables('tests');
	}
}
