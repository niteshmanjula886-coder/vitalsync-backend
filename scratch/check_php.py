import os, re

backend_dir = r'c:\Users\nites\.android-studio\vitalsync-backend'
for root, dirs, files in os.walk(backend_dir):
    if 'scratch' in root or '.git' in root:
        continue
    for file in files:
        if file.endswith('.php'):
            full_path = os.path.join(root, file)
            with open(full_path, 'r', encoding='utf-8', errors='ignore') as f:
                content = f.read()
            for line in content.splitlines():
                if ('require' in line or 'include' in line) and ('auth_middleware' in line or 'db.php' in line or 'config' in line):
                    quotes = re.findall(r'["\']([^"\']+)["\']', line)
                    for q in quotes:
                        if 'config' in q or 'db' in q:
                            # Strip leading slash so join works relative to root
                            rel_q = q.lstrip('/\\')
                            resolved = os.path.normpath(os.path.join(root, rel_q))
                            if not os.path.exists(resolved):
                                rel_file = os.path.relpath(full_path, backend_dir)
                                print(f"[MISSING] {rel_file}: {line.strip()} -> {resolved}")
