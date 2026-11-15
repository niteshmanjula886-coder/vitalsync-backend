import re

with open('c:/Users/nites/.android-studio/vitalsync-backend/api/reports/weekly_report.php', 'r', encoding='utf-8') as f:
    c = f.read()

# find all prepare statements
prepares = re.findall(r'\$conn->prepare\(\s*["\']([^"\']+)["\']\s*\)', c)
print(f"Total prepares in weekly_report.php: {len(prepares)}")
for i, p in enumerate(prepares):
    print(f"[{i+1}] {p}")

