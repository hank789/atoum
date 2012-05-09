<?php

namespace mageekguy\atoum\tests\units\reports;

require __DIR__ . '/../../runner.php';

use
	mageekguy\atoum
;

class asynchronous extends atoum\test
{
	public function testHandleEvent()
	{
		$this
			->if($depedencies = new atoum\depedencies())
			->and($depedencies['mock\mageekguy\atoum\reports\asynchronous'] = new atoum\depedencies())
			->and($depedencies['mock\mageekguy\atoum\reports\asynchronous']['adapter'] = $adapter = new atoum\test\adapter())
			->and($report = new \mock\mageekguy\atoum\reports\asynchronous($depedencies))
			->then
				->object($report->handleEvent(atoum\runner::runStop, new atoum\runner()))->isIdenticalTo($report)
				->variable($report->getTitle())->isNull()
			->if($report->setTitle($title = uniqid()))
			->then
				->object($report->handleEvent(atoum\runner::runStop, new atoum\runner()))->isIdenticalTo($report)
				->string($report->getTitle())->isEqualTo($title)
			->if($adapter->date = function($format) { return $format; })
			->and($report->setTitle('%1$s' . ($title = uniqid())))
			->then
				->object($report->handleEvent(atoum\runner::runStop, new atoum\runner()))->isIdenticalTo($report)
				->string($report->getTitle())->isEqualTo('Y-m-d' . $title)
			->if($report->setTitle('%1$s' . '%2$s' . ($title = uniqid())))
			->then
				->object($report->handleEvent(atoum\runner::runStop, new atoum\runner()))->isIdenticalTo($report)
				->string($report->getTitle())->isEqualTo('Y-m-d' . 'H:i:s' . $title)
			->if($report->setTitle('%1$s' . '%2$s' . '%3$s' . ($title = uniqid())))
			->then
				->object($report->handleEvent(atoum\runner::runStop, new atoum\runner()))->isIdenticalTo($report)
				->string($report->getTitle())->isEqualTo('Y-m-d' . 'H:i:s' . 'SUCCESS' . $title)
			->if($report->setTitle('%1$s' . '%2$s' . '%3$s' . ($title = uniqid())))
			->then
				->object($report->handleEvent(atoum\test::success, $this))->isIdenticalTo($report)
				->object($report->handleEvent(atoum\runner::runStop, new atoum\runner()))->isIdenticalTo($report)
				->string($report->getTitle())->isEqualTo('Y-m-d' . 'H:i:s' . 'SUCCESS' . $title)
			->if($report->setTitle('%1$s' . '%2$s' . '%3$s' . ($title = uniqid())))
			->then
				->object($report->handleEvent(atoum\test::fail, $this))->isIdenticalTo($report)
				->object($report->handleEvent(atoum\runner::runStop, new atoum\runner()))->isIdenticalTo($report)
				->string($report->getTitle())->isEqualTo('Y-m-d' . 'H:i:s' . 'FAIL' . $title)
			->if($report->setTitle('%1$s' . '%2$s' . '%3$s' . ($title = uniqid())))
			->then
				->object($report->handleEvent(atoum\test::error, $this))->isIdenticalTo($report)
				->object($report->handleEvent(atoum\runner::runStop, new atoum\runner()))->isIdenticalTo($report)
				->string($report->getTitle())->isEqualTo('Y-m-d' . 'H:i:s' . 'FAIL' . $title)
			->if($report->setTitle('%1$s' . '%2$s' . '%3$s' . ($title = uniqid())))
			->then
				->object($report->handleEvent(atoum\test::exception, $this))->isIdenticalTo($report)
				->object($report->handleEvent(atoum\runner::runStop, new atoum\runner()))->isIdenticalTo($report)
				->string($report->getTitle())->isEqualTo('Y-m-d' . 'H:i:s' . 'FAIL' . $title)
		;
	}
}

?>
