<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Models\Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

#[Group('qa')]
#[Group('security')]
#[Group('storage')]
final class TenantImageStorageScenarioTest extends TenantTestCase
{
    #[Test]
    public function uploaded_images_use_the_tenant_aware_public_filesystem(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('avatar.png', 80, 80);
        $image = Image::upload($file, 'avatars', $this->customer);

        $this->assertSame('public', $image->disk);
        $this->assertStringStartsWith('project_img/avatars/', $image->path);
        $this->assertStringNotContainsString(base_path('project_img'), $image->getFullPathAttribute());
        Storage::disk('public')->assertExists($image->path);

        $image->delete();
        Storage::disk('public')->assertMissing($image->path);
    }
}
