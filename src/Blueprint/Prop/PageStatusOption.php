<?php

namespace Kirby\Blueprint\Prop;

use Kirby\Attribute\LabelAttribute;
use Kirby\Attribute\TextAttribute;
use Kirby\Cms\ModelWithContent;
use Kirby\Exception\InvalidArgumentException;
use Kirby\Foundation\Component;

/**
 * Page Status Option
 *
 * @package   Kirby Blueprint
 * @author    Bastian Allgeier <bastian@getkirby.com>
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier
 * @license   https://opensource.org/licenses/MIT
 */
class PageStatusOption extends Component
{
	public function __construct(
		public string $id,
		public bool $disabled = false,
		public LabelAttribute|null $label = null,
		public TextAttribute|null $text = null,
	) {
		if (in_array($this->id, ['draft', 'unlisted', 'listed']) === false) {
			throw new InvalidArgumentException('The status must be draft, unlisted or listed');
		}

		$this->label ??= new LabelAttribute(['*' => 'page.status.' . $id]);
		$this->text  ??= new TextAttribute(['*' => 'page.status.' . $id . '.description']);
	}

	public static function prefab(string $id, array|string|bool|null $option = null): static
	{
		$option = match (true) {
			// disabled
			$option === false => [
				'disabled' => true
			],

			// use default values for the status
			$option === null, $option === true => [],

			// simple string for label. the text will be unset
			is_string($option) === true => [
				'label' => ['*' => $option],
				'text'  => ['*' => null],
			],

			// already defined as array definition
			default => $option
		};

		$option['id'] = $id;

		return static::factory($option);
	}

	public function render(ModelWithContent $model): array|false
	{
		if ($this->disabled === true) {
			return false;
		}

		return [
			'label' => $this->label?->render($model),
			'text'  => $this->text?->render($model),
		];
	}
}
