for fname in ['vitalsync.sql', 'vitalsync_migration.sql', 'vitalsync_master_architecture.sql', 'vitalsync_api_migration.sql']:
    p = f'c:/Users/nites/.android-studio/vitalsync-backend/{fname}'
    with open(p, 'r', encoding='utf-8', errors='ignore') as f:
        text = f.read()
    import re
    m = re.search(r'CREATE TABLE [^;]*`hydration_logs`.*?;', text, re.DOTALL | re.I)
    if m:
        print(f'=== {fname} ===')
        print(m.group(0))

