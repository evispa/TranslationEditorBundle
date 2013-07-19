<?php

namespace Nercury\TranslationEditorBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class NercuryTranslationEditorBundle extends Bundle
{
    public function build(\Symfony\Component\DependencyInjection\ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new DependencyInjection\Compiler\FormThemeLoaderPass());
    }
}
