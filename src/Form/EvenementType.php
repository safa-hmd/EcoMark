<?php

namespace App\Form;

use App\Entity\Evenement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Controller\Admin\EventAdminController;



class EvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre')
            ->add('description')
            ->add('dateEvent')
            ->add('lieu')
            ->add('image', FileType::class, [
        'label' => 'Image (PNG, JPG)',
        'mapped' => false, // Important : ne pas lier directement à l’entité
        'required' => false,
        'constraints' => [
            new File([
                'maxSize' => '5M',
                'mimeTypes' => [
                    'image/jpeg',
                    'image/png',
                ],
                'mimeTypesMessage' => 'Veuillez uploader une image JPEG ou PNG valide',])],])
            ->add('capacite')
            ->add('createdAt')
            
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evenement::class,]);
    }
}
