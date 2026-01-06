<?php

namespace MKA\Formbuilder;

class FormFormatter {

/**
 * Build a mapping of field IDs to their metadata (label, type, etc.)
 */
private function getFieldMapFromDefinition($form_definition_json) {
    $definition = json_decode($form_definition_json, true);
    $fieldMap = [];

    if (!is_array($definition)) return $fieldMap;

    foreach ($definition as $row) {
        foreach ($row as $field) {
            if (isset($field['id']) && isset($field['label'])) {
                $fieldMap[$field['id']] = [
                    'label' => $field['label'],
                    'type' => $field['type'] ?? 'text',
                    'tooltip' => $field['tooltip'] ?? '',
                    'options' => $field['options'] ?? [],
                ];
            }
        }
    }

    return $fieldMap;
}

/**
 * Take stored entry data and map it into a readable format using the field map.
 */
 
 public function formatSubmissionForReview($submission_json, $form_definition_json) {
    $definition = json_decode($form_definition_json, true);
    $submission = json_decode($submission_json, true);
    $formatted = [];

    if (!is_array($definition)) return $formatted;
    if (!is_array($submission)) $submission = [];

    foreach ($definition as $row) {
        foreach ($row as $field) {
            $fieldId = $field['id'] ?? '';
            $type = $field['type'] ?? 'text';
            $label = $field['label'] ?? $fieldId;
            $tooltip = $field['tooltip'] ?? '';
            $options = $field['options'] ?? [];

            // Always include headers
            if (preg_match('/^h[1-6]$/', $type)) {
                $formatted[] = [
                    'field_id' => $fieldId,
                    'label' => $label,
                    'type' => $type,
                    'tooltip' => '',
                    'value' => null
                ];
                continue;
            }

            // Skip non-input types
            if (in_array($type, ['separator', 'paragraph', 'image'])) {
                continue;
            }

            // Only include if submitted
            if (!array_key_exists($fieldId, $submission)) {
                continue;
            }

            $formatted[] = [
                'field_id' => $fieldId,
                'label' => $label,
                'type' => $type,
                'tooltip' => $tooltip,
                'value' => $submission[$fieldId]
            ];
        }
    }

    return $formatted;
}

 
 /*
 public function formatSubmissionForReview($submission_json, $form_definition_json) {
    $definition = json_decode($form_definition_json, true);
    $submission = json_decode($submission_json, true);
    $formatted = [];

    if (!is_array($definition)) return $formatted;
    if (!is_array($submission)) $submission = [];

    foreach ($definition as $row) {
        foreach ($row as $field) {
            $fieldId = $field['id'] ?? '';
            $type = $field['type'] ?? 'text';
            $label = $field['label'] ?? $fieldId;
            $tooltip = $field['tooltip'] ?? '';
            $options = $field['options'] ?? [];

            // Always include headers
            if (preg_match('/^h[1-6]$/', $type)) {
                $formatted[] = [
                    'field_id' => $fieldId,
                    'label' => $label,
                    'type' => $type,
                    'tooltip' => '',
                    'value' => null
                ];
                continue;
            }

            // Skip paragraphs, separators, images
            if (in_array($type, ['paragraph', 'separator', 'image'])) {
                continue;
            }

            // Only include inputs that were actually submitted
            if (!array_key_exists($fieldId, $submission)) {
                continue;
            }

            $formatted[] = [
                'field_id' => $fieldId,
                'label' => $label,
                'type' => $type,
                'tooltip' => $tooltip,
                'value' => $submission[$fieldId]
            ];
        }
    }

    return $formatted;
}
  */
 
}
