<?php

namespace App\Form;

use App\Entity\Produit;
use App\Entity\PointRecyclage;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomProduit', null, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Nom du produit'
                ],
                'label' => 'Nom du produit'
            ])
            ->add('description', TextareaType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Description du produit'
                ],
                'label' => 'Description'
            ])
            ->add('prix', NumberType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.01',
                    'min' => '0.01',
                    'placeholder' => 'Prix'
                ],
                'label' => 'Prix (€)',
                'html5' => true
            ])
            ->add('quantiteStock', NumberType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'min' => '0',
                    'placeholder' => 'Quantité en stock'
                ],
                'label' => 'Quantité en stock',
                'html5' => true
            ])
            ->add('etatProduit', ChoiceType::class, [
                'choices' => [
                    'Disponible' => 'Disponible',
                    'En rupture' => 'En rupture',
                    'Bientôt disponible' => 'Bientôt disponible'
                ],
                'placeholder' => 'Sélectionnez un état',
                'attr' => [
                    'class' => 'form-control'
                ],
                'label' => 'État du produit'
            ])
            ->add('pointRecyclage', EntityType::class, [
                'class' => PointRecyclage::class,
                'choice_label' => 'nomPoint',
                'required' => false,
                'placeholder' => 'Choisir un point de recyclage (optionnel)',
                'attr' => [
                    'class' => 'form-control'
                ],
                'label' => 'Point de recyclage'
            ])
            ->add('photo', FileType::class, [
                'label' => 'Photo du produit',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/jpg',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPEG, PNG, JPG, GIF)',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Produit::class,
        ]);
    }
}