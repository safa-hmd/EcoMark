<?php
namespace App\Form;

use App\Entity\Commande;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class CommandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ❌ SUPPRIMER ces champs (automatiques dans le contrôleur) :
            // ->add('statut')
            // ->add('dateCommande')
            
            ->add('montantTotal', NumberType::class, [
                'label' => 'Montant total (€)',
                'attr' => [
                    'placeholder' => 'Ex: 150.50',
                    'step' => '0.01'
                ]
            ])
            
            // ❌ SUPPRIMER cette ligne :
            // ->add('client')
            
            // ✅ OPTION 1 : Ne pas afficher le champ User (il est défini automatiquement dans le contrôleur)
            // C'est la meilleure option pour un espace client
            
            // ✅ OPTION 2 : Si tu veux quand même permettre de choisir un user (pour un admin par exemple)
            // Décommenter ces lignes :
            /*
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => function(User $user) {
                    return $user->getNom() . ' ' . $user->getPrenom() . ' (' . $user->getEmail() . ')';
                },
                'label' => 'Client',
                'placeholder' => 'Sélectionner un client',
                'required' => true,
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            */
            
            ->add('produits', TextareaType::class, [
                'label' => 'Liste des produits',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Décrivez les produits commandés...',
                    'rows' => 4
                ]
            ])
            
            ->add('adresseLivraison', TextType::class, [
                'label' => 'Adresse de livraison',
                'attr' => [
                    'placeholder' => 'Ex: 123 Rue de la Paix'
                ]
            ])
            
            ->add('ville', TextType::class, [
                'label' => 'Ville',
                'attr' => [
                    'placeholder' => 'Ex: Paris'
                ]
            ])
            
            ->add('codePostal', TextType::class, [
                'label' => 'Code postal',
                'attr' => [
                    'placeholder' => 'Ex: 75001'
                ]
            ])
            
            ->add('methodePaiement', ChoiceType::class, [
                'label' => 'Méthode de paiement',
                'choices' => [
                    'Carte Bancaire' => 'carte_bancaire',
                    'PayPal' => 'paypal',
                    'Carte Postale' => 'carte_postale'
                ],
                'expanded' => true,  // Boutons radio
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