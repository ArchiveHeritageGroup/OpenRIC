<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for static pages -- adapted from Heratio static_page / static_page_i18n / slug tables.
 *
 * OpenRiC simplifies the Heratio object→static_page→slug triple-table pattern into
 * two clean tables: static_pages (with slug inline) and static_page_translations (i18n).
 * Protected pages are seeded on creation.
 */
return new class extends Migration
{
    public function up(): void
    {
        // -- static_pages --
        Schema::create('static_pages', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 255)->unique();
            $table->boolean('is_protected')->default(false);
            $table->boolean('is_published')->default(true);
            $table->integer('sort_order')->default(0);
            $table->string('source_culture', 16)->default('en');
            $table->timestamps();

            $table->index('slug');
            $table->index('is_published');
            $table->index('sort_order');
        });

        // -- static_page_translations --
        Schema::create('static_page_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('static_page_id')
                ->constrained('static_pages')
                ->cascadeOnDelete();
            $table->string('culture', 16);
            $table->string('title', 1024);
            $table->text('content')->default('');
            $table->timestamps();

            $table->unique(['static_page_id', 'culture']);
            $table->index('culture');
        });

        // -- Seed protected pages --
        $this->seedProtectedPages();
    }

    public function down(): void
    {
        Schema::dropIfExists('static_page_translations');
        Schema::dropIfExists('static_pages');
    }

    /**
     * Seed the five protected pages with default content.
     * Mirrors Heratio's protected slugs: home, about, contact, privacy, terms.
     */
    private function seedProtectedPages(): void
    {
        $now   = now();
        $pages = [
            [
                'slug'      => 'home',
                'title'     => 'Welcome',
                'content'   => "# Welcome to OpenRiC\n\nOpenRiC is a **Records in Contexts** management system built on the RiC-O ontology.\n\nUse the navigation above to browse archival records, authority records, and places.",
                'sort_order' => 10,
            ],
            [
                'slug'      => 'about',
                'title'     => 'About',
                'content'   => "# About\n\nThis is an archival description system based on the International Council on Archives' Records in Contexts standard (RiC-O).\n\nIt provides multi-level archival description, authority records, place management, and linked-data capabilities.",
                'sort_order' => 20,
            ],
            [
                'slug'      => 'contact',
                'title'     => 'Contact',
                'content'   => "# Contact\n\nFor inquiries about this archive, please contact the repository administrator.\n\n**Email**: admin@example.org\n\n**Address**: [Your institution address]",
                'sort_order' => 30,
            ],
            [
                'slug'      => 'privacy',
                'title'     => 'Privacy Policy',
                'content'   => "# Privacy Policy\n\nThis site collects minimal personal data necessary for authentication and audit logging.\n\n## Data Collected\n\n- User account information (name, email)\n- Authentication logs (IP address, timestamp)\n- Audit trail of record modifications\n\n## Data Retention\n\nAudit logs are retained for the lifetime of the system. User accounts can be deactivated upon request.",
                'sort_order' => 40,
            ],
            [
                'slug'      => 'terms',
                'title'     => 'Terms and Conditions',
                'content'   => "# Terms and Conditions\n\nBy using this system, you agree to the following terms:\n\n1. You will use the system only for its intended archival management purposes.\n2. You will not attempt to access records or functions beyond your assigned permissions.\n3. All modifications to archival descriptions are logged and auditable.\n4. The institution reserves the right to modify access at any time.",
                'sort_order' => 50,
            ],
        ];

        foreach ($pages as $page) {
            $id = DB::table('static_pages')->insertGetId([
                'slug'           => $page['slug'],
                'is_protected'   => true,
                'is_published'   => true,
                'sort_order'     => $page['sort_order'],
                'source_culture' => 'en',
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);

            DB::table('static_page_translations')->insert([
                'static_page_id' => $id,
                'culture'        => 'en',
                'title'          => $page['title'],
                'content'        => $page['content'],
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
        }
    }
};
