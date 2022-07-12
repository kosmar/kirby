<?php

namespace Kirby\Blueprint;

/**
 * @covers \Kirby\Blueprint\TableColumn
 */
class TableColumnTest extends TestCase
{
	/**
	 * @covers ::__construct
	 */
	public function testConstruct()
	{
		$column = new TableColumn(
			id: 'test'
		);

		$this->assertSame('test', $column->id);

		$this->assertInstanceOf(Label::class, $column->label);
		$this->assertSame('Test', $column->label->value);

		$this->assertFalse($column->mobile);

		$this->assertInstanceOf(TableColumnAlignment::class, $column->align);
		$this->assertSame('left', $column->align->value);

		$this->assertNull($column->type);
		$this->assertNull($column->value);
		$this->assertNull($column->width);
	}
}
