<?php
namespace App\Form;
use App\Entity\Commande;
use App\Entity\Livraison;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
class LivraisonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
{
    $builder
        ->add('adresse')
        ->add('dateLivraison')
        ->add('statut', ChoiceType::class, [
    'choices' => [
        'en_preparation' => 'en_preparation',
        'Expédiée' => 'expediee',
        'Livrée' => 'livree'
    ],
    'expanded' => true,
    'multiple' => false
])
        ->add('numeroSuivi')
        ->add('transporteur')
        ->add('fraisLivraison')
        ->add('commande', EntityType::class, [
            'class' => Commande::class,
            'choice_label' => 'id',
        ])
    ;
}
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Livraison::class,
        ]);
    }
}
