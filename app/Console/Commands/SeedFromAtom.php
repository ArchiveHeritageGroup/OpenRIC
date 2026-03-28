<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seed OpenRiC triplestore from AtoM MySQL database.
 * Adapted from /usr/share/nginx/heratio/packages/ahg-ric/tools/ric_extractor_v5.py (1753 lines)
 */
class SeedFromAtom extends Command
{
    protected $signature = 'openric:seed-from-atom
        {--database=archive : AtoM MySQL database name}
        {--limit=0 : Max records to import (0 = all)}
        {--dry-run : Show what would be imported without writing}';

    protected $description = 'Import archival descriptions from an AtoM MySQL database into the OpenRiC Fuseki triplestore';

    private string $baseUri;
    private string $fusekiEndpoint;
    private string $fusekiUser;
    private string $fusekiPass;
    private int $imported = 0;
    private int $agents = 0;

    private const LEVEL_TO_RIC = [
        'fonds' => 'RecordSet', 'subfonds' => 'RecordSet', 'collection' => 'RecordSet',
        'series' => 'RecordSet', 'subseries' => 'RecordSet', 'file' => 'RecordSet',
        'item' => 'Record', 'part' => 'RecordPart',
    ];

    private const ACTOR_TYPE_TO_RIC = [
        'corporate body' => 'CorporateBody', 'person' => 'Person', 'family' => 'Family',
    ];

    public function handle(): int
    {
        $database = $this->option('database');
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');

        $this->baseUri = config('openric.base_uri', 'https://ric.theahg.co.za/entity');
        $this->fusekiEndpoint = config('fuseki.endpoint') . '/update';
        $this->fusekiUser = config('fuseki.username');
        $this->fusekiPass = config('fuseki.password');

        // Configure AtoM MySQL connection
        config(["database.connections.atom" => [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => $database,
            'username' => 'root',
            'password' => 'Merlot@123',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]]);

        $this->info("Seeding from AtoM database: {$database}");

        // Count
        $totalRecords = DB::connection('atom')->table('information_object')->where('id', '>', 1)->count();
        $totalActors = DB::connection('atom')->table('actor')->where('id', '>', 1)->count();
        $this->info("Found: {$totalRecords} records, {$totalActors} actors");

        if ($dryRun) {
            $this->warn('Dry run — no data will be written to Fuseki');
        }

        // Extract and load records
        $this->info('Importing records...');
        $this->importRecords($database, $limit, $dryRun);

        // Extract and load agents
        $this->info('Importing agents...');
        $this->importAgents($database, $limit, $dryRun);

        // Extract and load events/activities
        $this->info('Importing activities...');
        $this->importActivities($database, $dryRun);

        $this->newLine();
        $this->info("Done. Imported {$this->imported} records, {$this->agents} agents.");

        return Command::SUCCESS;
    }

    private function importRecords(string $database, int $limit, bool $dryRun): void
    {
        $query = DB::connection('atom')
            ->table('information_object as io')
            ->join('information_object_i18n as ioi', function ($j) {
                $j->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('term_i18n as ti', function ($j) {
                $j->on('io.level_of_description_id', '=', 'ti.id')->where('ti.culture', '=', 'en');
            })
            ->where('io.id', '>', 1)
            ->select(
                'io.id', 'io.parent_id', 'io.identifier',
                'ioi.title', 'ioi.scope_and_content', 'ioi.arrangement',
                'ioi.extent_and_medium', 'ioi.archival_history', 'ioi.acquisition',
                'ioi.physical_characteristics', 'ioi.finding_aids',
                'ioi.location_of_originals', 'ioi.location_of_copies',
                'ioi.related_units_of_description', 'ioi.rules',
                'ti.name as level_name'
            )
            ->orderBy('io.lft');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $bar = $this->output->createProgressBar($limit > 0 ? $limit : $query->count());

        $query->chunk(50, function ($rows) use ($dryRun, $bar) {
            $triples = [];

            foreach ($rows as $row) {
                $level = strtolower($row->level_name ?? 'item');
                $ricType = self::LEVEL_TO_RIC[$level] ?? 'RecordSet';
                $iri = $this->baseUri . '/' . strtolower($ricType) . '/' . $row->id;

                $triples[] = "<{$iri}> a rico:{$ricType}";

                if ($row->title) {
                    $triples[] = "<{$iri}> rico:title " . $this->literal($row->title);
                }
                if ($row->identifier) {
                    $triples[] = "<{$iri}> rico:identifier " . $this->literal($row->identifier);
                }
                if ($row->scope_and_content) {
                    $triples[] = "<{$iri}> rico:scopeAndContent " . $this->literal($row->scope_and_content);
                }
                if ($row->arrangement) {
                    $triples[] = "<{$iri}> rico:structure " . $this->literal($row->arrangement);
                }
                if ($row->extent_and_medium) {
                    $triples[] = "<{$iri}> rico:carrierExtent " . $this->literal($row->extent_and_medium);
                }
                if ($row->archival_history) {
                    $triples[] = "<{$iri}> rico:history " . $this->literal($row->archival_history);
                }
                if ($row->physical_characteristics) {
                    $triples[] = "<{$iri}> rico:physicalCharacteristics " . $this->literal($row->physical_characteristics);
                }

                // Parent relationship
                if ($row->parent_id && $row->parent_id > 1) {
                    $parentIri = $this->baseUri . '/recordset/' . $row->parent_id;
                    $triples[] = "<{$iri}> rico:isOrWasIncludedIn <{$parentIri}>";
                }

                $this->imported++;
                $bar->advance();
            }

            if (! $dryRun && count($triples) > 0) {
                $this->executeSparqlUpdate($triples);
            }
        });

        $bar->finish();
        $this->newLine();
    }

    private function importAgents(string $database, int $limit, bool $dryRun): void
    {
        $query = DB::connection('atom')
            ->table('actor as a')
            ->join('actor_i18n as ai', function ($j) {
                $j->on('a.id', '=', 'ai.id')->where('ai.culture', '=', 'en');
            })
            ->leftJoin('term_i18n as ti', function ($j) {
                $j->on('a.entity_type_id', '=', 'ti.id')->where('ti.culture', '=', 'en');
            })
            ->where('a.id', '>', 1)
            ->whereNotNull('ai.authorized_form_of_name')
            ->where('ai.authorized_form_of_name', '!=', '')
            ->orderBy('a.id')
            ->select(
                'a.id', 'ai.authorized_form_of_name', 'ai.dates_of_existence',
                'ai.history', 'ai.places', 'ai.legal_status',
                'ai.functions', 'ai.mandates', 'ai.general_context',
                'ti.name as entity_type'
            );

        if ($limit > 0) {
            $query->limit($limit);
        }

        $bar = $this->output->createProgressBar($limit > 0 ? $limit : $query->count());

        $query->chunk(50, function ($rows) use ($dryRun, $bar) {
            $triples = [];

            foreach ($rows as $row) {
                $entityType = strtolower($row->entity_type ?? 'person');
                $ricType = self::ACTOR_TYPE_TO_RIC[$entityType] ?? 'Person';
                $iri = $this->baseUri . '/' . strtolower($ricType) . '/' . $row->id;

                $triples[] = "<{$iri}> a rico:{$ricType}";
                $triples[] = "<{$iri}> rico:title " . $this->literal($row->authorized_form_of_name);

                // AgentName
                $nameIri = $iri . '/name';
                $triples[] = "<{$iri}> rico:hasAgentName <{$nameIri}>";
                $triples[] = "<{$nameIri}> a rico:AgentName";
                $triples[] = "<{$nameIri}> rico:textualValue " . $this->literal($row->authorized_form_of_name);

                if ($row->history) {
                    $triples[] = "<{$iri}> rico:history " . $this->literal($row->history);
                }
                if ($row->dates_of_existence) {
                    $triples[] = "<{$iri}> rico:descriptiveNote " . $this->literal('Dates: ' . $row->dates_of_existence);
                }
                if ($row->legal_status) {
                    $triples[] = "<{$iri}> rico:hasOrHadLegalStatus " . $this->literal($row->legal_status);
                }

                $this->agents++;
                $bar->advance();
            }

            if (! $dryRun && count($triples) > 0) {
                $this->executeSparqlUpdate($triples);
            }
        });

        $bar->finish();
        $this->newLine();
    }

    private function importActivities(string $database, bool $dryRun): void
    {
        $rows = DB::connection('atom')
            ->table('event as e')
            ->join('event_i18n as ei', function ($j) {
                $j->on('e.id', '=', 'ei.id')->where('ei.culture', '=', 'en');
            })
            ->leftJoin('term_i18n as ti', function ($j) {
                $j->on('e.type_id', '=', 'ti.id')->where('ti.culture', '=', 'en');
            })
            ->select('e.id', 'e.object_id', 'e.actor_id', 'e.start_date', 'e.end_date', 'ei.date as date_display', 'ti.name as event_type')
            ->get();

        $triples = [];

        foreach ($rows as $row) {
            if (! $row->object_id || $row->object_id <= 1) {
                continue;
            }

            // Link agent to record via rico:hasOrHadCreator for creation events
            $eventType = strtolower($row->event_type ?? 'creation');

            if ($row->actor_id && $row->actor_id > 1) {
                if (in_array($eventType, ['creation', 'accumulation', 'contribution', 'collection'])) {
                    $recordIri = $this->baseUri . '/recordset/' . $row->object_id;
                    $agentIri = $this->baseUri . '/person/' . $row->actor_id;
                    $triples[] = "<{$recordIri}> rico:hasOrHadCreator <{$agentIri}>";
                }
            }

            // Add date if present
            if ($row->date_display || $row->start_date) {
                $recordIri = $this->baseUri . '/recordset/' . $row->object_id;
                $dateIri = $this->baseUri . '/date/' . $row->id;
                $triples[] = "<{$recordIri}> rico:isAssociatedWithDate <{$dateIri}>";
                $triples[] = "<{$dateIri}> a rico:DateRange";

                if ($row->date_display) {
                    $triples[] = "<{$dateIri}> rico:expressedDate " . $this->literal($row->date_display);
                }
                if ($row->start_date) {
                    $date = substr($row->start_date, 0, 10);
                    $triples[] = "<{$dateIri}> rico:hasBeginningDate \"{$date}\"^^xsd:date";
                }
                if ($row->end_date) {
                    $date = substr($row->end_date, 0, 10);
                    $triples[] = "<{$dateIri}> rico:hasEndDate \"{$date}\"^^xsd:date";
                }
            }
        }

        if (! $dryRun && count($triples) > 0) {
            $this->info("  Writing " . count($triples) . " activity triples...");
            // Batch in chunks of 500
            foreach (array_chunk($triples, 500) as $chunk) {
                $this->executeSparqlUpdate($chunk);
            }
        }
    }

    private function executeSparqlUpdate(array $triples): void
    {
        $prefixes = "PREFIX rico: <https://www.ica.org/standards/RiC/ontology#>\n"
            . "PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>\n"
            . "PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>\n"
            . "PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>\n"
            . "PREFIX openric: <https://ric.theahg.co.za/ontology#>\n";

        $sparql = $prefixes . "INSERT DATA {\n  " . implode(" .\n  ", $triples) . " .\n}";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->fusekiEndpoint,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $sparql,
            CURLOPT_HTTPHEADER => ['Content-Type: application/sparql-update'],
            CURLOPT_USERPWD => "{$this->fusekiUser}:{$this->fusekiPass}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            $this->error("Fuseki error (HTTP {$httpCode}): " . substr($response, 0, 200));
        }
    }

    private function literal(string $value): string
    {
        $escaped = str_replace(['\\', '"', "\n", "\r", "\t"], ['\\\\', '\\"', '\\n', '\\r', '\\t'], $value);
        return '"' . $escaped . '"';
    }
}
