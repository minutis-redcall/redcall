<?php

/**
 * Polyfill for sensio/framework-extra-bundle ^6.2 which still references
 * Symfony\Component\Routing\Loader\AnnotationClassLoader (renamed to
 * AttributeClassLoader in Symfony 6.4). Aliases the old name to the new one
 * so the abandoned bundle keeps working until we migrate to native attributes.
 */

$aliases = [
    \Symfony\Component\Routing\Loader\AttributeClassLoader::class     => 'Symfony\\Component\\Routing\\Loader\\AnnotationClassLoader',
    \Symfony\Component\Routing\Loader\AttributeDirectoryLoader::class => 'Symfony\\Component\\Routing\\Loader\\AnnotationDirectoryLoader',
    \Symfony\Component\Routing\Loader\AttributeFileLoader::class      => 'Symfony\\Component\\Routing\\Loader\\AnnotationFileLoader',
];

foreach ($aliases as $real => $legacy) {
    if (!class_exists($legacy, false) && class_exists($real)) {
        class_alias($real, $legacy);
    }
}
