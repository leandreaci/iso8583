<?php


final class MessageMakeTest extends \PHPUnit\Framework\TestCase
{
    /**
     *
     * @test
     */
    public function test()
    {
        $message = new \Andromeda\ISO8583\ExampleMessage();
        $isoMaker = new \Andromeda\ISO8583\Parser($message);
        $isoMaker->addMTI('0800');
        $isoMaker->addData(3, '123456');
        $isoMaker->addData(4, '000000001000');
        $isoMaker->addData(7, '1234567890');

        $this->assertStringContainsString('0800',$isoMaker->getISO());
    }

}