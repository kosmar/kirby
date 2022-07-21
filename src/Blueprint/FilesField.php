<?php

namespace Kirby\Blueprint;

/**
 * Files field
 *
 * @package   Kirby Blueprint
 * @author    Bastian Allgeier <bastian@getkirby.com>
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier
 * @license   https://opensource.org/licenses/MIT
 */
class FilesField extends PickerField
{
	public const TYPE = 'files';

	public function __construct(
		public string $id,
		...$args
	) {
		parent::__construct($id, ...$args);
	}
}
