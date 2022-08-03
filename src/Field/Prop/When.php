<?php

namespace Kirby\Field\Prop;

use Kirby\Cms\ModelWithContent;
use Kirby\Foundation\Factory;
use Kirby\Foundation\Renderable;

/**
 * Conditions when the field will be shown
 *
 * @since     3.1.0
 * @package   Kirby Field
 * @author    Bastian Allgeier <bastian@getkirby.com>
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier
 * @license   https://opensource.org/licenses/MIT
 */
class When implements Factory, Renderable
{
	public function __construct(
		public array $conditions = []
	) {
	}

	public static function factory(array $conditions = []): static
	{
		return new static($conditions);
	}

	public function isTrue(array $data = []): bool
	{
		if (empty($this->conditions) === true) {
			return true;
		}

		foreach ($this->conditions as $key => $expected) {
			$value = $data[$key] ?? null;

			if ($value !== $expected) {
				return false;
			}
		}

		return true;
	}

	public function render(ModelWithContent $model): array
	{
		return $this->conditions;
	}
}
