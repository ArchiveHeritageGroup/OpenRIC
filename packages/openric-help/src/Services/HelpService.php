<?php

declare(strict_types=1);

namespace OpenRiC\Help\Services;

use OpenRiC\Help\Contracts\HelpServiceInterface;

/**
 * Help service -- adapted from Heratio AhgHelp\Services\HelpArticleService (167 lines).
 *
 * Provides a static PHP array of help topics with title, slug, category, and markdown content.
 * No database required -- all content is bundled with the package.
 */
class HelpService implements HelpServiceInterface
{
    /**
     * Static registry of all help topics.
     *
     * @return array<int, array{title: string, slug: string, category: string, content: string}>
     */
    private function allTopics(): array
    {
        return [
            // -- Getting Started --
            [
                'title'    => 'Welcome to OpenRiC',
                'slug'     => 'welcome',
                'category' => 'Getting Started',
                'content'  => <<<'MD'
# Welcome to OpenRiC

OpenRiC is a **Records in Contexts** management system built on the RiC-O ontology (ICA standard). It provides:

- Full ISAD(G) and ISAAR-CPF compliant descriptive forms
- RDF-native storage in Apache Jena Fuseki
- Elasticsearch-powered search across all entity types
- Workflow management for archival processes
- Digital object management and IIIF support

## Quick Links

- **Browse Records**: Navigate to `/browse` to explore record resources
- **Create New Record**: Use the "New Record" button in the navigation
- **Search**: Use the search bar at the top of any page
- **Admin Panel**: Access administrative functions at `/admin`
MD,
            ],
            [
                'title'    => 'System Requirements',
                'slug'     => 'system-requirements',
                'category' => 'Getting Started',
                'content'  => <<<'MD'
# System Requirements

## Server Requirements

- **PHP**: 8.3 or higher with extensions: pdo_pgsql, mbstring, xml, curl, gd
- **PostgreSQL**: 15 or higher
- **Apache Jena Fuseki**: 4.x for RDF triplestore
- **Elasticsearch**: 8.x for full-text search
- **Nginx**: Recommended as the web server
- **Composer**: 2.x for PHP dependency management
- **Node.js**: 18+ (for asset compilation only)

## Browser Support

OpenRiC supports all modern browsers: Chrome 90+, Firefox 90+, Safari 14+, Edge 90+.
MD,
            ],
            [
                'title'    => 'First Steps After Installation',
                'slug'     => 'first-steps',
                'category' => 'Getting Started',
                'content'  => <<<'MD'
# First Steps After Installation

1. **Log in** with the default administrator account
2. **Change the admin password** via Settings > User Management
3. **Configure the repository** under Settings > General
4. **Set up Fuseki connection** under Settings > Triplestore
5. **Import initial data** using the Ingest tool at Admin > Ingest
6. **Configure dropdowns** at Admin > Dropdowns for your institution's needs
7. **Create user accounts** for your team at Admin > Users
MD,
            ],

            // -- Record Management --
            [
                'title'    => 'Creating Records',
                'slug'     => 'creating-records',
                'category' => 'Record Management',
                'content'  => <<<'MD'
# Creating Records

OpenRiC supports creating archival records following the ISAD(G) standard mapped to RiC-O.

## Required Fields

- **Title** (rico:title): The name of the record
- **Identifier** (rico:identifier): A unique reference code

## Optional Fields

- **Scope and Content**: Description of the record's contents
- **Date Statement**: Dates associated with the record
- **Level of Description**: Fonds, series, file, item, etc.
- **Extent**: Physical or logical extent
- **Creator**: Person or organization that created the record

## Steps

1. Navigate to Records > New Record
2. Fill in the required fields
3. Add optional descriptive metadata
4. Set the level of description
5. Link to a parent record if applicable
6. Click Save
MD,
            ],
            [
                'title'    => 'Editing and Updating Records',
                'slug'     => 'editing-records',
                'category' => 'Record Management',
                'content'  => <<<'MD'
# Editing and Updating Records

To edit a record:

1. Navigate to the record's detail page
2. Click the "Edit" button
3. Modify the fields as needed
4. Click "Save" to persist your changes

All changes are recorded in the audit log with the user, timestamp, and changed fields.

## Versioning

OpenRiC tracks all modifications via RDF-Star provenance annotations. Each change creates a new assertion with the editor's identity and timestamp.
MD,
            ],
            [
                'title'    => 'Record Hierarchies',
                'slug'     => 'record-hierarchies',
                'category' => 'Record Management',
                'content'  => <<<'MD'
# Record Hierarchies

OpenRiC supports multi-level archival hierarchies using the `rico:isOrWasPartOf` relationship:

- **Fonds**: The highest level, representing an entire collection
- **Sub-fonds**: A subdivision of a fonds
- **Series**: A group of records organized by function or activity
- **Sub-series**: A subdivision of a series
- **File**: A unit of records accumulated during use
- **Item**: The smallest discrete unit

## Managing Hierarchies

- When creating or editing a record, use the "Parent Record" field to establish the hierarchy
- The tree view on the browse page shows the full hierarchy
- Records can be moved between parents using drag-and-drop in the tree view
MD,
            ],

            // -- Authority Records --
            [
                'title'    => 'Managing Agents (Authority Records)',
                'slug'     => 'managing-agents',
                'category' => 'Authority Records',
                'content'  => <<<'MD'
# Managing Agents (Authority Records)

Agents in OpenRiC represent persons, families, and corporate bodies following ISAAR-CPF mapped to RiC-O.

## Agent Types

- **Person**: An individual (rico:Person)
- **Family**: A family unit (rico:Family)
- **Corporate Body**: An organization (rico:CorporateBody)

## Required Fields

- **Authorized Name**: The primary form of the agent's name
- **Agent Type**: Person, Family, or Corporate Body

## Linking Agents to Records

Agents can be linked to records through various relationships:
- Creator (rico:hasCreator)
- Accumulator (rico:hasAccumulator)
- Subject (rico:hasOrHadSubject)
MD,
            ],

            // -- Search --
            [
                'title'    => 'Search Guide',
                'slug'     => 'search-guide',
                'category' => 'Search',
                'content'  => <<<'MD'
# Search Guide

OpenRiC provides multiple search methods powered by Elasticsearch.

## Quick Search

Use the search bar at the top of any page. This searches across titles, identifiers, and descriptions.

## Advanced Search

Access Advanced Search from the search page to:
- Search by specific fields (title, identifier, date range, creator)
- Filter by entity type (Record, Agent, Place)
- Filter by level of description
- Sort results by relevance, date, or title

## SPARQL Query

For advanced users, the SPARQL endpoint allows direct RDF queries at `/sparql`.
MD,
            ],

            // -- Digital Objects --
            [
                'title'    => 'Digital Object Management',
                'slug'     => 'digital-objects',
                'category' => 'Digital Objects',
                'content'  => <<<'MD'
# Digital Object Management

OpenRiC manages digital objects (files, images, documents) linked to archival records.

## Supported Formats

- **Images**: JPEG, PNG, TIFF, GIF, WebP
- **Documents**: PDF, DOCX, ODT, TXT
- **Audio**: MP3, WAV, FLAC, OGG
- **Video**: MP4, MKV, WebM

## Upload Process

1. Navigate to the record you want to attach a file to
2. Click "Add Digital Object"
3. Select the file or drag-and-drop
4. Add metadata (title, description, mime type)
5. Click Upload

## IIIF Support

Images are served via IIIF Image API for zoomable viewing using OpenSeadragon.
MD,
            ],

            // -- Administration --
            [
                'title'    => 'User Management',
                'slug'     => 'user-management',
                'category' => 'Administration',
                'content'  => <<<'MD'
# User Management

## Creating Users

1. Navigate to Admin > Users
2. Click "Create User"
3. Fill in name, email, and password
4. Assign a role (Administrator, Editor, Viewer)
5. Click Save

## Roles

- **Administrator**: Full access to all features including settings
- **Editor**: Can create, edit, and delete records
- **Viewer**: Read-only access to records and search

## Password Policy

Passwords must be at least 8 characters and include a mix of letters, numbers, and symbols.
MD,
            ],
            [
                'title'    => 'Backup and Restore',
                'slug'     => 'backup-restore',
                'category' => 'Administration',
                'content'  => <<<'MD'
# Backup and Restore

## Creating Backups

Navigate to Admin > Backups and choose:

- **Full Backup**: Backs up both the PostgreSQL database and Fuseki triplestore
- **Database Only**: PostgreSQL dump only
- **Triplestore Only**: Fuseki N-Quads export only

Backups are stored on disk and tracked in the backups table.

## Restoring

1. Navigate to Admin > Backups
2. Find the backup you want to restore
3. Click the download icon to get a copy first
4. Use the restore command to apply the backup

## Scheduled Backups

Configure automatic backups in Settings > Backup with frequency and retention options.
MD,
            ],
            [
                'title'    => 'System Settings',
                'slug'     => 'system-settings',
                'category' => 'Administration',
                'content'  => <<<'MD'
# System Settings

Access settings at Admin > Settings. Configuration groups include:

## General
- Application name, URL, and locale
- Default timezone and date format

## Triplestore
- Fuseki URL and dataset name
- SPARQL endpoint configuration

## Search
- Elasticsearch host and index settings
- Search result display options

## Security
- Session timeout
- Password requirements
- Two-factor authentication settings

## Theme
- Logo and branding colors
- Custom CSS
MD,
            ],

            // -- Import / Export --
            [
                'title'    => 'CSV Import Guide',
                'slug'     => 'csv-import',
                'category' => 'Import / Export',
                'content'  => <<<'MD'
# CSV Import Guide

## Preparing Your CSV

Your CSV file should have:
- A header row with column names
- One record per row
- UTF-8 encoding

## Column Mapping

During import, you map each CSV column to a RiC-O property:
- `title` -> Record title
- `identifier` -> Reference code
- `description` -> Scope and content
- `date` -> Date statement
- `creator` -> Creator name

## Import Steps

1. Go to Admin > Ingest
2. Upload your CSV file
3. Map columns to RiC-O properties
4. Preview the import
5. Confirm to process

## Validation

The system validates each row for required fields and data format before importing.
MD,
            ],
            [
                'title'    => 'Exporting Data',
                'slug'     => 'exporting-data',
                'category' => 'Import / Export',
                'content'  => <<<'MD'
# Exporting Data

OpenRiC supports multiple export formats:

## CSV Export
Export search results or browse lists as CSV files for spreadsheet use.

## EAD3 Export
Export records as Encoded Archival Description (EAD3) XML for interoperability with other archival systems.

## EAC-CPF Export
Export authority records as EAC-CPF XML.

## RDF Export
Export raw RDF data in Turtle, JSON-LD, or N-Triples format from the SPARQL endpoint.

## Steps
1. Navigate to the records you want to export
2. Use the Export button and select format
3. Choose whether to include related records
4. Download the generated file
MD,
            ],

            // -- Troubleshooting --
            [
                'title'    => 'Common Issues',
                'slug'     => 'common-issues',
                'category' => 'Troubleshooting',
                'content'  => <<<'MD'
# Common Issues

## Search Not Returning Results
- Check that Elasticsearch is running: `systemctl status elasticsearch`
- Verify the index exists: Admin > Settings > Search
- Reindex if needed: `php artisan openric:reindex`

## Triplestore Connection Failed
- Check that Fuseki is running: `systemctl status fuseki`
- Verify the URL in Settings > Triplestore
- Test the SPARQL endpoint directly in a browser

## Slow Performance
- Check database query logs for slow queries
- Ensure PostgreSQL has adequate memory allocated
- Consider adding indexes for frequently queried fields

## Permission Denied Errors
- Verify your user role has the required permissions
- Check file system permissions for uploads directory
- Ensure the web server user can write to storage/
MD,
            ],
            [
                'title'    => 'Getting Support',
                'slug'     => 'getting-support',
                'category' => 'Troubleshooting',
                'content'  => <<<'MD'
# Getting Support

## Feedback
Use the Feedback form (available at the bottom of every page) to report bugs, request features, or provide general feedback.

## Logs
Check the application logs at `storage/logs/laravel.log` for detailed error information.

## Documentation
This help system covers the most common operations. For API documentation, visit `/api/documentation`.

## Community
OpenRiC is an open-source project. Visit the project repository for:
- Issue tracking
- Feature requests
- Contributing guidelines
MD,
            ],
        ];
    }

    public function getTopics(): array
    {
        $grouped = [];

        foreach ($this->allTopics() as $topic) {
            $category = $topic['category'];
            $grouped[$category][] = $topic;
        }

        ksort($grouped);

        return $grouped;
    }

    public function getTopic(string $slug): ?array
    {
        foreach ($this->allTopics() as $topic) {
            if ($topic['slug'] === $slug) {
                return $topic;
            }
        }

        return null;
    }

    public function searchTopics(string $query): array
    {
        $query   = mb_strtolower(trim($query));
        $results = [];

        if ($query === '') {
            return $results;
        }

        foreach ($this->allTopics() as $topic) {
            $titleLower   = mb_strtolower($topic['title']);
            $contentLower = mb_strtolower($topic['content']);

            $inTitle   = str_contains($titleLower, $query);
            $inContent = str_contains($contentLower, $query);

            if ($inTitle || $inContent) {
                // Extract a snippet around the match
                $snippet = '';
                if ($inContent) {
                    $pos = mb_strpos($contentLower, $query);
                    if ($pos !== false) {
                        $start   = max(0, $pos - 60);
                        $snippet = mb_substr($topic['content'], $start, 150);
                        if ($start > 0) {
                            $snippet = '...' . $snippet;
                        }
                        if ($start + 150 < mb_strlen($topic['content'])) {
                            $snippet .= '...';
                        }
                    }
                }

                $results[] = [
                    'title'    => $topic['title'],
                    'slug'     => $topic['slug'],
                    'category' => $topic['category'],
                    'snippet'  => $snippet ?: mb_substr($topic['content'], 0, 150) . '...',
                ];
            }
        }

        // Sort: title matches first
        usort($results, function (array $a, array $b) use ($query): int {
            $aInTitle = str_contains(mb_strtolower($a['title']), $query);
            $bInTitle = str_contains(mb_strtolower($b['title']), $query);

            if ($aInTitle && !$bInTitle) {
                return -1;
            }
            if (!$aInTitle && $bInTitle) {
                return 1;
            }

            return strcmp($a['title'], $b['title']);
        });

        return $results;
    }
}
