<?php

declare(strict_types=1);

namespace App\Agents;

use Laravel\Boost\Contracts\SupportsGuidelines;
use Laravel\Boost\Contracts\SupportsMcp;
use Laravel\Boost\Contracts\SupportsSkills;
use Laravel\Boost\Install\Agents\Agent;
use Laravel\Boost\Install\Enums\Platform;

class Antigravity extends Agent implements SupportsGuidelines, SupportsMcp, SupportsSkills
{
    public function guidelinesPath(): string
    {
        return config('boost.agents.antigravity.guidelines_path', 'AGENTS.md');
    }

    public function skillsPath(): string
    {
        return config('boost.agents.antigravity.skills_path', '.agents/skills');
    }

    public function displayName(): string
    {
        return 'Antigravity';
    }

    public function name(): string
    {
        return 'antigravity';
    }

    public function detectOnSystem(Platform $platform): bool
    {
        return false;
    }

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.agents'],
            'files' => ['AGENTS.md'],
        ];
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        return match ($platform) {
            Platform::Darwin => [
                'paths' => ['/Applications/Antigravity.app'],
            ],
            Platform::Linux => [
                'command' => 'command -v antigravity',
            ],
            Platform::Windows => [
                'paths' => [
                    '%LOCALAPPDATA%\\Programs\\Antigravity',
                ],
            ],
        };
    }

    public function mcpConfigPath(): string
    {
        $default = match (PHP_OS_FAMILY) {
            'Windows' => $_SERVER['USERPROFILE'].'\\.gemini\\antigravity\\mcp_config.json',
            default => $this->expandTilde('~/.gemini/antigravity/mcp_config.json'),
        };

        return config('boost.agents.antigravity.mcp_config_path', $default);
    }

    public function mcpConfigKey(): string
    {
        return 'mcpServers';
    }

    /** {@inheritDoc} */
    public function httpMcpServerConfig(string $url): array
    {
        return [
            'command' => 'npx',
            'args' => ['-y', 'mcp-remote', $url],
        ];
    }

    /** {@inheritDoc} */
    public function mcpServerConfig(string $command, array $args = [], array $env = []): array
    {
        return collect([
            'command' => $command,
            'args' => $args,
            'cwd' => config('boost.executable_paths.current_directory', base_path()),
            'env' => $env,
        ])->filter(fn ($value): bool => ! in_array($value, [[], null, ''], true))
            ->toArray();
    }

    /**
     * Helper to resolve the user home directory safely.
     */
    private function expandTilde(string $path): string
    {
        if (strpos($path, '~') !== 0) {
            return $path;
        }

        $home = $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'] ?? null;

        if (! $home) {
            return $path;
        }

        return str_replace('~', rtrim($home, '/\\'), $path);
    }
}
