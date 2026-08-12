<?php
/** Explainable local inference for maintenance risk. */

function maintenance_risk(array $asset): array
{
    $features = [
        'repair_count' => min(10, (int) $asset['repairs']) / 10,
        'open_repair_count' => min(5, (int) $asset['open_repairs']) / 5,
        'asset_age_years' => min(12, max(0, (float) $asset['asset_age_years'])) / 12,
        'warranty_days_remaining' => max(-730, min(730, (int) $asset['warranty_days_remaining'])) / 730,
        'active_hours_30d' => min(720, max(0, (float) $asset['active_hours_30d'])) / 720,
        'crash_count_30d' => min(30, max(0, (int) $asset['crash_count_30d'])) / 30,
        'battery_health_percent' => min(100, max(0, (float) ($asset['battery_health_percent'] ?? 100))) / 100,
    ];

    $fallback = [
        'version' => 'baseline-logistic-v1',
        'intercept' => -3.0,
        'weights' => [
            'repair_count' => 1.35, 'open_repair_count' => 2.25,
            'asset_age_years' => 1.10, 'warranty_days_remaining' => -0.85,
            'active_hours_30d' => 0.40, 'crash_count_30d' => 1.20,
            'battery_health_percent' => -0.75,
        ],
    ];
    $modelFile = __DIR__ . '/ml/maintenance_model.json';
    $model = is_file($modelFile) ? json_decode((string) file_get_contents($modelFile), true) : null;
    if (!is_array($model) || !isset($model['intercept'], $model['weights'])) $model = $fallback;

    $logit = (float) $model['intercept'];
    foreach ($features as $name => $value) $logit += ((float) ($model['weights'][$name] ?? 0)) * $value;
    $score = 1 / (1 + exp(-max(-30, min(30, $logit))));
    $level = $score >= 0.70 ? 'High' : ($score >= 0.35 ? 'Medium' : 'Low');
    return ['score' => $score, 'level' => $level, 'version' => (string) ($model['version'] ?? 'custom-logistic'), 'features' => $features];
}
