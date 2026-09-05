<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

final class DatabaseIndexTest extends TestCase
{
    private string $schemaContent;

    protected function setUp(): void
    {
        $schemaPath = dirname(__DIR__) . '/config/schema.sql';
        $this->assertFileExists($schemaPath);
        $this->schemaContent = file_get_contents($schemaPath);
    }

    public function testSchemaContainsNewsIndexes(): void
    {
        $this->assertStringContainsString('idx_news_status_published', $this->schemaContent);
        $this->assertStringContainsString('idx_news_published_at', $this->schemaContent);
        $this->assertStringContainsString('idx_news_author_created', $this->schemaContent);
        $this->assertStringContainsString('idx_news_editor', $this->schemaContent);
        $this->assertStringContainsString('idx_news_title', $this->schemaContent);
    }

    public function testSchemaContainsAuditLogIndexes(): void
    {
        $this->assertStringContainsString('idx_audit_log_user_created', $this->schemaContent);
        $this->assertStringContainsString('idx_audit_log_entity', $this->schemaContent);
        $this->assertStringContainsString('idx_audit_log_created_at', $this->schemaContent);
    }

    public function testSchemaContainsFooterIndexes(): void
    {
        $this->assertStringContainsString('idx_enlaces_footer_grupo_orden', $this->schemaContent);
    }

    public function testSchemaContainsUserAndEntityIndexes(): void
    {
        $this->assertStringContainsString('idx_app_user_role', $this->schemaContent);
        $this->assertStringContainsString('idx_app_user_active', $this->schemaContent);
        $this->assertStringContainsString('idx_news_tag_tag_id', $this->schemaContent);
        $this->assertStringContainsString('idx_project_active', $this->schemaContent);
        $this->assertStringContainsString('idx_service_active', $this->schemaContent);
        $this->assertStringContainsString('idx_staff_member_orden', $this->schemaContent);
    }
}
