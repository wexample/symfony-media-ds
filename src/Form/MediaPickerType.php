<?php

namespace Wexample\SymfonyMediaDs\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\SymfonyMedia\Entity\Media;
use Wexample\SymfonyMedia\Repository\MediaRepository;
use Wexample\SymfonyMedia\Service\MediaLibraryService;
use Wexample\SymfonyMediaDs\Form\DataTransformer\MediaPickerTransformer;

/**
 * A field whose value is one media (`multiple: false`, a ManyToOne) or
 * several (`multiple: true`, a ManyToMany), picked in the media library,
 * which opens in a modal or, `target: panel`, a panel:
 *
 *     $builder->add('cover', MediaPickerType::class, ['accept' => 'image/*']);
 *     $builder->add('attachments', MediaPickerType::class, ['multiple' => true, 'target' => 'panel']);
 */
class MediaPickerType extends AbstractType
{
    public function __construct(
        private readonly MediaRepository $mediaRepository,
        private readonly MediaLibraryService $library,
    ) {
    }

    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder->addModelTransformer(new MediaPickerTransformer($this->mediaRepository, $options['multiple']));
    }

    public function buildView(
        FormView $view,
        FormInterface $form,
        array $options
    ): void {
        $data = $form->getData();
        $media = $options['multiple'] ? [...($data ?? [])] : array_filter([$data]);

        $view->vars['multiple'] = $options['multiple'];
        $view->vars['target'] = $options['target'];
        $view->vars['accept'] = $options['accept'];
        // What the field shows before anything is picked: the ids it posts
        // say nothing to the person reading it.
        $view->vars['selected'] = array_map(fn (Media $item) => $this->library->describe($item), $media);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'multiple' => false,
            'target' => 'modal',
            // What may be picked, as an `accept` attribute says it —
            // `image/*`, `application/pdf,.svg` —: the library lists only
            // that, and takes only that in an upload. Null, anything.
            'accept' => null,
            'compound' => false,
            'invalid_message' => 'This media cannot be found.',
        ]);
        $resolver->setAllowedTypes('multiple', 'bool');
        $resolver->setAllowedTypes('accept', ['null', 'string']);
        $resolver->setAllowedValues('target', ['modal', 'panel']);
    }

    public function getParent(): string
    {
        return TextType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'media_picker';
    }
}
