<?php

namespace App\Form;

use App\Entity\PointRecyclage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class PointRecyclageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomPoint', TextType::class, [
                'label' => 'Nom du point'
            ])
            ->add('adresse', TextType::class, [
                'label' => 'Adresse'
            ])
            ->add('typeMatiereAcceptee', TextType::class, [
                'label' => 'Type de matière acceptée'
            ])
            ->add('capaciteMax', IntegerType::class, [
                'label' => 'Capacité maximale'
            ])
            ->add('responsable', TextType::class, [
                'label' => 'Responsable'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PointRecyclage::class,
        ]);
    }
}