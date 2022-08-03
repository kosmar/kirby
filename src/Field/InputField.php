<?php

namespace Kirby\Field;

use Kirby\Blueprint\Prop\Help;
use Kirby\Blueprint\Prop\Label;
use Kirby\Cms\ModelWithContent;

/**
 * Base class for all saveable fields
 *
 * @package   Kirby Field
 * @author    Bastian Allgeier <bastian@getkirby.com>
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier
 * @license   https://opensource.org/licenses/MIT
 */
class InputField extends Field
{
	public const TYPE = 'input';

	public function __construct(
		public string $id,
		public bool $autofocus = false,
		public bool $disabled = false,
		public Help|null $help = null,
		public Label|null $label = null,
		public bool $required = false,
		public bool $translate = true,
		...$args
	) {
		parent::__construct($id, ...$args);
	}

	public function defaults(): void
	{
		$this->label ??= Label::fallback($this->id);
	}

	public function fill(mixed $value = null): static
	{
		$this->value->set($value);
		return $this;
	}

	public function isInput(): bool
	{
		return true;
	}

	public function isActive(array $values = []): bool
	{
		if ($this->disabled === true) {
			return false;
		}

		return parent::isActive($values);
	}

	public function render(ModelWithContent $model): array
	{
		return parent::render($model) + [
			'autofocus' => $this->autofocus,
			'disabled'  => $this->disabled,
			'help'      => $this->help?->render($model),
			'label'     => $this->label?->render($model),
			'required'  => $this->required,
		];
	}

	public function submit(mixed $value = null): static
	{
		if ($this->disabled === true) {
			return $this;
		}

		$this->value->submit($value);
		return $this;
	}
}
