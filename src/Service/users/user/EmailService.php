<?php

namespace App\Service\users\user;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class EmailService
{
    private MailerInterface $mailer;
    private Environment $twig;

    public function __construct(MailerInterface $mailer, Environment $twig)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    public function sendPasswordResetEmail(string $toEmail, string $resetToken): void
    {
        $htmlBody = $this->twig->render('frontOffice/user/emails/password_reset.html.twig', [
            'token' => $resetToken,
        ]);

        $email = (new Email())
            ->from('UniEarn <i.ferjani.26@gmail.com>')
            ->to($toEmail)
            ->subject('🔐 Réinitialisation de votre mot de passe UniEarn')
            ->html($htmlBody);

        $this->mailer->send($email);
    }

    public function sendIntruderAlert(\App\Entity\users\user\User $user, string $ip, ?string $photoPath, string $confirmToken, string $lockToken): void
    {
        $email = (new Email())
            ->from('UniEarn Security <i.ferjani.26@gmail.com>')
            ->to($user->getEmail())
            ->subject('🚨 Tentative de connexion suspecte — UniEarn')
            ->html($this->twig->render('frontOffice/user/emails/intruder_alert.html.twig', [
                'user' => $user,
                'ip' => $ip,
                'timestamp' => (new \DateTime())->format('d/m/Y à H:i:s'),
                'confirmToken' => $confirmToken,
                'lockToken' => $lockToken,
                'hasPhoto' => $photoPath !== null && file_exists($photoPath)
            ]));

        if ($photoPath && file_exists($photoPath)) {
            $email->embedFromPath($photoPath, 'intruder_photo');
        }

        $this->mailer->send($email);
    }
}
