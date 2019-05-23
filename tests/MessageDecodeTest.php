<?php


final class MessageDecodeTest extends \PHPUnit\Framework\TestCase
{
    /**
     *
     * @test
     */
    public function test()
    {
        $message = new \Andromeda\ISO8583\ExampleMessage();
        $parser = new \Andromeda\ISO8583\Parser($message);
        $parser->addMessage('0800A238000000C0000804000000000000000000010401000443495194000443040176007008043177567000140003001000');
        $this->assertTrue($parser->validateISO());
    }

}