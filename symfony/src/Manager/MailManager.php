<?php

namespace App\Manager;

use App\Provider\Email\EmailProvider;
use Twig\Environment;

class MailManager
{
    /**
     * @var EmailProvider
     */
    private $emailProvider;

    /**
     * @var Environment
     */
    private $templating;

    /**
     * @var LanguageConfigManager
     */
    private $languageConfig;

    public function __construct(EmailProvider $emailProvider,
        Environment $templating,
        LanguageConfigManager $languageConfig)
    {
        $this->emailProvider  = $emailProvider;
        $this->templating     = $templating;
        $this->languageConfig = $languageConfig;
    }

    public function simple(string $to, string $subject, string $textBody, string $htmlBody, string $locale)
    {
        $content = $this->templating->render('message/email_simple.html.twig', [
            'website_url' => getenv('WEBSITE_URL'),
            'subject'     => $subject,
            'content'     => $htmlBody,
            'language'    => $this->languageConfig->getLanguageConfig($locale),
        ]);

        if (getenv('APP_ENV') === 'dev') {
            $subject = sprintf('[TEST] %s', $subject);
        }

        $this->emailProvider->send($to, $subject, $textBody, $content);
    }
}