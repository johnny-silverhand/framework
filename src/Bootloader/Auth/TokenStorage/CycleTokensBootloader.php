<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Bootloader\Auth\TokenStorage;

use Spiral\Auth\Cycle\Token;
use Spiral\Auth\Cycle\TokenStorage as CycleStorage;
use Spiral\Auth\TokenStorageInterface;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Bootloader\Auth\HttpAuthBootloader;
use Spiral\Bootloader\Cycle\AnnotatedBootloader;
use Spiral\Bootloader\Cycle\CycleBootloader;
use Spiral\Bootloader\TokenizerBootloader;

/**
 * Stores authentication token in database via Cycle ORM.
 */
final class CycleTokensBootloader extends Bootloader
{
    protected const DEPENDENCIES = [
        HttpAuthBootloader::class,
        CycleBootloader::class,
        AnnotatedBootloader::class
    ];

    protected const SINGLETONS = [
        TokenStorageInterface::class => CycleStorage::class
    ];

    /**
     * @param TokenizerBootloader $tokenizer
     *
     * @throws \ReflectionException
     */
    public function boot(TokenizerBootloader $tokenizer): void
    {
        $tokenClass = new \ReflectionClass(Token::class);
        $tokenizer->addDirectory(dirname($tokenClass->getFileName()));
    }
}
