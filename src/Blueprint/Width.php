<?php

namespace Kirby\Blueprint;

/**
 * The width of the field in the field grid.
 *
 * @package   Kirby Blueprint
 * @author    Bastian Allgeier <bastian@getkirby.com>
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier
 * @license   https://opensource.org/licenses/MIT
 */
class Width extends Enumeration
{
	public array $allowed = [
		'1/1',
		'1/2',
		'1/3',
		'1/4',
		'2/3',
		'3/4',
	];

	public string|null $default = '1/1';
}
