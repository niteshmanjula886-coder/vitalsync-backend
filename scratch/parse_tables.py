with open('c:/Users/nites/.android-studio/vitalsync-backend/vitalsync.sql', 'r', encoding='utf-8', errors='ignore') as f:
    text = f.read()

import re
matches = re.findall(r'DROP TABLE IF EXISTS `([^`]+)`;', text)
print('Tables in vitalsync.sql:', matches)

