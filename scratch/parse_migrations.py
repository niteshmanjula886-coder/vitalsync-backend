for fname in ['vitalsync_migration.sql', 'vitalsync_master_architecture.sql', 'vitalsync_api_migration.sql']:
    p = f'c:/Users/nites/.android-studio/vitalsync-backend/{fname}'
    with open(p, 'r', encoding='utf-8', errors='ignore') as f:
        text = f.read()
    import re
    matches = re.findall(r'CREATE TABLE (?:IF NOT EXISTS )?`?([a-zA-Z0-9_]+)`?', text, re.I)
    print(fname, ':', set(matches))

