<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistreController extends AbstractController
{
    #[Route('/inscription', name: 'insc')]
    public function index(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher)
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        // Champ rôle
        $form->add('role', ChoiceType::class, [
            'choices'  => [
                'Client'  => 'ROLE_CLIENT',
                'Vendeur' => 'ROLE_VENDEUR',
                'Admin'   => 'ROLE_ADMIN',
            ],
            'mapped' => false,
            'attr' => ['class' => 'form-select'],
            'label' => 'Rôle',
        ]);

        // Bouton d'inscription
        $form->add('Inscrire', SubmitType::class, [
            'attr' => ['class' => 'btn btn-primary btn-lg w-100', 'style' => 'font-weight: bold;'],
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hashage du mot de passe 
            $plainPassword = $form->get('password')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            // Upload photo 
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $newFilename = uniqid().'.'.$photoFile->guessExtension();
                try {
                    $photoFile->move($this->getParameter('photos_directory'), $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de la photo.');
                }
                $user->setPhoto($newFilename);
            }

            // Role 
            $role = $form->get('role')->getData();
            $user->setRoles([$role]);

            $em->persist($user);
            $em->flush();

        
            return $this->redirectToRoute('app_login');
        }

        return $this->render('user/registre.html.twig', ['form' => $form->createView()]);
    }
}