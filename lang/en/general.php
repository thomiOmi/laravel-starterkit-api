<?php

return [
    'resource_retrieved' => ':resource retrieved successfully',
    'resource_created' => ':resource created successfully',
    'resource_updated' => ':resource updated successfully',
    'resource_deleted' => ':resource deleted successfully',
    'resource_restored' => ':resource restored successfully',
    'resource_delete_error' => 'Failed to delete :resource',
    'resource_not_found' => ':resource not found',
    'resource_bulk_action' => ':resource :action successfully',

    'action_forbidden' => 'You are not authorized to perform this action',
    'self_delete_forbidden' => 'You cannot delete your own account',

    'roles_assigned' => 'Roles assigned successfully',

    'idempotency_invalid' => 'The Idempotency-Key header must be a valid v4 UUID.',
    'idempotency_conflict' => 'The Idempotency-Key was already used with a different request body.',

    'sunset_unavailable' => 'This endpoint is no longer available and has been sunset.',
];
