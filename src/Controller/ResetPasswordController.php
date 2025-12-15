<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route('/reset-password')]
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;
    public function __construct(private ResetPasswordHelperInterface $resetPasswordHelper,private EntityManagerInterface $entityManager) {}
   
    #[Route('', name: 'app_forgot_password_request')]
    public function request(Request $request, MailerInterface $mailer, TranslatorInterface $translator): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $email */
            $email = $form->get('email')->getData();

            return $this->processSendingPasswordResetEmail($email, $mailer, $translator
            );
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form,
        ]);
    }

 
     // Confirmation 
    #[Route('/check-email', name: 'app_check_email')]
    public function checkEmail(): Response
    {
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            $resetToken = $this->resetPasswordHelper->generateFakeResetToken();
        }

        return $this->render('reset_password/check_email.html.twig', [
            'resetToken' => $resetToken,
        ]);
    }

 
 //change password  
    #[Route('/reset/{token}', name: 'app_reset_password')]
    public function reset(Request $request, UserPasswordHasherInterface $passwordHasher, TranslatorInterface $translator, ?string $token = null): Response
    {
        if ($token) {
            $this->storeTokenInSession($token);
            return $this->redirectToRoute('app_reset_password');
        }

        $token = $this->getTokenFromSession();

        if (null === $token) {
            throw $this->createNotFoundException('No reset password token found in the URL or in the session.');
        }

        try {
            /** @var User $user */
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('reset_password_error', sprintf(
                '%s - %s',
                $translator->trans(ResetPasswordExceptionInterface::MESSAGE_PROBLEM_VALIDATE, [], 'ResetPasswordBundle'),
                $translator->trans($e->getReason(), [], 'ResetPasswordBundle')
            ));

            return $this->redirectToRoute('app_forgot_password_request');
        }

        // The token is valid; allow the user to change their password.
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $this->resetPasswordHelper->removeResetRequest($token);

            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            $this->entityManager->flush();

            $this->cleanSessionAfterReset();
            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form,
        ]);
    }


private function processSendingPasswordResetEmail(string $emailFormData, MailerInterface $mailer, TranslatorInterface $translator): RedirectResponse
{
    $user = $this->entityManager->getRepository(User::class)->findOneBy([
        'email' => $emailFormData,
    ]);

    if (!$user) {
        return $this->redirectToRoute('app_check_email');
    }

    try {
        $resetToken = $this->resetPasswordHelper->generateResetToken($user);
    } catch (ResetPasswordExceptionInterface $e) {
        // Active le debug
        $this->addFlash('reset_password_error', sprintf(
            '%s - %s',
            $translator->trans(ResetPasswordExceptionInterface::MESSAGE_PROBLEM_HANDLE, [], 'ResetPasswordBundle'),
            $translator->trans($e->getReason(), [], 'ResetPasswordBundle')
        ));
        return $this->redirectToRoute('app_check_email');
    }

    try {
        // 1. Crée un transport DIRECT
        $transport = \Symfony\Component\Mailer\Transport::fromDsn($_ENV['MAILER_DSN']);
        $directMailer = new \Symfony\Component\Mailer\Mailer($transport);
        
        // 2. Crée l'email (utilise Email simple au lieu de TemplatedEmail pour être sûr)
        $email = (new \Symfony\Component\Mime\Email())
            ->from('hamdisafa235@gmail.com')
            ->to($user->getEmail())
            ->subject('Your password reset request')
            ->html('
                <h1>Password Reset</h1>
                <p>Click the link below to reset your password:</p>
                <a href="' . $this->generateUrl('app_reset_password', 
                    ['token' => $resetToken->getToken()], 
                    \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL) . '">
                    Reset My Password
                </a>
                <p>This link expires in 1 hour.</p>
                <p>If you didn\'t request this, please ignore this email.</p>
            ');
        
        // 3. Envoie DIRECTEMENT
        $directMailer->send($email);
        
        $this->addFlash('success', 'Password reset email sent! Check your inbox.');
        
    } catch (\Exception $e) {
        $this->addFlash('error', ' Email error: ' . $e->getMessage());
    }

    // Store the token object in session for retrieval in check-email route.
    $this->setTokenObjectInSession($resetToken);

    return $this->redirectToRoute('app_check_email');
}

}
