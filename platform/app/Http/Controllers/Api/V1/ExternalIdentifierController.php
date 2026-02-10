<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreExternalIdentifierRequest;
use App\Http\Resources\Api\V1\ExternalIdentifierResource;
use App\Models\Base;
use App\Models\Colorway;
use App\Models\Customer;
use App\Models\ExternalIdentifier;
use App\Models\Integration;
use App\Models\Inventory;
use App\Models\Order;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ExternalIdentifierController extends ApiController
{
    /**
     * Allowed morph types for identifiable_type query param.
     *
     * @var array<string, string>
     */
    private const IDENTIFIABLE_TYPE_ALIASES = [
        'base' => Base::class,
        'colorway' => Colorway::class,
        'customer' => Customer::class,
        'inventory' => Inventory::class,
        'order' => Order::class,
    ];

    /**
     * Lookup external identifiers by integration_id and either (external_type + external_id)
     * or (identifiable_type + identifiable_id).
     */
    public function index(Request $request): JsonResponse
    {
        $integrationId = $request->input('integration_id');
        if (! $integrationId) {
            throw ValidationException::withMessages([
                'integration_id' => ['The integration_id parameter is required.'],
            ]);
        }

        $integration = Integration::find($integrationId);
        if (! $integration) {
            throw ValidationException::withMessages([
                'integration_id' => ['The selected integration_id is invalid.'],
            ]);
        }

        $this->authorize('view', $integration);

        $query = ExternalIdentifier::query()->forIntegration($integration);

        $externalType = $request->query('external_type');
        $externalId = $request->query('external_id');
        $identifiableType = $request->query('identifiable_type');
        $identifiableId = $request->query('identifiable_id');

        if ($request->filled('external_type') && $request->filled('external_id')) {
            $query->ofType($externalType)->where('external_id', $externalId);
        } elseif ($request->filled('identifiable_type') && $request->filled('identifiable_id')) {
            $resolvedType = self::IDENTIFIABLE_TYPE_ALIASES[strtolower($identifiableType)] ?? $identifiableType;
            $query->where('identifiable_type', $resolvedType)->where('identifiable_id', $identifiableId);
        } else {
            throw ValidationException::withMessages([
                'query' => ['Provide either (integration_id, external_type, external_id) or (integration_id, identifiable_type, identifiable_id).'],
            ]);
        }

        $identifiers = $query->get();

        return $this->successResponse(ExternalIdentifierResource::collection($identifiers));
    }

    /**
     * Create an external identifier mapping.
     */
    public function store(StoreExternalIdentifierRequest $request): JsonResponse
    {
        try {
            $identifier = ExternalIdentifier::create($request->validated());
        } catch (UniqueConstraintViolationException $e) {
            throw ValidationException::withMessages([
                'external_id' => ['An external identifier with this integration, external type, and external ID already exists.'],
            ]);
        }

        return $this->createdResponse(new ExternalIdentifierResource($identifier));
    }
}
