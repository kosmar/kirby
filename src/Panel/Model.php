<?php

namespace Kirby\Panel;

use Kirby\Blueprint\Prop\Image;
use Kirby\Http\Uri;

/**
 * Provides information about the model for the Panel
 * @since 3.6.0
 *
 * @package   Kirby Panel
 * @author    Nico Hoffmann <nico@getkirby.com>
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier
 * @license   https://getkirby.com/license
 */
abstract class Model
{
	/**
	 * Get the content values for the model
	 *
	 * @return array
	 */
	public function content(): array
	{
		return [];
	}

	/**
	 * Returns the drag text from a custom callback
	 * if the callback is defined in the config
	 * @internal
	 *
	 * @param string $type markdown or kirbytext
	 * @param mixed ...$args
	 * @return string|null
	 */
	public function dragTextFromCallback(string $type, ...$args): ?string
	{
		$option   = 'panel.' . $type . '.' . $this->model::CLASS_ALIAS . 'DragText';
		$callback = $this->model->kirby()->option($option);

		if (
			empty($callback) === false &&
			is_a($callback, 'Closure') === true &&
			($dragText = $callback($this->model, ...$args)) !== null
		) {
			return $dragText;
		}

		return null;
	}

	/**
	 * Returns the correct drag text type
	 * depending on the given type or the
	 * configuration
	 *
	 * @internal
	 *
	 * @param string|null $type (`auto`|`kirbytext`|`markdown`)
	 * @return string
	 */
	public function dragTextType(string $type = null): string
	{
		$type ??= 'auto';

		if ($type === 'auto') {
			$kirby = $this->model->kirby();
			$type  = $kirby->option('panel.kirbytext', true) ? 'kirbytext' : 'markdown';
		}

		return $type === 'markdown' ? 'markdown' : 'kirbytext';
	}

	/**
	 * Returns the setup for a dropdown option
	 * which is used in the changes dropdown
	 * for example.
	 *
	 * @return array
	 */
	public function dropdownOption(): array
	{
		return [
			'icon' => 'page',
			'link' => $this->url(),
			'text' => $this->model->id(),
		];
	}

	/**
	 * Returns the Panel image definition
	 *
	 * @internal
	 *
	 * @param string|array|false|null $settings
	 * @return array|null
	 */
	public function image(): ?Image
	{
		return $this->model->blueprint()->image();
	}

	/**
	 * Data URI placeholder string for Panel image
	 *
	 * @internal
	 *
	 * @return string
	 */
	public static function imagePlaceholder(): string
	{
		return 'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw';
	}

	/**
	 * Checks for disabled dropdown options according
	 * to the given permissions
	 *
	 * @param string $action
	 * @param array $options
	 * @param array $permissions
	 * @return bool
	 */
	public function isDisabledDropdownOption(string $action, array $options, array $permissions): bool
	{
		$option = $options[$action] ?? true;
		return $permissions[$action] === false || $option === false || $option === 'false';
	}

	/**
	 * Returns lock info for the Panel
	 *
	 * @return array|false array with lock info,
	 *                     false if locking is not supported
	 */
	public function lock()
	{
		if ($lock = $this->model->lock()) {
			if ($lock->isUnlocked() === true) {
				return ['state' => 'unlock'];
			}

			if ($lock->isLocked() === true) {
				return [
					'state' => 'lock',
					'data'  => $lock->get()
				];
			}

			return ['state' => null];
		}

		return false;
	}

	/**
	 * Returns an array of all actions
	 * that can be performed in the Panel
	 * This also checks for the lock status
	 *
	 * @param array $unlock An array of options that will be force-unlocked
	 * @return array
	 */
	public function options(array $unlock = []): array
	{
		$options = $this->model->permissions()->toArray();

		if ($this->model->isLocked()) {
			foreach ($options as $key => $value) {
				if (in_array($key, $unlock)) {
					continue;
				}

				$options[$key] = false;
			}
		}

		return $options;
	}

	/**
	 * Returns the full path without leading slash
	 *
	 * @return string
	 */
	abstract public function path(): string;

	/**
	 * Prepares the response data for page pickers
	 * and page fields
	 *
	 * @param array|null $params
	 * @return array
	 */
	public function pickerData(array $params = []): array
	{
		return [
			'id'       => $this->model->id(),
			'image'    => $this->image(
				$params['image'] ?? [],
				$params['layout'] ?? 'list'
			),
			'info'     => $this->model->toSafeString($params['info'] ?? false),
			'link'     => $this->url(true),
			'sortable' => true,
			'text'     => $this->model->toSafeString($params['text'] ?? false),
		];
	}

	/**
	 * Returns the data array for the
	 * view's component props
	 *
	 * @internal
	 *
	 * @return array
	 */
	public function props(): array
	{
		$request = $this->model->kirby()->request();
		$props   = $this->model->blueprint()->render($this->model, $request->get('tab'));

		$props += [
			'lock'        => [],
			'permissions' => [],
		];

		return $props;
	}

	/**
	 * Returns link url and tooltip
	 * for model (e.g. used for prev/next
	 * navigation)
	 *
	 * @internal
	 *
	 * @param string $tooltip
	 * @return array
	 */
	public function toLink(string $tooltip = 'title'): array
	{
		return [
			'link'    => $this->url(true),
			'tooltip' => (string)$this->model->{$tooltip}()
		];
	}

	/**
	 * Returns link url and tooltip
	 * for optional sibling model and
	 * preserves tab selection
	 *
	 * @internal
	 *
	 * @param \Kirby\Cms\ModelWithContent|null $model
	 * @param string $tooltip
	 * @return array
	 */
	protected function toPrevNextLink($model = null, string $tooltip = 'title'): ?array
	{
		if ($model === null) {
			return null;
		}

		$data = $model->panel()->toLink($tooltip);

		if ($tab = $model->kirby()->request()->get('tab')) {
			$uri = new Uri($data['link'], [
				'query' => ['tab' => $tab]
			]);

			$data['link'] = $uri->toString();
		}

		return $data;
	}

	/**
	 * Returns the url to the editing view
	 * in the Panel
	 *
	 * @internal
	 *
	 * @param bool $relative
	 * @return string
	 */
	public function url(bool $relative = false): string
	{
		if ($relative === true) {
			return '/' . $this->path();
		}

		return $this->model->kirby()->url('panel') . '/' . $this->path();
	}

	/**
	 * Returns the data array for
	 * this model's Panel view
	 *
	 * @internal
	 *
	 * @return array
	 */
	abstract public function view(): array;
}
