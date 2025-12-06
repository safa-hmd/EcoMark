<?php

namespace App\Form;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        
        $builder
            ->add('email')
              ->add('password', PasswordType::class, [
               'required' => false,  // L'utilisateur peut laisser vide s'il ne veut pas changer

    ])
            
            ->add('nom')
            ->add('prenom')
            ->add('adresse')
            ->add('telephone')
             ->add('photo', FileType::class, [
        'label' => 'Photo de profil',
        'mapped' => false, // IMPORTANT si tu ne veux pas que Symfony essaye de la mettre dans l'entité automatiquement
        'required' => false, // rendre le champ optionnel
        'constraints' => [
            new File([
                'maxSize' => '2M',
                'mimeTypes' => [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                ],
                'mimeTypesMessage' => 'Veuillez télécharger une image valide (jpg, png, gif)',
            ])
        ],
    ])
;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }



}
