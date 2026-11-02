<?php

declare(strict_types=1);

namespace Tipi\Localization\Exceptions;

use RuntimeException;

/**
 * A management operation was rejected because it would break an invariant.
 */
final class InvalidLocaleOperationException extends RuntimeException implements LocalizationException {}
