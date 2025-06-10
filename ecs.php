<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\FunctionNotation\FunctionDeclarationFixer;
use PhpCsFixer\Fixer\FunctionNotation\LambdaNotUsedImportFixer;
use PhpCsFixer\Fixer\FunctionNotation\MethodArgumentSpaceFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->paths([
        __DIR__ . '/src',
        __FILE__,
    ]);

    $ecsConfig->sets([
        \craft\ecs\SetList::CRAFT_CMS_4,
    ]);

    $ecsConfig->rules([
        LambdaNotUsedImportFixer::class, // Cleans up unused 'use' imports in closures
        MethodArgumentSpaceFixer::class, // Fix argument spacing
    ]);

    $ecsConfig->skip([
        FunctionDeclarationFixer::class, // Prevents forcing spaces after `fn`
    ]);
};
