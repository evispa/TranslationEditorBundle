<?php

namespace Nercury\TranslationEditorBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Injects one more form theme file for block editor html theming
 */
class FormThemeLoaderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $form_themes = $container->getParameter('twig.form.resources');
        $form_themes[] = 'NercuryTranslationEditorBundle:Form:fields.html.twig';
        $container->setParameter('twig.form.resources', $form_themes);
    }
}
