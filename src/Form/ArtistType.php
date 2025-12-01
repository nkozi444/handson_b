<?php

namespace App\Form;

use App\Entity\Artist;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ArtistType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Artist Name',
                'attr' => ['class' => 'input-text'],
            ])

            ->add('bio', TextareaType::class, [
                'label' => 'Biography',
                'required' => false,
                'attr' => ['rows' => 5, 'class' => 'input-textarea'],
            ])

            ->add('email', TextType::class, [
                'label' => 'Email (optional)',
                'required' => false,
                'attr' => ['class' => 'input-text'],
            ])

            ->add('website', TextType::class, [
                'label' => 'Website (optional)',
                'required' => false,
                'attr' => ['class' => 'input-text'],
            ])

            ->add('isActive', CheckboxType::class, [
                'label' => 'Active',
                'required' => false,
                'label_attr' => ['class' => 'checkbox-label'],
            ])

            // IMAGE UPLOAD (NOT MAPPED TO ENTITY)
            ->add('imageFile', FileType::class, [
                'label' => 'Artist Photo',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '6M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG, WEBP)',
                    ])
                ],
                'attr' => ['class' => 'input-file'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Artist::class,
        ]);
    }
}
