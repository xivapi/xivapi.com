<?php

namespace App\Common\Utils;

use App\Common\Service\Redis\Redis;
use Postmark\PostmarkClient;
use Twig\Environment as TwigEnvironment;

class Mail
{
    /** @var TwigEnvironment */
    private $twig;

    /**
     * TestTwig constructor.
     */
    public function __construct(TwigEnvironment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * Send an email
     */
    public function send(
        string $email,
        string $subject,
        string $template,
        array $templateVariables
    ) {
        // build html
        $html = $this->twig->render($template, $templateVariables);
        $hash = sha1($html);

        // don't send the same email
        if (Redis::Cache()->get(__METHOD__ . $hash)) {
            return;
        }

        Redis::Cache()->set(__METHOD__ . $hash, true);

        // send
        $client = new PostmarkClient(getenv('POSTMARK_KEY'));
        $client->sendEmail("mog@mogboard.com", $email, $subject, $html);
    }
}
