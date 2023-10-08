<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class MailerService
{
    const sender='kontakt@gpvoting.pl';
    private MailerInterface $mail;
    private Environment $twig;
    private MailerInterface $mailer;
    private TranslatorInterface $translator;

    public function __construct(MailerInterface $mail,Environment $twig,TranslatorInterface $translator)
    {
        $this->mailer = $mail;
        $this->twig = $twig;
        $this->translator = $translator;
    }

    public function sendEmailWithPassword($email, $password)
    {
        $body=$this->renderView(
                'email/participant_password_email.html.twig',['password'=>$password]
            );
        $title=$this->translator->trans('participants.list.email_with_password');
        $this->sendMail($email,$title,$body);
    }

    private function renderView(string $template,array $params=[]): string
    {
        return $this->twig->render($template,$params);
    }

    private function sendMail(string $recipent,string $title,string $body,array $attachments=[])
    {
        $mail = (new Email())
            ->from(self::sender)
            ->priority(Email::PRIORITY_HIGH)
            ->subject($title)
            ->addTo($recipent)
            ->html($body);
        foreach ($attachments as $attachment)
        {
            $mail->attach($attachment);
        }
        $this->mailer->send($mail);
    }
}