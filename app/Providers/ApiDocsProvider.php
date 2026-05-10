<?php

declare(strict_types=1);

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Path;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\Response;
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
            /** @var SecurityScheme $securityScheme */
            $securityScheme = SecurityScheme::http('bearer');
            $openApi->secure($securityScheme);

            // Customize global response structures
            /** @var iterable<Path> $paths */
            $paths = $openApi->paths;
            foreach ($paths as $path) {
                /** @var iterable<Operation> $operations */
                $operations = $path->operations;
                foreach ($operations as $operation) {
                    /** @var array<string, Response|Reference> $responses */
                    $responses = $operation->responses;
                    foreach ($responses as $code => $response) {
                        $this->addStatusFieldToResponse($response, (int) $code >= 200 && (int) $code < 300);
                    }
                }
            }

            // Also customize shared component responses
            /** @var array<string, Response|Reference> $responses */
            $responses = $openApi->components->responses;
            foreach ($responses as $response) {
                $this->addStatusFieldToResponse($response, false); // Components are usually errors in this project
            }
        });
    }

    /**
     * Helper to add status field to a response schema.
     *
     * @param  Response|Reference  $response
     */
    private function addStatusFieldToResponse($response, bool $isSuccess): void
    {
        // Handle References
        if ($response instanceof Reference) {
            return; // We handle actual objects in components loop
        }

        /** @var Response $response */
        $statusValue = $isSuccess ? 'success' : 'error';

        // Check if it's a JSON response
        if (isset($response->content['application/json'])) {
            /** @var Schema $schema */
            $schema = $response->content['application/json'];

            if ($schema->type instanceof ObjectType) {
                /** @var ObjectType $objectType */
                $objectType = $schema->type;

                // Add status property if it doesn't exist
                if (! isset($objectType->properties['status'])) {
                    $objectType->addProperty('status', (new StringType)->example($statusValue));
                }
            }
        }
    }
}
