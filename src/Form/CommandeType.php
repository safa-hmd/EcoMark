<?php

namespace App\Form;

use App\Entity\Commande;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class CommandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Champ Montant total (calculé automatiquement, en lecture seule)
            ->add('montantTotal', NumberType::class, [
                'label' => 'Montant total (€)',
                'attr' => [
                    'readonly' => true,
                    'placeholder' => 'Ex: 150.50',
                    'step' => '0.01',
                    'class' => 'form-control bg-light'
                ],
                'required' => false,
            ])
            
            // ❌ SUPPRIMÉ : Le champ produits (récupéré depuis "Mes Achats")
            
            // Champ Adresse de livraison
            ->add('adresseLivraison', TextType::class, [
                'label' => 'Adresse de livraison',
                'attr' => [
                    'placeholder' => 'Ex: 123 Rue de la Paix'
                ]
            ])
            
            // Champ Ville
            ->add('ville', TextType::class, [
                'label' => 'Ville',
                'attr' => [
                    'placeholder' => 'Ex: Paris'
                ]
            ])
            
            // Champ Code postal
            ->add('codePostal', TextType::class, [
                'label' => 'Code postal',
                'attr' => [
                    'placeholder' => 'Ex: 75001'
                ]
            ])
            
            // Champ Méthode de paiement
            ->add('methodePaiement', ChoiceType::class, [
                'label' => 'Méthode de paiement',
                'choices' => [
                    'Carte Bancaire' => 'carte_bancaire',
                    'PayPal' => 'paypal',
                    'Carte Postale' => 'carte_postale'
                ],
                'expanded' => true,
                'multiple' => false,
                'attr' => [
                    'class' => 'form-check'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commande::class,
        ]);
    }
}