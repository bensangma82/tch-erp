<?php

namespace Tests\Unit;

use App\Support\Rbac\PermissionRules;
use App\Support\Rbac\RoleCatalogue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RbacRefinementRulesTest extends TestCase
{
    public function test_role_catalogue_keeps_privileged_roles_out_of_matrix(): void
    {
        $roles = RoleCatalogue::configurableRoles();
        $this->assertCount(26, $roles);
        $this->assertArrayNotHasKey('admin', $roles);
        $this->assertArrayNotHasKey('super_admin', $roles);
        $this->assertArrayHasKey('nursing_supervisor', $roles);
        $this->assertArrayHasKey('admin', RoleCatalogue::userRoles());
        $this->assertArrayHasKey('super_admin', RoleCatalogue::userRoles(true));
    }

    public function test_snapshot_is_order_independent_and_role_specific(): void
    {
        $hash = PermissionRules::snapshot('nursing', 1, [2, 1], [2 => 'b', 1 => 'a']);
        $this->assertSame($hash, PermissionRules::snapshot('nursing', 1, [1, 2], [1 => 'a', 2 => 'b']));
        $this->assertNotSame($hash, PermissionRules::snapshot('doctor', 1, [1, 2], [1 => 'a', 2 => 'b']));
        $this->assertNotSame($hash, PermissionRules::snapshot('nursing', 2, [1, 2], [1 => 'a', 2 => 'b']));
        $this->assertNotSame($hash, PermissionRules::snapshot('nursing', 1, [1, 2], [1 => 'a', 2 => 'changed']));
    }

    public function test_administration_actions_need_view_permission(): void
    {
        $this->expectException(ValidationException::class);
        PermissionRules::validateRoleSelection(['administration.execute']);
    }

    public function test_direct_only_permissions_cannot_be_selected_for_a_role(): void
    {
        $this->expectException(ValidationException::class);
        PermissionRules::validateRoleSelection(['billing.investigation-refund.process']);
    }

    public function test_valid_selection_and_intentional_empty_role_are_allowed(): void
    {
        PermissionRules::validateRoleSelection(['administration.view', 'administration.execute']);
        PermissionRules::validateRoleSelection([]);
        $this->assertTrue(true);
    }
}
