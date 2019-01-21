<?php

namespace App\Service\Common;

use Postmark\PostmarkClient;

class Mail
{
    /** @var PostmarkClient */
    private $client;

    public function __construct()
    {
        $this->client = new PostmarkClient(getenv('POSTMARK_API_KEY'));
    }

    public function send(string $toEmail, string $subject, string $message)
    {
        $this->client->sendEmail("no-reply@xivapi.com", $toEmail, $subject, $message);
    }
}
