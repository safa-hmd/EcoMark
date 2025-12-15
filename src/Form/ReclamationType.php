<?php

namespace App\Form;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use App\Entity\Reclamation;
use App\Entity\Reponse;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;

class ReclamationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Objet', TextType::class, [
                'label' => 'Objet',
                'attr' => ['placeholder' => 'Ex: Problème de livraison']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['placeholder' => 'Décrivez votre problème...', 'rows' => 4]
            ]);
    //                 $builder
    // ->add('recaptcha', EWZRecaptchaType::class, [
    //     'label' => false,
    //     'mapped' => false,
    //     'constraints' => [
    //         new RecaptchaTrue(),
    //     ]
    // ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reclamation::class,
        ]);
    }
}
