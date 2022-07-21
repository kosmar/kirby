<?php

namespace Kirby\Blueprint;

/**
 * Users field
 *
 * @package   Kirby Blueprint
 * @author    Bastian Allgeier <bastian@getkirby.com>
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier
 * @license   https://opensource.org/licenses/MIT
 */
class UsersField extends PickerField
{
	public const TYPE = 'users';

	public function __construct(
		public string $id,
		...$args
	) {
		parent::__construct($id, ...$args);
	}
}
