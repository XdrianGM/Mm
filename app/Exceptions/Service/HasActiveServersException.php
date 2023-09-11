<?php

namespace Jexactyl\Exceptions\Service;

use Illuminate\Http\Response;
use Jexactyl\Exceptions\DisplayException;

class HasActiveServersException extends DisplayException
{
    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
