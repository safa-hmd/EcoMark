<?php
namespace App\Security;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class GoogleAuthenticator extends OAuth2Authenticator
{
    private ClientRegistry $clientRegistry;
    private EntityManagerInterface $em;
    private RouterInterface $router;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(ClientRegistry $clientRegistry,EntityManagerInterface $em,RouterInterface $router,UserPasswordHasherInterface $passwordHasher
    ) {
        $this->clientRegistry = $clientRegistry;
        $this->em = $em;
        $this->router = $router;
        $this->passwordHasher = $passwordHasher;
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }



    public function authenticate(Request $request): SelfValidatingPassport
    {
        //Récupérer le token Google
        $accessToken = $this->fetchAccessToken($this->getGoogleClient());

        // Récupération des infos utilisateur depuis Google
        /** @var GoogleUser $googleUser */
        $googleUser = $this->getGoogleClient()->fetchUserFromToken($accessToken);
        $email = $googleUser->getEmail();

        return new SelfValidatingPassport(
            new UserBadge($email, function ($userIdentifier) use ($googleUser, $email) {
                // Chercher un User existant
                $user = $this->em->getRepository(User::class)->findOneBy([
                    'email' => $userIdentifier
                ]);

                //  Sinon : créer
                if (!$user) {
                    $user = new User();
                    $user->setEmail($userIdentifier);

                    // Définir un mot de passe aléatoire (OBLIGATOIRE)
                    $randomPassword = bin2hex(random_bytes(16));
                    $hashedPassword = $this->passwordHasher->hashPassword($user, $randomPassword);
                    $user->setPassword($hashedPassword);

                    // Récupérer le nom complet de Google
                    $fullName = $googleUser->getName();
                    $firstName = $googleUser->getFirstName();
                    $lastName = $googleUser->getLastName();

                    // Si l'entité possède un champ "nom" (last name)
                    if (method_exists($user, 'setNom')) {
                        $user->setNom($lastName ?? $fullName ?? 'Utilisateur');
                    }

                    // Si l'entité possède un champ "prenom" (first name)
                    if (method_exists($user, 'setPrenom')) {
                        $user->setPrenom($firstName ?? 'Google');
                    }
                    $user->setRoles(['ROLE_CLIENT']);

                    // Définir d'autres champs si nécessaires
                    if (method_exists($user, 'setIsVerified')) {
                        $setter = 'setIsVerified';
                        $user->$setter(true);
                    }

                    $this->em->persist($user);
                    $this->em->flush();
                }

                return $user;
            })
        );
    }



    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var User $user */
        $user = $token->getUser();

        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $route = 'profilAdmin';
        } elseif (in_array('ROLE_VENDEUR', $user->getRoles())) {
            $route = 'profilVendeur';
        } else {
            $route = 'profil_client';
        }

        return new RedirectResponse(
            $this->router->generate($route, ['id' => $user->getId()])
        );
    }


    //Rôle : définit ce qui se passe si la connexion échoue.
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?RedirectResponse
    {
        return new RedirectResponse(
            $this->router->generate('app_login')
        );
    }

    //centralise la récupération du client Google OAuth2.
    private function getGoogleClient()
    {
        return $this->clientRegistry->getClient('google');
    }
}