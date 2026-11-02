<?php

declare(strict_types=1);

namespace Tipi\Localization\Exceptions;

use InvalidArgumentException;

/**
 * Data passed to a management action is malformed.
 */
final class InvalidLocaleDataException extends InvalidArgumentException implements LocalizationException {}
