<?php

namespace Kirby\Cms;

use Kirby\Exception\DuplicateException;
use Kirby\Exception\LogicException;
use Kirby\Exception\PermissionException;
use Kirby\Filesystem\Dir;
use PHPUnit\Framework\TestCase;

class ContentLockTest extends TestCase
{
	protected $app;
	protected $fixtures;

	public function app()
	{
		return new App([
			'roots' => [
				'index' => $this->fixtures = __DIR__ . '/fixtures/ContentLockTest'
			],
			'site' => [
				'children' => [
					['slug' => 'test'],
					['slug' => 'foo']
				]
			],
			'users' => [
				['email' => 'test@getkirby.com'],
				['email' => 'homer@simpson.com'],
				['email' => 'peter@lustig.de']
			]
		]);
	}

	public function setUp(): void
	{
		$this->app = $this->app();
		Dir::make($this->fixtures . '/content/test');
	}

	public function tearDown(): void
	{
		Dir::remove($this->fixtures);
	}

	public function testCreate()
	{
		$app = $this->app;
		$page = $app->page('test');

		$app->impersonate('test@getkirby.com');
		$this->assertTrue($page->lock()->create());
		$this->assertTrue($page->lock()->create());

		$this->assertFalse(empty($app->locks()->get($page)));
	}

	public function testCreateWithExistingLock()
	{
		$this->expectException(DuplicateException::class);
		$this->expectExceptionMessage('/test is already locked');

		$app = $this->app;
		$page = $app->page('test');

		$app->impersonate('test@getkirby.com');
		$this->assertTrue($page->lock()->create());

		$app->impersonate('homer@simpson.com');
		$page->lock()->create();
	}

	public function testCreateUnauthenticated()
	{
		$this->expectException(PermissionException::class);
		$this->expectExceptionMessage('No user authenticated');

		$app = $this->app;
		$page = $app->page('test');
		$page->lock()->create();
	}

	public function testGetWithNoLock()
	{
		$app = $this->app;
		$page = $app->page('test');

		$this->assertFalse($page->lock()->get());
	}

	public function testGetWithSameUser()
	{
		$app = $this->app;
		$page = $app->page('test');

		$app->impersonate('test@getkirby.com');
		$page->lock()->create();

		$this->assertFalse($page->lock()->get());
	}

	public function testGet()
	{
		$app = $this->app;
		$page = $app->page('test');

		$app->impersonate('test@getkirby.com');
		$page->lock()->create();

		$app->impersonate('homer@simpson.com');
		$data = $page->lock()->get();

		$this->assertFalse(empty($data));
		$this->assertFalse($data['unlockable']);
		$this->assertEquals('test@getkirby.com', $data['email']);
		$this->assertArrayHasKey('time', $data);
	}

	public function testGetUserMissing()
	{
		$app = $this->app;
		$page = $app->page('test');

		$app->impersonate('test@getkirby.com');
		$page->lock()->create();
		$this->assertFileExists($this->fixtures . '/content/test/.lock');

		$app->impersonate('homer@simpson.com');
		$data = $page->lock()->get();
		$this->assertFileExists($this->fixtures . '/content/test/.lock');
		$this->assertFalse(empty($data));
		$this->assertFalse($data['unlockable']);
		$this->assertEquals('test@getkirby.com', $data['email']);
		$this->assertArrayHasKey('time', $data);

		$app->users()->remove($app->user('test@getkirby.com'));
		$data = $page->lock()->get();
		$this->assertFileDoesNotExist($this->fixtures . '/content/test/.lock');
		$this->assertFalse($data);
	}

	public function testIsLocked()
	{
		$app = $this->app;
		$page = $app->page('test');

		$app->impersonate('test@getkirby.com');
		$page->lock()->create();
		$this->assertFalse($page->lock()->isLocked());

		$app->impersonate('homer@simpson.com');
		$this->assertTrue($page->lock()->isLocked());
	}

	public function testRemoveWithNoLock()
	{
		$app = $this->app;
		$page = $app->page('test');
		$app->impersonate('test@getkirby.com');

		$this->assertTrue($page->lock()->remove());
	}

	public function testRemoveFormOtherUser()
	{
		$this->expectException(LogicException::class);
		$this->expectExceptionMessage('The content lock can only be removed by the user who created it. Use unlock instead.');

		$app = $this->app;
		$page = $app->page('test');
		$app->impersonate('test@getkirby.com');
		$page->lock()->create();

		$app->impersonate('homer@simpson.com');
		$page->lock()->remove();
	}

	public function testRemove()
	{
		$app = $this->app;
		$page = $app->page('test');

		$app->impersonate('test@getkirby.com');

		$this->assertTrue($page->lock()->create());
		$this->assertFalse(empty($app->locks()->get($page)));

		$this->assertTrue($page->lock()->remove());
		$this->assertTrue(empty($app->locks()->get($page)));
	}

	public function testUnlockWithNoLock()
	{
		$app = $this->app;
		$page = $app->page('test');
		$app->impersonate('test@getkirby.com');

		$this->assertTrue($page->lock()->unlock());
	}

	public function testUnlock()
	{
		$app = $this->app;
		$page = $app->page('test');

		$app->impersonate('test@getkirby.com');
		$this->assertTrue($page->lock()->create());

		$app->impersonate('homer@simpson.com');
		$this->assertTrue($page->lock()->unlock());

		$this->assertFalse(empty($app->locks()->get($page)['unlock']));
	}

	public function testIsUnlocked()
	{
		$app = $this->app;
		$page = $app->page('test');

		$app->impersonate('test@getkirby.com');
		$this->assertTrue($page->lock()->create());

		$app->impersonate('homer@simpson.com');
		$this->assertTrue($page->lock()->unlock());
		$this->assertFalse($page->lock()->isUnlocked());

		$app->impersonate('test@getkirby.com');
		$this->assertTrue($page->lock()->isUnlocked());
	}

	public function testResolveWithNoUnlock()
	{
		$app = $this->app;
		$page = $app->page('test');

		$app->impersonate('test@getkirby.com');
		$this->assertTrue($page->lock()->create());

		$app->impersonate('homer@simpson.com');
		$this->assertTrue($page->lock()->resolve());
	}

	public function testResolve()
	{
		$app = $this->app;
		$page = $app->page('test');

		$app->impersonate('test@getkirby.com');
		$this->assertTrue($page->lock()->create());

		$app->impersonate('homer@simpson.com');
		$this->assertTrue($page->lock()->unlock());
		$this->assertFalse(empty($app->locks()->get($page)['unlock']));

		$app->impersonate('test@getkirby.com');
		$this->assertTrue($page->lock()->isUnlocked());
		$this->assertTrue($page->lock()->resolve());
		$this->assertFalse($page->lock()->isUnlocked());
		$this->assertTrue(empty($app->locks()->get($page)['unlock']));
	}

	public function testResolveWithRemainingUnlocks()
	{
		$app = $this->app;
		$page = $app->page('test');

		$app->impersonate('test@getkirby.com');
		$this->assertTrue($page->lock()->create());

		$app->impersonate('homer@simpson.com');
		$this->assertTrue($page->lock()->unlock());
		$this->assertEquals(count($app->locks()->get($page)['unlock']), 1);
		$this->assertTrue($page->lock()->create());

		$app->impersonate('peter@lustig.de');
		$this->assertTrue($page->lock()->unlock());
		$this->assertEquals(count($app->locks()->get($page)['unlock']), 2);

		$app->impersonate('test@getkirby.com');
		$this->assertTrue($page->lock()->isUnlocked());
		$this->assertTrue($page->lock()->resolve());
		$this->assertFalse($page->lock()->isUnlocked());
		$this->assertEquals(count($app->locks()->get($page)['unlock']), 1);

		$app->impersonate('homer@simpson.com');
		$this->assertTrue($page->lock()->isUnlocked());
	}

	public function testState()
	{
		$app = $this->app;
		$page = $app->page('test');

		$app->impersonate('peter@lustig.de');

		$page->lock()->create();

		$this->assertSame(null, $page->lock()->state());

		$app->impersonate('test@getkirby.com');

		// state is locked
		$this->assertSame('lock', $page->lock()->state());

		// user force unlocks the lock
		$page->lock()->unlock();

		$app->impersonate('peter@lustig.de');

		// state is now unlock for the original user
		$this->assertSame('unlock', $page->lock()->state());
	}

	public function testToArray()
	{
		$app = $this->app;
		$page = $app->page('test');

		$app->impersonate('peter@lustig.de');

		$page->lock()->create();

		$expected = [
			'state' => null,
			'data'  => false
		];

		$this->assertSame($expected, $page->lock()->toArray());

		$app->impersonate('test@getkirby.com');

		$lockArray = $page->lock()->toArray();

		// state is locked
		$this->assertSame('lock', $lockArray['state']);
		$this->assertSame('peter@lustig.de', $lockArray['data']['email']);

		// user force unlocks the lock
		$page->lock()->unlock();

		$app->impersonate('peter@lustig.de');

		$lockArray = $page->lock()->toArray();

		// state is locked
		$this->assertSame('unlock', $lockArray['state']);
		$this->assertSame(false, $lockArray['data']);
	}
}
