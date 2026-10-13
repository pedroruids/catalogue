<?php

declare(strict_types=1);

namespace App\Http\Controllers\Offline;

use App\Http\Controllers\Controller;
use App\Support\CatalogSnapshot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Snapshot JSON do catálogo para a cópia local do vendedor (Xavier #10). A versão é o ETag:
 * o cliente repete o pedido com `If-None-Match` e recebe 304 se nada mudou.
 */
final class CatalogSnapshotController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $snapshot = CatalogSnapshot::build();

        $response = response()->json($snapshot)
            ->setEtag($snapshot['version'])
            // Dados do vendedor autenticado: nunca em caches partilhadas; revalidar sempre.
            ->setPrivate()
            ->header('Cache-Control', 'private, no-cache');

        $response->isNotModified($request);

        return $response;
    }
}
