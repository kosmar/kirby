<?php

namespace Kirby\Blueprint;

/**
 * Select field
 *
 * @package   Kirby Blueprint
 * @author    Bastian Allgeier <bastian@getkirby.com>
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier
 * @license   https://opensource.org/licenses/MIT
 */
class SelectField extends OptionField
{
	public const TYPE = 'select';

	public function __construct(
		public string $id,
		public Icon|null $icon = null,
		public Placeholder|null $placeholder = null,
		...$args
	) {
		parent::__construct($id, ...$args);
	}
}
