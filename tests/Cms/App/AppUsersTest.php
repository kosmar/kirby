<?php

namespace Kirby\Cms;

use Exception;
use Kirby\Exception\InvalidArgumentException;
use Kirby\Exception\NotFoundException;
use Kirby\Exception\PermissionException;
use Kirby\Filesystem\Dir;
use Kirby\Http\Request\Auth\BasicAuth;

class AppUsersTest extends TestCase
{
	protected $app;
	protected $fixtures;

	public function setUp(): void
	{
		$this->app = new App([
			'roots' => [
				'index' => $this->fixtures = __DIR__ . '/fixtures/AppUsersTest'
			]
		]);
	}

	public function tearDown(): void
	{
		Dir::remove($this->fixtures);
	}

	public function testImpersonate()
	{
		$self = $this;

		$app = $this->app->clone([
			'users' => [
				[
					'id'    => 'testtest',
					'email' => 'homer@simpsons.com',
					'role'  => 'admin'
				]
			]
		]);

		// impersonate as kirby
		$user = $app->impersonate('kirby');
		$this->assertSame($user, $app->user());
		$this->assertSame('kirby', $user->id());
		$this->assertSame('kirby@getkirby.com', $user->email());
		$this->assertSame('admin', $user->role()->name());
		$this->assertTrue($user->isKirby());
		$this->assertNull($app->user(null, false));

		// impersonate as existing user
		$user = $app->impersonate('homer@simpsons.com');
		$this->assertSame($user, $app->user());
		$this->assertSame('homer@simpsons.com', $user->email());
		$user = $app->impersonate('testtest');
		$this->assertSame($user, $app->user());
		$this->assertSame('homer@simpsons.com', $user->email());
		$this->assertNull($app->user(null, false));

		// impersonate as nobody
		$user = $app->impersonate('nobody');
		$this->assertSame('nobody', $user->id());
		$this->assertSame('nobody@getkirby.com', $user->email());
		$this->assertSame('nobody', $user->role()->name());
		$this->assertTrue($user->isNobody());
		$this->assertSame($user, $app->user());
		$this->assertNull($app->user(null, false));

		// unimpersonate
		$user = $app->impersonate();
		$this->assertNull($user);
		$this->assertNull($app->user());
		$this->assertNull($app->user(null, false));

		// with callback
		$result = $app->impersonate('homer@simpsons.com', function ($user) use ($app, $self) {
			$self->assertSame($user, $app->user());
			$self->assertSame('homer@simpsons.com', $user->email());
			$self->assertNull($app->user(null, false));

			return 'test1';
		});
		$this->assertSame('test1', $result);
		$this->assertNull($app->user());
		$this->assertNull($app->user(null, false));

		// with Exception in the callback
		$app->impersonate('kirby');
		$caught = false;
		try {
			$app->impersonate('homer@simpsons.com', function ($user) use ($app, $self) {
				$self->assertSame($user, $app->user());
				$self->assertSame('homer@simpsons.com', $user->email());
				$self->assertNull($app->user(null, false));

				throw new Exception('Something bad happened');
			});
		} catch (Exception $e) {
			$caught = true;

			$this->assertSame('Something bad happened', $e->getMessage());

			// the previous user should be restored
			$this->assertSame('kirby@getkirby.com', $app->user()->email());
			$this->assertNull($app->user(null, false));
		}
		$this->assertTrue($caught);
	}

	public function testImpersonateErrorMissingUser()
	{
		$this->expectException(NotFoundException::class);
		$this->app->impersonate('homer@simpsons.com');
	}

	/**
	 * @todo remove in 3.9.0
	 */
	public function testImpersonateDeprecatedThisAsApp()
	{
		$app = $this->app->clone([
			'options' => [
				'debug' => false
			],
			'site' => [
				'content' => ['title' => 'Foo']
			]
		]);

		$self = $this;
		$app->impersonate('kirby', function () use ($self) {
			$self->assertSame('Foo', (string)$this->site()->title());
		});

		// with debug mode on

		$app = $this->app->clone([
			'options' => [
				'debug' => true
			]
		]);

		$this->expectExceptionMessage('Calling $kirby->site() as $this->site() has been deprecated inside the $kirby->impersonate() callback function. Use a dedicated $kirby object for your call instead of $this. In Kirby 3.9.0 $this will no longer refer to the $kirby object, but the current context of the callback function.');

		$app->impersonate('kirby', function () {
			$this->site()->title();
		});
	}

	public function testLoad()
	{
		$app = $this->app->clone([
			'roots' => [
				'site' => __DIR__ . '/fixtures'
			]
		]);

		$this->assertCount(1, $app->users());
		$this->assertEquals('user@getkirby.com', $app->users()->first()->email());
	}

	public function testSet()
	{
		$app = $this->app->clone([
			'users' => [
				[
					'email' => 'user@getkirby.com'
				]
			]
		]);

		$this->assertCount(1, $app->users());
		$this->assertEquals('user@getkirby.com', $app->users()->first()->email());
	}

	public function basicAuthApp()
	{
		return $this->app->clone([
			'options' => [
				'api' => [
					'basicAuth' => true
				]
			],
			'users' => [
				[
					'email'    => 'test@getkirby.com',
					'password' => User::hashPassword('correct-horse-battery-staple')
				]
			],
			'request' => [
				'url' => 'https://getkirby.com/login'
			]
		]);
	}

	public function testUserFromBasicAuth()
	{
		$app  = $this->basicAuthApp();
		$auth = new BasicAuth(base64_encode('test@getkirby.com:correct-horse-battery-staple'));
		$user = $app->auth()->currentUserFromBasicAuth($auth);

		$this->assertInstanceOf(User::class, $user);
		$this->assertEquals('test@getkirby.com', $user->email());
	}

	public function testUserFromBasicAuthDisabled()
	{
		$this->expectException(PermissionException::class);
		$this->expectExceptionMessage('Basic authentication is not activated');

		$app = $this->basicAuthApp()->clone([
			'options' => [
				'api' => [
					'basicAuth' => false
				]
			]
		]);

		$auth = new BasicAuth(base64_encode('test@getkirby.com:correct-horse-battery-staple'));
		$user = $app->auth()->currentUserFromBasicAuth($auth);
	}

	public function testUserFromBasicAuthOverHttp()
	{
		$this->expectException(PermissionException::class);
		$this->expectExceptionMessage('Basic authentication is only allowed over HTTPS');

		$app = $this->basicAuthApp()->clone([
			'request' => [
				'url' => 'http://getkirby.com/login'
			]
		]);

		$auth = new BasicAuth(base64_encode('test@getkirby.com:correct-horse-battery-staple'));
		$user = $app->auth()->currentUserFromBasicAuth($auth);
	}

	public function testUserFromBasicAuthWithInvalidHeader()
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid authorization header');

		$app = $this->basicAuthApp()->clone([
			'request' => [
				'url' => 'http://getkirby.com/login'
			]
		]);

		$user = $app->auth()->currentUserFromBasicAuth();
	}
}
