<?php

namespace Kirby\Blueprint;

use Kirby\Attribute\UrlAttribute;
use Kirby\Blueprint\Prop\Accept;
use Kirby\Blueprint\Prop\FileOptions;
use Kirby\Blueprint\Prop\FileImage;

/**
 * File blueprint
 *
 * @package   Kirby Blueprint
 * @author    Bastian Allgeier <bastian@getkirby.com>
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier
 * @license   https://opensource.org/licenses/MIT
 */
class FileBlueprint extends Blueprint
{
	public const DEFAULT = 'files/default';
	public const TYPE = 'file';

	public function __construct(
		public string $id,
		public Accept|null $accept = null,
		public FileImage|null $image = null,
		public FileOptions|null $options = null,
		public UrlAttribute|null $preview = null,
		...$args
	) {
		parent::__construct($id, ...$args);
	}

	public function defaults(): void
	{
		$this->image ??= new FileImage;

		parent::defaults();
	}
}
