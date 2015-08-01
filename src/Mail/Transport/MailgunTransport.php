<?php namespace October\Rain\Mail\Transport;

use GuzzleHttp\ClientInterface;
use Illuminate\Mail\Transport\MailgunTransport as MailgunTransportBase;
use Swift_Mime_Message;
use GuzzleHttp\Post\PostFile;

class MailgunTransport extends MailgunTransportBase
{
    /**
     * {@inheritdoc}
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $options = ['auth' => ['api', $this->key]];

        if (version_compare(ClientInterface::VERSION, '6') === 1) {
            $options['multipart'] = [
                ['name' => 'to', 'contents' => $this->getTo($message)],
                ['name' => 'message', 'contents' => (string) $message, 'filename' => 'message.mime'],
            ];
        } else {
            $options['body'] = [
                'to' => $this->getTo($message),
                'message' => new PostFile('message', (string) $message),
            ];
        }

        return $this->getHttpClient()->post($this->url, $options);
    }
}
