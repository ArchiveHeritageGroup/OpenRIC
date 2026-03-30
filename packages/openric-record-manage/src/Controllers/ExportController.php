<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

/**
 * Export controller — EAD, Dublin Core, MODS, CSV exports.
 * Adapted from Heratio ExportController (697 lines).
 */
class ExportController extends Controller
{
    /**
     * Export a record as EAD 2002 XML.
     */
    public function ead(int $recordId): Response
    {
        $record = $this->getRecord($recordId);
        if (!$record) {
            abort(404);
        }

        $agents   = $this->getAgents($recordId, 'creator');
        $subjects = $this->getAccessPoints($recordId, 'subject');
        $places   = $this->getAccessPoints($recordId, 'place');
        $genres   = $this->getAccessPoints($recordId, 'genre');
        $notes    = $this->getNotes($recordId);
        $children = $this->getChildren($recordId);

        $xml = $this->buildEadXml($record, $agents, $subjects, $places, $genres, $notes, $children);

        $filename = ($record->identifier ?: 'record-' . $recordId) . '_ead.xml';

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Export a record as Dublin Core 1.1 XML.
     */
    public function dc(int $recordId): Response
    {
        $record = $this->getRecord($recordId);
        if (!$record) {
            abort(404);
        }

        $agents   = $this->getAgents($recordId, 'creator');
        $subjects = $this->getAccessPoints($recordId, 'subject');
        $places   = $this->getAccessPoints($recordId, 'place');
        $events   = $this->getEvents($recordId);

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<metadata xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/">' . "\n";
        $xml .= '  <dc:title>' . $this->e($record->title) . "</dc:title>\n";

        foreach ($agents as $a) {
            $xml .= '  <dc:creator>' . $this->e($a->agent_name) . "</dc:creator>\n";
        }
        foreach ($subjects as $s) {
            $xml .= '  <dc:subject>' . $this->e($s->term_name) . "</dc:subject>\n";
        }
        if ($record->scope_and_content) {
            $xml .= '  <dc:description>' . $this->e($record->scope_and_content) . "</dc:description>\n";
        }
        foreach ($events as $ev) {
            $date = $ev->date_display ?: ($ev->start_date ?? '');
            if ($date) {
                $xml .= '  <dc:date>' . $this->e($date) . "</dc:date>\n";
            }
        }
        if ($record->level) {
            $xml .= '  <dc:type>' . $this->e($record->level) . "</dc:type>\n";
        }
        if ($record->identifier) {
            $xml .= '  <dc:identifier>' . $this->e($record->identifier) . "</dc:identifier>\n";
        }
        foreach ($places as $p) {
            $xml .= '  <dc:coverage>' . $this->e($p->term_name) . "</dc:coverage>\n";
        }
        if ($record->access_conditions) {
            $xml .= '  <dc:rights>' . $this->e($record->access_conditions) . "</dc:rights>\n";
        }
        $xml .= "</metadata>\n";

        $filename = ($record->identifier ?: 'record-' . $recordId) . '_dc.xml';

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Export a record as MODS 3.5 XML.
     */
    public function mods(int $recordId): Response
    {
        $record = $this->getRecord($recordId);
        if (!$record) {
            abort(404);
        }

        $agents   = $this->getAgents($recordId, 'creator');
        $subjects = $this->getAccessPoints($recordId, 'subject');
        $places   = $this->getAccessPoints($recordId, 'place');
        $genres   = $this->getAccessPoints($recordId, 'genre');
        $events   = $this->getEvents($recordId);

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<mods xmlns="http://www.loc.gov/mods/v3" version="3.5">' . "\n";
        $xml .= "  <titleInfo>\n    <title>" . $this->e($record->title) . "</title>\n  </titleInfo>\n";

        foreach ($agents as $a) {
            $xml .= "  <name type=\"personal\">\n    <namePart>" . $this->e($a->agent_name) . "</namePart>\n";
            $xml .= "    <role><roleTerm type=\"text\" authority=\"marcrelator\">creator</roleTerm></role>\n  </name>\n";
        }

        if ($record->level) {
            $xml .= '  <typeOfResource>' . $this->e($record->level) . "</typeOfResource>\n";
        }
        if ($record->scope_and_content) {
            $xml .= '  <abstract>' . $this->e($record->scope_and_content) . "</abstract>\n";
        }
        foreach ($subjects as $s) {
            $xml .= '  <subject><topic>' . $this->e($s->term_name) . "</topic></subject>\n";
        }
        foreach ($places as $p) {
            $xml .= '  <subject><geographic>' . $this->e($p->term_name) . "</geographic></subject>\n";
        }
        foreach ($genres as $g) {
            $xml .= '  <genre>' . $this->e($g->term_name) . "</genre>\n";
        }
        if ($record->identifier) {
            $xml .= '  <identifier type="local">' . $this->e($record->identifier) . "</identifier>\n";
        }
        if ($record->access_conditions) {
            $xml .= '  <accessCondition type="restriction on access">' . $this->e($record->access_conditions) . "</accessCondition>\n";
        }

        $xml .= "  <recordInfo>\n";
        $xml .= '    <recordContentSource>' . $this->e(config('app.name', 'OpenRiC')) . "</recordContentSource>\n";
        $xml .= '    <recordCreationDate encoding="iso8601">' . gmdate('Y-m-d') . "</recordCreationDate>\n";
        $xml .= "  </recordInfo>\n";
        $xml .= "</mods>\n";

        $filename = ($record->identifier ?: 'record-' . $recordId) . '_mods.xml';

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Export records as CSV.
     */
    public function csv(int $recordId): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $record = $this->getRecord($recordId);
        if (!$record) {
            abort(404);
        }

        $rows = DB::table('records')
            ->where('id', $recordId)
            ->orWhere('parent_id', $recordId)
            ->orderBy('id')
            ->get();

        $headers = [
            'identifier', 'title', 'iri', 'level',
            'scope_and_content', 'extent_and_medium', 'archival_history',
            'acquisition', 'appraisal', 'accruals', 'arrangement',
            'access_conditions', 'reproduction_conditions', 'physical_characteristics',
            'finding_aids', 'location_of_originals', 'location_of_copies',
            'related_units_of_description', 'rules', 'sources',
        ];

        $callback = function () use ($rows, $headers) {
            $fp = fopen('php://output', 'w');
            fputcsv($fp, $headers);
            foreach ($rows as $row) {
                $line = [];
                foreach ($headers as $h) {
                    $line[] = $row->$h ?? '';
                }
                fputcsv($fp, $line);
            }
            fclose($fp);
        };

        $filename = ($record->identifier ?: 'record-' . $recordId) . '_export.csv';

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function getRecord(int $recordId): ?object
    {
        return DB::table('records')->where('id', $recordId)->first();
    }

    private function getAgents(int $recordId, string $relationType): \Illuminate\Support\Collection
    {
        try {
            return DB::table('record_agents')
                ->where('record_id', $recordId)
                ->where('relation_type', $relationType)
                ->get();
        } catch (\Illuminate\Database\QueryException $e) {
            return collect();
        }
    }

    private function getAccessPoints(int $recordId, string $type): \Illuminate\Support\Collection
    {
        try {
            return DB::table('record_access_points')
                ->where('record_id', $recordId)
                ->where('type', $type)
                ->get();
        } catch (\Illuminate\Database\QueryException $e) {
            return collect();
        }
    }

    private function getNotes(int $recordId): \Illuminate\Support\Collection
    {
        try {
            return DB::table('record_notes')
                ->where('record_id', $recordId)
                ->get();
        } catch (\Illuminate\Database\QueryException $e) {
            return collect();
        }
    }

    private function getEvents(int $recordId): \Illuminate\Support\Collection
    {
        try {
            return DB::table('record_events')
                ->where('record_id', $recordId)
                ->get();
        } catch (\Illuminate\Database\QueryException $e) {
            return collect();
        }
    }

    private function getChildren(int $recordId): \Illuminate\Support\Collection
    {
        return DB::table('records')
            ->where('parent_id', $recordId)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();
    }

    private function e(?string $value = null): string
    {
        return htmlspecialchars($value ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function buildEadXml($record, $agents, $subjects, $places, $genres, $notes, $children): string
    {
        $eadLevel = $this->mapLevelToEad($record->level);
        $date = gmdate('Y-m-d H:i e');

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= "<ead>\n";
        $xml .= "<eadheader langencoding=\"iso639-2b\" countryencoding=\"iso3166-1\" dateencoding=\"iso8601\">\n";
        $xml .= '  <eadid>' . $this->e($record->identifier) . "</eadid>\n";
        $xml .= "  <filedesc><titlestmt><titleproper>" . $this->e($record->title) . "</titleproper></titlestmt></filedesc>\n";
        $xml .= "  <profiledesc><creation>Generated by OpenRiC <date>{$date}</date></creation></profiledesc>\n";
        $xml .= "</eadheader>\n";
        $xml .= "<archdesc level=\"{$eadLevel}\">\n";
        $xml .= "  <did>\n";
        if ($record->identifier) {
            $xml .= '    <unitid>' . $this->e($record->identifier) . "</unitid>\n";
        }
        $xml .= '    <unittitle>' . $this->e($record->title) . "</unittitle>\n";
        foreach ($agents as $a) {
            $xml .= '    <origination><name>' . $this->e($a->agent_name) . "</name></origination>\n";
        }
        if ($record->extent_and_medium) {
            $xml .= '    <physdesc><extent>' . $this->e($record->extent_and_medium) . "</extent></physdesc>\n";
        }
        $xml .= "  </did>\n";

        if ($record->scope_and_content) {
            $xml .= '  <scopecontent><p>' . $this->e($record->scope_and_content) . "</p></scopecontent>\n";
        }
        if ($record->arrangement) {
            $xml .= '  <arrangement><p>' . $this->e($record->arrangement) . "</p></arrangement>\n";
        }
        if ($record->access_conditions) {
            $xml .= '  <accessrestrict><p>' . $this->e($record->access_conditions) . "</p></accessrestrict>\n";
        }
        if ($record->reproduction_conditions) {
            $xml .= '  <userestrict><p>' . $this->e($record->reproduction_conditions) . "</p></userestrict>\n";
        }

        if ($subjects->isNotEmpty() || $places->isNotEmpty() || $genres->isNotEmpty()) {
            $xml .= "  <controlaccess>\n";
            foreach ($subjects as $s) {
                $xml .= '    <subject>' . $this->e($s->term_name) . "</subject>\n";
            }
            foreach ($places as $p) {
                $xml .= '    <geogname>' . $this->e($p->term_name) . "</geogname>\n";
            }
            foreach ($genres as $g) {
                $xml .= '    <genreform>' . $this->e($g->term_name) . "</genreform>\n";
            }
            $xml .= "  </controlaccess>\n";
        }

        if ($children->isNotEmpty()) {
            $xml .= "  <dsc type=\"combined\">\n";
            foreach ($children as $child) {
                $childLevel = $this->mapLevelToEad($child->level);
                $xml .= "    <c level=\"{$childLevel}\"><did><unittitle>" . $this->e($child->title) . "</unittitle></did></c>\n";
            }
            $xml .= "  </dsc>\n";
        }

        $xml .= "</archdesc>\n</ead>\n";
        return $xml;
    }

    private function mapLevelToEad(?string $level): string
    {
        $map = [
            'Fonds' => 'fonds', 'Sub-fonds' => 'subfonds', 'Collection' => 'collection',
            'Series' => 'series', 'Sub-series' => 'subseries', 'File' => 'file',
            'Item' => 'item', 'Part' => 'item',
        ];
        return $map[$level ?? ''] ?? 'otherlevel';
    }
}
