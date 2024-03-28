<?php

namespace APP\API\v1\orcid;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use PKP\core\PKPBaseController;

class OrcidController extends PKPBaseController
{
    /**
     * @inheritDoc
     */
    public function getHandlerPath(): string
    {
        return 'orcid';
    }

    /**
     * @inheritDoc
     */
    public function getRouteGroupMiddleware(): array
    {
        // TODO: See what middleware should be used here
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getGroupRoutes(): void
    {
        Route::post('authorize', $this->authorizeOrcid(...))
            ->name('orcid.authorize');
        Route::post('verify', $this->verify(...))
            ->name('orcid.verify');
    }

    public function authorizeOrcid(Request $illuminateRequest): JsonResponse
    {
        return response()->json([], Response::HTTP_OK);
    }

    public function verify(Request $illuminateRequest): JsonResponse
    {
        return response()->json([], Response::HTTP_OK);
    }
}
