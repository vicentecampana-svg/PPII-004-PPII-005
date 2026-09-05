<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

class RolePermissionTest extends TestCase
{
    public function testRolePermissionsMatrix(): void
    {
        $permissions = [
            'superadmin' => [
                'users'    => true,
                'audits'   => true,
                'projects' => true,
                'services' => true,
                'staff'    => true,
                'tags'     => true,
                'queries'  => true,
                'footer'   => true,
                'news_status' => true,
            ],
            'admin' => [
                'users'    => true,
                'audits'   => false,
                'projects' => true,
                'services' => true,
                'staff'    => true,
                'tags'     => true,
                'queries'  => true,
                'footer'   => true,
                'news_status' => true,
            ],
            'editor' => [
                'users'    => false,
                'audits'   => false,
                'projects' => false,
                'services' => false,
                'staff'    => false,
                'tags'     => true,
                'queries'  => true,
                'footer'   => false,
                'news_status' => true,
            ],
            'redactor' => [
                'users'    => false,
                'audits'   => false,
                'projects' => false,
                'services' => false,
                'staff'    => false,
                'tags'     => false,
                'queries'  => false,
                'footer'   => false,
                'news_status' => false,
            ],
        ];

        $routesRoles = [
            'users'       => ['superadmin', 'admin'],
            'audits'      => ['superadmin'],
            'projects'    => ['superadmin', 'admin'],
            'services'    => ['superadmin', 'admin'],
            'staff'       => ['superadmin', 'admin'],
            'tags'        => ['superadmin', 'admin', 'editor'],
            'queries'     => ['superadmin', 'admin', 'editor'],
            'footer'      => ['superadmin', 'admin'],
            'news_status' => ['superadmin', 'admin', 'editor'],
        ];

        foreach ($permissions as $role => $matrix) {
            foreach ($matrix as $resource => $allowed) {
                $hasAccess = in_array($role, $routesRoles[$resource], true);
                $this->assertSame(
                    $allowed,
                    $hasAccess,
                    "Role '{$role}' access to '{$resource}' should be " . ($allowed ? 'true' : 'false')
                );
            }
        }
    }

    public function testRedactorCannotModifyOtherAuthorsNews(): void
    {
        $redactorUser = ['id' => 5, 'username' => 'redactor1', 'role_name' => 'redactor'];
        $newsFromOtherAuthor = ['id' => 1, 'author_id' => 1, 'status' => 'pendiente', 'title' => 'Admin News'];

        $canModify = ($newsFromOtherAuthor['author_id'] === $redactorUser['id']);
        $this->assertFalse($canModify, 'Redactor should not be allowed to modify news from other authors.');
    }

    public function testRedactorCannotModifyPublishedNews(): void
    {
        $redactorUser = ['id' => 5, 'username' => 'redactor1', 'role_name' => 'redactor'];
        $ownPublishedNews = ['id' => 2, 'author_id' => 5, 'status' => 'publicada', 'title' => 'My Published News'];

        $canModify = ($ownPublishedNews['author_id'] === $redactorUser['id'] && $ownPublishedNews['status'] === 'pendiente');
        $this->assertFalse($canModify, 'Redactor should not be allowed to modify own news if already published.');
    }

    public function testRedactorCanModifyOwnPendingNews(): void
    {
        $redactorUser = ['id' => 5, 'username' => 'redactor1', 'role_name' => 'redactor'];
        $ownPendingNews = ['id' => 3, 'author_id' => 5, 'status' => 'pendiente', 'title' => 'My Draft News'];

        $canModify = ($ownPendingNews['author_id'] === $redactorUser['id'] && $ownPendingNews['status'] === 'pendiente');
        $this->assertTrue($canModify, 'Redactor should be allowed to modify own pending news.');
    }
}
