<?php

namespace Kirby\Blueprint;

/**
 * Info section
 *
 * @package   Kirby Blueprint
 * @author    Bastian Allgeier <bastian@getkirby.com>
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier
 * @license   https://opensource.org/licenses/MIT
 */
class InfoSection extends Section
{
	public const TYPE = 'info';

	public function __construct(
		public string $id,
		public Help|null $help = null,
		public Label|null $label = null,
		public Kirbytext|null $text = null,
		public Theme|null $theme = null,
		...$args
	) {
		parent::__construct($id, ...$args);

		$this->label ??= Label::fallback($id);
	}
}
