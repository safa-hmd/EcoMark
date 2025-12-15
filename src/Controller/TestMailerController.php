<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
//Methode pour tester l'envoi d'email via Symfony Mailer ou non 
class TestMailerController extends AbstractController
{
    #[Route('/test-email', name: 'test_email')]
    public function sendTestEmail(MailerInterface $mailer): Response
    {
        $email = (new Email())
            ->from('ton_email@gmail.com')
            ->to('hamdisafa235@gmail.com')  // Mets ton email pour tester
            ->subject('Test Symfony Mailer')
            ->text('Ceci est un test d’envoi d’email depuis Symfony.');

        $mailer->send($email);

        return new Response('Email envoyé ! Vérifie ta boîte.');
    }
}
