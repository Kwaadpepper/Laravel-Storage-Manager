<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Exception;

/** @template TErrorCode of DomainErrorCode */
class DomainException extends \DomainException
{
    private readonly DomainErrorCode $domainErrorCode;

    /** @param TErrorCode $error */
    public function __construct(
        DomainErrorCode $error,
        ?\Throwable $previous = null,
    ) {
        $this->domainErrorCode = $error;
        parent::__construct(
            $error->message(),
            $this->domainErrorCode->code(),
            $previous
        );
    }

    /** @return TErrorCode */
    public function getDomainErrorCode(): DomainErrorCode
    {
        return $this->domainErrorCode;
    }
}
