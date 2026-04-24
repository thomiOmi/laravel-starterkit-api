<?php

declare(strict_types=1);

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Illuminate\Support\ServiceProvider;

class ApiDocsProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Scramble::afterOpenApiGenerated(function (OpenApi $openApi) {
            $openApi->secure(
                SecurityScheme::http('bearer')
            );

            // Customize global response structures
            foreach ($openApi->paths as $path) {
                foreach ($path->operations as $operation) {
                    foreach ($operation->responses as $code => $response) {
                        $this->addStatusFieldToResponse($response, (int) $code >= 200 && (int) $code < 300);
                    }
                }
            }

            // Also customize shared component responses
            foreach ($openApi->components->responses as $response) {
                $this->addStatusFieldToResponse($response, false); // Components are usually errors in this project
            }
        });
    }

    /**
     * Helper to add status field to a response schema.
     */
    private function addStatusFieldToResponse($response, bool $isSuccess): void
    {
        // Handle References
        if ($response instanceof Reference) {
            return; // We handle actual objects in components loop
        }

        $statusValue = $isSuccess ? 'success' : 'error';

        // Check if it's a JSON response
        if (isset($response->content['application/json'])) {
            $schema = $response->content['application/json'];

            if ($schema instanceof Schema && $schema->type instanceof ObjectType) {
                // Add status property if it doesn't exist
                if (! isset($schema->type->properties['status'])) {
                    $schema->type->addProperty('status', (new StringType)->example($statusValue));
                }
            }
        }
    }
}
