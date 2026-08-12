#!/usr/bin/env python3
"""Train a no-dependency logistic-regression maintenance model from CSV data.

CSV columns: repair_count,open_repair_count,asset_age_years,warranty_days_remaining,
active_hours_30d,crash_count_30d,battery_health_percent,failed_within_90_days
"""
import csv, json, math, sys
from pathlib import Path

FEATURES = ['repair_count', 'open_repair_count', 'asset_age_years', 'warranty_days_remaining',
            'active_hours_30d', 'crash_count_30d', 'battery_health_percent']
SCALE = [10, 5, 12, 730, 720, 30, 100]

def sigmoid(value): return 1 / (1 + math.exp(-max(-30, min(30, value))))

if len(sys.argv) != 2:
    raise SystemExit(f'Usage: {Path(sys.argv[0]).name} training_data.csv')
with open(sys.argv[1], newline='', encoding='utf-8') as source:
    rows = list(csv.DictReader(source))
if len(rows) < 30:
    raise SystemExit('At least 30 labelled historical rows are required before training.')
try:
    X = [[float(row[name]) / scale for name, scale in zip(FEATURES, SCALE)] for row in rows]
    y = [float(row['failed_within_90_days']) for row in rows]
except (KeyError, ValueError) as error:
    raise SystemExit(f'Invalid training CSV: {error}')
if len(set(y)) < 2: raise SystemExit('Training data must contain both maintenance outcomes (0 and 1).')
weights = [0.0] * len(FEATURES); intercept = 0.0
for _ in range(2500):
    errors = [sigmoid(intercept + sum(w * x for w, x in zip(weights, row))) - target for row, target in zip(X, y)]
    rate = 0.12 / len(rows)
    intercept -= rate * sum(errors)
    weights = [w - rate * sum(error * row[i] for error, row in zip(errors, X)) for i, w in enumerate(weights)]
Path(__file__).with_name('maintenance_model.json').write_text(json.dumps({
    'version': 'trained-logistic-v1', 'intercept': intercept, 'weights': dict(zip(FEATURES, weights)),
    'trained_rows': len(rows), 'target': 'maintenance failure within 90 days'
}, indent=2) + '\n', encoding='utf-8')
print(f'Trained model on {len(rows)} rows: {Path(__file__).with_name("maintenance_model.json")}')
