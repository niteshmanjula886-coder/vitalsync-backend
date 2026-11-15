import re

with open('c:/Users/nites/.android-studio/vitalsync-backend/api/reports/weekly_report.php', 'r', encoding='utf-8') as f:
    orig = f.read()

# Let's inspect executive summary and recommendations generation in weekly_report.php
# to ensure we don't break the clinical advice and recommendations
idx = orig.find('// 12. Generate Clinical Rule-Based Executive Summary')
print('Clinical summary found at:', idx)
print(orig[idx:idx+800])

