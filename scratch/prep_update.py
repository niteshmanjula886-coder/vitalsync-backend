import re

with open('c:/Users/nites/.android-studio/vitalsync-backend/api/reports/weekly_report.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Let's inspect the exact lines from line 1 to 35
print(content[:600])

