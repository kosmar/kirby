<?php

namespace Kirby\Blueprint\Prop;

use Kirby\Attribute\LabelAttribute;
use Kirby\Blueprint\TestCase;
use Kirby\Field\Fields;
use Kirby\Field\TextField;
use Kirby\Section\FieldsSection;
use Kirby\Section\InfoSection;
use Kirby\Section\Sections;

/**
 * @covers \Kirby\Blueprint\Tab
 */
class TabTest extends TestCase
{
	/**
	 * @covers ::__construct
	 */
	public function testConstruct()
	{
		$tab = new Tab(
			id: 'test'
		);

		$this->assertSame('test', $tab->id);
		$this->assertNull($tab->label);
		$this->assertNull($tab->icon);
		$this->assertNull($tab->columns);
	}

	/**
	 * @covers ::fields
	 */
	public function testFields()
	{
		$tab = new Tab(
			id: 'test',
			columns: new Columns([
				new Column(
					id: 'a',
					sections: new Sections([
						new FieldsSection(
							id: 'a',
							fields: new Fields([
								new TextField(id: 'a'),
								new TextField(id: 'b')
							])
						)
					])
				)
			])
		);

		$this->assertCount(2, $tab->fields());
		$this->assertSame('a', $tab->fields()->a->id);
		$this->assertSame('b', $tab->fields()->b->id);
	}

	/**
	 * @covers ::render
	 */
	public function testRender()
	{
		$tab = new Tab(
			id: 'test',
			label: new LabelAttribute(['*' => 'My Tab'])
		);

		$expected = [
			'icon'    => null,
			'id'      => 'test',
			'label'   => 'My Tab',
			'link'    => '/pages/test?tab=test'
		];

		$this->assertSame($expected, $tab->render($this->model()));
	}

	/**
	 * @covers ::render
	 */
	public function testRenderActive()
	{
		$tab = new Tab(
			id: 'test',
			label: new LabelAttribute(['*' => 'My Tab'])
		);

		$expected = [
			'columns' => null,
			'id'      => 'test',
		];

		$this->assertSame($expected, $tab->render($this->model(), true));
	}

	/**
	 * @covers ::sections
	 */
	public function testSections()
	{
		$tab = new Tab(
			id: 'test',
			columns: new Columns([
				new Column(
					id: 'a',
					sections: new Sections([
						new InfoSection(id: 'a')
					])
				),
				new Column(
					id: 'b',
					sections: new Sections([
						new InfoSection(id: 'b')
					])
				)
			])
		);

		$this->assertCount(2, $tab->sections());
		$this->assertSame('a', $tab->sections()->first()->id);
		$this->assertSame('b', $tab->sections()->last()->id);
	}
}
