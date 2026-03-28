<?php

declare(strict_types=1);

namespace OpenRiC\Core\Services;

use OpenRiC\Core\Contracts\StandardsMappingServiceInterface;

class StandardsMappingService implements StandardsMappingServiceInterface
{
    public function renderIsadG(array $entityProperties): array
    {
        $mapping = $this->getIsadgMapping();
        $rendered = [];

        foreach ($mapping as $code => $config) {
            $ricoProperty = $config['rico_property'];
            $value = null;

            // Look for the property in entity data
            if (isset($entityProperties[$ricoProperty])) {
                $propValues = $entityProperties[$ricoProperty];
                if (is_array($propValues) && isset($propValues[0]['value'])) {
                    $value = implode('; ', array_column($propValues, 'value'));
                } elseif (is_string($propValues)) {
                    $value = $propValues;
                }
            }

            $rendered[$code] = [
                'code' => $code,
                'label' => $config['label'],
                'value' => $value,
                'required' => $config['required'] ?? false,
                'area' => $this->getIsadgArea($code),
            ];
        }

        return $rendered;
    }

    public function renderIsaarCpf(array $entityProperties): array
    {
        $mapping = $this->getIsaarCpfMapping();
        $rendered = [];

        foreach ($mapping as $code => $config) {
            $ricoProperty = $config['rico_property'];
            $value = null;

            if (isset($entityProperties[$ricoProperty])) {
                $propValues = $entityProperties[$ricoProperty];
                if (is_array($propValues) && isset($propValues[0]['value'])) {
                    $value = implode('; ', array_column($propValues, 'value'));
                } elseif (is_string($propValues)) {
                    $value = $propValues;
                }
            }

            $rendered[$code] = [
                'code' => $code,
                'label' => $config['label'],
                'value' => $value,
            ];
        }

        return $rendered;
    }

    public function isadgToRico(array $formData): array
    {
        $properties = [];

        if (! empty($formData['title'])) {
            $properties['rico:title'] = ['value' => $formData['title'], 'datatype' => 'xsd:string'];
        }
        if (! empty($formData['identifier'])) {
            $properties['rico:identifier'] = ['value' => $formData['identifier'], 'datatype' => 'xsd:string'];
        }
        if (! empty($formData['scope_and_content'])) {
            $properties['rico:scopeAndContent'] = ['value' => $formData['scope_and_content'], 'datatype' => 'xsd:string'];
        }
        if (! empty($formData['extent'])) {
            $properties['rico:carrierExtent'] = ['value' => $formData['extent'], 'datatype' => 'xsd:string'];
        }
        if (! empty($formData['admin_history'])) {
            $properties['rico:history'] = ['value' => $formData['admin_history'], 'datatype' => 'xsd:string'];
        }
        if (! empty($formData['arrangement'])) {
            $properties['rico:structure'] = ['value' => $formData['arrangement'], 'datatype' => 'xsd:string'];
        }
        if (! empty($formData['access_conditions'])) {
            $properties['rico:conditionsOfAccess'] = ['value' => $formData['access_conditions'], 'datatype' => 'xsd:string'];
        }
        if (! empty($formData['reproduction_conditions'])) {
            $properties['rico:conditionsOfUse'] = ['value' => $formData['reproduction_conditions'], 'datatype' => 'xsd:string'];
        }
        if (! empty($formData['language'])) {
            $properties['rico:hasOrHadLanguage'] = ['value' => $formData['language'], 'datatype' => 'xsd:string'];
        }
        if (! empty($formData['physical_characteristics'])) {
            $properties['rico:physicalCharacteristics'] = ['value' => $formData['physical_characteristics'], 'datatype' => 'xsd:string'];
        }
        if (! empty($formData['accruals'])) {
            $properties['rico:accruals'] = ['value' => $formData['accruals'], 'datatype' => 'xsd:string'];
        }
        if (! empty($formData['note'])) {
            $properties['rico:descriptiveNote'] = ['value' => $formData['note'], 'datatype' => 'xsd:string'];
        }

        return $properties;
    }

    public function isaarCpfToRico(array $formData): array
    {
        $properties = [];

        if (! empty($formData['authorized_name'])) {
            $properties['rico:hasAgentName'] = ['value' => $formData['authorized_name'], 'datatype' => 'xsd:string'];
        }
        if (! empty($formData['history'])) {
            $properties['rico:history'] = ['value' => $formData['history'], 'datatype' => 'xsd:string'];
        }
        if (! empty($formData['identifier'])) {
            $properties['rico:identifier'] = ['value' => $formData['identifier'], 'datatype' => 'xsd:string'];
        }

        return $properties;
    }

    public function getIsadgMapping(): array
    {
        return config('openric.mappings.isadg', []);
    }

    public function getIsaarCpfMapping(): array
    {
        return config('openric.mappings.isaar_cpf', []);
    }

    private function getIsadgArea(string $code): string
    {
        $areas = [
            '3.1' => 'Identity Statement Area',
            '3.2' => 'Context Area',
            '3.3' => 'Content and Structure Area',
            '3.4' => 'Conditions of Access and Use Area',
            '3.5' => 'Allied Materials Area',
            '3.6' => 'Notes Area',
            '3.7' => 'Description Control Area',
        ];

        $prefix = substr($code, 0, 3);

        return $areas[$prefix] ?? 'Other';
    }
}
