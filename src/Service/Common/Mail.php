<?php

namespace App\Service\Common;

use Postmark\PostmarkClient;

class Mail
{
    /**
     * Send an email
     */
    public function send(string $toEmail, string $subject, string $message)
    {
        $client = new PostmarkClient(getenv('POSTMARK_API_KEY'));
        $client->sendEmail("no-reply@xivapi.com", $toEmail, $subject, $message);
    }
}
