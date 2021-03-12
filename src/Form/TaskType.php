<?php

namespace App\Form;

use App\Entity\Tag;
use App\Entity\Task;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class TaskType extends AbstractType
{
    private $security;
 
    public function __construct(Security $security)
    {
        $this->security = $security;
    }  


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la tâche', 
                'attr' => [ 
                    'class' => 'form-control col-6', 
                    'title' => 'Non de la tache']])
            ->add('description' , TextareaType::class, [
                'label' => 'Description', 
                'attr' => [ 
                    'class' => 'form-control col-6', 
                    'title' => 'Description']])
            ->add('tag', EntityType::class, [
                  'class' => Tag::class,
                  'query_builder' => function (EntityRepository $er) { return $er->createQueryBuilder('c')->orderBy('c.name', 'ASC');}, 
                  'choice_label' => 'name',
                  'label' => 'Catégorie',
                  'attr' => [
                      'class' => 'form-control col-6',
                      'title' => 'Catégorie'
                  ]
            ])
            ->add('beginAt', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date de début', 
                'attr' => [ 
                    'class' => 'form-control col-6', 
                    'title' => 'Date de début']])
            ->add('endAt', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date de fin', 
                'attr' => [ 
                    'class' => 'form-control col-6', 
                    'title' => 'Date de fin']])
            ->add('address', TextType::class, [
                'label' => 'address', 
                'attr' => [ 
                    'class' => 'form-control col-6', 
                    'title' => 'address']]);
            $user = $this->security->getUser();
            if ($user->getRoles()[0] === 'ROLE_ADMIN') {
                $builder->add('isArchived', ChoiceType::class, [
                    'label' => 'Archivage : ',
                    'attr' => [
                        'class' =>'form-control col-6',
                        'title' => 'date de début',
                    ],
                    'choices'  => [
                        'Oui' => true,
                        'Non' => false,
                    ],
                ]);
            }else{
                $builder ->add('isArchived', HiddenType::class, [
                   'data' => '0',   ]);
                }
            $builder->add('save', SubmitType::class , [
                'label' => 'Enregistrer', 
                'attr' => [ 
                    'class' => 'btn btn-primary', 
                    'title' => 'Enregistrer']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
        ]);
    }
}
