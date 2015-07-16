<?php namespace October\Rain\Mail\Transport;

use GuzzleHttp\ClientInterface;
use Illuminate\Mail\Transport\MandrillTransport as MandrillTransportBase;
use Swift_Mime_Message;

class MandrillTransport extends MandrillTransportBase
{
    /**
     * {@inheritdoc}
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $data = [
            'key' => $this->key,
            'to' => $this->getToAddresses($message),
            'raw_message' => (string) $message,
            'async' => false,
        ];

        if (version_compare(ClientInterface::VERSION, '6') === 1) {
            $options = ['form_params' => $data];
        } else {
            $options = ['body' => $data];
        }

        return $this->getHttpClient()->post('https://mandrillapp.com/api/1.0/messages/send-raw.json', $options);
    }
}
