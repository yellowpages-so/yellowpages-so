<?php

namespace App\Shared\Exceptions;

class ResourceNotFoundException extends DomainException
{
    public function __construct(
        string $resource,
        string $identifier
    ) {
        parent::__construct(
            message: "{$resource} was not found.",
            errorCode: 'resource_not_found',
            statusCode: 404,
            context: [
                'resource' => $resource,
                'identifier' => $identifier,
            ],
        );
    }
}
