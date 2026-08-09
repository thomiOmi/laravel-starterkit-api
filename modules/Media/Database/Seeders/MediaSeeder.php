<?php

declare(strict_types=1);

namespace Modules\Media\Database\Seeders;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\Role;
use Modules\Media\Database\Factories\MediaFactory;

/**
 * Seed the Media module: permissions, role grants, and sample files.
 *
 * Permission rows are firstOrCreate so the seeder stays idempotent and
 * safe to run standalone. The admin role receives every permission via
 * `PermissionEnum::cases()` in IAMSeeder; this seeder only extends the
 * basic user role and creates a few real files for local development.
 */
class MediaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedPermissions();

        $this->seedSampleMedia();
    }

    /**
     * Create the media permissions and grant the basic user role.
     */
    private function seedPermissions(): void
    {
        foreach ([
            PermissionEnum::MediaView,
            PermissionEnum::MediaCreate,
            PermissionEnum::MediaDelete,
        ] as $permission) {
            Permission::firstOrCreate(['name' => $permission->value, 'guard_name' => 'sanctum']);
        }

        $user = Role::firstOrCreate(['name' => RoleEnum::User->value, 'guard_name' => 'sanctum']);
        $user->givePermissionTo([
            PermissionEnum::MediaView->value,
            PermissionEnum::MediaCreate->value,
        ]);
    }

    /**
     * Create sample media records backed by real files so signed URLs
     * and static URLs actually resolve during local development.
     */
    private function seedSampleMedia(): void
    {
        foreach (['public', 'local'] as $disk) {
            $content = "Sample media file for the {$disk} disk.";
            $path = 'media/seeds/sample-'.$disk.'.txt';

            Storage::disk($disk)->put($path, $content);

            MediaFactory::new()->create([
                'disk' => $disk,
                'mime_type' => 'text/plain',
                'size' => strlen($content),
                'path' => $path,
                'meta' => [
                    'original_name' => 'sample-'.$disk.'.txt',
                    'extension' => 'txt',
                ],
            ]);
        }
    }
}
