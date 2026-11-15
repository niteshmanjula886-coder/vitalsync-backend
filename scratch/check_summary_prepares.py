import re

with open('c:/Users/nites/.android-studio/vitalsync-backend/vitals/summary.php', 'r', encoding='utf-8') as f:
    c = f.read()

prepares = re.findall(r'\$conn->prepare\(\s*["\']([^"\']+)["\']\s*\)', c)
print(f"Total prepares in vitals/summary.php: {len(prepares)}")
for i, p in enumerate(prepares):
    print(f"[{i+1}] {p}")

