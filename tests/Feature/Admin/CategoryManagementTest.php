<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_categories(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.categories.index'))
            ->assertOk();
    }

    public function test_student_cannot_view_categories(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->get(route('admin.categories.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_category(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), [
                'name' => 'Mathematics',
                'description' => 'Quantitative questions.',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', [
            'name' => 'Mathematics',
            'description' => 'Quantitative questions.',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_create_subcategory(): void
    {
        $admin = User::factory()->admin()->create();
        $parent = Category::create([
            'name' => 'Project Management',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), [
                'parent_id' => $parent->id,
                'name' => 'Scope Performance Domain',
                'description' => 'Scope questions.',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', [
            'parent_id' => $parent->id,
            'name' => 'Scope Performance Domain',
            'description' => 'Scope questions.',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_category(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create([
            'name' => 'Math',
            'description' => 'Old description.',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.categories.update', $category), [
                'name' => 'Mathematics',
                'description' => 'Updated description.',
            ])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Mathematics',
            'description' => 'Updated description.',
            'is_active' => false,
        ]);
    }

    public function test_admin_can_deactivate_category(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create([
            'name' => 'English',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'));

        $this->assertFalse($category->fresh()->is_active);
    }

    public function test_category_name_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();

        Category::create(['name' => 'Biology']);

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), [
                'name' => 'Biology',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_category_cannot_use_itself_as_parent(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create([
            'name' => 'Project Management',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.categories.update', $category), [
                'parent_id' => $category->id,
                'name' => 'Project Management',
            ])
            ->assertSessionHasErrors('parent_id');
    }
}
