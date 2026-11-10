<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * RF-NEW-004: la noticia ya fue resuelta por otro editor; la segunda
 * resolución se rechaza y no modifica nada.
 */
final class NewsConflictException extends \RuntimeException
{
}
