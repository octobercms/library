<?php

use October\Rain\Mail\Mailable;

/**
 * MailableTest
 */
class MailableTest extends TestCase
{
    /**
     * testViewDataCallbackIsApplied confirms buildViewDataUsing contributes view data
     */
    public function testViewDataCallbackIsApplied()
    {
        $previousCallback = Mailable::$viewDataCallback;

        Mailable::buildViewDataUsing(fn () => ['extra' => 'value']);

        try {
            $data = (new Mailable)->with('name', 'Jeff')->buildViewData();
        }
        finally {
            Mailable::$viewDataCallback = $previousCallback;
        }

        $this->assertSame('value', $data['extra'] ?? null);
        $this->assertSame('Jeff', $data['name'] ?? null);
    }
}
