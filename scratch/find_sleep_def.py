for fname in ['vitalsync.sql', 'vitalsync_migration.sql', 'vitalsync_master_architecture.sql', 'vitalsync_api_migration.sql']:
    p = f'c:/Users/nites/.android-studio/vitalsync-backend/{fname}'
    with open(p, 'r', encoding='utf-8', errors='ignore') as f:
        text = f.read()
    import re
    for tbl in ['sleep_records', 'sleep_logs', 'vitals_logs', 'vitals_readings', 'workout_sessions', 'activity_sessions']:
        m = re.search(rf'CREATE TABLE [^;]*`{tbl}`.*?;', text, re.DOTALL | re.I)
        if m:
            print(f'=== {fname} : {tbl} ===')
            print(m.group(0))

